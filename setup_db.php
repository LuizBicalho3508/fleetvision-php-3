<?php
// db_explorer.php - Visualizador e Exportador de Banco de Dados
// ATENÇÃO: APAGUE ESTE ARQUIVO APÓS O USO POR SEGURANÇA

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. CONEXÃO ---
$configFile = __DIR__ . '/app/Config/Database.php';

if (file_exists($configFile)) {
    require_once $configFile;
    try {
        $pdo = \App\Config\Database::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dbName = $pdo->query("SELECT current_database()")->fetchColumn();
    } catch (Exception $e) {
        die("Erro ao conectar via Config: " . $e->getMessage());
    }
} else {
    // Fallback manual
    $host = 'localhost';
    $db   = 'traccar';
    $user = 'traccar';
    $pass = 'traccar'; 
    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
        $driver = 'pgsql';
        $dbName = $db;
    } catch (PDOException $e) {
        die("Erro de conexão manual: " . $e->getMessage());
    }
}

// --- 2. FUNÇÕES AUXILIARES ---
function getTables($pdo) {
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

function getColumns($pdo, $table) {
    $sql = "
        SELECT 
            c.column_name, 
            c.data_type, 
            c.character_maximum_length, 
            c.is_nullable, 
            c.column_default,
            (SELECT 'PK' FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name WHERE tc.constraint_type = 'PRIMARY KEY' AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name LIMIT 1) as is_pk,
            (SELECT 'FK' FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name WHERE tc.constraint_type = 'FOREIGN KEY' AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name LIMIT 1) as is_fk
        FROM information_schema.columns c
        WHERE c.table_name = '$table'
        ORDER BY c.ordinal_position
    ";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getIndexes($pdo, $table) {
    $sql = "SELECT indexname, indexdef FROM pg_indexes WHERE tablename = '$table' ORDER BY indexname";
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
        printf("%-20s %-15s %-10s %-20s %s\n", "COLUNA", "TIPO", "NULL", "DEFAULT", "CHAVE");
        foreach ($cols as $c) {
            $key = trim(($c['is_pk'] ?? '') . ' ' . ($c['is_fk'] ?? ''));
            $type = $c['data_type'] . ($c['character_maximum_length'] ? "({$c['character_maximum_length']})" : "");
            printf("%-20s %-15s %-10s %-20s %s\n", 
                substr($c['column_name'], 0, 20), 
                substr($type, 0, 15), 
                $c['is_nullable'], 
                substr($c['column_default'] ?? 'NULL', 0, 20), 
                $key
            );
        }
        
        // Índices
        $idxs = getIndexes($pdo, $t);
        if (!empty($idxs)) {
            echo "\nÍNDICES:\n";
            foreach ($idxs as $idx) {
                echo " * " . $idx['indexname'] . ": " . $idx['indexdef'] . "\n";
            }
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
                    // Limpa quebras de linha para ficar numa linha só no TXT
                    $cleanVal = str_replace(["\n", "\r"], " ", $val ?? 'NULL');
                    $line[] = mb_strimwidth($cleanVal, 0, 30, "...");
                }
                echo implode(" | ", $line) . "\n";
            }
        } else {
            echo "(Sem dados)\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n\n";
    }
    
    exit; // Para o script aqui para não imprimir o HTML no arquivo
}

