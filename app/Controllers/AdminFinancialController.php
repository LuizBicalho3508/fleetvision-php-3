<?php
namespace App\Controllers;

use Exception;
use PDO;

class AdminFinancialController extends ApiController {

    public function __construct() {
        parent::__construct();
        // Segurança: Apenas Superadmin pode acessar
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso Negado'], 403);
            exit;
        }
    }

    // Lista os tenants com cálculos financeiros
    public function index() {
        try {
            $year = $_GET['year'] ?? date('Y');

            // 1. Busca todos os Tenants
            $sql = "SELECT id, name, slug, unit_price, due_day, payment_history, status FROM saas_tenants ORDER BY name ASC";
            $tenants = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            // 2. Conta veículos ATIVOS por tenant
            $sqlCount = "SELECT tenant_id, COUNT(*) as total FROM saas_vehicles WHERE status = 'active' GROUP BY tenant_id";
            $counts = $this->pdo->query($sqlCount)->fetchAll(PDO::FETCH_KEY_PAIR); // [id_tenant => quantidade]

            // 3. Processa os dados
            foreach ($tenants as &$t) {
                // Quantidade de veículos
                $t['active_vehicles'] = $counts[$t['id']] ?? 0;
                
                // Cálculo do Faturamento (Qtd * Preço Unitário)
                $t['total_amount'] = (float)$t['unit_price'] * $t['active_vehicles'];
                
                // Histórico de Pagamentos (JSON)
                $history = json_decode($t['payment_history'] ?? '{}', true);
                // Retorna apenas os meses pagos do ano selecionado
                $t['payments_year'] = $history[$year] ?? []; 
            }

            $this->json(['data' => $tenants]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salva Configurações (Preço e Data)
    public function saveSettings() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $stmt = $this->pdo->prepare("UPDATE saas_tenants SET unit_price = ?, due_day = ? WHERE id = ?");
            $stmt->execute([
                $data['unit_price'], 
                $data['due_day'], 
                $data['id']
            ]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Marca/Desmarca Pagamento
    public function togglePayment() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'];
        $month = (int)$data['month'];
        $year = (int)$data['year'];
        $action = $data['action']; // 'add' ou 'remove'

        try {
            // Busca histórico atual
            $stmt = $this->pdo->prepare("SELECT payment_history FROM saas_tenants WHERE id = ?");
            $stmt->execute([$id]);
            $currentJson = $stmt->fetchColumn();
            $history = json_decode($currentJson ?: '{}', true);

            // Garante que o ano existe no array
            if (!isset($history[$year])) $history[$year] = [];

            // Adiciona ou Remove o mês
            if ($action === 'add') {
                if (!in_array($month, $history[$year])) {
                    $history[$year][] = $month;
                }
            } else {
                $history[$year] = array_values(array_diff($history[$year], [$month]));
            }

            // Salva no banco
            $update = $this->pdo->prepare("UPDATE saas_tenants SET payment_history = ? WHERE id = ?");
            $update->execute([json_encode($history), $id]);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}