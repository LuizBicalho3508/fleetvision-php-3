<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;

class ViewController {

    private $permissionMap = [
        'dashboard'       => 'dashboard_view',
        'mapa'            => 'map_view',
        'frota'           => 'vehicles_view',
        'motoristas'      => 'drivers_view',
        'estoque'         => 'stock_view',
        'jornada'         => 'journey_view',
        'alertas'         => 'alerts_view',
        'cercas'          => 'geofences_view',
        'historico'       => 'map_history',
        'clientes'        => 'customers_view',
        'filiais'         => 'settings_view',
        'financeiro'      => 'financial_view',
        'usuarios'        => 'users_view',
        'perfis'          => 'users_view',
        'relatorios'      => 'reports_view',
        
        // SuperAdmin
        'admin_crm'             => '*', 
        'admin_financeiro'      => '*',
        'admin_server'          => '*',
        'admin_gestao'          => '*',
        'admin_usuarios_tenant' => '*',
        'admin_debug'           => '*',
        'api_docs'              => '*',
        'teste'                 => '*',
    ];

    public function __call($method, $args) {
        $data = (isset($args[0]) && is_array($args[0])) ? $args[0] : [];
        return $this->render($method, $data);
    }

    public function render($viewName, $data = []) {
        if (!is_array($data)) $data = [];
        extract($data);

        $viewPath = __DIR__ . '/../../views/' . $viewName . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo "<h1>404 View Not Found: $viewName</h1>";
            return;
        }

        if (isset($this->permissionMap[$viewName])) {
            $requiredPerm = $this->permissionMap[$viewName];
            
            // Verificação de Segurança
            if (!AuthMiddleware::hasPermission($requiredPerm)) {
                $this->renderDebugErrorPage($requiredPerm);
                return;
            }
        }

        // Renderiza
        $standaloneViews = ['login', 'recover', 'reset', 'landing'];
        if (in_array($viewName, $standaloneViews)) {
            require $viewPath;
        } else {
            if (file_exists(__DIR__ . '/../../views/layout.php')) {
                require __DIR__ . '/../../views/layout.php';
            } else {
                require $viewPath;
            }
        }
    }

    public function generic($viewName) {
        $this->render($viewName);
    }

    private function renderDebugErrorPage($perm) {
        $slug = $_SESSION['tenant_slug'] ?? 'admin';
        $userId = $_SESSION['user_id'] ?? 'NÃO LOGADO';
        
        echo "<div style='font-family:monospace; padding:50px; background:#fef2f2; border:2px solid red; text-align:center;'>
                <h1 style='color:red'>🚫 ACESSO NEGADO</h1>
                <p>O AuthMiddleware negou seu acesso.</p>
                <hr>
                <p><strong>ID Usuário:</strong> $userId</p>
                <p><strong>Permissão Exigida:</strong> $perm</p>
                <hr>
                <p style='color:blue'>Se você está vendo isso sendo o usuário 7, o arquivo <b>AuthMiddleware.php</b> NÃO foi atualizado corretamente no servidor.</p>
                <a href='/$slug/dashboard'>Voltar</a>
              </div>";
        exit;
    }
}
?>