<?php
namespace App\Services;

class AsaasService {
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $environment = 'prod') {
        $this->apiKey = $apiKey;
        $this->baseUrl = ($environment === 'sandbox') 
            ? 'https://sandbox.asaas.com/api/v3' 
            : 'https://www.asaas.com/api/v3';
    }

    private function request($endpoint, $method = 'GET', $data = null) {
        $curl = curl_init();
        
        // Monta URL com Query String se for GET e tiver dados
        $url = $this->baseUrl . $endpoint;
        if ($method === 'GET' && is_array($data)) {
            $url .= '?' . http_build_query($data);
            $data = null; // Limpa para não enviar no body
        }

        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    // --- MÉTODOS FINANCEIROS ---

    public function getBalance() {
        return $this->request('/finance/balance');
    }

    /**
     * Busca pagamentos com filtros (Status, Data, Cliente)
     */
    public function getPayments(array $filters = []) {
        // Filtros comuns: status, dueDate, customer, offset, limit
        return $this->request('/payments', 'GET', $filters);
    }

    /**
     * Busca clientes no Asaas por nome ou CPF/CNPJ
     */
    public function searchCustomers($query) {
        return $this->request('/customers', 'GET', [
            'name' => $query,
            'limit' => 20
        ]);
    }

    public function getCustomerById($id) {
        return $this->request("/customers/$id");
    }
}
?>