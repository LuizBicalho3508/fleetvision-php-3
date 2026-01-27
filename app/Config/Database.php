<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Carrega configurações
        $config = require __DIR__ . '/../../config.php';
        $db = $config['db'];

        try {
            $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['name']}";
            $this->pdo = new PDO($dsn, $db['user'], $db['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Importante para segurança
        } catch (PDOException $e) {
            // Retorna JSON válido em caso de erro para não quebrar o fetch do JS
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Falha na conexão com o Banco de Dados.']);
            exit;
        }
    }

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}