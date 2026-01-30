<?php
// db_explorer.php - Visualizador e Exportador de Banco de Dados (Blindado)

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. CONEXÃO ---
$configFile = __DIR__ . '/config.php';

if (file_exists($configFile)) {
    $config = require $configFile;
    $db = $config['db'];
    try {
        $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['name']}";
        $pdo = new PDO($dsn, $db['user'], $db['pass']);
        $dbName = $db['name'];
    } catch (Exception $e) {
        die("Erro de conexão (Config): " . $e->getMessage());
    }
} else {
    // Fallback Manual
    $host = '127.0.0.1';
    $dbName   = 'traccar';
    $user = 'traccar';
    $pass = 'traccar'; 
    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbName", $user, $pass);
    } catch (PDOException $e) {
        die("Erro de conexão (Manual): " . $e->getMessage());
    }
}

// --- 2. FUNÇÕES AUXILIARES ---
function getTables($pdo) {
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

function getColumns($pdo, $table) {
    $sql = "SELECT column_name, data_type, character_maximum_length, is_nullable, column_default 
            FROM information_schema.columns WHERE table_name = '$table' ORDER BY ordinal_position";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getData($pdo, $table) {
    try {
        return $pdo->query("SELECT * FROM \"$table\" LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function getCount($pdo, $table) {
    try {
        return $pdo->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
    } catch (Exception $e) { return 0; }
}

// *** FUNÇÃO DE CORREÇÃO SUPER BLINDADA ***
function safeString($val) {
    // 1. Se for NULL
    if ($val === null) return 'NULL';
    
    // 2. Se for Recurso (Stream do Postgres)
    if (is_resource($val)) {
        // Tenta ler o conteúdo
        $content = stream_get_contents($val);
        if ($content === false) return 'RESOURCE_ERROR';
        // Se a leitura funcionar, precisamos garantir que o ponteiro volte ao inicio se for usado de novo (opcional aqui)
        return $content;
    }

    // 3. Se for Array/Objeto
    if (is_array($val) || is_object($val)) {
        return json_encode($val);
    }

    // 4. Se for booleano
    if (is_bool($val)) return $val ? 'true' : 'false';

    // 5. Garantia final: Converte forçadamente para string
    return (string)$val;
}

// --- 3. LÓGICA DE EXPORTAÇÃO (TXT) ---
if (isset($_GET['export']) && $_GET['export'] == 'txt') {
    $filename = "db_dump_" . $dbName . "_" . date('Y-m-d_H-i') . ".txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "RELATÓRIO DE ESTRUTURA DO BANCO DE DADOS: " . strtoupper($dbName) . "\n";
    echo "Gerado em: " . date('d/m/Y H:i:s') . "\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $tables = getTables($pdo);
    
    foreach ($tables as $t) {
        $count = getCount($pdo, $t);
        echo "TABELA: $t ($count registros)\n";
        echo str_repeat("-", 30) . "\n";
        
        // Colunas
        echo "ESTRUTURA:\n";
        $cols = getColumns($pdo, $t);
        printf("%-20s %-15s %-10s %s\n", "COLUNA", "TIPO", "NULL", "DEFAULT");
        foreach ($cols as $c) {
            $type = $c['data_type'] . ($c['character_maximum_length'] ? "({$c['character_maximum_length']})" : "");
            printf("%-20s %-15s %-10s %s\n", 
                substr(safeString($c['column_name']), 0, 20), 
                substr(safeString($type), 0, 15), 
                safeString($c['is_nullable']), 
                substr(safeString($c['column_default']), 0, 20)
            );
        }
        
        // Dados
        echo "\nDADOS (Amostra 50):\n";
        $rows = getData($pdo, $t);
        if (!empty($rows)) {
            $headers = array_keys($rows[0]);
            echo implode(" | ", $headers) . "\n";
            foreach ($rows as $row) {
                $line = [];
                foreach ($row as $val) {
                    // *** USO DA NOVA FUNÇÃO BLINDADA ***
                    $valStr = safeString($val); 
                    
                    // Remove quebras de linha para o TXT ficar limpo
                    // Agora $valStr é GARANTIDAMENTE string, então str_replace não falha
                    $cleanVal = str_replace(["\n", "\r"], " ", $valStr);
                    $line[] = mb_strimwidth($cleanVal, 0, 30, "...");
                }
                echo implode(" | ", $line) . "\n";
            }
        } else {
            echo "(Sem dados)\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n\n";
    }
    exit;
}

// --- 4. INTERFACE HTML ---
$tables = getTables($pdo);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>DB Explorer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800"><i class="fas fa-database text-blue-600"></i> DB Explorer</h1>
            <a href="?export=txt" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition">
                <i class="fas fa-download mr-2"></i> Baixar Relatório TXT
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($tables as $t): $count = getCount($pdo, $t); ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-700"><?php echo $t; ?></h3>
                        <span class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-500 font-mono"><?php echo $count; ?> reg</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>