<?php
namespace App\Services;

use Exception;

class TraccarService {
    // URL da API do Traccar (Ajuste se necessário)
    private $baseUrl = 'http://127.0.0.1:8082/api';
    private $auth;

    public function __construct() {
        // Credenciais do Admin do Traccar (Usuário:Senha)
        // Recomendado: Mover para arquivo de configuração .env no futuro
        $this->auth = base64_encode('admin:admin'); 
    }

    // ========================================================================
    //                         GERENCIAMENTO DE DISPOSITIVOS
    // ========================================================================

    /**
     * Cadastra um novo dispositivo
     */
    public function addDevice($name, $uniqueId) {
        $data = [
            'name' => $name, 
            'uniqueId' => $uniqueId
        ];
        return $this->request('POST', '/devices', $data);
    }

    /**
     * Atualiza um dispositivo existente (Nome ou IMEI)
     */
    public function updateDevice($traccarId, $name, $uniqueId) {
        $data = [
            'id' => $traccarId,
            'name' => $name,
            'uniqueId' => $uniqueId
        ];
        return $this->request('PUT', "/devices/{$traccarId}", $data);
    }

    /**
     * Remove um dispositivo
     */
    public function removeDevice($traccarId) {
        return $this->request('DELETE', "/devices/{$traccarId}");
    }

    /**
     * Busca dispositivo pelo IMEI (para verificar duplicidade ou recuperar ID)
     */
    public function findDeviceByUniqueId($uniqueId) {
        // A API do Traccar permite filtrar por uniqueId
        $devices = $this->request('GET', "/devices?uniqueId=" . $uniqueId);
        return !empty($devices) ? $devices[0] : null;
    }

    // ========================================================================
    //                         DADOS PARA O MAPA (PREMIUM)
    // ========================================================================

    /**
     * Busca a última posição conhecida de TODOS os dispositivos.
     * Essencial para o Mapa em Tempo Real.
     */
    public function getPositions() {
        return $this->request('GET', '/positions');
    }

    /**
     * Busca a lista de dispositivos com STATUS (online, offline, unknown).
     * Essencial para mostrar a bolinha verde/cinza no grid.
     */
    public function getDevices() {
        return $this->request('GET', '/devices');
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout para não travar o PHP
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        // Tratamento de Erros de Conexão (CURL)
        if ($response === false) {
            throw new Exception("Falha na conexão com Traccar: " . $curlError);
        }

        // Tratamento de Erros da API (HTTP 4xx/5xx)
        if ($httpCode >= 400) {
            // Tratamento específico para duplicidade
            if ($httpCode === 400 && strpos($response, 'Unique index') !== false) {
                throw new Exception("Este IMEI já está cadastrado no servidor Traccar.");
            }
            
            // Tenta ler mensagem de erro do Traccar ou usa o código
            $msg = $response;
            try {
                $jsonErr = json_decode($response, true);
                if (isset($jsonErr['message'])) $msg = $jsonErr['message'];
            } catch (Exception $e) {}

            throw new Exception("Erro Traccar ($httpCode): " . $msg);
        }

        return json_decode($response, true);
    }
    // ... métodos anteriores ...

    // --- COMANDOS ---
    public function sendCommand($deviceId, $type, $attributes = []) {
        $data = [
            'deviceId' => $deviceId,
            'type'     => $type, // ex: 'custom', 'engineStop', 'engineResume'
            'attributes' => $attributes // ex: ['data' => 'reset']
        ];
        // Envia comando síncrono (aguarda resposta do dispositivo se suportado)
        return $this->request('POST', '/commands/send', $data);
    }

    // --- RELATÓRIOS (HISTÓRICO) ---
    public function getRoute($deviceIds, $from, $to) {
        // Formato datas: YYYY-MM-DDTHH:mm:ssZ
        $query = http_build_query([
            'deviceId' => $deviceIds,
            'from' => $from,
            'to' => $to
        ]);
        return $this->request('GET', '/reports/route?' . $query);
    }
    // ... dentro da class TraccarService ...

    /**
     * Busca relatórios de eventos (Essencial para Alertas e Histórico)
     */
    public function getEvents($deviceIds, $from, $to) {
        // Traccar Java API espera parâmetros repetidos: deviceId=1&deviceId=2...
        $queryString = "from={$from}&to={$to}";
        
        if (is_array($deviceIds)) {
            foreach ($deviceIds as $id) {
                $queryString .= "&deviceId={$id}";
            }
        } else {
            $queryString .= "&deviceId={$deviceIds}";
        }

        // Tipo de eventos que queremos filtrar (opcional, remove lixo)
        // Se quiser tudo, remova esta parte
        // $queryString .= "&type=deviceOverspeed&type=ignitionOn&type=geofenceEnter&type=geofenceExit&type=alarm&type=maintenance";

        return $this->request('GET', '/reports/events?' . $queryString);
    }
    // ... métodos anteriores ...

    // --- GESTÃO DE MOTORISTAS ---

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