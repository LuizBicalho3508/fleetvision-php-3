<?php
namespace App\Controllers;

use Exception;
use PDO;

class AdminTenantController extends ApiController {

    public function __construct() {
        parent::__construct();
        // Segurança: Apenas Superadmin acessa
        // Verifique se o seu usuário no banco tem role_name = 'superadmin'
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso restrito ao Superadmin.'], 403);
            exit;
        }
    }

    // Listar Empresas
    public function index() {
        try {
            $sql = "SELECT * FROM saas_tenants ORDER BY id DESC";
            $stmt = $this->pdo->query($sql);
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->json(['success' => true, 'data' => $tenants]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salvar (Criar ou Editar)
    public function store() {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $primary = $_POST['primary_color'] ?? '#3b82f6';
        $secondary = $_POST['secondary_color'] ?? '#1e293b';

        if (empty($name) || empty($slug)) {
            $this->json(['error' => 'Nome e Slug (URL) são obrigatórios.'], 400);
            return;
        }

        try {
            // Lógica de Upload de Logo
            $logoUrl = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    // Nome único para evitar cache
                    $fileName = 'logo_' . $slug . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../../public/uploads/tenants/';
                    
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) {
                        $logoUrl = 'uploads/tenants/' . $fileName;
                    }
                }
            }

            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_tenants SET name=?, slug=?, status=?, primary_color=?, secondary_color=?";
                $params = [$name, $slug, $status, $primary, $secondary];

                // Só atualiza logo se enviou um novo
                if ($logoUrl) {
                    $sql .= ", logo_url=?";
                    $params[] = $logoUrl;
                }

                $sql .= " WHERE id=?";
                $params[] = $id;

                $this->pdo->prepare($sql)->execute($params);

            } else {
                // INSERT (Verifica se slug já existe)
                $check = $this->pdo->prepare("SELECT id FROM saas_tenants WHERE slug = ?");
                $check->execute([$slug]);
                if($check->fetch()) {
                    $this->json(['error' => 'Este Slug (URL) já está em uso.'], 400);
                    return;
                }

                $sql = "INSERT INTO saas_tenants (name, slug, status, primary_color, secondary_color, logo_url) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$name, $slug, $status, $primary, $secondary, $logoUrl];
                $this->pdo->prepare($sql)->execute($params);
            }

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Excluir Empresa
    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            $this->json(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            // Verifica segurança (não pode deletar se tiver usuários)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM saas_users WHERE tenant_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $this->json(['error' => 'Impossível excluir: Existem usuários vinculados a esta empresa.'], 400);
                return;
            }

            $this->pdo->prepare("DELETE FROM saas_tenants WHERE id = ?")->execute([$id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}