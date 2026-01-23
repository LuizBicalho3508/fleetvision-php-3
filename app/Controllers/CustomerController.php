<?php
namespace App\Controllers;

use PDO;
use Exception;

class CustomerController extends ApiController {

    // Lista clientes (GET api/customers)
    public function index() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, document, email, phone, address, 
                       asaas_customer_id, asaas_customer_name, financial_status,
                       contract_value, due_day, contract_status
                FROM saas_customers 
                WHERE tenant_id = ? 
                ORDER BY id DESC
            ");
            $stmt->execute([$this->tenant_id]);
            $this->json(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salva ou Atualiza (POST api/customers/save)
    public function store() {
        if (!$this->isAdmin()) $this->json(['error' => 'Sem permissão'], 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        
        if (empty($name)) $this->json(['error' => 'Nome obrigatório'], 400);

        try {
            if ($id) {
                $stmt = $this->pdo->prepare("UPDATE saas_customers SET name=?, document=?, phone=?, email=?, address=?, contract_value=?, due_day=? WHERE id=? AND tenant_id=?");
                $stmt->execute([
                    $name, $input['document']??'', $input['phone']??'', $input['email']??'', 
                    $input['address']??'', $input['contract_value']??0, $input['due_day']??10, 
                    $id, $this->tenant_id
                ]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO saas_customers (tenant_id, name, document, phone, email, address, contract_value, due_day, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([
                    $this->tenant_id, $name, $input['document']??'', $input['phone']??'', 
                    $input['email']??'', $input['address']??'', $input['contract_value']??0, 
                    $input['due_day']??10
                ]);
            }
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Exclui (POST api/customers/delete)
    public function delete() {
        if (!$this->isAdmin()) $this->json(['error' => 'Sem permissão'], 403);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) $this->json(['error' => 'ID inválido'], 400);

        try {
            $stmt = $this->pdo->prepare("DELETE FROM saas_customers WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $this->tenant_id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Erro ao excluir. Verifique vínculos.'], 500);
        }
    }

    // Vincula Asaas (POST api/customers/link_asaas)
    public function linkAsaas() {
        if (!$this->isAdmin()) $this->json(['error' => 'Sem permissão'], 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $localId = $input['local_id'] ?? null;
        $asaasId = $input['asaas_id'] ?? null;
        $asaasName = $input['asaas_name'] ?? 'Cliente Asaas';

        if (!$localId || !$asaasId) $this->json(['error' => 'Dados incompletos'], 400);

        try {
            $stmt = $this->pdo->prepare("UPDATE saas_customers SET asaas_customer_id = ?, asaas_customer_name = ?, financial_status = 'ok' WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$asaasId, $asaasName, $localId, $this->tenant_id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function isAdmin() {
        return $this->user_role === 'admin' || $this->user_role === 'superadmin';
    }
}