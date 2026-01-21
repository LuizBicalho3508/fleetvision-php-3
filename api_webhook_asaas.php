<?php
// ARQUIVO: api_webhook_asaas.php
// Recebe notificações do Asaas e bloqueia/desbloqueia usuários
// NÃO REQUER SESSÃO (É chamado pelo servidor do Asaas)

ini_set('display_errors', 0);
header('Content-Type: application/json');

require 'db.php'; // Sua conexão PDO

// 1. Recebe o Payload
$json = file_get_contents('php://input');
$event = json_decode($json, true);

// Log para debug (opcional, crie a pasta logs e dê permissão)
// file_put_contents('logs/webhook_asaas.log', date('Y-m-d H:i:s') . " - " . $json . "\n", FILE_APPEND);

if (!isset($event['event'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Evento inválido']));
}

$payment = $event['payment'];
$asaasCustomerId = $payment['customer'];
$eventType = $event['event'];

// 2. Identifica o Cliente Local pelo ID do Asaas
$stmt = $pdo->prepare("SELECT id, tenant_id FROM saas_customers WHERE asaas_customer_id = ? LIMIT 1");
$stmt->execute([$asaasCustomerId]);
$localCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$localCustomer) {
    // Cliente não vinculado localmente, ignora
    echo json_encode(['status' => 'ignored', 'reason' => 'Customer not linked']);
    exit;
}

$localCustomerId = $localCustomer['id'];
$tenantId = $localCustomer['tenant_id'];

// 3. Processa Eventos
switch ($eventType) {
    case 'PAYMENT_OVERDUE': // Venceu e não pagou
        // Bloqueia usuários atrelados a este cliente
        // Verifica tolerância (opcional, aqui estamos bloqueando direto)
        blockUsers($pdo, $localCustomerId);
        updateCustomerStatus($pdo, $localCustomerId, 'overdue');
        break;

    case 'PAYMENT_RECEIVED': // Pagou
    case 'PAYMENT_CONFIRMED':
        // Antes de desbloquear, verifica se há OUTRAS contas vencidas deste mesmo cliente
        if (!hasOtherOverduePayments($tenantId, $asaasCustomerId)) {
            unblockUsers($pdo, $localCustomerId);
            updateCustomerStatus($pdo, $localCustomerId, 'ok');
        }
        break;
}

echo json_encode(['status' => 'processed']);

// --- FUNÇÕES AUXILIARES ---

function blockUsers($pdo, $customerId) {
    // Muda status para 'blocked' (ou inactive) na tabela de usuários
    $stmt = $pdo->prepare("UPDATE saas_users SET status = 'inactive' WHERE customer_id = ?");
    $stmt->execute([$customerId]);
}

function unblockUsers($pdo, $customerId) {
    $stmt = $pdo->prepare("UPDATE saas_users SET status = 'active' WHERE customer_id = ?");
    $stmt->execute([$customerId]);
}

function updateCustomerStatus($pdo, $customerId, $status) {
    $stmt = $pdo->prepare("UPDATE saas_customers SET financial_status = ? WHERE id = ?");
    $stmt->execute([$status, $customerId]);
}

function hasOtherOverduePayments($tenantId, $asaasCustomerId) {
    // Para ter certeza absoluta, consultamos a API do Asaas para ver se ainda deve
    // Requer o token do tenant.
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT asaas_token FROM saas_tenants WHERE id = ?");
    $stmt->execute([$tenantId]);
    $token = $stmt->fetchColumn();
    
    if (!$token) return false; // Se não tem token, na dúvida libera

    $ch = curl_init("https://api.asaas.com/v3/payments?customer=$asaasCustomerId&status=OVERDUE&limit=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $token"]);
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    
    // Se totalCount > 0, ainda tem boletos vencidos
    return ($data['totalCount'] ?? 0) > 0;
}
?>