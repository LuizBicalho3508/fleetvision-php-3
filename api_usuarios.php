<?php
// ARQUIVO: api_usuarios.php
// Responsável por Gerir Usuários e Aplicar Regras de Bloqueio Financeiro

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
$action     = $_REQUEST['action'] ?? '';
$input      = json_decode(file_get_contents('php://input'), true) ?? [];

// --- MIDDLEWARE DE BLOQUEIO FINANCEIRO ---
// Se quem está chamando a API não for admin, verifica se ELE PRÓPRIO está bloqueado
if ($user_role !== 'admin' && $user_role !== 'superadmin') {
    // Busca o status do cliente dono deste usuário
    $stmtCheck = $pdo->prepare("
        SELECT c.financial_status 
        FROM saas_users u
        JOIN saas_customers c ON u.customer_id = c.id
        WHERE u.id = ? AND u.tenant_id = ?
    ");
    $stmtCheck->execute([$user_id, $tenant_id]);
    $status = $stmtCheck->fetchColumn();

    if ($status === 'overdue') {
        http_response_code(403);
        exit(json_encode(['error' => 'ACESSO SUSPENSO. Contate o financeiro do seu fornecedor.']));
    }
}

switch ($action) {

    // --- LISTAR USUÁRIOS (COM STATUS DO CLIENTE) ---
    case 'get_users':
        try {
            // JOIN importante: Traz o status financeiro do Cliente (Pai)
            $sql = "SELECT u.id, u.name, u.email, u.active, u.role_id, u.customer_id, 
                           r.name as role_name, 
                           c.name as customer_name, 
                           c.financial_status as customer_fin_status
                    FROM saas_users u
                    LEFT JOIN saas_roles r ON u.role_id = r.id
                    LEFT JOIN saas_customers c ON u.customer_id = c.id
                    WHERE u.tenant_id = ?
                    ORDER BY u.name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tenant_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Refina os dados para o frontend
            foreach ($users as &$u) {
                // Se o cliente está 'overdue', o usuário está efetivamente bloqueado,
                // mesmo que u.active seja 1.
                $u['is_blocked_financial'] = ($u['customer_fin_status'] === 'overdue');
            }

            echo json_encode(['success' => true, 'data' => $users]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- SALVAR USUÁRIO ---
    case 'save_user':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = $input['role_id'] ?? null;
        $cust = $input['customer_id'] ?? null; // ID do Cliente vinculado
        $active = isset($input['active']) ? (int)$input['active'] : 1;
        $pass = $input['password'] ?? '';

        if (empty($name) || empty($email)) http_400('Nome e Email obrigatórios');

        try {
            // Verifica email duplicado
            $check = $pdo->prepare("SELECT id FROM saas_users WHERE email = ? AND tenant_id = ? " . ($id ? "AND id != ?" : ""));
            $params = [$email, $tenant_id];
            if ($id) $params[] = $id;
            $check->execute($params);
            if ($check->fetch()) http_400('Email já cadastrado.');

            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_users SET name=?, email=?, role_id=?, customer_id=?, active=? WHERE id=? AND tenant_id=?";
                $params = [$name, $email, $role, $cust, $active, $id, $tenant_id];
                
                if (!empty($pass)) {
                    $sql = str_replace('active=?', 'active=?, password=?', $sql);
                    array_splice($params, 5, 0, password_hash($pass, PASSWORD_DEFAULT));
                }
                $pdo->prepare($sql)->execute($params);
            } else {
                // INSERT
                if (empty($pass)) http_400('Senha obrigatória para novos usuários');
                $stmt = $pdo->prepare("INSERT INTO saas_users (tenant_id, name, email, password, role_id, customer_id, active, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$tenant_id, $name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $cust, $active]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    // --- EXCLUIR USUÁRIO ---
    case 'delete_user':
        if ($user_role !== 'admin' && $user_role !== 'superadmin') http_403('Sem permissão');
        $id = $input['id'] ?? null;
        if (!$id || $id == $user_id) http_400('Operação inválida');
        try {
            $pdo->prepare("DELETE FROM saas_users WHERE id = ? AND tenant_id = ?")->execute([$id, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;
        
    // --- LISTAR ROLES E CUSTOMERS (Para o Select do Modal) ---
    case 'get_form_data':
        try {
            $roles = $pdo->query("SELECT id, name FROM saas_roles WHERE tenant_id = $tenant_id")->fetchAll(PDO::FETCH_ASSOC);
            $custs = $pdo->query("SELECT id, name, financial_status FROM saas_customers WHERE tenant_id = $tenant_id ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'roles' => $roles, 'customers' => $custs]);
        } catch (Exception $e) { http_500($e->getMessage()); }
        break;

    default: http_404('Action inválida'); break;
}

// Helpers
function http_400($m) { http_response_code(400); exit(json_encode(['error'=>$m])); }
function http_403($m) { http_response_code(403); exit(json_encode(['error'=>$m])); }
function http_404($m) { http_response_code(404); exit(json_encode(['error'=>$m])); }
function http_500($m) { http_response_code(500); exit(json_encode(['error'=>$m])); }
?>