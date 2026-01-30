<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use PDO;
use Exception;

class AdminDesignController extends ApiController {

    public function getSettings() {
        $this->checkPermission();
        try {
            $tenantId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : $_SESSION['tenant_id'];

            if ($tenantId != $_SESSION['tenant_id'] && !AuthMiddleware::hasPermission('*')) {
                $tenantId = $_SESSION['tenant_id'];
            }

            $stmt = $this->pdo->prepare("SELECT * FROM saas_tenants WHERE id = ?");
            $stmt->execute([$tenantId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            // Ajusta URLs
            foreach (['logo_url', 'login_bg_url'] as $field) {
                if (!empty($settings[$field]) && $settings[$field][0] !== '/' && strpos($settings[$field], 'http') === false) {
                    $settings[$field] = '/' . $settings[$field];
                }
            }
            
            $this->json($settings);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save() {
        $this->checkPermission();

        try {
            // DEBUG: Se der erro, verifique o arquivo error.log do servidor
            // error_log("POST recebido: " . print_r($_POST, true));

            $targetId = $_SESSION['tenant_id'];
            if (!empty($_POST['id']) && AuthMiddleware::hasPermission('*')) {
                $targetId = $_POST['id'];
            }

            // Lista de campos exata do banco
            $allowedFields = [
                'primary_color', 'secondary_color', 
                'sidebar_color', 'sidebar_text_color', 
                'login_card_color', 'login_text_color', 
                'login_bg_color', 'login_input_bg_color', 'login_btn_text_color'
            ];

            $sqlParts = [];
            $params = [];

            // Monta Query dinamicamente baseado no que chegou
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $sqlParts[] = "$field = ?";
                    $params[] = $_POST[$field];
                }
            }

            // Uploads
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            $handleUpload = function($key, $prefix) use ($targetId, $uploadDir) {
                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
                    if(in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                        $name = $prefix . '_' . $targetId . '_' . time() . '.' . $ext;
                        if(move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $name)) {
                            return 'uploads/' . $name;
                        }
                    }
                }
                return null;
            };

            if ($p = $handleUpload('logo', 'logo')) { $sqlParts[] = "logo_url = ?"; $params[] = $p; }
            if ($p = $handleUpload('login_bg', 'bg')) { $sqlParts[] = "login_bg_url = ?"; $params[] = $p; }

            if (empty($sqlParts)) {
                $this->json(['success' => false, 'message' => 'Nenhum dado recebido pelo servidor.']);
                return;
            }

            $sql = "UPDATE saas_tenants SET " . implode(', ', $sqlParts) . " WHERE id = ?";
            $params[] = $targetId;

            $this->pdo->prepare($sql)->execute($params);

            // Atualiza Sessão
            if ($targetId == $_SESSION['tenant_id']) {
                $stmt = $this->pdo->prepare("SELECT * FROM saas_tenants WHERE id = ?");
                $stmt->execute([$targetId]);
                $_SESSION['tenant_data'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    private function checkPermission() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!AuthMiddleware::hasPermission('*') && !AuthMiddleware::hasPermission('settings_view')) {
            $this->json(['error' => 'Acesso Negado'], 403);
            exit;
        }
    }
}
?>