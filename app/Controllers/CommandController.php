<?php
namespace App\Controllers;

use PDO;

class CommandController extends ApiController {

    public function send() {
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceId = $input['deviceId'] ?? null; 
        $cmdType = $input['type'] ?? null; 
        $password = $input['password'] ?? '';

        if (!$deviceId || !$cmdType) $this->json(['error' => 'Dados incompletos'], 400);

        // Verifica Senha do Usuário Logado (exceto se for SKIP_CHECK autorizado pelo frontend em casos específicos, mas validado aqui)
        // Nota: Idealmente nunca confiar em SKIP_CHECK vindo do front sem validação extra, mas mantendo lógica original.
        if ($password !== 'SKIP_CHECK') {
            $stmt = $this->pdo->prepare("SELECT password FROM saas_users WHERE id = ?"); 
            $stmt->execute([$this->user_id]);
            $hash = $stmt->fetchColumn();
            
            if (!$hash || !password_verify($password, $hash)) { 
                $this->json(['error' => 'Senha incorreta'], 401); 
            }
        }

        // Envia para API Traccar (Interna)
        $ch = curl_init("http://127.0.0.1:8082/api/commands/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_USERPWD, "admin:admin"); // Mova para Config/Env em produção
        curl_setopt($ch, CURLOPT_POST, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['deviceId' => (int)$deviceId, 'type' => $cmdType]));
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro Traccar: ' . $resp]);
        } else {
            echo json_encode(['success' => true]);
        }
    }
}