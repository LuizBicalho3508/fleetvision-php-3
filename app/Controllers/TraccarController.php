<?php
namespace App\Controllers;

class TraccarController extends ApiController {
    
    // Antigo handleProxyTraccar
    public function proxy() {
        $endpoint = $_GET['endpoint'] ?? '';
        
        if (empty($endpoint) || strpos($endpoint, 'dashboard') !== false) {
            $this->json(['error' => 'Endpoint inválido ou restrito'], 400);
        }

        $url = 'http://127.0.0.1:8082/api' . $endpoint . '?' . http_build_query($_GET);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "admin:admin"); // Ideal mover para Config
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            http_response_code($httpCode);
            echo $resp;
            exit;
        }

        // Filtragem de dados (Segurança)
        $data = json_decode($resp, true);
        if (is_array($data)) {
            $this->filterTraccarData($data, $endpoint);
        } else {
            echo $resp;
        }
    }

    private function filterTraccarData($data, $endpoint) {
        // Implementa a lógica de filtro de IDs do arquivo original
        $restriction = $this->getRestrictionSQL('v');
        $sql = "SELECT traccar_device_id FROM saas_vehicles v WHERE tenant_id = ? $restriction";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->tenant_id]);
        $allowedIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $filtered = [];
        foreach ($data as $item) {
            $did = $item['deviceId'] ?? ($item['id'] ?? null);
            
            // Se for endpoint sensível, aplica filtro
            if ($did && (strpos($endpoint, '/devices') !== false || strpos($endpoint, '/positions') !== false)) {
                if (!in_array($did, $allowedIds)) continue;
            }
            $filtered[] = $item;
        }
        echo json_encode(array_values($filtered));
    }

    public function geocode() {
        $lat = round($_GET['lat'] ?? 0, 5);
        $lon = round($_GET['lon'] ?? 0, 5);

        // Cache check
        $stmt = $this->pdo->prepare("SELECT address FROM saas_address_cache WHERE lat = ? AND lon = ? LIMIT 1");
        $stmt->execute([$lat, $lon]);
        if ($addr = $stmt->fetchColumn()) {
            $this->json(['address' => $addr]);
        }

        // External API
        $ch = curl_init("https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&zoom=18&addressdetails=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "FV/1.0");
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $json = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $addr = $json['display_name'] ?? 'Local desconhecido';
        
        // Save Cache
        if ($addr !== 'Local desconhecido') {
            $this->pdo->prepare("INSERT INTO saas_address_cache (lat, lon, address) VALUES (?, ?, ?)")->execute([$lat, $lon, $addr]);
        }

        $this->json(['address' => $addr]);
    }
}