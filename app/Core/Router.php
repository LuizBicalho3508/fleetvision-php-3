<?php
namespace App\Core;

class Router {
    private $routes = [];
    
    // Arquivos que NUNCA podem ser acessados diretamente via Auto-View
    private $blockedViews = [
        'sidebar', 'layout', 'header', 'footer', 
        'includes/header', 'includes/footer', 
        '404', '500'
    ];

    public function add($method, $path, $controller, $action, $params = []) {
        $pathRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        $pathRegex = "#^" . trim($pathRegex, '/') . "$#";
        $this->routes[] = compact('method', 'pathRegex', 'controller', 'action', 'params');
    }

    public function dispatch($uri, $method) {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path === '') $path = 'dashboard'; 

        // 1. Tenta encontrar uma Rota Registrada
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pathRegex'], $path, $matches)) {
                array_shift($matches);
                $args = !empty($route['params']) ? $route['params'] : $matches;
                
                $controllerClass = "App\\Controllers\\" . $route['controller'];
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    
                    // CORREÇÃO AQUI: Trocamos method_exists por is_callable
                    // Isso permite que o __call do ViewController funcione para login, dashboard, etc.
                    if (is_callable([$controller, $route['action']])) {
                        return call_user_func_array([$controller, $route['action']], $args);
                    } else {
                        // Última tentativa: Se for o ViewController, tenta generic
                        if ($route['controller'] === 'ViewController') {
                           return $controller->render($route['action']);
                        }
                        $this->sendError(500, "Método '{$route['action']}' não encontrado em $controllerClass");
                    }
                } else {
                    $this->sendError(500, "Controller '$controllerClass' não encontrado.");
                }
                return;
            }
        }

        // 2. AUTO-VIEW (Fallback para arquivos soltos)
        if ($method === 'GET' && strpos($path, 'sys/') === false) {
            if (in_array($path, $this->blockedViews)) {
                $this->sendError(403, "Acesso negado.");
            }

            $viewPath = __DIR__ . '/../../views/' . $path . '.php';

            if (file_exists($viewPath)) {
                $isStandalone = in_array($path, ['login', 'recover', 'reset', 'landing']);
                
                if ($isStandalone) {
                    require $viewPath;
                } else {
                    // Passa pelo ViewController para garantir a segurança/permissão
                    $vc = new \App\Controllers\ViewController();
                    return $vc->render($path);
                }
                return;
            }
        }

        $this->sendError(404, "Página não encontrada: /$path");
    }

    private function sendError($code, $message) {
        http_response_code($code);
        if (strpos($_SERVER['REQUEST_URI'], '/sys/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $message]);
        } else {
            echo "<div style='font-family:sans-serif;text-align:center;padding:50px;'>
                    <h1>Erro $code</h1>
                    <p>$message</p>
                    <a href='/' style='color:blue;'>Voltar ao Início</a>
                  </div>";
        }
        exit;
    }
}