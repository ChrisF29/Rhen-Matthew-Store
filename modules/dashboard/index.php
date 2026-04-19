<?php
$db = pdo();

$stats = [
    'products' => 0,
    'low_stock' => 0,
    'daily_sales' => 0.0,
    'utang_balance' => 0.0,
    'pending_deliveries' => 0,
];

$lowStockProducts = [];
$recentSales = [];

try {
    $stats['products'] = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $stats['low_stock'] = (int) $db->query('SELECT COUNT(*) FROM products WHERE stock_quantity <= 10')->fetchColumn();
    $stats['daily_sales'] = (float) $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'paid'")->fetchColumn();
    $stats['utang_balance'] = (float) $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE status = 'pending'")->fetchColumn();
    $stats['pending_deliveries'] = (int) $db->query("SELECT COUNT(*) FROM deliveries WHERE status IN ('pending', 'in_transit')")->fetchColumn();

    $lowStockStatement = $db->query(
        'SELECT name, category, size, stock_quantity
         FROM products
         ORDER BY stock_quantity ASC, name ASC
         LIMIT 8'
    );
    $lowStockProducts = $lowStockStatement->fetchAll();

    $salesStatement = $db->query(
        'SELECT id, customer_name, total_amount, payment_type, created_at
         FROM sales
         ORDER BY created_at DESC
         LIMIT 6'
    );
    $recentSales = $salesStatement->fetchAll();
} catch (Throwable $exception) {
    // Keep dashboard rendering even if tables are not created yet.
}
?>
<section class="module-screen" data-module="dashboard">
    <div class="module-toolbar">
        <div>
            <h3>Operations Snapshot</h3>
            <p>Track current inventory health, sales movement, and delivery queue at a glance.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn btn-primary" href="index.php?module=sales">
                <i data-lucide="plus"></i>
                Record Sale
            </a>
            <a class="btn btn-ghost" href="index.php?module=inventory">
                <i data-lucide="arrow-left-right"></i>
                Stock Movement
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <article class="stat-card">
            <div>
                <p class="stat-label">Total Products</p>
                <h4><?= h((string) $stats['products']) ?></h4>
            </div>
            <i data-lucide="package"></i>
        </article>

        <article class="stat-card warning">
            <div>
                <p class="stat-label">Low Stock Items</p>
                <h4><?= h((string) $stats['low_stock']) ?></h4>
            </div>
            <i data-lucide="alert-triangle"></i>
        </article>

        <article class="stat-card success">
            <div>
                <p class="stat-label">Daily Paid Sales</p>
                <h4>PHP <?= h(number_format($stats['daily_sales'], 2)) ?></h4>
            </div>
            <i data-lucide="wallet"></i>
        </article>

        <article class="stat-card warning">
            <div>
                <p class="stat-label">Utang</p>
                <h4>PHP <?= h(number_format($stats['utang_balance'], 2)) ?></h4>
            </div>
            <i data-lucide="credit-card"></i>
        </article>

        <article class="stat-card info">
            <div>
                <p class="stat-label">Active Deliveries</p>
                <h4><?= h((string) $stats['pending_deliveries']) ?></h4>
            </div>
            <i data-lucide="truck"></i>
        </article>
    </div>

    <div class="split-grid">
        <section class="panel">
            <div class="panel-head">
                <h4>Low Stock Watchlist</h4>
                <span>Threshold: 10 units</span>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Stock</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($lowStockProducts) === 0): ?>
                        <tr><td colspan="4" class="empty-cell">No products yet. Add items in the Products module.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?= h((string) $product['name']) ?></td>
                                <td><?= h((string) $product['category']) ?></td>
                                <td><?= h((string) $product['size']) ?></td>
                                <td>
                                    <span class="stock-pill <?= (int) $product['stock_quantity'] <= 10 ? 'critical' : 'healthy' ?>">
                                        <?= h((string) $product['stock_quantity']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h4>Recent Sales</h4>
                <a href="index.php?module=sales">View all</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Sale #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($recentSales) === 0): ?>
                        <tr><td colspan="4" class="empty-cell">No sales recorded today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td>#<?= h((string) $sale['id']) ?></td>
                                <td><?= h((string) $sale['customer_name']) ?></td>
                                <td>PHP <?= h(number_format((float) $sale['total_amount'], 2)) ?></td>
                                <td><span class="status-chip"><?= h((string) strtoupper((string) $sale['payment_type'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
