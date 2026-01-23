<?php
namespace App\Controllers;

use PDO;

class DashboardController extends ApiController {

    public function getKpis() {
        $restr = $this->getRestrictionSQL('v');
        
        // Total
        $sqlTotal = "SELECT COUNT(*) FROM saas_vehicles v WHERE v.tenant_id = ? AND v.status = 'active' $restr";
        $stmt = $this->pdo->prepare($sqlTotal);
        $stmt->execute([$this->tenant_id]);
        $total = $stmt->fetchColumn();

        // Online (Requer join com tc_devices)
        $sqlOnline = "SELECT COUNT(v.id) FROM saas_vehicles v 
                      JOIN tc_devices d ON v.traccar_device_id = d.id 
                      WHERE v.tenant_id = ? AND v.status = 'active' 
                      AND d.lastupdate > NOW() - INTERVAL '10 minutes' $restr";
        $stmt = $this->pdo->prepare($sqlOnline);
        $stmt->execute([$this->tenant_id]);
        $online = $stmt->fetchColumn();

        // Moving
        $sqlMoving = "SELECT COUNT(v.id) FROM saas_vehicles v 
                      JOIN tc_devices d ON v.traccar_device_id = d.id 
                      JOIN tc_positions p ON d.positionid = p.id
                      WHERE v.tenant_id = ? AND v.status = 'active' 
                      AND d.lastupdate > NOW() - INTERVAL '10 minutes' AND p.speed > 1 $restr";
        $stmt = $this->pdo->prepare($sqlMoving);
        $stmt->execute([$this->tenant_id]);
        $moving = $stmt->fetchColumn();

        $stopped = max(0, $online - $moving);
        $offline = max(0, $total - $online);

        $this->json([
            'total_vehicles' => $total,
            'online' => $online,
            'moving' => $moving,
            'stopped' => $stopped,
            'offline' => $offline
        ]);
    }

    public function getData() {
        $type = $_GET['type'] ?? 'online';
        $restr = $this->getRestrictionSQL('v');
        
        $sql = "SELECT v.id, v.name, v.plate, v.traccar_device_id as deviceid, t.lastupdate, t.positionid, p.speed, p.address
                FROM saas_vehicles v 
                LEFT JOIN tc_devices t ON v.traccar_device_id = t.id 
                LEFT JOIN tc_positions p ON t.positionid = p.id
                WHERE v.tenant_id = ? AND v.status = 'active' $restr";

        if ($type === 'offline') $sql .= " AND (t.lastupdate < NOW() - INTERVAL '24 hours' OR t.lastupdate IS NULL)";
        elseif ($type === 'online') $sql .= " AND t.lastupdate >= NOW() - INTERVAL '24 hours'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->tenant_id]);
        $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAlerts() {
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 200) : 5;
        $restr = $this->getRestrictionSQL('v');

        $sql = "SELECT e.id, e.type, e.eventtime as event_time, v.name as vehicle_name, p.latitude, p.longitude, e.attributes, v.plate
                FROM tc_events e 
                JOIN saas_vehicles v ON e.deviceid = v.traccar_device_id
                LEFT JOIN tc_positions p ON e.positionid = p.id
                WHERE v.tenant_id = ? $restr 
                ORDER BY e.eventtime DESC LIMIT $limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->tenant_id]);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dict = [
            'deviceOverspeed'=>'Excesso de Velocidade', 'geofenceExit'=>'Saiu da Cerca',
            'geofenceEnter'=>'Entrou na Cerca', 'ignitionOn'=>'Ignição Ligada',
            'ignitionOff'=>'Ignição Desligada', 'deviceOffline'=>'Offline'
        ];

        foreach($alerts as &$a) { 
            $a['type_label'] = $dict[$a['type']] ?? $a['type']; 
            $a['formatted_time'] = date('d/m/Y H:i:s', strtotime($a['event_time'])); 
        }

        $this->json($alerts);
    }
}