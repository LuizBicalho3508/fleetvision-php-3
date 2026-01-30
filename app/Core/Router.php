<?php
namespace App\Core;

class Router {
    private $routes = [];
    
    // Arquivos que NUNCA podem ser acessados diretamente via Auto-View (Segurança)
    private $blockedViews = [
        'sidebar', 'layout', 'header', 'footer', 
        'includes/header', 'includes/footer', 
        '404', '500'
    ];

    /**
     * Adiciona uma rota à lista
     * @param string $method GET, POST, etc.
     * @param string $path Caminho URL (suporta {id})
     * @param string $controller Nome da Classe
     * @param string $action Nome do Método
     * @param array $params Parâmetros fixos opcionais (para compatibilidade com generic)
     */
    public function add($method, $path, $controller, $action, $params = []) {
        // Converte {param} em regex para capturar valores dinâmicos
        $pathRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        $pathRegex = "#^" . trim($pathRegex, '/') . "$#";
        
        // Armazena tudo, inclusive os parâmetros estáticos ($params)
        $this->routes[] = compact('method', 'pathRegex', 'controller', 'action', 'params');
    }

    // Executa a rota correspondente
    public function dispatch($uri, $method) {
        // 1. Limpeza da URL
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        
        if ($path === '') $path = 'dashboard'; // Rota padrão se vazio

        // -------------------------------------------------------
        // 2. Tenta encontrar uma Rota Registrada (Controllers)
        // -------------------------------------------------------
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pathRegex'], $path, $matches)) {
                
                array_shift($matches); // Remove o match completo da regex
                
                // LÓGICA HÍBRIDA (CORREÇÃO DO ERRO):
                // Se definimos parâmetros fixos no add() (ex: ['dashboard']), usamos eles.
                // Se não, usamos os parâmetros capturados na URL (ex: ID do usuário).
                $args = !empty($route['params']) ? $route['params'] : $matches;
                
                $controllerClass = "App\\Controllers\\" . $route['controller'];
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $route['action'])) {
                        // Passa os argumentos corretos para a função
                        return call_user_func_array([$controller, $route['action']], $args);
                    } else {
                        $this->sendError(500, "Método '{$route['action']}' não encontrado em $controllerClass");
                    }
                } else {
                    $this->sendError(500, "Controller '$controllerClass' não encontrado.");
                }
                return;
            }
        }

        // -------------------------------------------------------
        // 3. AUTO-VIEW: Se não achou Rota, tenta carregar View direto
        // -------------------------------------------------------
        // Útil para páginas simples que você esqueceu de registrar no index.php
        if ($method === 'GET' && strpos($path, 'sys/') === false) {
            
            if (in_array($path, $this->blockedViews)) {
                $this->sendError(403, "Acesso negado.");
            }

            // Tenta achar o arquivo na pasta views
            $viewPath = __DIR__ . '/../../views/' . $path . '.php';

            if (file_exists($viewPath)) {
                // IMPORTANTE: Para Auto-View funcionar igual ao generic(),
                // precisamos carregar o Layout se não for login/recover.
                
                // Verifica se é uma página "isolada" (sem menu)
                $isStandalone = in_array($path, ['login', 'recover', 'reset', 'landing']);
                
                if ($isStandalone) {
                    require $viewPath;
                } else {
                    // Simula o comportamento do ViewController::render
                    // A view layout.php deve usar a variável $viewPath ou incluir o arquivo
                    // Como seu layout.php provavelmente faz include baseada em path, vamos fazer um require direto
                    // Mas para manter o design, o ideal é passar pelo ViewController.
                    // Então, se caiu aqui, redirecionamos para o generic() dinamicamente:
                    
                    $vc = new \App\Controllers\ViewController();
                    return $vc->render($path);
                }
                return;
            }
        }

        // -------------------------------------------------------
        // 4. Erro 404
        // -------------------------------------------------------
        $this->sendError(404, "Página não encontrada: /$path");
    }

    private function sendError($code, $message) {
        http_response_code($code);
        if (strpos($_SERVER['REQUEST_URI'], '/sys/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $message]);
        } else {
            // Estilo visual simples para erro
            echo "<div style='font-family:sans-serif;text-align:center;padding:50px;color:#475569;'>
                    <h1 style='font-size:40px;margin-bottom:10px;'>Erro $code</h1>
                    <p>$message</p>
                    <a href='/' style='color:#2563eb;text-decoration:none;font-weight:bold;margin-top:20px;display:block;'>Voltar ao Início</a>
                  </div>";
        }
        exit;
    }
}