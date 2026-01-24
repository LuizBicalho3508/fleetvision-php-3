<?php
namespace App\Services;

use Exception;

class TraccarApi {

    private $baseUrl;
    private $email;
    private $password;

    public function __construct() {
        // Carrega configurações do arquivo JSON na raiz
        $configPath = __DIR__ . '/../../traccar_config.json';
        
        if (!file_exists($configPath)) {
            // Configuração padrão de fallback se o arquivo não existir
            $this->baseUrl = 'http://localhost:8082';
            $this->email = 'admin';
            $this->password = 'admin';
        } else {
            $config = json_decode(file_get_contents($configPath), true);
            $this->baseUrl = rtrim($config['url'] ?? 'http://localhost:8082', '/');
            $this->email = $config['email'] ?? '';
            $this->password = $config['password'] ?? '';
        }
    }

    /**
     * Faz requisições genéricas para a API do Traccar
     * * @param string $endpoint Ex: '/devices', '/positions'
     * @param string $method 'GET', 'POST', 'PUT', 'DELETE'
     * @param array $data Dados para enviar no corpo ou query string
     * @return array|null
     * @throws Exception
     */
    public function curl($endpoint, $method = 'GET', $data = []) {
        // Garante que o endpoint comece com /api (padrão Traccar 5+)
        if (strpos($endpoint, '/api') !== 0) {
            $endpoint = '/api' . (strpos($endpoint, '/') === 0 ? $endpoint : '/' . $endpoint);
        }

        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        // Autenticação Básica (Compatível com a maioria das versões do Traccar)
        curl_setopt($ch, CURLOPT_USERPWD, $this->email . ":" . $this->password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30s
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignora SSL localmente (cuidado em produção)
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        // Configuração do Método HTTP
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'GET':
            default:
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                }
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro de conexão com Traccar: $error");
        }

        $responseData = json_decode($response, true);

        // Tratamento de erros HTTP (400, 401, 404, 500)
        if ($httpCode >= 400) {
            $msg = $responseData['message'] ?? "Erro HTTP $httpCode no Traccar.";
            // Se for 401, as credenciais estão erradas
            if ($httpCode === 401) {
                $msg = "Acesso Negado ao Traccar (Verifique traccar_config.json)";
            }
            throw new Exception($msg, $httpCode);
        }

        return $responseData;
    }
}