<?php
namespace App\Controllers;

use Exception;
use PDO;

class AdminTenantController extends ApiController {

    public function __construct() {
        parent::__construct();
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? null;
        
        // Bypass para Super Admins (1 e 7)
        if (in_array($userId, [1, 7])) return;

        // Verificação padrão para outros
        $role = strtolower(str_replace(' ', '', $_SESSION['user_role'] ?? ''));
        $perms = $_SESSION['user_permissions'] ?? [];

        if ($role !== 'superadmin' && !in_array('*', $perms)) {
            $this->json(['error' => 'Acesso Negado.'], 403);
            exit;
        }
    }

    public function index() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM saas_tenants ORDER BY id DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ajusta caminhos de logo
            foreach ($data as &$tenant) {
                if (!empty($tenant['logo_url']) && $tenant['logo_url'][0] !== '/' && strpos($tenant['logo_url'], 'http') === false) {
                    $tenant['logo_url'] = '/' . $tenant['logo_url'];
                }
            }
            $this->json(['data' => $data]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save() {
        ini_set('display_errors', 0);
        header('Content-Type: application/json');

        try {
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $slug = $_POST['slug'] ?? '';
            $status = $_POST['status'] ?? 'active';

            if (empty($name) || empty($slug)) {
                echo json_encode(['error' => 'Nome e Slug são obrigatórios']);
                exit;
            }

            // --- LÓGICA DE PROTEÇÃO DE CORES ---
            // 1. Valores Padrão (para criação de novo tenant)
            $currentData = [
                'primary_color'      => '#2563eb',
                'secondary_color'    => '#1e293b',
                'sidebar_color'      => '#ffffff',
                'sidebar_text_color' => '#334155',
                'login_card_color'   => '#ffffff',
                'login_text_color'   => '#334155'
            ];

            // 2. Se for Edição (ID existe), busca os dados atuais do banco
            // Isso impede que salvar na lista sobrescreva as cores personalizadas com o padrão azul
            if ($id) {
                $stmt = $this->pdo->prepare("SELECT * FROM saas_tenants WHERE id = ?");
                $stmt->execute([$id]);
                $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($dbData) {
                    $currentData = array_merge($currentData, $dbData);
                }
            }

            // 3. Define as cores finais:
            // Lógica: Se veio no POST, usa o novo. Se não veio, mantém o do banco ($currentData).
            $primary_color      = $_POST['primary_color']      ?? $currentData['primary_color'];
            $secondary_color    = $_POST['secondary_color']    ?? $currentData['secondary_color'];
            $sidebar_color      = $_POST['sidebar_color']      ?? $currentData['sidebar_color'];
            $sidebar_text_color = $_POST['sidebar_text_color'] ?? $currentData['sidebar_text_color'];
            $login_card_color   = $_POST['login_card_color']   ?? $currentData['login_card_color'];
            $login_text_color   = $_POST['login_text_color']   ?? $currentData['login_text_color'];

            // Upload de Logo (Mantém a atual se não enviar nova)
            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                    $uploadDir = __DIR__ . '/../../uploads/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                    $filename = 'logo_' . ($id ? $id : time()) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $filename)) {
                        $logoPath = 'uploads/' . $filename;
                    }
                }
            }

            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_tenants SET 
                            name = ?, slug = ?, status = ?, 
                            primary_color = ?, secondary_color = ?, 
                            sidebar_color = ?, sidebar_text_color = ?, 
                            login_card_color = ?, login_text_color = ?";
                $params = [$name, $slug, $status, $primary_color, $secondary_color, $sidebar_color, $sidebar_text_color, $login_card_color, $login_text_color];

                if ($logoPath) {
                    $sql .= ", logo_url = ?";
                    $params[] = $logoPath;
                }

                $sql .= " WHERE id = ?";
                $params[] = $id;

                $this->pdo->prepare($sql)->execute($params);
            } else {
                // INSERT
                $sql = "INSERT INTO saas_tenants (name, slug, status, primary_color, secondary_color, sidebar_color, sidebar_text_color, login_card_color, login_text_color, logo_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([$name, $slug, $status, $primary_color, $secondary_color, $sidebar_color, $sidebar_text_color, $login_card_color, $login_text_color, $logoPath]);
            }

            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            if (!$id) { echo json_encode(['error' => 'ID inválido']); exit; }

            $this->pdo->prepare("DELETE FROM saas_tenants WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}
?>