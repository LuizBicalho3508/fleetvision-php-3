<?php
namespace App\Services;

use Exception;

class TraccarService {
    private $baseUrl;
    private $auth;

    public function __construct() {
        // Carrega configurações do arquivo central
        $config = require __DIR__ . '/../../config.php';
        
        if (!isset($config['traccar'])) {
            throw new Exception("Configuração do Traccar não encontrada em config.php");
        }

        $this->baseUrl = rtrim($config['traccar']['base_url'], '/');
        $this->auth    = base64_encode($config['traccar']['user'] . ':' . $config['traccar']['pass']);
    }

    // ========================================================================
    //                         MÉTODO AUXILIAR (REQ)
    // ========================================================================

    private function request($method, $endpoint, $data = null) {
        $ch = curl_init($this->baseUrl . $endpoint);
        
        $headers = [
            'Authorization: Basic ' . $this->auth,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Aumentei para 10s para evitar falhas em relatórios pesados
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        // Tratamento de Erros de Conexão (CURL)
        if ($response === false) {
            // Log do erro para debug interno se necessário
            error_log("Erro Conexão Traccar: $curlError");
            throw new Exception("Falha na conexão com o servidor de rastreamento.");
        }

        // Tratamento de Erros da API (HTTP 4xx/5xx)
        if ($httpCode >= 400) {
            // Tratamento específico para duplicidade
            if ($httpCode === 400 && strpos($response, 'Unique index') !== false) {
                throw new Exception("Este identificador (IMEI) já está cadastrado.");
            }
            
            // Tenta decodificar o erro vindo do Java/Traccar
            $msg = "Erro HTTP $httpCode";
            try {
                $jsonErr = json_decode($response, true);
                if (isset($jsonErr['message'])) {
                    $msg = $jsonErr['message'];
                } elseif (is_string($response)) {
                    // Às vezes o Traccar retorna texto puro no corpo
                    $msg = substr(strip_tags($response), 0, 200); 
                }
            } catch (Exception $e) {}

            throw new Exception("Traccar API: " . $msg);
        }

        $decoded = json_decode($response, true);
        
        // Verifica se o JSON é válido
        if (json_last_error() !== JSON_ERROR_NONE && $httpCode !== 204) {
            throw new Exception("Erro ao decodificar resposta do servidor.");
        }

        return $decoded;
    }

    // ========================================================================
    //                         GERENCIAMENTO DE DISPOSITIVOS
    // ========================================================================

    public function addDevice($name, $uniqueId) {
        $data = [
            'name' => $name, 
            'uniqueId' => $uniqueId
        ];
        return $this->request('POST', '/devices', $data);
    }

    public function updateDevice($traccarId, $name, $uniqueId) {
        $data = [
            'id' => $traccarId,
            'name' => $name,
            'uniqueId' => $uniqueId
        ];
        return $this->request('PUT', "/devices/{$traccarId}", $data);
    }

    public function removeDevice($traccarId) {
        return $this->request('DELETE', "/devices/{$traccarId}");
    }

    public function findDeviceByUniqueId($uniqueId) {
        $devices = $this->request('GET', "/devices?uniqueId=" . $uniqueId);
        return !empty($devices) ? $devices[0] : null;
    }

    // ========================================================================
    //                         DADOS PARA O MAPA
    // ========================================================================

    public function getPositions() {
        return $this->request('GET', '/positions');
    }

    public function getDevices() {
        return $this->request('GET', '/devices');
    }

    // ========================================================================
    //                         COMANDOS E RELATÓRIOS
    // ========================================================================

    public function sendCommand($deviceId, $type, $attributes = []) {
        $data = [
            'deviceId' => $deviceId,
            'type'     => $type,
            'attributes' => $attributes
        ];
        return $this->request('POST', '/commands/send', $data);
    }

    public function getRoute($deviceIds, $from, $to) {
        $query = http_build_query([
            'deviceId' => $deviceIds,
            'from' => $from,
            'to' => $to
        ]);
        return $this->request('GET', '/reports/route?' . $query);
    }

    public function getEvents($deviceIds, $from, $to) {
        $queryString = "from={$from}&to={$to}";
        
        if (is_array($deviceIds)) {
            foreach ($deviceIds as $id) {
                $queryString .= "&deviceId={$id}";
            }
        } else {
            $queryString .= "&deviceId={$deviceIds}";
        }
        return $this->request('GET', '/reports/events?' . $queryString);
    }

    // ========================================================================
    //                         GESTÃO DE MOTORISTAS
    // ========================================================================

    public function getDrivers() {
        return $this->request('GET', '/drivers');
    }

    public function addDriver($name, $uniqueId) {
        $data = [
            'name' => $name,
            'uniqueId' => $uniqueId
        ];
        return $this->request('POST', '/drivers', $data);
    }

    public function updateDriver($id, $name, $uniqueId) {
        $data = [
            'id' => $id,
            'name' => $name,
            'uniqueId' => $uniqueId
        ];
        return $this->request('PUT', "/drivers/{$id}", $data);
    }

    public function deleteDriver($id) {
        return $this->request('DELETE', "/drivers/{$id}");
    }
}