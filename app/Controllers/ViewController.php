<?php
namespace App\Controllers;

class ViewController {

    public function render($viewName) {
        $viewName = basename($viewName); // Segurança básica
        
        // Caminhos possíveis para a view
        $possiblePaths = [
            __DIR__ . '/../../views/' . $viewName . '.php',
            __DIR__ . '/../../' . $viewName . '.php'
        ];

        $viewPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $viewPath = $path;
                break;
            }
        }

        if (!$viewPath) {
            http_response_code(404);
            echo "Página não encontrada: " . htmlspecialchars($viewName);
            return;
        }

        // Lógica de Layout
        // Se for Login ou Recuperação de Senha, carrega SEM o layout padrão (tela cheia)
        if (in_array($viewName, ['login', 'recover', 'reset'])) {
            require $viewPath;
        } else {
            // Para todas as outras páginas, carrega DENTRO do layout
            // O layout vai usar a variável $viewPath para incluir o conteúdo
            global $slug; // Disponibiliza slug para o layout
            require __DIR__ . '/../../views/layout.php';
        }
    }
    
    // Métodos de Rota
    public function login() { $this->render('login'); }
    public function dashboard() { $this->render('dashboard'); }
    
    // Admin Views
    public function adminServer() { $this->render('admin_server'); }
    public function adminTenants() { $this->render('admin_usuarios_tenant'); }
    public function adminCrm() { $this->render('admin_crm'); }
    public function adminGestao() { $this->render('admin_gestao'); }
    
    // Genérico (Mapeia URL para arquivo PHP de mesmo nome)
    public function generic($page) {
        $this->render($page);
    }
}