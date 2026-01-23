<?php
namespace App\Core;

use App\Config\Database;

class Router {
    private $routes = [];

    /**
     * Registra uma nova rota de API manualmente.
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $path Caminho da rota (ex: 'api/users')
     * @param string $controller Nome da classe do Controller (ex: 'UserController')
     * @param string $action Nome do método no Controller (ex: 'index')
     */
    public function add($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * Processa a URL e decide o que carregar (API, View ou Admin Legado).
     */
    public function dispatch($uri, $method) {
        // 1. Normalização da URL
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        
        // Separação de segmentos para identificar Tenant
        $parts = explode('/', $path);
        $firstSegment = $parts[0] ?? '';

        // Lógica de Tenant Multi-cliente:
        // Se NÃO for 'api', o primeiro segmento é o slug do cliente (ex: /clienteA/dashboard)
        if ($firstSegment !== 'api') {
            array_shift($parts); // Remove o slug para processar a rota interna
            $internalUri = implode('/', $parts);
        } else {
            // Se for API, mantém o caminho completo (ex: api/auth/login)
            $internalUri = $path;
        }

        // Rota padrão (Home)
        if (empty($internalUri)) {
            $internalUri = 'dashboard';
        }

        // 2. Tenta processar Rotas de API (Controllers Registrados)
        foreach ($this->routes as $route) {
            if ($route['path'] === $internalUri && $route['method'] === $method) {
                $controllerClass = "App\\Controllers\\" . $route['controller'];
                $action = $route['action'];
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $action)) {
                        return $controller->$action();
                    }
                }
                // Erro 500 se o controller registrado não existir
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => "Erro interno: Controller $controllerClass não configurado."]);
                return;
            }
        }

        // 3. Tenta carregar uma View (Frontend ou Admin)
        // Sanitização básica para evitar Directory Traversal
        $internalUri = str_replace(['..', '//'], '', $internalUri);

        // Caminhos possíveis para o arquivo
        $viewPath = __DIR__ . '/../../views/' . $internalUri . '.php';
        $legacyPath = __DIR__ . '/../../' . $internalUri . '.php'; // Procura na raiz (para admin_*.php antigos)
        
        $fileToLoad = null;

        if (file_exists($viewPath)) {
            $fileToLoad = $viewPath;
        } elseif (strpos($internalUri, 'admin_') === 0 && file_exists($legacyPath)) {
            // Suporte para arquivos admin_*.php que ainda estão na raiz
            $fileToLoad = $legacyPath;
        }

        if ($fileToLoad) {
            // Injeta conexão global para views legadas que precisem ($pdo)
            global $pdo; 
            $pdo = Database::getConnection();
            
            // Lista de páginas que carregam SOZINHAS (sem sidebar/topbar)
            $standalonePages = ['login', 'recover', 'reset', '404'];

            if (in_array($internalUri, $standalonePages)) {
                require $fileToLoad;
            } else {
                // Páginas do Sistema: Carrega dentro do Layout Mestre
                $pageName = $internalUri; // Usado para título e active state no menu
                $childView = $fileToLoad; // Variável que o layout.php vai incluir
                
                require __DIR__ . '/../../views/layout.php';
            }
            return;
        }

        // 4. Rota não encontrada (404)
        http_response_code(404);
        
        if (strpos($internalUri, 'api/') === 0) {
            // Resposta JSON para API
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Endpoint não encontrado: ' . $internalUri]);
        } else {
            // Resposta Visual
            if (file_exists(__DIR__ . '/../../views/404.php')) {
                require __DIR__ . '/../../views/404.php';
            } else {
                // Fallback simples
                echo "<div style='font-family:sans-serif; text-align:center; padding:50px; color:#64748b;'>";
                echo "<h1 style='font-size:48px; margin-bottom:10px;'>404</h1>";
                echo "<p>Página não encontrada: <strong>" . htmlspecialchars($internalUri) . "</strong></p>";
                echo "<a href='javascript:history.back()' style='color:#3b82f6; text-decoration:none;'>Voltar</a>";
                echo "</div>";
            }
        }
    }
}
?>