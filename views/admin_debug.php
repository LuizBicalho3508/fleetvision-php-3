<?php
// debug_db.php - Coloque na raiz do projeto
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega configurações (ajuste o caminho se necessário)
require_once 'app/Config/Database.php';
use App\Config\Database;

session_start();

echo "<div style='font-family:monospace; padding:20px;'>";
echo "<h1>🕵️ Raio-X do Sistema</h1>";

// 1. VERIFICA SESSÃO
echo "<h3>1. Quem está logado? (Sessão PHP)</h3>";
if (empty($_SESSION)) {
    echo "<span style='color:red; font-weight:bold;'>⚠️ NENHUMA SESSÃO ATIVA. Faça login novamente.</span>";
} else {
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    $tId = $_SESSION['tenant_id'] ?? 'INDEFINIDO';
    echo "<div>Tenant ID esperado na busca: <strong>$tId</strong></div>";
}

// 2. VERIFICA CONEXÃO E DADOS
echo "<h3>2. O que o PHP vê no Banco?</h3>";
try {
    $pdo = Database::getConnection();
    
    // Busca simples sem filtros complexos
    $sql = "SELECT id, plate, tenant_id, customer_id, status FROM saas_vehicles LIMIT 10";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) === 0) {
        echo "<h2 style='color:red'>❌ A tabela 'saas_vehicles' está VAZIA para o PHP!</h2>";
        echo "<p>Se você vê dados no terminal, o PHP está conectando no banco errado.</p>";
    } else {
        echo "<h2 style='color:green'>✅ Sucesso: " . count($rows) . " veículos encontrados no geral.</h2>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>
                <tr style='background:#eee;'><th>ID</th><th>Placa</th><th>Tenant ID</th><th>Customer ID</th><th>Status</th></tr>";
        
        $matchCount = 0;
        $tenantIdSessao = $_SESSION['tenant_id'] ?? 0;

        foreach ($rows as $r) {
            $match = ($r['tenant_id'] == $tenantIdSessao) ? "style='background:#dff0d8'" : "style='background:#f2dede'";
            if ($r['tenant_id'] == $tenantIdSessao) $matchCount++;
            
            echo "<tr $match>
                    <td>{$r['id']}</td>
                    <td>{$r['plate']}</td>
                    <td>{$r['tenant_id']} " . ($r['tenant_id'] == $tenantIdSessao ? '✅' : '❌') . "</td>
                    <td>{$r['customer_id']}</td>
                    <td>{$r['status']}</td>
                  </tr>";
        }
        echo "</table>";
        
        if ($matchCount == 0) {
            echo "<p style='color:red; font-weight:bold;'>PROBLEMA IDENTIFICADO: Existem veículos, mas NENHUM pertence ao seu Tenant ID ($tenantIdSessao).</p>";
        }
    }

} catch (Exception $e) {
    echo "<h2 style='color:red'>🔥 ERRO CRÍTICO DE CONEXÃO: " . $e->getMessage() . "</h2>";
}
echo "</div>";
?>