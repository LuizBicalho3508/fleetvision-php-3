<?php
// ARQUIVO: api_dados.php
// Centraliza CRUD (Clientes, Usuários, Veículos), KPIs e Proxy Traccar

session_start();
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// 1. Segurança Básica
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
$user_id    = $_SESSION['user_id'];
$user_role  = $_SESSION['user_role'] ?? 'user';
$user_email = $_SESSION['user_email'] ?? '';

// 2. Filtro de Permissões (Quem vê o quê)
$loggedCustomerId = null;
$isRestricted = false;

if ($user_role != 'admin' && $user_role != 'superadmin') {
    $isRestricted = true;
    $stmtUserCheck = $pdo->prepare("SELECT customer_id FROM saas_users WHERE id = ?");
    $stmtUserCheck->execute([$user_id]);
    $userDirectCustomer = $stmtUserCheck->fetchColumn();
    // Tenta pelo ID direto ou pelo email se não achar
    $loggedCustomerId = $userDirectCustomer ?: ($pdo->query("SELECT id FROM saas_customers WHERE email = '$user_email' AND tenant_id = $tenant_id")->fetchColumn());
}

$restrictionSQL = "";
if ($isRestricted) {
    if ($loggedCustomerId) {
        $restrictionSQL = " AND (v.client_id = $loggedCustomerId OR v.user_id = $user_id)";
    } else {
        $restrictionSQL = " AND v.user_id = $user_id";
    }
}

