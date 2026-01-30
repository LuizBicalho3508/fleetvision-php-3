<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'QUEM_SOU_EU' => [
        'ID Usuario' => $_SESSION['user_id'] ?? 'Nao Logado',
        'Nome' => $_SESSION['user_name'] ?? '-',
        'Tenant ID' => $_SESSION['tenant_id'] ?? '-',
        'Customer ID (Crucial)' => $_SESSION['customer_id'] ?? 'NULL (Isso eh bom para Admin)',
    ],
    'TESTE_LOGICA' => [
        'Eh Admin?' => (empty($_SESSION['customer_id']) ? 'SIM' : 'NAO'),
        'Filtraria por Cliente?' => (!empty($_SESSION['customer_id']) ? 'SIM, ID: ' . $_SESSION['customer_id'] : 'NAO, Mostra Tudo')
    ]
]);
?>