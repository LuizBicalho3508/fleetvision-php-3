<?php
namespace App\Controllers;

use PDO;
use Exception;

class HistoryController extends ApiController {

    public function getRoute() {
        try {
            $deviceId = $_GET['device_id'] ?? null;
            $from = $_GET['from'] ?? null; // Formato: YYYY-MM-DD HH:mm:ss
            $to = $_GET['to'] ?? null;

            if (!$deviceId || !$from || !$to) {
                $this->json(['error' => 'Parâmetros inválidos. Informe device_id, from e to.'], 400);
            }

            // 1. Segurança: Verifica se o veículo pertence ao Tenant atual
            $restr = $this->getRestrictionSQL('v');
            $sqlCheck = "SELECT v.traccar_device_id 
                         FROM saas_vehicles v 
                         WHERE v.id = ? AND v.tenant_id = ? $restr LIMIT 1";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([$deviceId, $this->tenant_id]);
            
            $traccarId = $stmtCheck->fetchColumn();

            if (!$traccarId) {
                $this->json(['error' => 'Veículo não encontrado ou sem permissão.'], 403);
            }

            // 2. Busca as posições na tabela do Traccar (tc_positions)
            // Ajuste os campos conforme seu banco (ex: servertime vs devicetime)
            $sql = "SELECT id, latitude, longitude, speed, course, address, devicetime as time, attributes
                    FROM tc_positions 
                    WHERE deviceid = ? 
                    AND devicetime BETWEEN ? AND ?
                    ORDER BY devicetime ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$traccarId, $from, $to]);
            $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Cálculos básicos de resumo (Opcional, mas útil para exibir totais)
            $totalDistance = 0;
            $maxSpeed = 0;
            
            // Se tiver atributo totalDistance no JSON attributes, poderia usar. 
            // Aqui faremos cálculo simples se necessário ou retornamos apenas os pontos.
            
            $this->json([
                'success' => true,
                'count' => count($positions),
                'data' => $positions
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}