<?php
namespace App\Controllers;

use App\Services\SGBrasService;

class SGBrasController {
    
    public function index() {
        $service = new SGBrasService();
        global $sgbrasConfigured, $sgbrasVehicles;
        
        $sgbrasConfigured = $service->hasCredentials();
        $sgbrasVehicles = $sgbrasConfigured ? $service->getVehicles() : [];
        
        $vc = new ViewController();
        $vc->render('sgbras_monitor');
    }

    public function saveConfig() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        
        if (($_SESSION['user_role'] ?? '') !== 'admin' && ($_SESSION['user_role'] ?? '') !== 'superadmin') {
            echo json_encode(['success' => false, 'error' => 'Sem permissão']);
            exit;
        }

        $user = $_POST['sgbras_user'] ?? '';
        $pass = $_POST['sgbras_pass'] ?? '';
        $slug = $_SESSION['tenant_slug'] ?? 'admin';

        try {
            $pdo = \App\Config\Database::getConnection();
            $stmt = $pdo->prepare("UPDATE saas_tenants SET sgbras_user = ?, sgbras_pass = ? WHERE slug = ?");
            $stmt->execute([$user, $pass, $slug]);
            unset($_SESSION['sgbras_tokens'][$slug]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // --- PROXY "FORCE BRUTE" (10 TENTATIVAS) ---
    public function proxy() {
        // Libera sessão e remove limites de tempo
        session_write_close();
        set_time_limit(0);
        
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $urlParam = '';
        if (preg_match('/url=(.*)/', $queryString, $matches)) {
            $urlParam = urldecode($matches[1]);
        }

        if (empty($urlParam) || !filter_var($urlParam, FILTER_VALIDATE_URL) || strpos($urlParam, 'sgbras.com') === false) {
            http_response_code(400); die();
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0 Chrome/120.0.0.0 Safari/537.36']);

        $attempts = 0;
        $maxAttempts = 10; // AUMENTADO PARA 10s DE PERSISTÊNCIA
        $data = null;
        $httpCode = 0;
        $contentType = '';
        $effectiveUrl = '';

        do {
            curl_setopt($ch, CURLOPT_URL, $urlParam);
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            // Se for .ts e der 404, espera e tenta de novo
            if ($httpCode == 404 && strpos($urlParam, '.ts') !== false) {
                $attempts++;
                if ($attempts < $maxAttempts) {
                    sleep(1); 
                    continue; 
                }
            }
            break;
        } while ($attempts < $maxAttempts);

        curl_close($ch);

        if ($httpCode != 200 || empty($data)) {
            http_response_code(404);
            die();
        }

        header("Content-Type: $contentType");
        header("Access-Control-Allow-Origin: *");
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Reescrita de Playlist M3U8
        if (strpos($urlParam, '.m3u8') !== false || strpos($contentType, 'mpegurl') !== false) {
            $parsedUrl = parse_url($urlParam);
            $originalQuery = [];
            if (isset($parsedUrl['query'])) parse_str($parsedUrl['query'], $originalQuery);
            $jsession = $originalQuery['jsession'] ?? '';

            $baseUrl = dirname($effectiveUrl);
            $lines = explode("\n", $data);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if ($line[0] === '#') {
                    echo $line . "\n";
                } else {
                    $tsUrl = str_starts_with($line, 'http') ? $line : $baseUrl . '/' . $line;
                    
                    if (!empty($jsession) && strpos($tsUrl, 'jsession=') === false) {
                        $glue = strpos($tsUrl, '?') === false ? '?' : '&';
                        $tsUrl .= $glue . 'jsession=' . $jsession;
                    }

                    echo '/sys/sgbras/proxy?url=' . urlencode($tsUrl) . "\n";
                }
            }
        } else {
            echo $data;
        }
        exit;
    }

    public function poll() {
        session_write_close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    public function stream() {
        session_write_close();
        header('Content-Type: application/json');
        $device = $_POST['device'] ?? '';
        $channel = $_POST['channel'] ?? 0;
        
        $service = new SGBrasService();
        $res = $service->getLiveStreamUrl($device, $channel);
        
        if ($res['success']) {
            $proxiedUrl = '/sys/sgbras/proxy?url=' . urlencode($res['url']);
            echo json_encode(['success' => true, 'url' => $proxiedUrl]);
        } else {
            echo json_encode($res);
        }
    }

    public function search() {
        session_write_close();
        header('Content-Type: application/json');
        $service = new SGBrasService();
        $device = $_POST['device'] ?? '';
        $start = $_POST['start'] ?? date('Y-m-d 00:00:00');
        $end = $_POST['end'] ?? date('Y-m-d 23:59:59');

        $alarms = $service->getAlarms($device, $start, $end);
        
        // Remove filtro de imagem para debug (mostra tudo)
        // if (isset($_POST['only_images']) && $_POST['only_images'] === 'true') {
        //    $alarms = array_filter($alarms, fn($a) => !empty($a['full_image']));
        // }
        
        echo json_encode(['data' => array_values($alarms)]);
    }

    public function location() {
        session_write_close();
        header('Content-Type: application/json');
        $device = $_POST['device'] ?? '';
        if (empty($device)) { echo json_encode(['success'=>false]); exit; }
        $service = new SGBrasService();
        $data = $service->getDeviceStatus($device);
        if ($data) echo json_encode(['success' => true, 'data' => $data]);
        else echo json_encode(['success' => false, 'error' => 'Offline']);
    }
}