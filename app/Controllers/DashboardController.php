<?php
namespace App\Controllers;

use App\Services\AsaasService;
use Exception;
use PDO;

class DashboardController extends ApiController {

    public function index() {
        $this->render('dashboard');
    }

    // --- ROTA PRINCIPAL DE DADOS (KPIs) ---
    public function getKpis() {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            $role = $_SESSION['user_role'] ?? 'user';
            $tenantId = $_SESSION['tenant_id'];
            $userId = $_SESSION['user_id'];
            $customerId = $_SESSION['customer_id'] ?? null;

            // 1. SUPER ADMIN (Visão Global de Servidor e Tenants)
            if ($role === 'superadmin' || in_array($userId, [1, 7])) { // IDs fixos de superadmin
                $this->json($this->getSuperAdminStats());
                return;
            }

            // 2. CLIENTE FINAL (Gestor de Frota - Foco em Operação)
            if (!empty($customerId)) {
                $this->json($this->getClientStats($tenantId, $customerId));
                return;
            }

            // 3. ADMIN DO TENANT (Visão de Negócio e Equipamentos)
            $this->json($this->getTenantAdminStats($tenantId));

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- LÓGICAS ESPECÍFICAS ---

    private function getSuperAdminStats() {
        // Quantidade de Tenants
        $tenants = $this->pdo->query("SELECT COUNT(*) FROM saas_tenants")->fetchColumn();
        
        // Quantidade Total de Veículos no Sistema
        $vehicles = $this->pdo->query("SELECT COUNT(*) FROM saas_vehicles")->fetchColumn();
        
        // Rastreadores em Estoque Global
        $stock = $this->pdo->query("SELECT COUNT(*) FROM saas_stock WHERE status = 'available'")->fetchColumn();

        // Saúde do Servidor (Simulada ou via shell_exec se permitido)
        $cpu = sys_getloadavg()[0] ?? 0; // Carga média
        
        return [
            'view_type' => 'superadmin',
            'cards' => [
                ['title' => 'Tenants Ativos', 'value' => $tenants, 'icon' => 'fa-globe', 'color' => 'blue'],
                ['title' => 'Veículos Monitorados', 'value' => $vehicles, 'icon' => 'fa-car', 'color' => 'green'],
                ['title' => 'Estoque Global', 'value' => $stock, 'icon' => 'fa-microchip', 'color' => 'orange'],
                ['title' => 'Carga CPU', 'value' => $cpu . '%', 'icon' => 'fa-server', 'color' => 'red'],
            ],
            'chart_label' => 'Crescimento de Veículos (Global)',
            // Dados Mockados para gráfico
            'chart_data' => [100, 120, 150, 180, 200, $vehicles] 
        ];
    }

    private function getTenantAdminStats($tenantId) {
        // Veículos Ativos
        $vehicles = $this->pdo->query("SELECT COUNT(*) FROM saas_vehicles WHERE tenant_id = $tenantId AND status = 'active'")->fetchColumn();
        
        // Clientes
        $customers = $this->pdo->query("SELECT COUNT(*) FROM saas_customers WHERE tenant_id = $tenantId")->fetchColumn();
        
        // Financeiro (Receita Mensal Estimada)
        $revenue = $this->pdo->query("SELECT SUM(unit_price) FROM saas_customers WHERE tenant_id = $tenantId")->fetchColumn();
        
        // Alertas Pendentes (Todos os clientes)
        // Se tiver tabela de alertas, conte aqui. Mock:
        $alerts = 0; 

        return [
            'view_type' => 'tenant_admin',
            'cards' => [
                ['title' => 'Veículos Ativos', 'value' => $vehicles, 'icon' => 'fa-truck', 'color' => 'blue'],
                ['title' => 'Meus Clientes', 'value' => $customers, 'icon' => 'fa-users', 'color' => 'purple'],
                ['title' => 'Receita Mensal (Est.)', 'value' => 'R$ ' . number_format($revenue, 2, ',', '.'), 'icon' => 'fa-wallet', 'color' => 'green'],
                ['title' => 'Alertas Gerais', 'value' => $alerts, 'icon' => 'fa-bell', 'color' => 'red'],
            ],
            'chart_label' => 'Ativações x Cancelamentos',
            'chart_data' => [5, 8, 12, 10, 15, 20] // Mock
        ];
    }

    private function getClientStats($tenantId, $customerId) {
        // Veículos do Cliente
        $vehicles = $this->pdo->query("SELECT COUNT(*) FROM saas_vehicles WHERE tenant_id = $tenantId AND customer_id = $customerId AND status = 'active'")->fetchColumn();
        
        // Km Rodado Hoje (Simulado - precisaria de integração SGBras/Traccar)
        $kmToday = rand(100, 500); 
        
        // Alertas do Cliente
        $alerts = 5; // Mock
        
        // Manutenções Próximas (Mock)
        $maintenance = 2;

        return [
            'view_type' => 'client',
            'cards' => [
                ['title' => 'Minha Frota', 'value' => $vehicles, 'icon' => 'fa-car', 'color' => 'blue'],
                ['title' => 'Km Rodado (Hoje)', 'value' => $kmToday . ' km', 'icon' => 'fa-road', 'color' => 'orange'],
                ['title' => 'Alertas Críticos', 'value' => $alerts, 'icon' => 'fa-exclamation-triangle', 'color' => 'red'],
                ['title' => 'Manutenções', 'value' => $maintenance, 'icon' => 'fa-wrench', 'color' => 'gray'],
            ],
            'chart_label' => 'Quilometragem Semanal',
            'chart_data' => [120, 150, 180, 200, 100, 50, $kmToday] // Mock
        ];
    }

    // --- OUTROS MÉTODOS (GetAlerts, GetData) MANTIDOS ---
    public function getAlerts() { $this->json(['data' => []]); }
    public function getData() { $this->json(['data' => []]); }
}
?>