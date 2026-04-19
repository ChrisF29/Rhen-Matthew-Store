<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $saleId = (int) $_GET['id'];

            $saleStatement = $db->prepare('SELECT * FROM sales WHERE id = :id LIMIT 1');
            $saleStatement->execute(['id' => $saleId]);
            $sale = $saleStatement->fetch();

            if (!$sale) {
                json_response(['success' => false, 'message' => 'Sale not found.'], 404);
            }

            $itemStatement = $db->prepare(
                'SELECT si.id, si.product_id, p.name AS product_name, si.quantity, si.price, si.subtotal
                 FROM sales_items si
                 INNER JOIN products p ON p.id = si.product_id
                 WHERE si.sale_id = :sale_id'
            );
            $itemStatement->execute(['sale_id' => $saleId]);
            $sale['items'] = $itemStatement->fetchAll();

            json_response(['success' => true, 'data' => $sale]);
        }

        $statement = $db->query(
            'SELECT s.id,
                    s.customer_name,
                    s.total_amount,
                    s.payment_type,
                    s.status,
                    s.created_at,
                    COALESCE(SUM(si.quantity), 0) AS total_items
             FROM sales s
             LEFT JOIN sales_items si ON si.sale_id = s.id
             GROUP BY s.id
             ORDER BY s.created_at DESC, s.id DESC'
        );

        json_response(['success' => true, 'data' => $statement->fetchAll()]);
    }

    if ($method === 'POST') {
        $data = request_data();
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $paymentType = (string) ($data['payment_type'] ?? 'cash');
        $items = $data['items'] ?? [];

        if ($customerName === '') {
            json_response(['success' => false, 'message' => 'Customer name is required.'], 422);
        }

        if (!in_array($paymentType, ['cash', 'utang'], true)) {
            json_response(['success' => false, 'message' => 'Invalid payment type.'], 422);
        }

        if (!is_array($items) || count($items) === 0) {
            json_response(['success' => false, 'message' => 'At least one sale item is required.'], 422);
        }

        $normalizedItems = normalize_sale_items($items);

        $db->beginTransaction();

        $productStatement = $db->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = :id LIMIT 1 FOR UPDATE');
        $resolvedItems = [];
        $totalAmount = 0.0;

        foreach ($normalizedItems as $item) {
            $productStatement->execute(['id' => $item['product_id']]);
            $product = $productStatement->fetch();

            if (!$product) {
                $db->rollBack();
                json_response([
                    'success' => false,
                    'message' => 'One or more products in the sale no longer exist.',
                ], 404);
            }

            if ((int) $product['stock_quantity'] < $item['quantity']) {
                $db->rollBack();
                json_response([
                    'success' => false,
                    'message' => sprintf('Insufficient stock for %s.', (string) $product['name']),
                ], 422);
            }

            $linePrice = (float) $product['price'];
            $lineSubtotal = $linePrice * $item['quantity'];
            $totalAmount += $lineSubtotal;

            $resolvedItems[] = [
                'product_id' => (int) $product['id'],
                'quantity' => $item['quantity'],
                'price' => $linePrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        $status = $paymentType === 'cash' ? 'paid' : 'pending';

        $insertSale = $db->prepare(
            'INSERT INTO sales (customer_name, total_amount, payment_type, status, created_at)
             VALUES (:customer_name, :total_amount, :payment_type, :status, NOW())'
        );
        $insertSale->execute([
            'customer_name' => $customerName,
            'total_amount' => round($totalAmount, 2),
            'payment_type' => $paymentType,
            'status' => $status,
        ]);

        $saleId = (int) $db->lastInsertId();

        $insertItem = $db->prepare(
            'INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal)
             VALUES (:sale_id, :product_id, :quantity, :price, :subtotal)'
        );

        $insertInventory = $db->prepare(
            'INSERT INTO inventory (product_id, quantity, type, notes, created_at)
             VALUES (:product_id, :quantity, :type, :notes, NOW())'
        );

        foreach ($resolvedItems as $resolvedItem) {
            $insertItem->execute([
                'sale_id' => $saleId,
                'product_id' => $resolvedItem['product_id'],
                'quantity' => $resolvedItem['quantity'],
                'price' => $resolvedItem['price'],
                'subtotal' => $resolvedItem['subtotal'],
            ]);

            $stockUpdated = adjust_product_stock($db, $resolvedItem['product_id'], -$resolvedItem['quantity']);
            if (!$stockUpdated) {
                $db->rollBack();
                json_response([
                    'success' => false,
                    'message' => 'Failed to update stock. Sale was rolled back.',
                ], 422);
            }

            $insertInventory->execute([
                'product_id' => $resolvedItem['product_id'],
                'quantity' => -$resolvedItem['quantity'],
                'type' => 'out',
                'notes' => 'Sale #' . $saleId,
            ]);
        }

        $db->commit();

        json_response([
            'success' => true,
            'message' => 'Sale recorded successfully.',
            'id' => $saleId,
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid sale id is required.'], 422);
        }

        $fields = [];
        $params = ['id' => $id];

        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, ['pending', 'paid'], true)) {
                json_response(['success' => false, 'message' => 'Invalid sale status.'], 422);
            }

            $fields[] = 'status = :status';
            $params['status'] = $status;
        }

        if (isset($data['payment_type'])) {
            $paymentType = (string) $data['payment_type'];
            if (!in_array($paymentType, ['cash', 'utang'], true)) {
                json_response(['success' => false, 'message' => 'Invalid payment type.'], 422);
            }

            $fields[] = 'payment_type = :payment_type';
            $params['payment_type'] = $paymentType;
        }

        if (count($fields) === 0) {
            json_response(['success' => false, 'message' => 'No valid fields supplied for update.'], 422);
        }

        $statement = $db->prepare('UPDATE sales SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $statement->execute($params);

        if ($statement->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Sale not found or no changes made.'], 404);
        }

        json_response(['success' => true, 'message' => 'Sale updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid sale id is required.'], 422);
        }

        $db->beginTransaction();

        $saleStatement = $db->prepare('SELECT id FROM sales WHERE id = :id LIMIT 1 FOR UPDATE');
        $saleStatement->execute(['id' => $id]);

        if (!$saleStatement->fetch()) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Sale not found.'], 404);
        }

        $itemStatement = $db->prepare('SELECT product_id, quantity FROM sales_items WHERE sale_id = :sale_id');
        $itemStatement->execute(['sale_id' => $id]);
        $items = $itemStatement->fetchAll();

        $inventoryStatement = $db->prepare(
            'INSERT INTO inventory (product_id, quantity, type, notes, created_at)
             VALUES (:product_id, :quantity, :type, :notes, NOW())'
        );

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            $stockUpdated = adjust_product_stock($db, $productId, $quantity);
            if (!$stockUpdated) {
                $db->rollBack();
                json_response([
                    'success' => false,
                    'message' => 'Unable to restore stock while deleting this sale.',
                ], 422);
            }

            $inventoryStatement->execute([
                'product_id' => $productId,
                'quantity' => $quantity,
                'type' => 'in',
                'notes' => 'Sale reversal #' . $id,
            ]);
        }

        $deleteStatement = $db->prepare('DELETE FROM sales WHERE id = :id');
        $deleteStatement->execute(['id' => $id]);

        $db->commit();
        json_response(['success' => true, 'message' => 'Sale deleted and stock restored.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling sales request.',
    ], 500);
}

function normalize_sale_items(array $items): array
{
    $grouped = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productId = (int) ($item['product_id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        if (!isset($grouped[$productId])) {
            $grouped[$productId] = 0;
        }

        $grouped[$productId] += $quantity;
    }

    if (count($grouped) === 0) {
        json_response(['success' => false, 'message' => 'Please provide valid sale items.'], 422);
    }

    $normalized = [];
    foreach ($grouped as $productId => $quantity) {
        $normalized[] = [
            'product_id' => (int) $productId,
            'quantity' => (int) $quantity,
        ];
    }

    return $normalized;
}