// --- 4. INTERFACE HTML ---
$tables = getTables($pdo);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>DB Explorer - FleetVision</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pk { color: #d97706; font-weight: bold; }
        .fk { color: #2563eb; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="bg-slate-900 text-white p-4 fixed w-full top-0 z-50 shadow-md flex justify-between items-center">
        <h1 class="text-xl font-bold"><i class="fas fa-database mr-2"></i> DB Explorer (<?php echo strtoupper($driver); ?>)</h1>
        
        <div class="flex gap-4">
            <a href="?export=txt" target="_blank" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded shadow font-bold transition flex items-center gap-2">
                <i class="fas fa-file-alt"></i> Exportar Relatório .TXT
            </a>
            
            <div class="text-xs bg-red-600 px-3 py-2 rounded font-bold animate-pulse flex items-center">
                MODO DEBUG
            </div>
        </div>
    </div>

    <div class="flex pt-20 h-screen">
        <div class="w-64 bg-white shadow-lg overflow-y-auto h-full fixed left-0 border-r border-gray-200 hidden md:block pt-4">
            <div class="px-4 py-2 bg-gray-50 font-bold text-gray-600 border-b text-xs uppercase tracking-wider">Tabelas (<?php echo count($tables); ?>)</div>
            <ul class="text-sm">
                <?php foreach ($tables as $t): ?>
                    <li>
                        <a href="#table-<?php echo $t; ?>" class="block px-4 py-2 hover:bg-blue-50 hover:text-blue-600 border-b border-gray-100 transition truncate">
                            <i class="fas fa-table text-gray-400 mr-2"></i> <?php echo $t; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="flex-1 md:ml-64 p-6 overflow-y-auto pb-20">
            <?php foreach ($tables as $table): 
                $columns = getColumns($pdo, $table);
                $indexes = getIndexes($pdo, $table);
                $rows = getData($pdo, $table);
                $count = getCount($pdo, $table);
            ?>

            <div id="table-<?php echo $table; ?>" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-10 overflow-hidden">
                <div class="bg-slate-100 p-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-slate-800 font-mono"><?php echo $table; ?></h2>
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-200"><?php echo $count; ?> reg.</span>
                </div>

                <div class="p-6">
                    <h3 class="font-bold text-gray-700 mb-2 border-l-4 border-blue-500 pl-2 uppercase text-xs">Estrutura</h3>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-xs border-collapse border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="border p-2 text-left">Nome</th>
                                    <th class="border p-2 text-left">Tipo</th>
                                    <th class="border p-2 text-left">Nulo?</th>
                                    <th class="border p-2 text-left">Padrão</th>
                                    <th class="border p-2 text-left">Chaves</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columns as $col): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2 font-mono font-medium"><?php echo $col['column_name']; ?></td>
                                        <td class="border p-2 text-gray-600">
                                            <?php echo $col['data_type']; ?>
                                            <?php if($col['character_maximum_length']) echo "({$col['character_maximum_length']})"; ?>
                                        </td>
                                        <td class="border p-2 text-center">
                                            <?php echo ($col['is_nullable'] == 'YES') ? '<span class="text-green-500">Sim</span>' : '<span class="text-red-500">Não</span>'; ?>
                                        </td>
                                        <td class="border p-2 text-gray-500 text-xs truncate max-w-xs"><?php echo $col['column_default']; ?></td>
                                        <td class="border p-2">
                                            <?php 
                                            if ($col['is_pk']) echo '<span class="pk"><i class="fas fa-key"></i> PK</span> ';
                                            if ($col['is_fk']) echo '<span class="fk"><i class="fas fa-link"></i> FK</span>'; 
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($indexes)): ?>
                        <h3 class="font-bold text-gray-700 mb-2 border-l-4 border-purple-500 pl-2 uppercase text-xs">Índices</h3>
                        <div class="bg-gray-50 p-3 rounded mb-6 text-[10px] font-mono text-gray-600 border border-gray-200">
                            <?php foreach ($indexes as $idx): ?>
                                <div class="mb-1"><strong><?php echo $idx['indexname']; ?>:</strong> <?php echo $idx['indexdef']; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="font-bold text-gray-700 mb-2 border-l-4 border-green-500 pl-2 uppercase text-xs flex justify-between cursor-pointer hover:text-green-600" onclick="document.getElementById('data-<?php echo $table; ?>').classList.toggle('hidden')">
                        <span>Dados (Amostra) <i class="fas fa-chevron-down ml-1"></i></span>
                    </h3>
                    <div id="data-<?php echo $table; ?>" class="overflow-x-auto hidden">
                        <?php if (count($rows) > 0): ?>
                            <table class="min-w-full text-[10px] border-collapse border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <?php foreach (array_keys($rows[0]) as $header): ?>
                                            <th class="border p-2 text-left font-mono text-gray-600 whitespace-nowrap"><?php echo $header; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr class="hover:bg-yellow-50">
                                            <?php foreach ($row as $val): ?>
                                                <td class="border p-1 whitespace-nowrap max-w-xs overflow-hidden text-ellipsis" title="<?php echo htmlspecialchars($val ?? ''); ?>">
                                                    <?php echo htmlspecialchars(mb_strimwidth($val ?? 'NULL', 0, 40, "...")); ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="text-gray-400 italic p-2 text-xs">Tabela vazia.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>