<?php
// ARQUIVO: api_mapa.php
// ATUALIZADO: Correção de Filtro de Permissões (Veículos por Usuário/Cliente)

session_start();
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 1. Conexão e Segurança
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403); 
    exit(json_encode(['error' => 'Sessão expirada.'])); 
}

if (!file_exists('db.php')) {
    http_response_code(500); 
    exit(json_encode(['error' => 'Erro crítico: db.php.']));
}
require 'db.php';

$tenant_id  = $_SESSION['tenant_id'];
$user_id    = $_SESSION['user_id'];
$user_role  = $_SESSION['user_role'] ?? 'user';
$action     = $_REQUEST['action'] ?? 'get_initial_data';

switch ($action) {
    case 'get_initial_data': 
        getInitialMapData($pdo, $tenant_id, $user_id, $user_role); 
        break;
    case 'geocode': 
        handleGeocode($_GET['lat']??0, $_GET['lon']??0, $pdo); 
        break;
    case 'secure_command': 
        handleSecureCommand($pdo, $user_id, $tenant_id, $user_role); 
        break;
    default: 
        http_response_code(404); 
        echo json_encode(['error' => 'Action inválida']); 
        break;
}

function getInitialMapData($pdo, $tenant_id, $user_id, $user_role) {
    // 1. Carrega Mapa de Motoristas (RFID -> Dados)
    $driversMap = [];
    try {
        $stmtD = $pdo->prepare("SELECT id, rfid_tag, name FROM saas_drivers WHERE tenant_id = :tid AND rfid_tag IS NOT NULL");
        $stmtD->execute([':tid' => $tenant_id]);
        while ($row = $stmtD->fetch(PDO::FETCH_ASSOC)) {
            $cleanTag = ltrim(trim($row['rfid_tag']), '0'); 
            if($cleanTag) $driversMap[$cleanTag] = $row;
            $driversMap[trim($row['rfid_tag'])] = $row;
        }
    } catch (Exception $e) {}

    // 2. Lógica de Restrição de Acesso (QUEM VÊ O QUE)
    $isRestricted = ($user_role !== 'admin' && $user_role !== 'superadmin');
    $linkedCustomerId = null;

    if ($isRestricted) {
        // Busca se o usuário está ligado a um Cliente (Empresa)
        $stmtMe = $pdo->prepare("SELECT customer_id FROM saas_users WHERE id = :uid");
        $stmtMe->execute([':uid' => $user_id]);
        $linkedCustomerId = $stmtMe->fetchColumn();
    }

    // 3. Montagem da Query de Veículos
    // Usa parâmetros nomeados para evitar erro de ordem
    $sql = "
        SELECT v.id, v.name, v.plate, v.traccar_device_id, v.category, v.last_telemetry, v.current_driver_id,
               i.url as icon_url, c.name as client_name, d.name as db_driver_name
        FROM saas_vehicles v 
        LEFT JOIN saas_custom_icons i ON CAST(v.category AS VARCHAR) = CAST(i.id AS VARCHAR) 
        LEFT JOIN saas_customers c ON v.client_id = c.id
        LEFT JOIN saas_drivers d ON v.current_driver_id = d.id
        WHERE v.tenant_id = :tenant_id
    ";

    $queryParams = [':tenant_id' => $tenant_id];

    // Aplica o Filtro se for usuário comum
    if ($isRestricted) {
        // O veículo deve pertencer ao usuário diretamente OU ao cliente do usuário
        $sql .= " AND (v.user_id = :user_id";
        $queryParams[':user_id'] = $user_id;

        if ($linkedCustomerId) {
            $sql .= " OR v.client_id = :client_id";
            $queryParams[':client_id'] = $linkedCustomerId;
        }
        
        $sql .= ")";
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        $dbVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success'=>false, 'error'=>'Erro SQL Veículos: ' . $e->getMessage()]); 
        return;
    }

    // Preparação dos dados para o JSON
    $vehiclesData = []; 
    $customerData = []; 
    $staticInfo = []; 
    $allowedIds = []; 
    $savedStates = [];

    foreach ($dbVehicles as $v) {
        if ($v['traccar_device_id']) {
            $tid = (int)$v['traccar_device_id'];
            $allowedIds[] = $tid;
            
            // Corrige caminho do ícone
            if ($v['icon_url']) {
                $vehiclesData[$tid] = (strpos($v['icon_url'], '/') === 0) ? $v['icon_url'] : '/' . $v['icon_url'];
            }
            if ($v['client_name']) {
                $customerData[$tid] = $v['client_name'];
            }
            
            $staticInfo[$tid] = [
                'plate'     => $v['plate'] ?: 'S/ PLACA',
                'name'      => $v['name'],
                'driver'    => $v['db_driver_name'],
                'driver_id' => $v['current_driver_id']
            ];

            if (!empty($v['last_telemetry'])) {
                $savedStates[$tid] = json_decode($v['last_telemetry'], true);
            }
        }
    }

    // 4. Busca Dados do Traccar (SSR)
    $traccarPositions = fetchTraccarData("/positions");
    $traccarDevices   = fetchTraccarData("/devices");
    
    $posMap = []; 
    foreach ($traccarPositions as $p) $posMap[$p['deviceId']] = $p;
    
    $devMap = []; 
    foreach ($traccarDevices as $d) $devMap[$d['id']] = $d;

    $finalDevices = []; 
    $initialPositions = [];

    // 5. Mesclagem (SaaS + Traccar)
    foreach ($dbVehicles as $v) {
        $tid = (int)$v['traccar_device_id'];
        if (!$tid) continue;

        // Dados do Dispositivo
        $tData = $devMap[$tid] ?? [
            'id' => $tid, 
            'status' => 'offline', 
            'lastUpdate' => null, 
            'disabled' => false
        ];
        $tData['name'] = $v['name']; // Garante nome do SaaS
        $finalDevices[] = $tData;

        // Dados de Posição
        $livePos = $posMap[$tid] ?? null;
        $savedState = $savedStates[$tid] ?? [];

        if ($livePos) {
            // Lógica de Atualização de Motorista (Ignição/Cartão)
            $driverUpdate = processDriverLogic($pdo, $v, $livePos, $driversMap, $tenant_id);
            
            if ($driverUpdate['updated']) {
                $staticInfo[$tid]['driver'] = $driverUpdate['new_driver_name'];
                $staticInfo[$tid]['driver_id'] = $driverUpdate['new_driver_id'];
            }

            // Normalização e Persistência
            $normalized = normalizeTelemetry($livePos, $savedState);
            $normalized['driver_name'] = $staticInfo[$tid]['driver']; // Injeta nome resolvido
            
            $livePos['saas_state'] = $normalized;
            $initialPositions[] = $livePos;

            // Salva no banco apenas se mudou
            try {
                $jsonState = json_encode($normalized);
                if ($jsonState !== $v['last_telemetry']) {
                    $up = $pdo->prepare("UPDATE saas_vehicles SET last_telemetry = ? WHERE id = ?");
                    $up->execute([$jsonState, $v['id']]);
                }
            } catch (Exception $e) {}
        }
    }

    echo json_encode([
        'success' => true,
        'config' => [
            'icons'      => $vehiclesData,
            'customers'  => $customerData,
            'staticInfo' => $staticInfo,
            'allowedIds' => $allowedIds,
            'wsToken'    => getTraccarSessionToken()
        ],
        'data' => [
            'devices'   => $finalDevices,
            'positions' => $initialPositions
        ]
    ]);
}

