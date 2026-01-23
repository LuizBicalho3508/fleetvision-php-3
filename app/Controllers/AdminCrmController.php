<?php
namespace App\Controllers;

use PDO;
use Exception;

class AdminCrmController extends ApiController {

    public function __construct() {
        parent::__construct();
        // Apenas Superadmin
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso Negado'], 403);
            exit;
        }
    }

    // Listar Leads
    public function index() {
        try {
            $sql = "SELECT * FROM saas_leads ORDER BY created_at DESC";
            $stmt = $this->pdo->query($sql);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupa por status para facilitar o Kanban no frontend, se necessário
            // Mas aqui retornaremos a lista plana e o JS organiza.
            $this->json(['success' => true, 'data' => $leads]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Criar ou Editar Lead
    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';

        if (empty($name)) {
            $this->json(['error' => 'Nome é obrigatório'], 400);
            return;
        }

        try {
            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_leads SET name=?, contact_name=?, email=?, phone=?, status=?, notes=?, source=?, updated_at=NOW() WHERE id=?";
                $this->pdo->prepare($sql)->execute([
                    $name,
                    $input['contact_name'] ?? '',
                    $input['email'] ?? '',
                    $input['phone'] ?? '',
                    $input['status'] ?? 'novo',
                    $input['notes'] ?? '',
                    $input['source'] ?? '',
                    $id
                ]);
            } else {
                // INSERT
                $sql = "INSERT INTO saas_leads (name, contact_name, email, phone, status, notes, source) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([
                    $name,
                    $input['contact_name'] ?? '',
                    $input['email'] ?? '',
                    $input['phone'] ?? '',
                    $input['status'] ?? 'novo',
                    $input['notes'] ?? '',
                    $input['source'] ?? ''
                ]);
            }
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Mover Card (Atualizar Status Rápido)
    public function updateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? '';

        if (!$id || !$status) {
            $this->json(['error' => 'Dados inválidos'], 400);
            return;
        }

        try {
            $sql = "UPDATE saas_leads SET status = ?, updated_at = NOW() WHERE id = ?";
            $this->pdo->prepare($sql)->execute([$status, $id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Excluir Lead
    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            $this->json(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            $this->pdo->prepare("DELETE FROM saas_leads WHERE id = ?")->execute([$id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}