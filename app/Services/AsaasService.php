<?php
namespace App\Services;

class AsaasService {
    private $apiKey;
    private $baseUrl;

    public function __construct() {
        // Em produção, mova isso para um arquivo de configuração ou .env
        $this->apiKey = '$aact_...SEU_API_KEY_DO_ASAAS...'; 
        
        // Use 'https://sandbox.asaas.com/api/v3' para testes
        // Use 'https://www.asaas.com/api/v3' para produção
        $this->baseUrl = 'https://sandbox.asaas.com/api/v3'; 
    }

    public function createPayment($customerId, $value, $dueDate, $description) {
        $data = [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED', // Deixa o cliente escolher (Pix/Boleto) no link
            'value' => $value,
            'dueDate' => $dueDate,
            'description' => $description,
            'postalService' => false // Não enviar carta via correio
        ];

        return $this->post('/payments', $data);
    }

    private function post($endpoint, $data) {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \Exception($json['errors'][0]['description'] ?? 'Erro Asaas: ' . $httpCode);
        }

        return $json;
    }
}