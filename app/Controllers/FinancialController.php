<?php
namespace App\Controllers;

use PDO;
use Exception;

class FinancialController extends ApiController {

    // Lista faturas do cliente logado
    public function index() {
        try {
            // 1. Identifica o Cliente (Customer) do Usuário Logado
            $custId = $this->getCustomerId();

            if (!$custId) {
                // Se o usuário não estiver vinculado a um cliente (ex: é um admin solto), retorna vazio ou erro
                $this->json(['data' => [], 'message' => 'Usuário não vinculado a um cliente financeiro.']);
                return;
            }

            // 2. Busca Faturas Locais
            $sql = "SELECT id, asaas_id, description, value, due_date, status, invoice_url, pdf_url 
                    FROM saas_invoices 
                    WHERE customer_id = ? AND tenant_id = ? 
                    ORDER BY due_date DESC LIMIT 12";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$custId, $this->tenant_id]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatação
            foreach ($invoices as &$inv) {
                $inv['formatted_value'] = 'R$ ' . number_format($inv['value'], 2, ',', '.');
                $inv['formatted_date'] = date('d/m/Y', strtotime($inv['due_date']));
                $inv['status_label'] = $this->translateStatus($inv['status']);
            }

            $this->json(['success' => true, 'data' => $invoices]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Helper: Pega ID do Cliente SaaS vinculado ao usuário
    private function getCustomerId() {
        if ($this->user_role === 'superadmin') return null; // Superadmin vê tudo em outra tela (CRM)

        $stmt = $this->pdo->prepare("SELECT customer_id FROM saas_users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn();
    }

    private function translateStatus($status) {
        $map = [
            'PENDING' => 'Pendente',
            'RECEIVED' => 'Pago',
            'CONFIRMED' => 'Confirmado',
            'OVERDUE' => 'Vencido',
            'REFUNDED' => 'Estornado',
            'CANCELLED' => 'Cancelado'
        ];
        return $map[$status] ?? $status;
    }
    
    // Método Opcional: Criar fatura de teste (apenas para debug inicial sem Asaas)
    // POST api/financeiro/test_create
    public function createTestInvoice() {
        if ($this->user_role !== 'superadmin') return;
        
        $input = json_decode(file_get_contents('php://input'), true);
        $custId = $input['customer_id'];
        
        $sql = "INSERT INTO saas_invoices (tenant_id, customer_id, description, value, due_date, status, invoice_url) 
                VALUES (?, ?, 'Mensalidade Rastreamento', 49.90, NOW() + INTERVAL '5 days', 'PENDING', '#')";
        
        $this->pdo->prepare($sql)->execute([$this->tenant_id, $custId]);
        $this->json(['success' => true]);
    }
}