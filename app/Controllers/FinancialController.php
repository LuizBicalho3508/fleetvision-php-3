<?php
namespace App\Controllers;

use App\Services\AsaasService;
use Exception;
use PDO;

class FinancialController extends ApiController {

    public function index() {
        $this->render('financeiro');
    }

    // --- MÉTODOS PRIVADOS ---
    private function getService() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $tenantId = $_SESSION['tenant_id'];
        $stmt = $this->pdo->prepare("SELECT asaas_token FROM saas_tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $token = $stmt->fetchColumn();
        if (empty($token)) throw new Exception("Token Asaas não configurado.");
        return new AsaasService($token);
    }

    /**
     * MÁGICA: Busca os nomes diretamente na API do Asaas
     * Otimizado para não buscar o mesmo cliente repetidamente
     */
    private function resolveCustomerNames($payments, AsaasService $asaas) {
        if (empty($payments)) return [];

        // 1. Identifica IDs únicos para evitar chamadas duplicadas
        $customerIds = array_unique(array_column($payments, 'customer'));
        $namesCache = [];

        // 2. Busca cada cliente na API
        foreach ($customerIds as $custId) {
            try {
                // Verifica se já temos o nome em cache (de uma iteração anterior ou banco local se quisesse misturar)
                // Aqui vamos direto na API como você pediu
                $customerData = $asaas->getCustomerById($custId);
                $namesCache[$custId] = $customerData['name'] ?? 'Cliente Sem Nome';
                
                // Opcional: Aqui poderíamos salvar esse nome no banco local para cache futuro
                // mas vamos manter puramente API por enquanto.
                
            } catch (Exception $e) {
                $namesCache[$custId] = 'Cliente Externo/Erro';
            }
        }

        // 3. Preenche o array original com os nomes encontrados
        foreach ($payments as &$p) {
            $custId = $p['customer'];
            $p['customerName'] = $namesCache[$custId] ?? "Cliente ($custId)";
        }

        return $payments;
    }

    // 1. DADOS GERAIS + GRÁFICOS
    public function getDashboardData() {
        try {
            $asaas = $this->getService();
            
            // Saldo
            $balance = $asaas->getBalance();
            
            // Extrato (Últimas 10) - Aqui aplicamos a resolução de nomes
            $paymentsRes = $asaas->getPayments(['limit' => 10]);
            $payments = $this->resolveCustomerNames($paymentsRes['data'] ?? [], $asaas);

            // Estatísticas rápidas para os Cards
            // Obtemos a contagem direto do header/meta da resposta para ser rápido
            $today = date('Y-m-d');
            $dueTodayRes = $asaas->getPayments(['dueDate' => $today, 'status' => 'PENDING', 'limit' => 1]);
            $overdueRes = $asaas->getPayments(['status' => 'OVERDUE', 'limit' => 1]);
            $receivedRes = $asaas->getPayments(['status' => 'RECEIVED', 'limit' => 1]);

            $this->json([
                'configured' => true,
                'balance' => $balance['balance'] ?? 0,
                'payments' => $payments, // Agora com nomes reais do Asaas
                'totals' => [
                    'today' => $dueTodayRes['totalCount'] ?? 0,
                    'overdue' => $overdueRes['totalCount'] ?? 0
                ],
                'chartData' => [
                    'received' => $receivedRes['totalCount'] ?? 0,
                    'overdue' => $overdueRes['totalCount'] ?? 0,
                    'pending' => $paymentsRes['totalCount'] ?? 0
                ]
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage(), 'configured' => false], 500);
        }
    }

    // 2. DETALHES DOS CARDS (MODAL)
    public function getCardDetails() {
        try {
            $type = $_GET['type'];
            $asaas = $this->getService();
            $filters = ['limit' => 20]; // Limite para não travar o modal

            if ($type === 'today') {
                $filters['dueDate'] = date('Y-m-d');
                $filters['status'] = 'PENDING';
            } elseif ($type === 'overdue') {
                $filters['status'] = 'OVERDUE';
            }

            $res = $asaas->getPayments($filters);
            
            // Resolve nomes aqui também para o modal ficar bonito
            $data = $this->resolveCustomerNames($res['data'] ?? [], $asaas);
            
            $this->json(['data' => $data]);

        } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }

    // 3. BUSCA DE CLIENTES (CONSULTA)
    public function searchCustomers() {
        try {
            $query = $_GET['q'] ?? '';
            $asaas = $this->getService();
            $res = $asaas->searchCustomers($query);
            $this->json(['data' => $res['data'] ?? []]);
        } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }

    // 4. BOLETOS DO CLIENTE (MODAL)
    public function getCustomerInvoices() {
        try {
            $id = $_GET['id'];
            $asaas = $this->getService();
            
            // Busca dados do cliente
            $customer = $asaas->getCustomerById($id);
            
            // Busca boletos
            $paymentsRes = $asaas->getPayments(['customer' => $id, 'limit' => 50]);
            $invoices = $paymentsRes['data'] ?? [];

            // Aqui não precisamos resolver nomes, pois já sabemos quem é o cliente
            foreach($invoices as &$inv) {
                $inv['customerName'] = $customer['name'];
            }

            $this->json([
                'customer' => $customer,
                'invoices' => $invoices
            ]);
        } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }
    
    // 5. CONFIGURAÇÃO
    public function saveConfig() {
         try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];

            if (empty($data['api_key'])) {
                $this->json(['error' => 'Chave obrigatória.'], 400); return;
            }

            $sql = "UPDATE saas_tenants SET asaas_token = ? WHERE id = ?";
            $this->pdo->prepare($sql)->execute([$data['api_key'], $tenantId]);
            $this->json(['success' => true]);

        } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }
}
?>