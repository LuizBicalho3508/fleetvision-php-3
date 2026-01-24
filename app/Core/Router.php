<?php
namespace App\Core;

class Router {
    private $routes = [];

    // Adiciona rota com suporte a parâmetros opcionais
    public function add($method, $path, $controller, $action, $params = []) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'params' => $params
        ];
    }

    public function dispatch($uri, $method) {
        // 1. Limpeza da URL
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        
        // Remove slug do tenant se necessário
        $parts = explode('/', $path);
        $first = $parts[0] ?? '';
        
        // LISTA ATUALIZADA: Adicionado 'sys' para proteger as novas rotas
        $reserved = ['api', 'sys', 'logout', 'login', 'assets', 'uploads', 'favicon.ico', 'admin_teste'];
        
        // Se a primeira parte da URL NÃO for reservada, assumimos que é um Cliente
        // e removemos essa parte para achar a rota interna (ex: /cliente/dashboard -> dashboard)
        if (!empty($first) && !in_array($first, $reserved)) {
            array_shift($parts); 
            $internalUri = implode('/', $parts);
        } else {
            // Se for reservada (ex: /sys/dashboard), mantemos a URL completa
            $internalUri = $path;
        }

        if (empty($internalUri)) $internalUri = 'login';

        // 2. Procura a Rota na Lista
        foreach ($this->routes as $route) {
            if ($route['path'] === $internalUri && $route['method'] === $method) {
                
                $controllerClass = "App\\Controllers\\" . $route['controller'];
                $action = $route['action'];
                $params = $route['params'] ?? [];
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $action)) {
                        // Executa o Controller passando parâmetros
                        return call_user_func_array([$controller, $action], $params);
                    }
                }
                
                // Erro 500
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => "Controller/Action not found: $controllerClass -> $action"]);
                return;
            }
        }

        // 3. Rota não encontrada (404)
        http_response_code(404);
        
        // Resposta JSON para rotas de API/SYS
        if (strpos($internalUri, 'api/') === 0 || strpos($internalUri, 'sys/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => "Rota não encontrada: $internalUri"]);
        } else {
            // Resposta HTML para páginas
            echo "<h1>Erro 404</h1><p>Página não encontrada: " . htmlspecialchars($internalUri) . "</p>";
        }
    }
}