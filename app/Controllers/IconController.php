<?php
namespace App\Controllers;

use Exception;
use PDO;

class IconController extends ApiController {

    // Lista ícones (Do Tenant + Globais)
    public function index() {
        try {
            $tenantId = $_SESSION['tenant_id'];

            // Busca ícones do próprio tenant OU ícones globais (tenant_id IS NULL)
            $sql = "SELECT *, 
                    CASE WHEN tenant_id IS NULL THEN 'global' ELSE 'custom' END as type 
                    FROM saas_icons 
                    WHERE tenant_id = ? OR tenant_id IS NULL 
                    ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Upload (Com nome e opção global)
    public function upload() {
        try {
            // 1. Verifica Arquivo
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->json(['error' => 'Nenhum arquivo enviado.'], 400);
                return;
            }

            // 2. Dados do Formulário
            $name = $_POST['name'] ?? pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
            $isGlobal = isset($_POST['is_global']) && $_POST['is_global'] === 'true';
            $userRole = $_SESSION['user_role'] ?? 'user';

            // 3. Define quem é o dono (Tenant ou Null/Global)
            // Apenas superadmin pode criar globais
            $tenantId = $_SESSION['tenant_id'];
            if ($isGlobal && $userRole === 'superadmin') {
                $tenantId = null; 
            }

            // 4. Processa Upload
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'gif'];

            if (!in_array($ext, $allowed)) {
                $this->json(['error' => 'Formato inválido. Use PNG, JPG ou SVG.'], 400);
                return;
            }

            $uploadDir = __DIR__ . '/../../uploads/icons/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = uniqid() . '.' . $ext;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $url = '/uploads/icons/' . $fileName;

                // Salva no Banco
                $stmt = $this->pdo->prepare("INSERT INTO saas_icons (tenant_id, name, url) VALUES (?, ?, ?)");
                $stmt->execute([$tenantId, $name, $url]);

                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Falha ao salvar arquivo no servidor.'], 500);
            }

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Deletar
    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'];
            $tenantId = $_SESSION['tenant_id'];
            $userRole = $_SESSION['user_role'] ?? 'user';

            // Busca o ícone
            $stmt = $this->pdo->prepare("SELECT * FROM saas_icons WHERE id = ?");
            $stmt->execute([$id]);
            $icon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$icon) {
                $this->json(['error' => 'Ícone não encontrado.'], 404);
                return;
            }

            // Permissão: 
            // - Superadmin pode deletar tudo
            // - Tenant só pode deletar o que é dele (não pode deletar global)
            if ($icon['tenant_id'] == null) {
                if ($userRole !== 'superadmin') {
                    $this->json(['error' => 'Você não tem permissão para excluir ícones globais.'], 403);
                    return;
                }
            } else {
                if ($icon['tenant_id'] != $tenantId) {
                    $this->json(['error' => 'Acesso negado.'], 403);
                    return;
                }
            }

            // Deleta Arquivo Físico
            $filePath = __DIR__ . '/../..' . $icon['url'];
            if (file_exists($filePath)) unlink($filePath);
            
            // Deleta do Banco
            $this->pdo->prepare("DELETE FROM saas_icons WHERE id = ?")->execute([$id]);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}