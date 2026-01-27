<?php
namespace App\Controllers;

use PDO;
use Exception;
use DateTime;
use DateTimeZone;

class JourneyController extends ApiController {

    public function index() {
        $viewName = 'jornada'; 
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/jornada.php';
        }
    }

    public function filter() {
        try {
            if (!isset($_SESSION['tenant_id'])) throw new Exception("Sessão expirada.");
            $tenantId = $_SESSION['tenant_id'];

            // Filtros (Se vier vazio, o padrão agora será tratado no Frontend, aqui aceitamos o que vier)
            $dateStart = $_GET['start'] ?? date('Y-m-d');
            $dateEnd   = $_GET['end']   ?? date('Y-m-d');
            $driverId  = $_GET['driver'] ?? null;

            $sql = "SELECT j.*, 
                           d.name as driver_name, d.document as driver_doc,
                           v.plate, v.icon,
                           EXTRACT(EPOCH FROM (COALESCE(j.end_time, NOW()) - j.start_time)) as duration_seconds
                    FROM saas_driver_journeys j
                    LEFT JOIN saas_drivers d ON j.driver_id = d.id
                    LEFT JOIN saas_vehicles v ON j.vehicle_id = v.id
                    WHERE j.tenant_id = ? 
                    AND j.start_time::date BETWEEN ? AND ?";
            
            $params = [$tenantId, $dateStart, $dateEnd];

            if (!empty($driverId)) {
                $sql .= " AND j.driver_id = ?";
                $params[] = $driverId;
            }

            $sql .= " ORDER BY j.start_time DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $processed_data = [];
            $totalSeconds = 0;
            $violations = 0;

            // Definição de Fusos
            $tzUTC   = new DateTimeZone('UTC'); // Banco geralmente salva em UTC
            $tzLocal = new DateTimeZone('America/Porto_Velho'); // Seu Fuso (RO)

            foreach ($raw_data as $row) {
                $seconds = (int) $row['duration_seconds'];
                $totalSeconds += $seconds;

                // Lei 13.103: > 5.5 horas (19800 segundos)
                $isViolation = ($seconds > 19800);
                if ($isViolation) $violations++;
                $row['is_13103_violation'] = $isViolation;
                
                // Formatação de Duração
                $h = floor($seconds / 3600);
                $m = floor(($seconds % 3600) / 60);
                $row['duration_formatted'] = sprintf("%02dh %02dm", $h, $m);

                // --- CORREÇÃO DE FUSO HORÁRIO ---
                // Start Time
                if ($row['start_time']) {
                    $dtStart = new DateTime($row['start_time'], $tzUTC); // Assume origem UTC
                    $dtStart->setTimezone($tzLocal); // Converte para RO
                    $row['start_time_iso'] = $dtStart->format('c'); // ISO para o JS processar fácil
                    $row['start_time_display'] = $dtStart->format('H:i');
                    $row['start_date_display'] = $dtStart->format('d/m/Y');
                }

                // End Time
                if ($row['end_time']) {
                    $dtEnd = new DateTime($row['end_time'], $tzUTC);
                    $dtEnd->setTimezone($tzLocal);
                    $row['end_time_iso'] = $dtEnd->format('c');
                    $row['end_time_display'] = $dtEnd->format('H:i');
                    $row['end_date_display'] = $dtEnd->format('d/m/Y');
                } else {
                    $row['end_time_display'] = '--:--';
                    $row['end_date_display'] = '-';
                }

                $processed_data[] = $row;
            }

            $totalH = floor($totalSeconds / 3600);
            $totalM = floor(($totalSeconds % 3600) / 60);

            $this->json([
                'data' => $processed_data,
                'summary' => [
                    'total_hours' => sprintf("%02d:%02d", $totalH, $totalM),
                    'total_trips' => count($processed_data),
                    'violations'  => $violations
                ]
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}