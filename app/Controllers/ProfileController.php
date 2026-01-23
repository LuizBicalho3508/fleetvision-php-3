<?php
namespace App\Controllers;

use Exception;

class ProfileController extends ApiController {

    // Retorna dados do perfil
    public function index() {
        $stmt = $this->pdo->prepare("SELECT id, name, email, avatar, created_at FROM saas_users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $user = $stmt->fetch();
        
        // Remove senha do retorno (segurança)
        $this->json(['success' => true, 'data' => $user]);
    }

    // Atualiza Dados Básicos e Avatar
    public function update() {
        $name = $_POST['name'] ?? '';
        
        if (empty($name)) $this->json(['error' => 'Nome é obrigatório'], 400);

        try {
            $avatarPath = null;
            
            // Lógica de Upload de Imagem
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($ext, $allowed)) {
                    $newDetails = 'avatar_' . $this->user_id . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                    
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newDetails)) {
                        $avatarPath = 'uploads/avatars/' . $newDetails;
                        
                        // Atualiza Sessão
                        $_SESSION['user_avatar'] = $avatarPath;
                    }
                }
            }

            // Atualiza Banco
            $sql = "UPDATE saas_users SET name = ?";
            $params = [$name];

            if ($avatarPath) {
                $sql .= ", avatar = ?";
                $params[] = $avatarPath;
            }

            $sql .= " WHERE id = ?";
            $params[] = $this->user_id;

            $this->pdo->prepare($sql)->execute($params);
            
            // Atualiza nome na sessão
            $_SESSION['user_name'] = $name;

            $this->json(['success' => true, 'avatar' => $avatarPath]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Troca de Senha Segura
    public function changePassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $currentPass = $input['current_password'] ?? '';
        $newPass = $input['new_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            $this->json(['error' => 'Preencha todos os campos'], 400);
        }

        // Busca senha atual hashada
        $stmt = $this->pdo->prepare("SELECT password FROM saas_users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $hash = $stmt->fetchColumn();

        // Verifica senha atual
        if (!password_verify($currentPass, $hash)) {
            $this->json(['error' => 'A senha atual está incorreta'], 401);
        }

        // Salva nova senha
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $this->pdo->prepare("UPDATE saas_users SET password = ? WHERE id = ?")->execute([$newHash, $this->user_id]);

        $this->json(['success' => true]);
    }
}