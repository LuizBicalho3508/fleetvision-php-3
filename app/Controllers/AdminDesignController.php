<?php
namespace App\Controllers;

use Exception;
use PDO;

class AdminDesignController extends ApiController {

    public function __construct() {
        parent::__construct();
        // Apenas Superadmin
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso Negado'], 403);
            exit;
        }
    }

    // Busca configurações de um Tenant específico
    public function getSettings() {
        $tenantId = $_GET['id'] ?? null;
        if (!$tenantId) {
            $this->json(['error' => 'ID do Tenant obrigatório'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, name, slug, logo_url, login_bg_url, login_opacity, login_btn_color, primary_color FROM saas_tenants WHERE id = ?");
            $stmt->execute([$tenantId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                // Prepara URLs
                if ($data['login_bg_url']) $data['login_bg_url'] = '/' . $data['login_bg_url'];
                if ($data['logo_url']) $data['logo_url'] = '/' . $data['logo_url'];
                $this->json(['success' => true, 'data' => $data]);
            } else {
                $this->json(['error' => 'Empresa não encontrada'], 404);
            }
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salva as configurações
    public function save() {
        $tenantId = $_POST['tenant_id'] ?? null;
        
        if (!$tenantId) {
            $this->json(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            $opacity = $_POST['login_opacity'] ?? 0.95;
            $btnColor = $_POST['login_btn_color'] ?? '#2563eb';
            
            // Upload do Background
            $bgPath = null;
            if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $fileName = 'bg_tenant_' . $tenantId . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../../public/uploads/tenants/';
                    
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    if (move_uploaded_file($_FILES['background']['tmp_name'], $uploadDir . $fileName)) {
                        $bgPath = 'uploads/tenants/' . $fileName;
                    }
                }
            }

            // Monta Query
            $sql = "UPDATE saas_tenants SET login_opacity = ?, login_btn_color = ?";
            $params = [$opacity, $btnColor];

            if ($bgPath) {
                $sql .= ", login_bg_url = ?";
                $params[] = $bgPath;
            }

            $sql .= " WHERE id = ?";
            $params[] = $tenantId;

            $this->pdo->prepare($sql)->execute($params);

            $this->json(['success' => true, 'bg_url' => $bgPath ? '/'.$bgPath : null]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Remove o fundo (volta ao padrão)
    public function resetBackground() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['tenant_id'] ?? null;

        if (!$id) return $this->json(['error' => 'ID inválido'], 400);

        try {
            $this->pdo->prepare("UPDATE saas_tenants SET login_bg_url = NULL WHERE id = ?")->execute([$id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}