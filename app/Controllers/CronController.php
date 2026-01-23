<?php
namespace App\Controllers;

use PDO;
use Exception;

class CronController extends ApiController {

    // 1. Notificações de Cobrança (Substitui cron_notificacoes.php)
    // Chame via cron: wget -qO- https://seu-site.com/api/cron/notifications
    public function runNotifications() {
        // Aumenta tempo de execução
        set_time_limit(300); 
        $logs = [];

        try {
            $sql = "SELECT id, asaas_token FROM saas_tenants WHERE asaas_token IS NOT NULL";
            $tenants = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tenants as $tenant) {
                $token = $tenant['asaas_token'];
                // Busca boletos vencendo em 3 dias
                $dueDate = date('Y-m-d', strtotime('+3 days'));
                
                // Chamada à API Asaas (Simulada aqui, ideal usar biblioteca ou Helper)
                $ch = curl_init("https://api.asaas.com/v3/payments?dueDate=$dueDate&status=PENDING&limit=50");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $token"]);
                $res = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (!empty($res['data'])) {
                    foreach ($res['data'] as $payment) {
                        // Lógica de notificação (Email/SMS)
                        // Aqui apenas simulamos o log para não enviar spam real durante testes
                        $logs[] = "[Tenant {$tenant['id']}] Boleto {$payment['id']} vence em 3 dias. Valor: R$ {$payment['value']}. Cliente: {$payment['customer']}";
                        
                        // TODO: Implementar envio real de e-mail usando mail() ou PHPMailer
                    }
                }
            }

            $this->json(['success' => true, 'processed' => count($logs), 'logs' => $logs]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // 2. Sincronização Global (Substitui sincronizar_tudo.php)
    // Apenas Superadmin pode chamar manualmente via botão no painel
    public function syncGlobalRules() {
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso Negado'], 403);
            return;
        }

        try {
            // Carrega config global
            $configFile = __DIR__ . '/../../traccar_config.json';
            if (!file_exists($configFile)) {
                throw new Exception("Arquivo de configuração não encontrado.");
            }
            $config = json_decode(file_get_contents($configFile), true);

            // Busca TODOS os veículos
            $vehicles = $this->pdo->query("SELECT id, name, traccar_device_id FROM saas_vehicles")->fetchAll(PDO::FETCH_ASSOC);
            
            require_once __DIR__ . '/../Services/TraccarApi.php'; // Ou usar o Helper existente
            $traccar = new \TraccarApi(); // Classe precisa estar disponível

            $count = 0;
            foreach ($vehicles as $v) {
                $tid = $v['traccar_device_id'];
                
                // Aplica Atributo Global (ex: Ignição)
                if (!empty($config['global_ignition_attr_id'])) {
                    $traccar->curl('/permissions', 'POST', [
                        'deviceId' => $tid, 
                        'attributeId' => $config['global_ignition_attr_id']
                    ]);
                }
                
                // Aplica Notificações Globais
                if (!empty($config['global_notification_ids'])) {
                    foreach ($config['global_notification_ids'] as $nid) {
                        $traccar->curl('/permissions', 'POST', [
                            'deviceId' => $tid, 
                            'notificationId' => $nid
                        ]);
                    }
                }
                $count++;
            }

            $this->json(['success' => true, 'message' => "$count veículos sincronizados com regras globais."]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}