// --- LÓGICA DE NEGÓCIO (DRIVERS & JORNADA) ---
function processDriverLogic($pdo, $vehicle, $pos, $driversMap, $tenantId) {
    $attr = $pos['attributes'] ?? [];
    $currentDriverId = $vehicle['current_driver_id'];
    $vehicleId = $vehicle['id'];
    
    // Ignição (Priority: ignition > motion)
    $ign = $attr['ignition'] ?? ($attr['motion'] ?? false);
    
    // Identificação de Cartão (RFID)
    $detectedRfid = null;
    if (!empty($attr['serial'])) { // Suntech
        $parts = explode('|', $attr['serial']);
        if (isset($parts[4])) $detectedRfid = ltrim(trim($parts[4]), '0');
    } elseif (!empty($attr['driverUniqueId'])) { // Padrão
        $detectedRfid = ltrim(trim($attr['driverUniqueId']), '0');
    }

    $newDriverId = $currentDriverId;
    $newDriverName = null;
    $shouldUpdate = false;

    // REGRA 1: Ignição OFF -> Remove Motorista
    if (!$ign) {
        if ($currentDriverId !== null) {
            closeJourney($pdo, $currentDriverId, $vehicleId);
            $newDriverId = null;
            $shouldUpdate = true;
        }
    }
    // REGRA 2: Ignição ON + Cartão Novo -> Troca Motorista
    elseif ($ign && $detectedRfid) {
        if (isset($driversMap[$detectedRfid])) {
            $matchedDriver = $driversMap[$detectedRfid];
            
            if ($matchedDriver['id'] != $currentDriverId) {
                if ($currentDriverId) closeJourney($pdo, $currentDriverId, $vehicleId);
                startJourney($pdo, $matchedDriver['id'], $vehicleId, $tenantId);
                
                $newDriverId = $matchedDriver['id'];
                $newDriverName = $matchedDriver['name'];
                $shouldUpdate = true;
            } else {
                $newDriverName = $matchedDriver['name']; // Já é o atual
            }
        }
    }

    if ($shouldUpdate) {
        try {
            $pdo->prepare("UPDATE saas_vehicles SET current_driver_id = ? WHERE id = ?")->execute([$newDriverId, $vehicleId]);
            return ['updated' => true, 'new_driver_id' => $newDriverId, 'new_driver_name' => $newDriverName];
        } catch (Exception $e) {}
    }

    return ['updated' => false];
}

function closeJourney($pdo, $driverId, $vehicleId) {
    try {
        $sql = "UPDATE saas_driver_journeys SET end_time = NOW() WHERE driver_id = ? AND vehicle_id = ? AND end_time IS NULL";
        $pdo->prepare($sql)->execute([$driverId, $vehicleId]);
    } catch (Exception $e) {}
}

