<?php
// Configurações Globais do Projeto
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '5432',
        'name' => 'traccar',
        'user' => 'traccar',
        'pass' => 'traccar' // Altere para a senha real
    ],
    'app' => [
        'base_url' => 'http://localhost', // ou seu dominio
        'debug' => true
    ]
];