<?php
namespace App\Controllers;

use App\Services\TraccarService;
use PDO;
use Exception;

class TraccarController extends ApiController {

    public function getPositions() {
        try {
            if (!isset($_SESSION['tenant_id'])) {
                $this->json(['data' => []]);
                return;
            }
            $tenantId = $_SESSION['tenant_id'];
            
            // ISOLAMENTO DE DADOS: Verifica se o usuário está restrito a um cliente
            $userCustomerId = $_SESSION['user_customer_id'] ?? null;

            // 1. Busca Veículos e Motorista Atualmente Vinculado
            $sql = "SELECT v.id as vehicle_id, v.plate, v.icon, v.speed_limit, v.identifier, v.current_driver_id,
                           c.name as customer_name, s.model, s.imei,
                           d_current.name as saved_driver_name
                    FROM saas_vehicles v
                    LEFT JOIN saas_customers c ON v.customer_id = c.id
                    LEFT JOIN saas_stock s ON v.stock_id = s.id
                    LEFT JOIN saas_drivers d_current ON v.current_driver_id = d_current.id
                    WHERE v.tenant_id = ? AND v.status = 'active'";
            
            $params = [$tenantId];

            // APLICA O FILTRO DE CLIENTE SE NECESSÁRIO
            if (!empty($userCustomerId)) {
                $sql .= " AND v.customer_id = ?";
                $params[] = $userCustomerId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $localVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mapa para busca rápida
            $vehiclesMap = [];
            foreach ($localVehicles as $v) {
                $key = preg_replace('/[^0-9]/', '', $v['identifier'] ?? $v['imei']);
                if ($key) $vehiclesMap[$key] = $v;
            }

            // 2. Busca Motoristas (Também deve filtrar se o usuário for restrito)
            $sqlDr = "SELECT id, document, name FROM saas_drivers WHERE tenant_id = ?";
            $paramsDr = [$tenantId];
            
            if (!empty($userCustomerId)) {
                $sqlDr .= " AND customer_id = ?";
                $paramsDr[] = $userCustomerId;
            }
            
            $stmtDr = $this->pdo->prepare($sqlDr);
            $stmtDr->execute($paramsDr);
            
            $allDrivers = []; 
            foreach($stmtDr->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $allDrivers[$d['document']] = $d; 
            }

            // 3. Dados Traccar (API Externa)
            $traccar = new TraccarService();
            $positions = $traccar->getPositions(); 
            $devices   = $traccar->getDevices();

            $deviceStatusMap = [];
            foreach($devices as $d) { $deviceStatusMap[$d['id']] = $d; }

            $finalData = [];

            foreach ($positions as $pos) {
                $deviceInfo = $deviceStatusMap[$pos['deviceId']] ?? null;
                if (!$deviceInfo) continue;

                $imei = $deviceInfo['uniqueId'];
                
                // Só processa se o veículo existir no mapa local (que já foi filtrado por cliente)
                if (isset($vehiclesMap[$imei])) {
                    $localInfo = $vehiclesMap[$imei];
                    $attrs = $pos['attributes'] ?? [];
                    
                    $normalized = $this->normalizeAttributes($attrs, $pos['protocol'] ?? '');
                    $isIgnitionOn = $normalized['ignition'];

                    // --- DETECÇÃO DE MOTORISTA ---
                    $detectedDriverId = null; 

                    // Busca ID no pacote atual
                    $rfid = null;
                    if (!empty($attrs['serial'])) {
                        $parts = explode('|', $attrs['serial']);
                        if (count($parts) >= 5) $rfid = $parts[4];
                    } elseif (!empty($attrs['driverUniqueId'])) {
                        $rfid = $attrs['driverUniqueId'];
                    }

                    if ($rfid && isset($allDrivers[$rfid])) {
                        $detectedDriverId = $allDrivers[$rfid]['id'];
                    }

                    // ID EFETIVO (Pacote ou Memória)
                    $effectiveDriverId = $detectedDriverId ?? $localInfo['current_driver_id'];

                    // --- GESTÃO DE JORNADA ---
                    $stmtCheckJ = $this->pdo->prepare("SELECT id, driver_id, start_time FROM saas_driver_journeys WHERE vehicle_id = ? AND status = 'open' LIMIT 1");
                    $stmtCheckJ->execute([$localInfo['vehicle_id']]);
                    $openJourney = $stmtCheckJ->fetch(PDO::FETCH_ASSOC);

                    // PROTEÇÃO ZUMBI (>12h fecha)
                    if ($openJourney) {
                        $startTime = strtotime($openJourney['start_time']);
                        if ((time() - $startTime) / 3600 > 12) {
                            $this->pdo->prepare("UPDATE saas_driver_journeys SET end_time = NOW(), status = 'closed' WHERE id = ?")->execute([$openJourney['id']]);
                            $this->pdo->prepare("UPDATE saas_vehicles SET current_driver_id = NULL WHERE id = ?")->execute([$localInfo['vehicle_id']]);
                            $openJourney = false;
                            $localInfo['current_driver_id'] = null;
                            $effectiveDriverId = $detectedDriverId; 
                        }
                    }

                    // 1. IGNIÇÃO LIGADA
                    if ($isIgnitionOn) {
                        // INICIAR NOVA JORNADA
                        if (!$openJourney && $effectiveDriverId) {
                            $ins = $this->pdo->prepare("INSERT INTO saas_driver_journeys (tenant_id, driver_id, vehicle_id, start_time, start_lat, start_lng, status) VALUES (?, ?, ?, NOW(), ?, ?, 'open')");
                            $ins->execute([$tenantId, $effectiveDriverId, $localInfo['vehicle_id'], $pos['latitude'], $pos['longitude']]);
                            
                            $this->pdo->prepare("UPDATE saas_vehicles SET current_driver_id = ? WHERE id = ?")->execute([$effectiveDriverId, $localInfo['vehicle_id']]);
                            
                            // Atualiza nome para exibição imediata
                            $stmtName = $this->pdo->prepare("SELECT name FROM saas_drivers WHERE id = ?");
                            $stmtName->execute([$effectiveDriverId]);
                            $localInfo['saved_driver_name'] = $stmtName->fetchColumn();
                        }

                        // TROCA DE MOTORISTA
                        if ($openJourney && $detectedDriverId && $openJourney['driver_id'] != $detectedDriverId) {
                            $this->pdo->prepare("UPDATE saas_driver_journeys SET end_time = NOW(), status = 'closed' WHERE id = ?")->execute([$openJourney['id']]);
                            
                            $ins = $this->pdo->prepare("INSERT INTO saas_driver_journeys (tenant_id, driver_id, vehicle_id, start_time, start_lat, start_lng, status) VALUES (?, ?, ?, NOW(), ?, ?, 'open')");
                            $ins->execute([$tenantId, $detectedDriverId, $localInfo['vehicle_id'], $pos['latitude'], $pos['longitude']]);
                            
                            $this->pdo->prepare("UPDATE saas_vehicles SET current_driver_id = ? WHERE id = ?")->execute([$detectedDriverId, $localInfo['vehicle_id']]);
                            $localInfo['saved_driver_name'] = $allDrivers[$rfid]['name'];
                        }
                    } 
                    // 2. IGNIÇÃO DESLIGADA
                    else {
                        if ($openJourney) {
                            $upd = $this->pdo->prepare("UPDATE saas_driver_journeys SET end_time = NOW(), end_lat = ?, end_lng = ?, status = 'closed' WHERE id = ?");
                            $upd->execute([$pos['latitude'], $pos['longitude'], $openJourney['id']]);

                            $this->pdo->prepare("UPDATE saas_vehicles SET current_driver_id = NULL WHERE id = ?")->execute([$localInfo['vehicle_id']]);
                            $localInfo['saved_driver_name'] = null;
                        }
                    }

                    // --- EXIBIÇÃO ---
                    $driverDisplay = 'Não Identificado';
                    
                    if (!$isIgnitionOn) {
                        $driverDisplay = 'Desvinculado (OFF)';
                    } else {
                        if ($detectedDriverId) {
                            $driverDisplay = $allDrivers[$rfid]['name'];
                        } elseif (!empty($localInfo['saved_driver_name'])) {
                            $driverDisplay = $localInfo['saved_driver_name'];
                        }
                    }

                    $finalData[] = [
                        'id'            => (string) $localInfo['vehicle_id'],
                        'plate'         => $localInfo['plate'],
                        'customer'      => $localInfo['customer_name'] ?? 'Empresa',
                        'driver'        => $driverDisplay,
                        'address'       => $pos['address'] ?? null,
                        'datetime'      => $pos['deviceTime'],
                        'speed'         => round(($pos['speed'] ?? 0) * 1.852),
                        'speed_limit'   => (int) ($localInfo['speed_limit'] ?? 80),
                        'ignition'      => $isIgnitionOn,
                        'battery_level' => $normalized['battery_level'],
                        'voltage'       => $normalized['voltage'],
                        'satellites'    => $normalized['satellites'],
                        'gsm_signal'    => $normalized['gsm_signal'],
                        'course'        => $pos['course'] ?? 0,
                        'lat'           => (float) $pos['latitude'],
                        'lng'           => (float) $pos['longitude'],
                        'imei'          => $imei,
                        'icon'          => $localInfo['icon'] ?? '/assets/img/car.png',
                        'model'         => $localInfo['model'] ?? 'Rastreador',
                        'status'        => $deviceInfo['status'] ?? 'offline'
                    ];
                }
            }

            $this->json(['data' => $finalData]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function geocode() {
        $lat = number_format($_GET['lat'], 5, '.', '');
        $lng = number_format($_GET['lng'], 5, '.', '');
        $stmt = $this->pdo->prepare("SELECT address FROM saas_address_cache WHERE lat = ? AND lng = ? LIMIT 1");
        $stmt->execute([$lat, $lng]);
        if ($cached = $stmt->fetchColumn()) { $this->json(['address' => $cached]); return; }
        
        // Uso de User-Agent e Referer para evitar bloqueio do Nominatim
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=0";
        $ch = curl_init($url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_USERAGENT, "FleetVision/1.0 (contact@fleetvision.com)");
        $resp = curl_exec($ch); 
        curl_close($ch);
        
        $data = json_decode($resp, true); 
        $addr = $data['display_name'] ?? 'Localização Desconhecida';
        
        // Simplifica o endereço (Rua, Bairro, Cidade)
        $parts = explode(',', $addr);
        $short = implode(',', array_slice($parts, 0, 3));
        
        if($short !== 'Localização Desconhecida') {
             try { $this->pdo->prepare("INSERT INTO saas_address_cache (lat, lng, address) VALUES (?, ?, ?)")->execute([$lat, $lng, $short]); } catch(Exception $e){}
        }
        $this->json(['address' => $short]);
    }

    private function normalizeAttributes($attrs, $protocol) {
        $ignition = false;
        if (isset($attrs['ignition'])) $ignition = (bool) $attrs['ignition'];
        elseif (isset($attrs['motion'])) $ignition = (bool) $attrs['motion'];

        $voltage = 0;
        if (isset($attrs['power'])) $voltage = (float) $attrs['power'];
        elseif (isset($attrs['adc1'])) $voltage = (float) $attrs['adc1'];

        $sat = $attrs['sat'] ?? 0;

        $batteryPct = 0;
        if (isset($attrs['batteryLevel'])) {
            $batteryPct = (int) $attrs['batteryLevel'];
        } elseif (isset($attrs['battery'])) {
            $val = (float) $attrs['battery'];
            if ($val > 100) $volts = $val / 1000; else $volts = $val;
            
            if ($volts >= 4.2) $batteryPct = 100;
            elseif ($volts <= 3.6) $batteryPct = 0;
            else $batteryPct = round(($volts - 3.6) / (4.2 - 3.6) * 100);
        }

        $gsm = 0;
        $rssi = $attrs['rssi'] ?? 0;
        if ($rssi > 0) {
            if ($rssi <= 5) $gsm = $rssi * 20; 
            elseif ($rssi <= 31) $gsm = round(($rssi / 31) * 100);
            else $gsm = ($rssi > 100) ? 100 : $rssi;
        }

        return [
            'ignition'      => $ignition,
            'voltage'       => $voltage,
            'battery_level' => $batteryPct,
            'satellites'    => $sat,
            'gsm_signal'    => $gsm
        ];
    }
}