<?php
// debug_tree.php - Diagnóstico Específico da Árvore
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Tree SGBras</title>
    <style>body{font-family:monospace;background:#1e1e1e;color:#0f0;padding:20px} .box{border:1px solid #444;padding:15px;margin-bottom:15px} b{color:white}</style>
</head>
<body>
    <h2>🕵️ Scanner de Árvore (LoadTree)</h2>
    <form method="POST">
        <input type="text" name="user" placeholder="Usuário" required style="padding:5px">
        <input type="text" name="pass" placeholder="Senha" required style="padding:5px">
        <button type="submit">ESCANEAR</button>
    </form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $baseUrl = 'https://a4.sgbras.com/StandardApiAction_';

    function api($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        return curl_exec($ch);
    }

    // 1. Login
    $json = json_decode(api($baseUrl . "login.action?account=$user&password=$pass"), true);
    if (!$json || $json['result'] != 0) {
        $json = json_decode(api($baseUrl . "login.action?account=$user&password=" . md5($pass)), true);
    }
    
    if (isset($json['jsession'])) {
        $jsession = $json['jsession'];
        echo "<p>✅ Token: $jsession</p>";

        // 2. Testar LoadTree com diferentes IDs de Raiz
        $roots = [-1, 0, 1];
        $found = false;

        foreach ($roots as $id) {
            echo "<div class='box'><h3>Tentando Raiz ID: $id</h3>";
            $url = $baseUrl . "loadTree.action?jsession=$jsession&id=$id&deviceType=1"; // deviceType=1 é comum
            $raw = api($url);
            $tree = json_decode($raw, true);

            // Mostra o começo do JSON para analisarmos a estrutura
            echo "<b>Resposta Crua (Primeiros 1000 chars):</b><br>";
            echo htmlspecialchars(substr($raw, 0, 1000));
            
            if (!empty($tree['infos'])) {
                echo "<br><br><b>✅ ESTRUTURA ENCONTRADA! (infos)</b>";
                echo "<br>Exemplo do primeiro item:<br>";
                echo "<pre>" . print_r($tree['infos'][0], true) . "</pre>";
                $found = true;
            } elseif (!empty($tree['nodes'])) {
                echo "<br><br><b>✅ ESTRUTURA ENCONTRADA! (nodes)</b>";
                print_r($tree['nodes'][0]);
                $found = true;
            } else {
                echo "<br>❌ Vazio ou formato desconhecido.";
            }
            echo "</div>";
        }

        // 3. Testar Busca por Nome (Search)
        echo "<div class='box'><h3>Tentativa: SearchVehicle (Vazio)</h3>";
        $rawSearch = api($baseUrl . "searchVehicle.action?jsession=$jsession&name=");
        echo htmlspecialchars(substr($rawSearch, 0, 1000));
        echo "</div>";

    } else {
        echo "❌ Falha Login";
    }
}
?>
</body>
</html>