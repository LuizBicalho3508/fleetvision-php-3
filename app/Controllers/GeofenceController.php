<?php
namespace App\Controllers;

use PDO;
use Exception;

class GeofenceController extends ApiController {

    // Lista cercas do Tenant (Via Banco de Dados - Mais rápido)
    public function index() {
        try {
            // Busca cercas onde attributes contém {"tenant_id": X} OU cercas globais (se houver lógica para isso)
            // No PostgreSQL, buscamos dentro do JSONB ou string attributes
            $sql = "SELECT id, name, description, area, attributes 
                    FROM tc_geofences 
                    WHERE attributes::jsonb @> ?::jsonb 
                    ORDER BY id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([json_encode(['tenant_id' => $this->tenant_id])]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processa para facilitar o frontend (decodifica área se necessário, ou deixa para o JS)
            $this->json(['success' => true, 'data' => $rows]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salva cerca (Via API Traccar - Para atualizar cache em tempo real)
    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? 'Sem nome';
        $area = $input['area'] ?? ''; // WKT Format (POLYGON((...)))
        $description = $input['description'] ?? '';

        if (empty($area)) $this->json(['error' => 'Área inválida'], 400);

        // Prepara objeto para o Traccar
        $geofenceData = [
            'name' => $name,
            'description' => $description,
            'area' => $area,
            'attributes' => [
                'tenant_id' => $this->tenant_id, // Vincula ao Tenant atual
                'color' => '#3b82f6' // Cor padrão (Azul FleetVision)
            ]
        ];

        // Envia para API Traccar (Porta 8082 por padrão)
        $res = $this->callTraccarApi('POST', '/geofences', $geofenceData);

        if (isset($res['id'])) {
            $this->json(['success' => true, 'id' => $res['id']]);
        } else {
            $this->json(['error' => 'Erro ao salvar no Traccar', 'details' => $res], 500);
        }
    }

    // Excluir cerca (Via API Traccar)
    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) $this->json(['error' => 'ID inválido'], 400);

        // Verifica permissão antes de deletar (Segurança)
        $stmt = $this->pdo->prepare("SELECT attributes FROM tc_geofences WHERE id = ?");
        $stmt->execute([$id]);
        $attr = json_decode($stmt->fetchColumn() ?: '{}', true);

        if (($attr['tenant_id'] ?? 0) != $this->tenant_id && $this->user_role !== 'superadmin') {
            $this->json(['error' => 'Sem permissão para esta cerca'], 403);
        }

        $this->callTraccarApi('DELETE', '/geofences/' . $id);
        $this->json(['success' => true]);
    }

    // Helper interno para chamar Traccar
    private function callTraccarApi($method, $endpoint, $data = null) {
        $url = 'http://127.0.0.1:8082/api' . $endpoint; // Ajuste IP/Porta se necessário
        $ch = curl_init($url);
        
        // Credenciais Admin do Traccar (Mover para Config em produção)
        curl_setopt($ch, CURLOPT_USERPWD, "admin:admin"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            return ['error' => true, 'code' => $httpCode, 'message' => $result];
        }

        return json_decode($result, true) ?: [];
    }
}