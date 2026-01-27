<?php
namespace App\Controllers;

use Exception;
use PDO;
use DateTime;

class CustomerController extends ApiController {

    // 1. LISTA CLIENTES (Com totais financeiros e veículos ativos)
    public function index() {
        try {
            $tenantId = $_SESSION['tenant_id'];

            // Subquery para contar veículos ativos
            $sql = "SELECT c.*, 
                           (SELECT COUNT(*) FROM saas_vehicles v WHERE v.customer_id = c.id AND v.status = 'active') as active_vehicles 
                    FROM saas_customers c 
                    WHERE c.tenant_id = ? 
                    ORDER BY c.name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcula Total Mensal Estimado
            foreach ($customers as &$c) {
                $c['estimated_total'] = (float)$c['unit_price'] * (int)$c['active_vehicles'];
            }

            $this->json(['data' => $customers]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // 2. SALVAR (Create/Update Unificado)
    public function store() { $this->save(false); }
    public function update() { $this->save(true); }

    private function save($isUpdate) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];

            if (empty($data['name'])) {
                $this->json(['error' => 'Nome é obrigatório.'], 400);
                return;
            }

            if ($isUpdate) {
                $sql = "UPDATE saas_customers SET 
                        name = ?, document = ?, email = ?, phone = ?, address = ?, 
                        unit_price = ?, invoice_due_day = ?, contract_due_date = ? 
                        WHERE id = ? AND tenant_id = ?";
                $params = [
                    $data['name'], $data['document'], $data['email'], $data['phone'], $data['address'],
                    $data['unit_price'] ?? 0, $data['invoice_due_day'] ?? 10, $data['contract_due_date'] ?? null,
                    $data['id'], $tenantId
                ];
            } else {
                $sql = "INSERT INTO saas_customers (
                        tenant_id, name, document, email, phone, address, 
                        unit_price, invoice_due_day, contract_due_date, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                $params = [
                    $tenantId, $data['name'], $data['document'], $data['email'], $data['phone'], $data['address'],
                    $data['unit_price'] ?? 0, $data['invoice_due_day'] ?? 10, $data['contract_due_date'] ?? null
                ];
            }

            $this->pdo->prepare($sql)->execute($params);
            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // 3. EXCLUIR
    public function delete() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'];
            
            // Impede exclusão se tiver veículos
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM saas_vehicles WHERE customer_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $this->json(['error' => 'Não é possível excluir: Cliente possui veículos vinculados.'], 400);
                return;
            }

            $this->pdo->prepare("DELETE FROM saas_customers WHERE id = ?")->execute([$id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // 4. LISTAR VEÍCULOS DO CLIENTE (Modal)
    public function getVehicles() {
        try {
            $id = $_GET['id'];
            $tenantId = $_SESSION['tenant_id'];
            
            $sql = "SELECT v.*, s.imei 
                    FROM saas_vehicles v 
                    LEFT JOIN saas_stock s ON v.stock_id = s.id 
                    WHERE v.customer_id = ? AND v.tenant_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id, $tenantId]);
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // 5. CALCULAR FATURA PROPORCIONAL (Pro-Rata)
    public function getInvoicePreview() {
        try {
            $customerId = $_GET['id'];
            $tenantId = $_SESSION['tenant_id'];

            // Dados do Cliente
            $stmt = $this->pdo->prepare("SELECT unit_price FROM saas_customers WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$customerId, $tenantId]);
            $unitPrice = (float) $stmt->fetchColumn();

            // Veículos Ativos
            $stmtV = $this->pdo->prepare("SELECT plate, active_since FROM saas_vehicles WHERE customer_id = ? AND status = 'active'");
            $stmtV->execute([$customerId]);
            $vehicles = $stmtV->fetchAll(PDO::FETCH_ASSOC);

            $total = 0;
            $items = [];
            $daysInMonth = 30; // Base comercial
            $pricePerDay = $unitPrice / $daysInMonth;
            
            $firstDayOfMonth = new DateTime(date('Y-m-01'));

            foreach ($vehicles as $v) {
                $activeSince = new DateTime($v['active_since']);
                
                // Se ativou ANTES deste mês -> Mensalidade Cheia
                if ($activeSince < $firstDayOfMonth) {
                    $amount = $unitPrice;
                    $days = 30;
                    $desc = "Mensalidade Integral";
                } else {
                    // Se ativou ESTE mês -> Proporcional
                    $daysActive = $daysInMonth - $activeSince->format('d') + 1;
                    if ($daysActive < 0) $daysActive = 0;
                    
                    $amount = $daysActive * $pricePerDay;
                    $days = $daysActive;
                    $desc = "Proporcional ({$daysActive} dias)";
                }

                $items[] = [
                    'plate' => $v['plate'],
                    'since' => $v['active_since'],
                    'days' => $days,
                    'amount' => number_format($amount, 2, '.', ''),
                    'desc' => $desc
                ];
                $total += $amount;
            }

            $this->json([
                'items' => $items,
                'total' => number_format($total, 2, '.', ''),
                'unit_price' => $unitPrice
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}