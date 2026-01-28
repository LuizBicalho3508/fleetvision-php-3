<?php
namespace App\Controllers;

use Exception;
use PDO;

class AdminTenantController extends ApiController {

    public function __construct() {
        parent::__construct();
        // Segurança: Apenas Superadmin
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso Negado'], 403);
            exit;
        }
    }

    public function index() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM saas_tenants ORDER BY id DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Corrige caminhos da logo para exibição
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
        // [FIX] Desativa saída de erros HTML para não quebrar o JSON
        ini_set('display_errors', 0);
        header('Content-Type: application/json');

        try {
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $slug = $_POST['slug'] ?? '';
            $status = $_POST['status'] ?? 'active';

            // Novas Cores de Personalização
            $primary_color    = $_POST['primary_color'] ?? '#2563eb';
            $secondary_color  = $_POST['secondary_color'] ?? '#1e293b';
            $sidebar_color    = $_POST['sidebar_color'] ?? '#ffffff';
            $login_card_color = $_POST['login_card_color'] ?? '#ffffff';

            if (empty($name) || empty($slug)) {
                echo json_encode(['error' => 'Nome e Slug são obrigatórios']);
                exit;
            }

            // Upload de Logo
            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    // [FIX] Caminho absoluto seguro
                    $uploadDir = __DIR__ . '/../../uploads/';
                    
                    // Cria pasta se não existir
                    if (!is_dir($uploadDir)) {
                        if (!@mkdir($uploadDir, 0777, true)) {
                            throw new Exception("Falha ao criar pasta 'uploads'. Verifique permissões.");
                        }
                    }
                    
                    // Gera nome único
                    $filename = 'logo_' . ($id ? $id : 'new_' . time()) . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    // Move arquivo
                    if (@move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                        $logoPath = 'uploads/' . $filename; // Caminho relativo para salvar no banco
                    } else {
                        $error = error_get_last();
                        throw new Exception("Falha no upload: " . ($error['message'] ?? 'Permissão negada ou pasta inválida.'));
                    }
                } else {
                    throw new Exception("Formato de arquivo inválido (apenas imagens).");
                }
            }

            // Banco de Dados
            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_tenants SET 
                            name = ?, slug = ?, status = ?, 
                            primary_color = ?, secondary_color = ?, 
                            sidebar_color = ?, login_card_color = ?";
                $params = [$name, $slug, $status, $primary_color, $secondary_color, $sidebar_color, $login_card_color];

                if ($logoPath) {
                    $sql .= ", logo_url = ?";
                    $params[] = $logoPath;
                }

                $sql .= " WHERE id = ?";
                $params[] = $id;

                $this->pdo->prepare($sql)->execute($params);
            } else {
                // INSERT
                $sql = "INSERT INTO saas_tenants (name, slug, status, primary_color, secondary_color, sidebar_color, login_card_color, logo_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([$name, $slug, $status, $primary_color, $secondary_color, $sidebar_color, $login_card_color, $logoPath]);
            }

            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function delete() {
        ini_set('display_errors', 0); 
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        try {
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