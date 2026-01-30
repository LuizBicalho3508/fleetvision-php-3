<?php
namespace App\Services;

class SGBrasService {
    private $baseUrl = 'https://a4.sgbras.com/StandardApiAction_';
    private $mediaHost = 'http://a4.sgbras.com:6604'; 
    
    private $account;
    private $password;
    private $jsession = null;
    private $tenantSlug;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->tenantSlug = $_SESSION['tenant_slug'] ?? 'admin';
        $this->loadCredentials();
    }

    private function loadCredentials() {
        if (!class_exists('\App\Config\Database')) return;
        try {
            $pdo = \App\Config\Database::getConnection();
            $stmt = $pdo->prepare("SELECT sgbras_user, sgbras_pass FROM saas_tenants WHERE slug = ? LIMIT 1");
            $stmt->execute([$this->tenantSlug]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($data && !empty($data['sgbras_user'])) {
                $this->account = $data['sgbras_user'];
                $this->password = $data['sgbras_pass'];
            }
        } catch (\Exception $e) { }
    }

    public function hasCredentials() {
        return !empty($this->account) && !empty($this->password);
    }

    private function login() {
        if (!$this->hasCredentials()) return false;
        
        if (isset($_SESSION['sgbras_tokens'][$this->tenantSlug])) {
            $this->jsession = $_SESSION['sgbras_tokens'][$this->tenantSlug];
            return true;
        }

        $url = $this->baseUrl . "login.action?account=" . urlencode($this->account) . "&password=" . urlencode($this->password);
        $response = $this->request($url);
        
        if (isset($response['result']) && ($response['result'] == 21 || $response['result'] == 22)) {
            $url = $this->baseUrl . "login.action?account=" . urlencode($this->account) . "&password=" . md5($this->password);
            $response = $this->request($url);
        }

        if ($response && isset($response['result']) && $response['result'] == 0) {
            $this->jsession = $response['jsession'];
            $_SESSION['sgbras_tokens'][$this->tenantSlug] = $this->jsession;
            return true;
        }
        return false;
    }

    // --- VEÍCULOS ---
    public function getVehicles() {
        if (!$this->login()) return [];

        $allVehicles = [];
        $urlFlat = $this->baseUrl . "getVehicleList.action?jsession=" . $this->jsession . "&page=1&limit=3000";
        $resFlat = $this->request($urlFlat);
        
        if (isset($resFlat['result']) && $resFlat['result'] == 2) {
            unset($_SESSION['sgbras_tokens'][$this->tenantSlug]);
            if ($this->login()) return $this->getVehicles();
            return [];
        }

        if (!empty($resFlat['vehicles'])) return $this->cleanVehicles($resFlat['vehicles']);

        $url = $this->baseUrl . "queryUserVehicle.action?jsession=" . $this->jsession;
        $response = $this->request($url);
        if (!empty($response['vehicles'])) $this->parseVehicleList($response['vehicles'], $allVehicles);
        if (!empty($response['companys'])) $this->extractRecursive($response['companys'], $allVehicles);
        if (!empty($response['teams']))    $this->extractRecursive($response['teams'], $allVehicles);

        return $this->cleanVehicles($allVehicles);
    }

    // --- ALERTAS (SEM FILTRO) ---
    public function getAlarms($devIdno, $startTime, $endTime) {
        if (!$this->login()) return [];

        $url = $this->baseUrl . "queryAlarmDetail.action?jsession=" . $this->jsession;
        $url .= "&devIdno=" . urlencode($devIdno);
        $url .= "&begintime=" . urlencode($startTime);
        $url .= "&endtime=" . urlencode($endTime);
        $url .= "&toMap=1";
        // SEM FILTRO armType = TRAZ TUDO
        $url .= "&pageRecords=200&currentPage=1"; // 200 itens para não pesar demais

        $response = $this->request($url, 60); 
        $alarms = $response['alarms'] ?? [];
        
        foreach ($alarms as &$alarm) {
            $this->processAlarmData($alarm);
        }
        return $alarms;
    }

    public function getLiveStreamUrl($devIdno, $channel = 0) {
        if (!$this->login()) return ['success' => false, 'error' => 'Login Falhou'];
        $url = "{$this->mediaHost}/hls/1_{$devIdno}_{$channel}_1.m3u8?jsession={$this->jsession}";
        return ['success' => true, 'url' => $url];
    }

    public function getDeviceStatus($devIdno) {
        if (!$this->login()) return null;
        $url = $this->baseUrl . "getDeviceStatus.action?jsession=" . $this->jsession . "&devIdno=" . urlencode($devIdno) . "&toMap=1&geoaddress=1";
        $response = $this->request($url, 15);
        
        if (!empty($response['status'][0])) {
            $d = $response['status'][0];
            $lat = abs($d['mlat'] ?? 0) > 90 ? ($d['mlat']/1000000) : ($d['mlat'] ?? 0);
            $lng = abs($d['mlng'] ?? 0) > 180 ? ($d['mlng']/1000000) : ($d['mlng'] ?? 0);
            return ['lat'=>$lat, 'lng'=>$lng, 'speed'=>($d['sp']??0)/10, 'address'=>$d['ps']??'N/D', 'time'=>$d['gt']??''];
        }
        return null;
    }

    // --- HELPERS ---
    private function processAlarmData(&$alarm) {
        if (!empty($alarm['img'])) {
            $imgs = explode(';', $alarm['img']);
            $firstImg = $imgs[0];
            $fullUrl = str_starts_with($firstImg, 'http') ? $firstImg : 'https://a4.sgbras.com/' . ltrim(ltrim($firstImg, '.'), '/');
            $alarm['full_image'] = $fullUrl;
        } else {
            $alarm['full_image'] = null;
        }

        $typeId = intval($alarm['atp'] ?? ($alarm['armType'] ?? 0));
        
        // Mapeamento Estendido
        $types = [
            2 => 'Fadiga', 3 => 'Bocejo', 4 => 'Distração', 5 => 'Fumando', 
            6 => 'Celular', 49 => 'Colisão Frontal', 50 => 'Saída Faixa',
            51 => 'Pedestre', 1 => 'SOS', 11 => 'Velocidade',
            19 => 'Entrada Cerca', 20 => 'Saída Cerca', 27 => 'Roubo'
        ];

        $alarm['type_label'] = $types[$typeId] ?? ($alarm['desc'] ?? "Evento #$typeId");
        $alarm['type_class'] = isset($types[$typeId]) ? 'bg-red-600' : 'bg-gray-500';
        $alarm['time_str'] = $alarm['createtime'] ?? date('d/m H:i', ($alarm['stm'] ?? 0)/1000);
        $alarm['position'] = $alarm['sps'] ?? ($alarm['lc'] ?? 'Localização N/D');
    }

    private function cleanVehicles($list) {
        $c=[]; $ids=[];
        foreach($list as $v) {
            $id = null;
            if(!empty($v['dl']) && is_array($v['dl'])) $id = $v['dl'][0]['id'] ?? null;
            if(!$id) $id = $v['idno'] ?? ($v['nm'] ?? null);
            $name = $v['userKey'] ?? ($v['name'] ?? ($v['nm'] ?? $id));
            if($id && !in_array($id, $ids)) { $c[]=['idno'=>$id, 'userKey'=>$name]; $ids[]=$id; }
        }
        usort($c, function($a,$b){return strcmp($a['userKey'],$b['userKey']);});
        return $c;
    }

    private function parseVehicleList($l, &$b) { foreach($l as $v) $b[]=$v; }
    private function extractRecursive($n, &$b) {
        foreach($n as $i) {
            if(!empty($i['vehicles'])) $this->parseVehicleList($i['vehicles'], $b);
            if(!empty($i['companys'])) $this->extractRecursive($i['companys'], $b);
            if(!empty($i['teams'])) $this->extractRecursive($i['teams'], $b);
        }
    }

    private function request($url, $t=20) {
        $ch=curl_init($url); 
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>$t, CURLOPT_SSL_VERIFYPEER=>false]);
        $d=curl_exec($ch); curl_close($ch); return json_decode($d, true);
    }
}