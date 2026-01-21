<?php
// ARQUIVO: api_clientes.php
// ATUALIZADO: Correção de Erros 500 (Datas Nulas e Divisão por Zero)

session_start();
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// 1. Segurança e Conexão
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403); 
    exit(json_encode(['error' => 'Sessão expirada.'])); 
}

if (!file_exists('db.php')) {
    http_response_code(500);
    exit(json_encode(['error' => 'Erro crítico: db.php não encontrado.']));
}
require 'db.php';

$tenant_id  = $_SESSION['tenant_id'];
$user_role  = $_SESSION['user_role'] ?? 'user';
$action     = $_REQUEST['action'] ?? '';
$input      = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // --- LISTAR CLIENTES ---
    case 'get_customers':
        try {
            $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM saas_vehicles v WHERE v.client_id = c.id AND v.status = 'active' AND v.tenant_id = ?) as active_vehicles_count
                    FROM saas_customers c
                    WHERE c.tenant_id = ? 
                    ORDER BY c.id DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tenant_id, $tenant_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- CÁLCULO DE PRÓ-RATA (CORRIGIDO) ---
    // --- CÁLCULO DE PRÓ-RATA (COM DATAS DE ATIVAÇÃO/DESATIVAÇÃO) ---
    case 'calculate_pro_rata':
        try {
            $clientId = $_GET['client_id'] ?? 0;
            
            // 1. Busca Cliente
            $stmt = $pdo->prepare("SELECT * FROM saas_customers WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$clientId, $tenant_id]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$client) throw new Exception("Cliente não encontrado.");

            // 2. Define Ciclo (Datas)
            $dueDay = (int)($client['due_day'] ?? 10);
            $today = new DateTime();
            $billingDate = new DateTime();
            
            // Se hoje >= dia do vencimento, calcula para o próximo mês
            if ((int)$today->format('d') >= $dueDay) {
                $billingDate->modify('+1 month');
            }
            $billingDate->setDate((int)$billingDate->format('Y'), (int)$billingDate->format('m'), $dueDay);
            
            $cycleStart = clone $billingDate;
            $cycleStart->modify('-1 month'); // Ciclo começa 1 mês antes do vencimento

            // 3. Busca Veículos
            // Fallback para evitar erro 500 se a coluna ainda não existir (mas rodamos o SQL acima)
            try {
                $stmtV = $pdo->prepare("SELECT id, plate, name, status, status_changed_at FROM saas_vehicles WHERE client_id = ? AND tenant_id = ?");
                $stmtV->execute([$clientId, $tenant_id]);
            } catch (Exception $e) {
                $stmtV = $pdo->prepare("SELECT id, plate, name, status, NULL as status_changed_at FROM saas_vehicles WHERE client_id = ? AND tenant_id = ?");
                $stmtV->execute([$clientId, $tenant_id]);
            }
            $vehicles = $stmtV->fetchAll(PDO::FETCH_ASSOC);

            // 4. Preço Base
            $activeCount = 0;
            foreach($vehicles as $v) if(($v['status']??'') === 'active') $activeCount++;
            
            $contractValue = floatval($client['contract_value'] ?? 0);
            $unitPrice = floatval($client['unit_price'] ?? 0);
            
            if ($unitPrice <= 0 && $activeCount > 0) $unitPrice = $contractValue / $activeCount;
            elseif ($unitPrice <= 0) $unitPrice = 0;

            $details = [];
            $totalProRata = 0;

            foreach ($vehicles as $v) {
                // Data de mudança de status (ou data muito antiga se null)
                $rawDate = $v['status_changed_at'] ?? '2000-01-01 00:00:00';
                try { $statusDate = new DateTime($rawDate); } catch(Exception $e) { $statusDate = new DateTime('2000-01-01'); }

                $cost = 0;
                $obs = "Ciclo completo";
                $daysActive = 30;
                
                // Datas de exibição na fatura
                $calcStart = $cycleStart;
                $calcEnd = $billingDate;

                // Cenário 1: Mudança DENTRO do ciclo atual
                if ($statusDate >= $cycleStart && $statusDate <= $billingDate) {
                    $diff = $billingDate->diff($statusDate);
                    $daysDiff = $diff->days > 30 ? 30 : $diff->days;

                    if ($v['status'] == 'active') {
                        // Ativou no meio: Cobra da ativação até o fim
                        $daysActive = $daysDiff;
                        $calcStart = $statusDate; // Data real da ativação
                        $cost = ($unitPrice / 30) * $daysActive;
                        $obs = "Ativação Nova";
                    } else {
                        // Desativou no meio: Cobra do início até a desativação
                        $daysActive = 30 - $daysDiff;
                        if ($daysActive < 0) $daysActive = 0;
                        $calcEnd = $statusDate; // Data real da desativação
                        $cost = ($unitPrice / 30) * $daysActive;
                        $obs = "Desativado";
                    }
                } 
                // Cenário 2: Sem mudança recente
                else {
                    if ($v['status'] == 'active') {
                        $cost = $unitPrice;
                        $daysActive = 30;
                    } else {
                        $cost = 0;
                        $daysActive = 0;
                        $obs = "Inativo";
                        $calcStart = null; $calcEnd = null; // Não exibe datas se inativo total
                    }
                }

                if ($cost > 0 || $v['status'] == 'active') {
                    $totalProRata += $cost;
                    $details[] = [
                        'plate' => $v['plate'] ?? 'S/ Placa',
                        'days' => $daysActive,
                        'cost' => number_format((float)$cost, 2, ',', '.'),
                        'obs' => $obs,
                        'start_date' => $calcStart ? $calcStart->format('d/m') : '-',
                        'end_date' => $calcEnd ? $calcEnd->format('d/m') : '-'
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'billing_date' => $billingDate->format('d/m/Y'),
                'cycle_start' => $cycleStart->format('d/m/Y'),
                'total_preview' => number_format((float)$totalProRata, 2, ',', '.'),
                'unit_price_base' => number_format((float)$unitPrice, 2, ',', '.'),
                'vehicles' => $details
            ]);

        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- SALVAR CLIENTE ---
    case 'save_customer':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $doc = $input['document'] ?? '';
        $phone = $input['phone'] ?? '';
        $email = $input['email'] ?? '';
        $addr = $input['address'] ?? '';
        
        $cValue = $input['contract_value'] ?? 0.00;
        $unitPrice = $input['unit_price'] ?? 0.00; // PREÇO POR VEÍCULO
        $cDay = $input['due_day'] ?? 10;
        $cStart = !empty($input['contract_start']) ? $input['contract_start'] : null;
        $cEnd = !empty($input['contract_end']) ? $input['contract_end'] : null;
        $statusManual = $input['financial_status'] ?? null;

        if (empty($name)) http_400('Nome obrigatório');

        try {
            if ($id) {
                $sql = "UPDATE saas_customers SET name=?, document=?, phone=?, email=?, address=?, contract_value=?, unit_price=?, due_day=?, contract_start=?, contract_end=?";
                $params = [$name, $doc, $phone, $email, $addr, $cValue, $unitPrice, $cDay, $cStart, $cEnd];
                
                if ($statusManual) {
                    $sql .= ", financial_status=?";
                    $params[] = $statusManual;
                }
                
                $sql .= " WHERE id=? AND tenant_id=?";
                $params[] = $id;
                $params[] = $tenant_id;
                
                $pdo->prepare($sql)->execute($params);
            } else {
                $stmt = $pdo->prepare("INSERT INTO saas_customers (tenant_id, name, document, phone, email, address, contract_value, unit_price, due_day, contract_start, contract_end, financial_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ok', 'active')");
                $stmt->execute([$tenant_id, $name, $doc, $phone, $email, $addr, $cValue, $unitPrice, $cDay, $cStart, $cEnd]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- VINCULAR ASAAS ---
    case 'link_asaas_customer':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        
        $localId = $input['local_id'] ?? null;
        $asaasId = $input['asaas_id'] ?? null;
        $asaasName = $input['asaas_name'] ?? 'Cliente Asaas';

        if (!$localId || !$asaasId) http_400('Dados incompletos');

        try {
            $stmt = $pdo->prepare("UPDATE saas_customers SET asaas_customer_id = ?, asaas_customer_name = ?, financial_status = 'ok' WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$asaasId, $asaasName, $localId, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- EXCLUIR CLIENTE ---
    case 'delete_customer':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        $id = $input['id'] ?? null;
        try {
            // Verifica dependências
            $chk = $pdo->prepare("SELECT count(*) FROM saas_vehicles WHERE client_id = ?");
            $chk->execute([$id]);
            if ($chk->fetchColumn() > 0) http_400('Cliente possui veículos. Remova-os antes.');

            $pdo->prepare("DELETE FROM saas_customers WHERE id = ? AND tenant_id = ?")->execute([$id, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action inválida']);
        break;
}

function http_400($msg) { http_response_code(400); exit(json_encode(['error' => $msg])); }
function http_403($msg) { http_response_code(403); exit(json_encode(['error' => $msg])); }
function http_500($msg) { http_response_code(500); exit(json_encode(['error' => $msg])); }
?>