function startJourney($pdo, $driverId, $vehicleId, $tenantId) {
    try {
        $check = $pdo->prepare("SELECT id FROM saas_driver_journeys WHERE driver_id = ? AND end_time IS NULL");
        $check->execute([$driverId]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO saas_driver_journeys (tenant_id, driver_id, vehicle_id, start_time) VALUES (?, ?, ?, NOW())")->execute([$tenantId, $driverId, $vehicleId]);
        }
    } catch (Exception $e) {}
}

function normalizeTelemetry($pos, $oldState) {
    $attr = $pos['attributes'] ?? [];
    
    // Normalização Segura
    $ign = $attr['ignition'] ?? ($attr['motion'] ?? ($oldState['ignition'] ?? false));
    
    $pwr = $attr['power'] ?? ($attr['adc1'] ?? ($attr['extBatt'] ?? null));
    if ($pwr > 1000) $pwr /= 1000;
    if (!$pwr && !empty($attr['charge'])) $pwr = 12.0;
    if ($pwr === null) $pwr = $oldState['power'] ?? 0;

    $bat = $attr['batteryLevel'] ?? null;
    if ($bat === null && isset($attr['battery'])) {
        $raw = $attr['battery'];
        if ($raw > 100) $raw /= 1000;
        if ($raw > 0) $bat = min(100, max(0, round(($raw - 3.6) / (4.2 - 3.6) * 100)));
    }
    if ($bat === null) $bat = $oldState['battery'] ?? 0;

    $blk = $attr['blocked'] ?? ($attr['out1'] ?? ($oldState['blocked'] ?? false));

    return [
        'ignition' => (bool)$ign,
        'battery'  => (int)$bat,
        'power'    => round((float)$pwr, 1),
        'sat'      => $attr['sat'] ?? ($oldState['sat'] ?? 0),
        'blocked'  => (bool)$blk,
        'updated'  => time()
    ];
}

// --- HELPERS BÁSICOS ---
function fetchTraccarData($endpoint) {
    global $TRACCAR_HOST;
    $TRACCAR_HOST = 'http://127.0.0.1:8082/api';
    $cookie = sys_get_temp_dir() . '/traccar_cookie_global.txt';
    
    if (!file_exists($cookie) || (time() - filemtime($cookie) > 1800)) {
        $ch = curl_init("$TRACCAR_HOST/session"); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, "email=admin&password=admin");
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_exec($ch); curl_close($ch);
    }

    $ch = curl_init($TRACCAR_HOST . $endpoint); 
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $d = curl_exec($ch); 
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch);
    return ($c >= 200 && $c < 300) ? json_decode($d, true) : [];
}

function getTraccarSessionToken() {
    $c = @file_get_contents(sys_get_temp_dir() . '/traccar_cookie_global.txt');
    if ($c && preg_match('/JSESSIONID\s+([^\s]+)/', $c, $m)) return $m[1]; 
    return '';
}

function handleGeocode($lat, $lon, $pdo) {
    $lat = round($lat, 5); $lon = round($lon, 5);
    try {
        $stmt = $pdo->prepare("SELECT address FROM saas_address_cache WHERE lat = ? AND lon = ? LIMIT 1");
        $stmt->execute([$lat, $lon]);
        if ($r = $stmt->fetchColumn()) { echo json_encode(['address' => $r]); return; }
    } catch(Exception $e) {}

    $ch = curl_init("https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&zoom=18&addressdetails=1");
    curl_setopt($ch, CURLOPT_USERAGENT, "FV/1.0"); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $j = json_decode(curl_exec($ch), true); 
    curl_close($ch);
    
    $a = $j['display_name'] ?? 'Local desconhecido';
    if ($a !== 'Local desconhecido') {
        try { 
            $pdo->prepare("INSERT INTO saas_address_cache (lat, lon, address) VALUES (?, ?, ?)")->execute([$lat, $lon, $a]); 
        } catch(Exception $e) {}
    }
    echo json_encode(['address' => $a]);
}

function handleSecureCommand($pdo, $uid, $tid, $role) {
    $in = json_decode(file_get_contents('php://input'), true);
    if (empty($in['password'])) { http_response_code(400); exit; }
    
    $stmt = $pdo->prepare("SELECT password FROM saas_users WHERE id = ?"); 
    $stmt->execute([$uid]);
    if (!password_verify($in['password'], $stmt->fetchColumn())) { 
        http_response_code(401); 
        exit(json_encode(['error' => 'Senha incorreta'])); 
    }
    
    $ch = curl_init("http://127.0.0.1:8082/api/commands/send"); 
    curl_setopt($ch, CURLOPT_USERPWD, "admin:admin");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['deviceId' => (int)$in['deviceId'], 'type' => $in['type']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $res = curl_exec($ch); 
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch);
    
    if ($code >= 400) { 
        http_response_code(500); echo $res; 
    } else { 
        echo json_encode(['success' => true]); 
    }
}
?>