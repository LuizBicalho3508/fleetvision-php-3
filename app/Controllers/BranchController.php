<?php
namespace App\Controllers;

use PDO;
use Exception;

class BranchController extends ApiController {

    // Listar Filiais
    public function index() {
        try {
            $sql = "SELECT * FROM saas_branches WHERE tenant_id = ? ORDER BY name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Opcional: Contar veículos por filial
            // foreach ($branches as &$b) {
            //    $stmtC = $this->pdo->prepare("SELECT COUNT(*) FROM saas_vehicles WHERE branch_id = ?");
            //    $stmtC->execute([$b['id']]);
            //    $b['vehicle_count'] = $stmtC->fetchColumn();
            // }

            $this->json(['success' => true, 'data' => $branches]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salvar (Criar ou Editar)
    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';

        if (empty($name)) {
            $this->json(['error' => 'O nome da filial é obrigatório.'], 400);
            return;
        }

        try {
            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_branches SET name=?, address=?, phone=?, manager_name=?, status=? WHERE id=? AND tenant_id=?";
                $this->pdo->prepare($sql)->execute([
                    $name,
                    $input['address'] ?? '',
                    $input['phone'] ?? '',
                    $input['manager_name'] ?? '',
                    $input['status'] ?? 'active',
                    $id,
                    $this->tenant_id
                ]);
            } else {
                // INSERT
                $sql = "INSERT INTO saas_branches (tenant_id, name, address, phone, manager_name, status) VALUES (?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([
                    $this->tenant_id,
                    $name,
                    $input['address'] ?? '',
                    $input['phone'] ?? '',
                    $input['manager_name'] ?? '',
                    $input['status'] ?? 'active'
                ]);
            }
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Excluir
    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            $this->json(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            // Verifica se tem vínculos (Opcional, mas recomendado)
            // $check = $this->pdo->prepare("SELECT COUNT(*) FROM saas_vehicles WHERE branch_id = ?");
            // $check->execute([$id]);
            // if ($check->fetchColumn() > 0) {
            //    $this->json(['error' => 'Não é possível excluir: Existem veículos nesta filial.'], 400);
            //    return;
            // }

            $stmt = $this->pdo->prepare("DELETE FROM saas_branches WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $this->tenant_id]);
            
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}