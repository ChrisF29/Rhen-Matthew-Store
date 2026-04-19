<?php
$currentModule = $currentModule ?? 'dashboard';
$user = current_user() ?? [];

$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard'],
    'products' => ['label' => 'Products', 'icon' => 'package'],
    'inventory' => ['label' => 'Inventory', 'icon' => 'boxes'],
    'sales' => ['label' => 'Sales', 'icon' => 'receipt-text'],
    'customers' => ['label' => 'Customers', 'icon' => 'contact'],
    'deliveries' => ['label' => 'Deliveries', 'icon' => 'truck'],
    'drivers' => ['label' => 'Drivers', 'icon' => 'car'],
    'users' => ['label' => 'Users', 'icon' => 'users-round'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="brand-block">
        <div class="brand-mark">
            <i data-lucide="bottle-wine"></i>
        </div>
        <div>
            <h1><?= h(APP_NAME) ?></h1>
            <p>Softdrinks Distributor</p>
        </div>
    </div>

    <div class="user-chip">
        <i data-lucide="shield-check"></i>
        <span><?= h(strtoupper((string) ($user['role'] ?? 'staff'))) ?></span>
    </div>

    <nav class="nav-list" aria-label="Main navigation">
        <?php foreach ($navItems as $moduleKey => $item): ?>
            <a
                class="nav-link <?= $moduleKey === $currentModule ? 'active' : '' ?>"
                href="index.php?module=<?= h($moduleKey) ?>"
            >
                <i data-lucide="<?= h($item['icon']) ?>"></i>
                <span><?= h($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<div class="main-shell">
    <header class="topbar">
        <button class="icon-btn menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">
            <i data-lucide="menu"></i>
        </button>

        <div class="topbar-title">
            <h2><?= h($pageTitle) ?></h2>
            <small><?= h(date('D, d M Y')) ?></small>
        </div>

        <div class="topbar-actions">
            <span class="user-greeting">Hello, <?= h((string) ($user['name'] ?? 'Staff')) ?></span>
            <a class="btn btn-ghost btn-sm" href="logout.php">
                <i data-lucide="log-out"></i>
                Logout
            </a>
        </div>
    </header>

    <main class="module-main">
