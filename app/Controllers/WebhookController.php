<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class WebhookController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function handleAsaas() {
        // 1. Segurança: Validação do Token do Asaas (Opcional mas Recomendado)
        // No painel do Asaas, você pode definir um token que será enviado no header
        $headers = getallheaders();
        $receivedToken = $headers['asaas-access-token'] ?? '';
        $mySecretToken = 'SEU_TOKEN_SECRETO_AQUI'; // Defina o mesmo valor no Asaas

        // Se quiser validar token, descomente:
        // if ($receivedToken !== $mySecretToken) {
        //    http_response_code(401);
        //    echo json_encode(['error' => 'Unauthorized']);
        //    exit;
        // }

        // 2. Captura o Payload
        $json = file_get_contents('php://input');
        $event = json_decode($json, true);

        if (!$event || !isset($event['event'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Payload']);
            exit;
        }

        // Log para debug (opcional, cria arquivo webhook.log na raiz)
        // file_put_contents(__DIR__ . '/../../webhook.log', date('Y-m-d H:i:s') . " - " . $event['event'] . "\n", FILE_APPEND);

        try {
            $payment = $event['payment'];
            $asaasId = $payment['id'];
            $eventType = $event['event'];

            // 3. Processa o Evento
            switch ($eventType) {
                case 'PAYMENT_RECEIVED':
                case 'PAYMENT_CONFIRMED':
                    $this->handlePaymentReceived($asaasId, $payment);
                    break;
                
                case 'PAYMENT_OVERDUE':
                    $this->handlePaymentOverdue($asaasId);
                    break;

                case 'PAYMENT_REFUNDED':
                    $this->updateInvoiceStatus($asaasId, 'REFUNDED');
                    break;
            }

            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Ação: Pagamento Recebido -> Baixa na Fatura + Desbloqueio
    private function handlePaymentReceived($asaasId, $paymentData) {
        // Atualiza a fatura local
        $sql = "UPDATE saas_invoices SET status = 'RECEIVED', payment_date = NOW() WHERE asaas_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asaasId]);

        // Verifica de quem é essa fatura para analisar desbloqueio
        $stmtGet = $this->pdo->prepare("SELECT customer_id, tenant_id FROM saas_invoices WHERE asaas_id = ?");
        $stmtGet->execute([$asaasId]);
        $invoice = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($invoice) {
            $this->checkCustomerStatus($invoice['customer_id'], $invoice['tenant_id']);
        }
    }

    // Ação: Pagamento Vencido -> Marca Fatura + Pode Bloquear Cliente
    private function handlePaymentOverdue($asaasId) {
        $this->updateInvoiceStatus($asaasId, 'OVERDUE');
        
        // Verifica se deve bloquear o cliente
        $stmtGet = $this->pdo->prepare("SELECT customer_id, tenant_id FROM saas_invoices WHERE asaas_id = ?");
        $stmtGet->execute([$asaasId]);
        $invoice = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($invoice) {
            // Marca cliente como inadimplente (OVERDUE)
            $sql = "UPDATE saas_customers SET financial_status = 'overdue' WHERE id = ?";
            $this->pdo->prepare($sql)->execute([$invoice['customer_id']]);
        }
    }

    // Helper: Atualiza status simples
    private function updateInvoiceStatus($asaasId, $status) {
        $sql = "UPDATE saas_invoices SET status = ? WHERE asaas_id = ?";
        $this->pdo->prepare($sql)->execute([$status, $asaasId]);
    }

    // Inteligência: Verifica se o cliente ainda tem pendências
    private function checkCustomerStatus($customerId, $tenantId) {
        // Conta quantas faturas VENCIDAS (OVERDUE) esse cliente ainda tem
        $sql = "SELECT COUNT(*) FROM saas_invoices 
                WHERE customer_id = ? AND tenant_id = ? AND status = 'OVERDUE'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerId, $tenantId]);
        $pendingCount = $stmt->fetchColumn();

        if ($pendingCount == 0) {
            // Se não deve nada, libera o acesso (OK)
            $sqlUpdate = "UPDATE saas_customers SET financial_status = 'ok' WHERE id = ?";
            $this->pdo->prepare($sqlUpdate)->execute([$customerId]);
        }
    }
}