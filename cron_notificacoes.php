<?php
// ARQUIVO: cron_notificacoes.php
// Roda via CLI (Cron Job) diariamente

require __DIR__ . '/db.php';

echo "Iniciando verificação de vencimentos...\n";

// 1. Busca todos os tenants com token configurado
$tenants = $pdo->query("SELECT id, asaas_token FROM saas_tenants WHERE asaas_token IS NOT NULL")->fetchAll();

foreach ($tenants as $tenant) {
    $token = $tenant['asaas_token'];
    
    // Busca cobranças vencendo em 3 dias
    $dueDate = date('Y-m-d', strtotime('+3 days'));
    
    $url = "https://api.asaas.com/v3/payments?dueDate=$dueDate&status=PENDING&limit=100";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $token"]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($res['data'])) {
        foreach ($res['data'] as $payment) {
            $asaasCustId = $payment['customer'];
            
            // Acha o cliente local
            $stmt = $pdo->prepare("SELECT id, name FROM saas_customers WHERE asaas_customer_id = ? AND tenant_id = ?");
            $stmt->execute([$asaasCustId, $tenant['id']]);
            $localCust = $stmt->fetch();

            if ($localCust) {
                // Acha os usuários desse cliente para notificar
                $stmtUsers = $pdo->prepare("SELECT email, name FROM saas_users WHERE customer_id = ? AND status = 'active'");
                $stmtUsers->execute([$localCust['id']]);
                $users = $stmtUsers->fetchAll();

                foreach ($users as $u) {
                    $msg = "Olá {$u['name']}, seu boleto de R$ " . number_format($payment['value'], 2, ',', '.') . " vence em 3 dias. Link: " . $payment['invoiceUrl'];
                    
                    // AQUI: Envie o email ou SMS
                    // mail($u['email'], "Aviso de Vencimento", $msg);
                    echo "Notificando {$u['email']} sobre boleto {$payment['id']}\n";
                }
            }
        }
    }
}

echo "Concluído.\n";
?>