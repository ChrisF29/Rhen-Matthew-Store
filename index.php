<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

$allowedModules = [
    'dashboard' => 'Dashboard',
    'products' => 'Products',
    'inventory' => 'Inventory',
    'sales' => 'Sales',
    'ongoing_deliveries' => 'Ongoing Deliveries',
    'customers' => 'Customers',
    'deliveries' => 'Deliveries',
    'drivers' => 'Drivers',
    'users' => 'Users',
];

$currentModule = $_GET['module'] ?? 'dashboard';

if (!array_key_exists($currentModule, $allowedModules)) {
    $currentModule = 'dashboard';
}

$pageTitle = $allowedModules[$currentModule];
$modulePath = __DIR__ . '/modules/' . $currentModule . '/index.php';

if (!file_exists($modulePath)) {
    http_response_code(404);
    exit('Requested module could not be found.');
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
require $modulePath;
require __DIR__ . '/includes/footer.php';
