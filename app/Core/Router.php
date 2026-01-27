<?php
namespace App\Core;

class Router {
    private $routes = [];

    // Adiciona uma rota à lista
    public function add($method, $path, $controller, $action, $params = []) {
        $path = trim($path, '/');
        $this->routes[] = compact('method', 'path', 'controller', 'action', 'params');
    }

    // Executa a rota correspondente
    public function dispatch($uri, $method) {
        // Limpa a rota recebida (O index.php já tratou o slug, então aqui chega limpo)
        $path = trim($uri, '/');
        
        foreach ($this->routes as $route) {
            // Compara a rota registrada com a rota atual
            if ($route['path'] === $path && $route['method'] === $method) {
                
                $controllerClass = "App\\Controllers\\" . $route['controller'];
                
                // Verifica se o Controller existe
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    
                    // Verifica se o Método existe
                    if (method_exists($controller, $route['action'])) {
                        return call_user_func_array([$controller, $route['action']], $route['params']);
                    } else {
                        $this->sendError(500, "Método '{$route['action']}' não encontrado em $controllerClass");
                    }
                } else {
                    $this->sendError(500, "Controller '$controllerClass' não encontrado (Verifique o nome do arquivo)");
                }
                return;
            }
        }

        // Se chegou aqui, nenhuma rota coincidiu
        $this->sendError(404, "Endpoint não encontrado: $path");
    }

    // Função auxiliar de erro
    private function sendError($code, $message) {
        http_response_code($code);
        // Se for API ou System, retorna JSON
        if (strpos($_SERVER['REQUEST_URI'], '/sys/') !== false || strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
        } else {
            // Se for navegador, mostra mensagem simples
            echo "<div style='font-family:sans-serif; padding:2rem; text-align:center; color:#333;'>
                    <h1>Erro $code</h1>
                    <p>$message</p>
                    <small>Verifique o arquivo <b>index.php</b> e as rotas registradas.</small>
                  </div>";
        }
        exit;
    }
}