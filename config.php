<?php
// config.php - Configurações Globais (Atualizado)

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '5432',
        'name' => 'traccar',
        'user' => 'traccar',
        'pass' => 'traccar' // Em produção, considere usar variáveis de ambiente (getenv)
    ],
    'app' => [
        'base_url' => 'http://localhost', // Ajuste para o domínio real em produção
        'env'      => 'development',      // 'development' ou 'production'
        'debug'    => true
    ],
    'traccar' => [
        'base_url' => 'http://127.0.0.1:8082/api',
        'user'     => 'admin', // Usuário admin do Traccar
        'pass'     => 'admin'  // Senha admin do Traccar
    ]
];