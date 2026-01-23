<?php
namespace App\Controllers;

use PDO;
use Exception;

class IconController extends ApiController {

    // Lista ícones do cliente
    public function index() {
        try {
            $sql = "SELECT * FROM saas_custom_icons WHERE tenant_id = ? ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $icons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Adiciona URL completa para exibição
            foreach ($icons as &$icon) {
                $icon['url'] = '/' . $icon['file_path'];
            }

            $this->json(['success' => true, 'data' => $icons]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Upload de novo ícone
    public function store() {
        $name = $_POST['name'] ?? '';
        
        if (empty($name)) {
            $this->json(['error' => 'Dê um nome para o ícone.'], 400);
            return;
        }

        if (!isset($_FILES['icon']) || $_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Selecione uma imagem válida.'], 400);
            return;
        }

        try {
            // Valida Extensão
            $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            
            if (!in_array($ext, $allowed)) {
                $this->json(['error' => 'Apenas imagens PNG, JPG, WEBP ou SVG.'], 400);
                return;
            }

            // Diretório de Upload
            $uploadDir = __DIR__ . '/../../public/uploads/icons/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Gera nome único para evitar conflito e cache
            $fileName = 'icon_' . uniqid() . '.' . $ext;
            $destination = $uploadDir . $fileName;

            // Move arquivo
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $destination)) {
                
                // Salva no Banco
                $relativePath = 'uploads/icons/' . $fileName;
                $sql = "INSERT INTO saas_custom_icons (tenant_id, name, file_path) VALUES (?, ?, ?)";
                $this->pdo->prepare($sql)->execute([$this->tenant_id, $name, $relativePath]);

                $this->json(['success' => true, 'message' => 'Ícone enviado com sucesso!']);
            } else {
                $this->json(['error' => 'Erro ao salvar o arquivo no servidor.'], 500);
            }

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Excluir ícone
    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            $this->json(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            // 1. Busca info do arquivo para deletar do disco
            $stmt = $this->pdo->prepare("SELECT file_path FROM saas_custom_icons WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $this->tenant_id]);
            $icon = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($icon) {
                // Deleta do Disco
                $fullPath = __DIR__ . '/../../public/' . $icon['file_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                // Deleta do Banco
                $this->pdo->prepare("DELETE FROM saas_custom_icons WHERE id = ?")->execute([$id]);
                
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Ícone não encontrado.'], 404);
            }

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}