<?php
namespace App\Controllers;

class AdminServerController extends ApiController {

    public function __construct() {
        parent::__construct();
        if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
            $this->json(['error' => 'Acesso Negado'], 403);
            exit;
        }
    }

    public function index() {
        // Apenas renderiza a view
        // O JS da view chamará o método 'stats' abaixo
        $this->json(['message' => 'Use o endpoint /stats']);
    }

    public function stats() {
        // 1. Uptime
        $uptime = shell_exec('uptime -p');
        $uptime = str_replace('up ', '', $uptime);

        // 2. CPU Load
        $load = sys_getloadavg();
        $cpu = isset($load[0]) ? $load[0] * 100 : 0; // Aproximação simples

        // 3. RAM (Linux)
        $free = shell_exec('free -m');
        $free = (string)trim($free);
        $freeArr = explode("\n", $free);
        $mem = preg_split("/\s+/", $freeArr[1]);
        $ramTotal = $mem[1];
        $ramUsed = $mem[2];
        $ramPct = round(($ramUsed / $ramTotal) * 100, 1);

        // 4. Disco
        $diskTotal = disk_total_space("/");
        $diskFree = disk_free_space("/");
        $diskUsed = $diskTotal - $diskFree;
        $diskPct = round(($diskUsed / $diskTotal) * 100, 1);

        // 5. Serviços (Verifica portas)
        $traccar = $this->checkPort('localhost', 8082); // Porta padrão Traccar
        $postgres = $this->checkPort('localhost', 5432); // Porta padrão PG

        $this->json([
            'uptime' => trim($uptime),
            'cpu' => round($cpu, 1),
            'ram_used' => $ramUsed,
            'ram_total' => $ramTotal,
            'ram_pct' => $ramPct,
            'disk_used' => round($diskUsed / 1024 / 1024 / 1024, 1), // GB
            'disk_total' => round($diskTotal / 1024 / 1024 / 1024, 1), // GB
            'disk_pct' => $diskPct,
            'services' => [
                'traccar' => $traccar,
                'postgres' => $postgres
            ]
        ]);
    }

    private function checkPort($host, $port, $timeout = 1) {
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        return false;
    }
}