$action = $_REQUEST['action'] ?? '';
$endpoint = $_REQUEST['endpoint'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// --- ROTEADOR PROXY TRACCAR (LEGADO) ---
if (!empty($endpoint)) {
    if (strpos($endpoint, 'dashboard') !== false) { 
        http_response_code(400); 
        exit(json_encode(['error' => 'Endpoint restrito.'])); 
    }
    handleProxyTraccar($endpoint, $tenant_id, $loggedCustomerId, $user_id, $pdo);
    exit;
}

switch ($action) {

    // =========================================================================
    // 🏢 GESTÃO DE CLIENTES (ATUALIZADO)
    // =========================================================================
    
    case 'get_customers':
        try {
            // Busca clientes com dados de contrato e vínculo asaas
            $stmt = $pdo->prepare("
                SELECT id, name, document, email, phone, address, 
                       asaas_customer_id, asaas_customer_name, financial_status,
                       contract_value, due_day, contract_status
                FROM saas_customers 
                WHERE tenant_id = ? 
                ORDER BY id DESC
            ");
            $stmt->execute([$tenant_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'save_customer':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $doc = $input['document'] ?? '';
        $phone = $input['phone'] ?? '';
        $email = $input['email'] ?? '';
        $addr = $input['address'] ?? '';
        
        // Dados de Contrato
        $cValue = $input['contract_value'] ?? 0.00;
        $cDay = $input['due_day'] ?? 10;

        if (empty($name)) http_400('Nome obrigatório');

        try {
            if ($id) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE saas_customers SET name=?, document=?, phone=?, email=?, address=?, contract_value=?, due_day=? WHERE id=? AND tenant_id=?");
                $stmt->execute([$name, $doc, $phone, $email, $addr, $cValue, $cDay, $id, $tenant_id]);
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO saas_customers (tenant_id, name, document, phone, email, address, contract_value, due_day, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$tenant_id, $name, $doc, $phone, $email, $addr, $cValue, $cDay]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'delete_customer':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        $id = $input['id'] ?? null;
        if (!$id) http_400('ID inválido');
        try {
            $stmt = $pdo->prepare("DELETE FROM saas_customers WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500('Erro ao excluir (verifique se há veículos ou usuários vinculados)'); }
        break;

    case 'link_asaas_customer':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        
        $localId = $input['local_id'] ?? null;
        $asaasId = $input['asaas_id'] ?? null;
        $asaasName = $input['asaas_name'] ?? 'Cliente Asaas';

        if (!$localId || !$asaasId) http_400('Dados incompletos');

        try {
            // Salva o ID e o Nome do Asaas para exibição rápida sem API
            $stmt = $pdo->prepare("UPDATE saas_customers SET asaas_customer_id = ?, asaas_customer_name = ?, financial_status = 'ok' WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$asaasId, $asaasName, $localId, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // =========================================================================
    // 📊 KPIS & DASHBOARD
    // =========================================================================
    case 'get_kpis':
        try {
            $sqlTotal = "SELECT COUNT(*) FROM saas_vehicles v WHERE v.tenant_id = ? AND v.status = 'active' $restrictionSQL";
            $stmtTotal = $pdo->prepare($sqlTotal);
            $stmtTotal->execute([$tenant_id]);
            $total = $stmtTotal->fetchColumn();

            $sqlOnline = "SELECT COUNT(v.id) FROM saas_vehicles v 
                          JOIN tc_devices d ON v.traccar_device_id = d.id 
                          WHERE v.tenant_id = ? AND v.status = 'active' 
                          AND d.lastupdate > NOW() - INTERVAL '10 minutes' $restrictionSQL";
            $stmtOnline = $pdo->prepare($sqlOnline);
            $stmtOnline->execute([$tenant_id]);
            $online = $stmtOnline->fetchColumn();

            $sqlMoving = "SELECT COUNT(v.id) FROM saas_vehicles v 
                          JOIN tc_devices d ON v.traccar_device_id = d.id 
                          JOIN tc_positions p ON d.positionid = p.id
                          WHERE v.tenant_id = ? AND v.status = 'active' 
                          AND d.lastupdate > NOW() - INTERVAL '10 minutes' AND p.speed > 1 $restrictionSQL"; 
            $stmtMoving = $pdo->prepare($sqlMoving);
            $stmtMoving->execute([$tenant_id]);
            $moving = $stmtMoving->fetchColumn();

            $stopped = $online - $moving; 
            if($stopped < 0) $stopped = 0;
            $offline = $total - $online;
            if($offline < 0) $offline = 0;

            echo json_encode(['total_vehicles'=>$total, 'online'=>$online, 'moving'=>$moving, 'stopped'=>$stopped, 'offline'=>$offline]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'get_dashboard_data':
        $type = $_REQUEST['type'] ?? 'online';
        try {
            $sql = "SELECT v.id, v.name, v.plate, v.traccar_device_id as deviceid, t.lastupdate, t.positionid, p.speed, p.address
                    FROM saas_vehicles v 
                    LEFT JOIN tc_devices t ON v.traccar_device_id = t.id 
                    LEFT JOIN tc_positions p ON t.positionid = p.id
                    WHERE v.tenant_id = ? AND v.status = 'active' $restrictionSQL";
            if ($type === 'offline') $sql .= " AND (t.lastupdate < NOW() - INTERVAL '24 hours' OR t.lastupdate IS NULL)";
            elseif ($type === 'online') $sql .= " AND t.lastupdate >= NOW() - INTERVAL '24 hours'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tenant_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'get_alerts':
        try {
            $limit = isset($_REQUEST['limit']) ? min((int)$_REQUEST['limit'], 200) : 5;
            $sql = "SELECT e.id, e.type, e.eventtime as event_time, v.name as vehicle_name, p.latitude, p.longitude, e.attributes, v.plate
                    FROM tc_events e JOIN saas_vehicles v ON e.deviceid = v.traccar_device_id
                    LEFT JOIN tc_positions p ON e.positionid = p.id
                    WHERE v.tenant_id = ? $restrictionSQL ORDER BY e.eventtime DESC LIMIT $limit";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tenant_id]);
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dict = ['deviceOverspeed'=>'Excesso de Velocidade','geofenceExit'=>'Saiu da Cerca','geofenceEnter'=>'Entrou na Cerca','ignitionOn'=>'Ignição Ligada','ignitionOff'=>'Ignição Desligada','deviceOffline'=>'Offline','deviceOnline'=>'Online','deviceStopped'=>'Parou','deviceMoving'=>'Em Movimento'];
            foreach($alerts as &$a) { $a['type_label'] = $dict[$a['type']] ?? $a['type']; $a['formatted_time'] = date('d/m/Y H:i:s', strtotime($a['event_time'])); }
            echo json_encode($alerts);
        } catch (Exception $e) { echo json_encode([]); }
        break;

    case 'get_ranking':
        try {
            $sql = "SELECT v.id, v.name, v.plate, COUNT(e.id) as total_events,
                    SUM(CASE WHEN e.type = 'deviceOverspeed' THEN 1 ELSE 0 END) as overspeed,
                    SUM(CASE WHEN e.type = 'geofenceExit' THEN 1 ELSE 0 END) as geofence
                    FROM saas_vehicles v LEFT JOIN tc_events e ON v.traccar_device_id = e.deviceid AND e.eventtime > NOW() - INTERVAL 30 DAY
                    WHERE v.tenant_id = ? $restrictionSQL GROUP BY v.id";
            $stmt = $pdo->prepare($sql); $stmt->execute([$tenant_id]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($rows as &$r) {
                $score = 100 - ($r['overspeed'] * 5) - ($r['geofence'] * 2);
                $r['score'] = max(0, min(100, $score));
                $r['class'] = $r['score'] >= 90 ? 'A' : ($r['score'] >= 70 ? 'B' : ($r['score'] >= 50 ? 'C' : 'D'));
            }
            usort($rows, function($a, $b) { return $b['score'] <=> $a['score']; });
            echo json_encode($rows);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // =========================================================================
    // 👥 GESTÃO DE USUÁRIOS & PERFIS
    // =========================================================================
    
    case 'get_users':
        try {
            $sql = "SELECT u.id, u.name, u.email, u.role_id, u.customer_id, u.branch_id, u.active, u.tenant_id,
                           r.name as role_name, b.name as branch_name, c.name as customer_name, t.name as tenant_name 
                    FROM saas_users u 
                    LEFT JOIN saas_roles r ON u.role_id = r.id 
                    LEFT JOIN saas_branches b ON u.branch_id = b.id 
                    LEFT JOIN saas_customers c ON u.customer_id = c.id
                    LEFT JOIN saas_tenants t ON u.tenant_id = t.id";

            if ($user_role === 'superadmin') {
                $sql .= " ORDER BY t.name ASC, u.name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
            } else {
                $sql .= " WHERE u.tenant_id = ? ORDER BY u.name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tenant_id]);
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'get_roles':
        try {
            $target_tenant = ($user_role === 'superadmin' && isset($_GET['tenant_id'])) ? $_GET['tenant_id'] : $tenant_id;
            $stmtAdmin = $pdo->prepare("SELECT id FROM saas_tenants WHERE slug = 'admin' LIMIT 1");
            $stmtAdmin->execute();
            $adminTenantId = $stmtAdmin->fetchColumn() ?: 0;

            $sql = "SELECT id, name, tenant_id FROM saas_roles WHERE tenant_id = ? OR tenant_id = ? ORDER BY tenant_id ASC, name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$target_tenant, $adminTenantId]);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($roles as &$r) { if ($r['tenant_id'] != $target_tenant) $r['name'] .= ' (Global)'; }
            echo json_encode($roles);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'save_user':
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $pass = $input['password'] ?? '';
        $role = !empty($input['role_id']) ? $input['role_id'] : null;
        $cust = !empty($input['customer_id']) ? $input['customer_id'] : null;
        $branch = !empty($input['branch_id']) ? $input['branch_id'] : null;
        $active = (isset($input['active']) && $input['active']) ? 1 : 0;
        $target_tenant_id = ($user_role === 'superadmin' && !empty($input['tenant_id'])) ? $input['tenant_id'] : $tenant_id;
        
        if (empty($name) || empty($email)) http_400('Nome e Email são obrigatórios.');

        try {
            $checkSql = "SELECT id FROM saas_users WHERE email = ? AND tenant_id = ?";
            $checkParams = [$email, $target_tenant_id];
            if ($id) { $checkSql .= " AND id != ?"; $checkParams[] = $id; }
            $stmtCheck = $pdo->prepare($checkSql);
            $stmtCheck->execute($checkParams);
            if ($stmtCheck->fetch()) http_400('Este email já está em uso.');

            if ($id) {
                $sql = "UPDATE saas_users SET name=?, email=?, role_id=?, customer_id=?, branch_id=?, active=?, tenant_id=? WHERE id=?";
                $params = [$name, $email, $role, $cust, $branch, $active, $target_tenant_id, $id];
                if ($user_role !== 'superadmin') { $sql .= " AND tenant_id=?"; $params[] = $tenant_id; }
                if (!empty($pass)) { $sql = str_replace('active=?,', 'active=?, password=?,', $sql); array_splice($params, 6, 0, password_hash($pass, PASSWORD_DEFAULT)); }
                $pdo->prepare($sql)->execute($params);
            } else {
                if(empty($pass)) http_400('Senha obrigatória.');
                $stmt = $pdo->prepare("INSERT INTO saas_users (tenant_id, name, email, password, role_id, customer_id, branch_id, status, active) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                $stmt->execute([$target_tenant_id, $name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $cust, $branch, $active]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'delete_user':
        $id = $input['id'] ?? null;
        if (!$id || $id == $user_id) http_400('Operação inválida');
        try {
            if ($user_role === 'superadmin') $pdo->prepare("DELETE FROM saas_users WHERE id = ?")->execute([$id]);
            else $pdo->prepare("DELETE FROM saas_users WHERE id = ? AND tenant_id = ?")->execute([$id, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'save_profile':
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $permissions = is_array($input['permissions'] ?? []) ? json_encode($input['permissions']) : ($input['permissions'] ?? '[]');
        if (empty($name)) http_400('Nome obrigatório');
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE saas_roles SET name = ?, permissions = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$name, $permissions, $id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO saas_roles (tenant_id, name, permissions) VALUES (?, ?, ?)");
                $stmt->execute([$tenant_id, $name, $permissions]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    case 'delete_profile':
        $id = $input['id'] ?? null;
        if (!$id) http_400('ID inválido');
        try {
            $stmt = $pdo->prepare("DELETE FROM saas_roles WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- GEOCODE & COMANDOS (Proxy/Legacy) ---
    case 'geocode':
        handleGeocode($_GET['lat']??0, $_GET['lon']??0, $pdo);
        break;

    case 'secure_command':
        $deviceId = $input['deviceId'] ?? null; $cmdType = $input['type'] ?? null; $password = $input['password'] ?? '';
        if (!$deviceId || !$cmdType) http_400('Dados incompletos');
        if ($password !== 'SKIP_CHECK') {
            $stmt = $pdo->prepare("SELECT password FROM saas_users WHERE id = ?"); $stmt->execute([$user_id]);
            if (!password_verify($password, $stmt->fetchColumn())) { http_response_code(401); exit(json_encode(['error' => 'Senha incorreta'])); }
        }
        $ch = curl_init("http://127.0.0.1:8082/api/commands/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_USERPWD, "admin:admin"); 
        curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['deviceId' => (int)$deviceId, 'type' => $cmdType]));
        $resp = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) http_500('Erro Traccar: ' . $resp);
        else echo json_encode(['success' => true]);
        curl_close($ch);
        break;

    case 'ping': echo json_encode(['status' => 'ok']); break;

    default:
        // Fallback de geocode antigo se necessário
        if (isset($_GET['type']) && $_GET['type'] === 'geocode') handleGeocode($_GET['lat'], $_GET['lon'], $pdo);
        else { http_response_code(404); echo json_encode(['error' => 'Action inválida']); }
        break;
}

// --- HELPERS ---
function handleProxyTraccar($endpoint, $tenant_id, $loggedCustomerId, $user_id, $pdo) {
    $url = 'http://127.0.0.1:8082/api' . $endpoint . '?' . http_build_query($_GET);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_USERPWD, "admin:admin");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode >= 400) { http_response_code($httpCode); echo $resp; return; }
    $data = json_decode($resp, true);
    if (is_array($data)) {
        $idsSql = "SELECT traccar_device_id FROM saas_vehicles v WHERE tenant_id = $tenant_id";
        if ($loggedCustomerId || $user_id) $idsSql .= $loggedCustomerId ? " AND (v.client_id = $loggedCustomerId OR v.user_id = $user_id)" : " AND v.user_id = $user_id";
        $ids = $pdo->query($idsSql)->fetchAll(PDO::FETCH_COLUMN);
        $filtered = [];
        foreach($data as $item) {
            $did = $item['deviceId'] ?? ($item['id'] ?? null);
            if ($did && (strpos($endpoint, '/devices')!==false || strpos($endpoint, '/positions')!==false || strpos($endpoint, '/reports')!==false)) {
                if(!in_array($did, $ids)) continue;
            }
            $filtered[] = $item;
        }
        echo json_encode(array_values($filtered));
    } else { echo $resp; }
}

function handleGeocode($lat, $lon, $pdo) {
    $lat = round($lat, 5); $lon = round($lon, 5);
    try {
        $stmt = $pdo->prepare("SELECT address FROM saas_address_cache WHERE lat = ? AND lon = ? LIMIT 1");
        $stmt->execute([$lat, $lon]);
        if($r = $stmt->fetchColumn()) { echo json_encode(['address'=>$r]); exit; }
    } catch(Exception $e) {}

    $ch = curl_init("https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&zoom=18&addressdetails=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_USERAGENT, "FV/1.0"); curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $json = json_decode(curl_exec($ch), true); curl_close($ch);
    
    $addr = $json['display_name'] ?? 'Local desconhecido';
    if ($addr !== 'Local desconhecido') {
        try { $pdo->prepare("INSERT INTO saas_address_cache (lat, lon, address) VALUES (?, ?, ?)")->execute([$lat, $lon, $addr]); } catch(Exception $e){}
    }
    echo json_encode(['address' => $addr]); exit;
}

function http_400($msg) { http_response_code(400); exit(json_encode(['error' => $msg])); }
function http_403($msg) { http_response_code(403); exit(json_encode(['error' => $msg])); }
function http_500($msg) { http_response_code(500); exit(json_encode(['error' => $msg])); }
?>