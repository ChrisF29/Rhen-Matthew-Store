<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $action = (string) ($_GET['action'] ?? '');
        if ($action === 'next_reference') {
            json_response([
                'success' => true,
                'data' => [
                    'reference_no' => generate_next_ongoing_reference($db),
                ],
            ]);
        }

        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Valid ongoing delivery id is required.'], 422);
            }

            $orderStatement = $db->prepare('SELECT * FROM ongoing_deliveries WHERE id = :id LIMIT 1');
            $orderStatement->execute(['id' => $id]);
            $order = $orderStatement->fetch();

            if (!$order) {
                json_response(['success' => false, 'message' => 'Ongoing delivery not found.'], 404);
            }

            $itemStatement = $db->prepare(
                'SELECT odi.id,
                        odi.product_id,
                        p.name AS product_name,
                        p.size,
                        p.price,
                        p.pieces_per_case,
                        odi.ordered_qty,
                        odi.order_unit,
                        odi.loaded_units,
                        odi.delivered_qty,
                        odi.delivered_units
                 FROM ongoing_delivery_items odi
                 INNER JOIN products p ON p.id = odi.product_id
                 WHERE odi.ongoing_delivery_id = :ongoing_delivery_id
                 ORDER BY odi.id ASC'
            );
            $itemStatement->execute(['ongoing_delivery_id' => $id]);
            $order['items'] = $itemStatement->fetchAll();

            json_response(['success' => true, 'data' => $order]);
        }

        $statement = $db->query(
            'SELECT od.id,
                    od.reference_no,
                    od.customer_name,
                    od.payment_type,
                    od.scheduled_date,
                    od.status,
                    od.sale_id,
                    od.created_at,
                    COALESCE(SUM(odi.loaded_units), 0) AS loaded_units,
                    COALESCE(SUM(odi.delivered_units), 0) AS delivered_units
             FROM ongoing_deliveries od
             LEFT JOIN ongoing_delivery_items odi ON odi.ongoing_delivery_id = od.id
             GROUP BY od.id
             ORDER BY FIELD(od.status, "in_transit", "pending_dispatch", "completed", "cancelled"), od.created_at DESC, od.id DESC'
        );

        json_response(['success' => true, 'data' => $statement->fetchAll()]);
    }

    if ($method === 'POST') {
        $payload = validate_ongoing_delivery_payload(request_data());

        $db->beginTransaction();

        $referenceNo = trim((string) ($payload['reference_no'] ?? ''));
        if ($referenceNo === '') {
            $referenceNo = generate_next_ongoing_reference($db);
        }

        $insertOrder = $db->prepare(
            'INSERT INTO ongoing_deliveries (
                reference_no,
                customer_name,
                payment_type,
                scheduled_date,
                status,
                notes,
                created_at,
                updated_at
             ) VALUES (
                :reference_no,
                :customer_name,
                :payment_type,
                :scheduled_date,
                "pending_dispatch",
                :notes,
                NOW(),
                NOW()
             )'
        );
        $insertOrder->execute([
            'reference_no' => $referenceNo,
            'customer_name' => $payload['customer_name'],
            'payment_type' => $payload['payment_type'],
            'scheduled_date' => $payload['scheduled_date'],
            'notes' => $payload['notes'],
        ]);

        $ongoingDeliveryId = (int) $db->lastInsertId();

        $productStatement = $db->prepare(
            'SELECT id, pieces_per_case
             FROM products
             WHERE id = :id
             LIMIT 1'
        );

        $insertItem = $db->prepare(
            'INSERT INTO ongoing_delivery_items (
                ongoing_delivery_id,
                product_id,
                ordered_qty,
                order_unit,
                loaded_units,
                delivered_qty,
                delivered_units,
                created_at
             ) VALUES (
                :ongoing_delivery_id,
                :product_id,
                :ordered_qty,
                :order_unit,
                :loaded_units,
                0,
                0,
                NOW()
             )'
        );

        foreach ($payload['items'] as $item) {
            $productStatement->execute(['id' => $item['product_id']]);
            $product = $productStatement->fetch();

            if (!$product) {
                $db->rollBack();
                json_response(['success' => false, 'message' => 'One or more products no longer exist.'], 404);
            }

            $piecesPerCase = (int) ($product['pieces_per_case'] ?? 24);
            $loadedUnits = calculate_base_units((int) $item['ordered_qty'], (string) $item['order_unit'], $piecesPerCase);

            $insertItem->execute([
                'ongoing_delivery_id' => $ongoingDeliveryId,
                'product_id' => $item['product_id'],
                'ordered_qty' => $item['ordered_qty'],
                'order_unit' => $item['order_unit'],
                'loaded_units' => $loadedUnits,
            ]);
        }

        ensure_customer_exists($db, $payload['customer_name']);

        $db->commit();

        json_response([
            'success' => true,
            'message' => 'Ongoing delivery order created successfully.',
            'id' => $ongoingDeliveryId,
            'reference_no' => $referenceNo,
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        $action = (string) ($data['action'] ?? '');

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid ongoing delivery id is required.'], 422);
        }

        if (!in_array($action, ['dispatch', 'cancel', 'complete'], true)) {
            json_response(['success' => false, 'message' => 'Invalid ongoing delivery action.'], 422);
        }

        $db->beginTransaction();

        $order = lock_ongoing_delivery($db, $id);
        if (!$order) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Ongoing delivery not found.'], 404);
        }

        if ($action === 'dispatch') {
            dispatch_ongoing_delivery($db, $order);
            $db->commit();
            json_response(['success' => true, 'message' => 'Delivery dispatched. Stock moved to transit.']);
        }

        if ($action === 'cancel') {
            cancel_ongoing_delivery($db, $order);
            $db->commit();
            json_response(['success' => true, 'message' => 'Ongoing delivery cancelled successfully.']);
        }

        $result = complete_ongoing_delivery($db, $order, $data);
        $db->commit();

        json_response([
            'success' => true,
            'message' => $result['message'],
            'sale_id' => $result['sale_id'] ?? null,
        ]);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
        json_response([
            'success' => false,
            'message' => 'Reference number already exists. Please refresh and try again.',
        ], 409);
    }

    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling ongoing deliveries.',
    ], 500);
}

function validate_ongoing_delivery_payload(array $data): array
{
    $referenceNo = trim((string) ($data['reference_no'] ?? ''));
    $customerName = trim((string) ($data['customer_name'] ?? ''));
    $paymentType = (string) ($data['payment_type'] ?? 'cash');
    $scheduledDate = (string) ($data['scheduled_date'] ?? '');
    $notes = trim((string) ($data['notes'] ?? ''));
    $items = $data['items'] ?? [];

    if ($customerName === '' || $scheduledDate === '') {
        json_response(['success' => false, 'message' => 'Customer name and scheduled date are required.'], 422);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
        json_response(['success' => false, 'message' => 'Scheduled date must be in YYYY-MM-DD format.'], 422);
    }

    if (!in_array($paymentType, ['cash', 'utang'], true)) {
        json_response(['success' => false, 'message' => 'Invalid payment type.'], 422);
    }

    if (!is_array($items) || count($items) === 0) {
        json_response(['success' => false, 'message' => 'At least one order item is required.'], 422);
    }

    return [
        'reference_no' => $referenceNo,
        'customer_name' => $customerName,
        'payment_type' => $paymentType,
        'scheduled_date' => $scheduledDate,
        'notes' => $notes !== '' ? $notes : null,
        'items' => normalize_ongoing_items($items),
    ];
}

function normalize_ongoing_items(array $items): array
{
    $grouped = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productId = (int) ($item['product_id'] ?? 0);
        $orderedQty = (int) ($item['ordered_qty'] ?? 0);
        $orderUnit = (string) ($item['order_unit'] ?? 'piece');

        if (!in_array($orderUnit, ['piece', 'case', 'half_case', 'quarter_case'], true)) {
            json_response(['success' => false, 'message' => 'Invalid order unit in ongoing delivery items.'], 422);
        }

        if ($productId <= 0 || $orderedQty <= 0) {
            continue;
        }

        $groupKey = $productId . '|' . $orderUnit;

        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [
                'product_id' => $productId,
                'ordered_qty' => 0,
                'order_unit' => $orderUnit,
            ];
        }

        $grouped[$groupKey]['ordered_qty'] += $orderedQty;
    }

    if (count($grouped) === 0) {
        json_response(['success' => false, 'message' => 'Please provide valid ongoing delivery items.'], 422);
    }

    return array_values($grouped);
}

function dispatch_ongoing_delivery(PDO $db, array $order): void
{
    if ((string) $order['status'] !== 'pending_dispatch') {
        json_response(['success' => false, 'message' => 'Only pending dispatch orders can be dispatched.'], 422);
    }

    $requiredStatement = $db->prepare(
        'SELECT product_id, SUM(loaded_units) AS required_units
         FROM ongoing_delivery_items
         WHERE ongoing_delivery_id = :ongoing_delivery_id
         GROUP BY product_id'
    );
    $requiredStatement->execute(['ongoing_delivery_id' => (int) $order['id']]);
    $requiredRows = $requiredStatement->fetchAll();

    if (count($requiredRows) === 0) {
        json_response(['success' => false, 'message' => 'Cannot dispatch an order without items.'], 422);
    }

    $productLock = $db->prepare(
        'SELECT id, name, stock_quantity
         FROM products
         WHERE id = :id
         LIMIT 1 FOR UPDATE'
    );

    foreach ($requiredRows as $row) {
        $productId = (int) $row['product_id'];
        $requiredUnits = (int) $row['required_units'];

        $productLock->execute(['id' => $productId]);
        $product = $productLock->fetch();

        if (!$product) {
            json_response(['success' => false, 'message' => 'One or more products no longer exist.'], 404);
        }

        $availableStock = (int) ($product['stock_quantity'] ?? 0);
        if ($availableStock < $requiredUnits) {
            json_response([
                'success' => false,
                'message' => sprintf('Insufficient stock to dispatch %s.', (string) $product['name']),
            ], 422);
        }
    }

    $insertInventory = $db->prepare(
        'INSERT INTO inventory (product_id, quantity, type, notes, created_at)
         VALUES (:product_id, :quantity, :type, :notes, NOW())'
    );

    foreach ($requiredRows as $row) {
        $productId = (int) $row['product_id'];
        $requiredUnits = (int) $row['required_units'];

        $stockUpdated = adjust_product_stock($db, $productId, -$requiredUnits);
        if (!$stockUpdated) {
            json_response(['success' => false, 'message' => 'Failed to reserve stock for dispatch.'], 422);
        }

        $insertInventory->execute([
            'product_id' => $productId,
            'quantity' => -$requiredUnits,
            'type' => 'out',
            'notes' => 'Dispatch ' . (string) $order['reference_no'],
        ]);
    }

    $update = $db->prepare(
        'UPDATE ongoing_deliveries
         SET status = "in_transit",
             dispatched_at = NOW(),
             updated_at = NOW()
         WHERE id = :id'
    );
    $update->execute(['id' => (int) $order['id']]);
}

function cancel_ongoing_delivery(PDO $db, array $order): void
{
    $status = (string) ($order['status'] ?? '');

    if (in_array($status, ['completed', 'cancelled'], true)) {
        json_response(['success' => false, 'message' => 'Completed or cancelled orders cannot be cancelled again.'], 422);
    }

    if ($status === 'in_transit') {
        $returnStatement = $db->prepare(
            'SELECT product_id, SUM(loaded_units - delivered_units) AS return_units
             FROM ongoing_delivery_items
             WHERE ongoing_delivery_id = :ongoing_delivery_id
             GROUP BY product_id'
        );
        $returnStatement->execute(['ongoing_delivery_id' => (int) $order['id']]);
        $returnRows = $returnStatement->fetchAll();

        $insertInventory = $db->prepare(
            'INSERT INTO inventory (product_id, quantity, type, notes, created_at)
             VALUES (:product_id, :quantity, :type, :notes, NOW())'
        );

        foreach ($returnRows as $row) {
            $productId = (int) $row['product_id'];
            $returnUnits = (int) $row['return_units'];

            if ($returnUnits <= 0) {
                continue;
            }

            $stockUpdated = adjust_product_stock($db, $productId, $returnUnits);
            if (!$stockUpdated) {
                json_response(['success' => false, 'message' => 'Failed to return stock while cancelling order.'], 422);
            }

            $insertInventory->execute([
                'product_id' => $productId,
                'quantity' => $returnUnits,
                'type' => 'in',
                'notes' => 'Cancelled ' . (string) $order['reference_no'],
            ]);
        }
    }

    $update = $db->prepare(
        'UPDATE ongoing_deliveries
         SET status = "cancelled",
             cancelled_at = NOW(),
             updated_at = NOW()
         WHERE id = :id'
    );
    $update->execute(['id' => (int) $order['id']]);
}

function complete_ongoing_delivery(PDO $db, array $order, array $data): array
{
    if ((string) $order['status'] !== 'in_transit') {
        json_response(['success' => false, 'message' => 'Only in-transit orders can be finalized.'], 422);
    }

    $itemsInput = $data['items'] ?? [];
    if (!is_array($itemsInput)) {
        json_response(['success' => false, 'message' => 'Invalid completion item payload.'], 422);
    }

    $deliveredQtyByItem = [];
    foreach ($itemsInput as $itemInput) {
        if (!is_array($itemInput)) {
            continue;
        }

        $itemId = (int) ($itemInput['item_id'] ?? 0);
        $deliveredQty = (int) ($itemInput['delivered_qty'] ?? 0);

        if ($itemId > 0) {
            $deliveredQtyByItem[$itemId] = max(0, $deliveredQty);
        }
    }

    $itemStatement = $db->prepare(
        'SELECT odi.id,
                odi.product_id,
                odi.ordered_qty,
                odi.order_unit,
                odi.loaded_units,
                p.price,
                p.pieces_per_case,
                p.name AS product_name
         FROM ongoing_delivery_items odi
         INNER JOIN products p ON p.id = odi.product_id
         WHERE odi.ongoing_delivery_id = :ongoing_delivery_id
         ORDER BY odi.id ASC
         FOR UPDATE'
    );
    $itemStatement->execute(['ongoing_delivery_id' => (int) $order['id']]);
    $items = $itemStatement->fetchAll();

    if (count($items) === 0) {
        json_response(['success' => false, 'message' => 'Order has no items to finalize.'], 422);
    }

    $updateItem = $db->prepare(
        'UPDATE ongoing_delivery_items
         SET delivered_qty = :delivered_qty,
             delivered_units = :delivered_units
         WHERE id = :id'
    );

    $saleRows = [];
    $backloadByProduct = [];
    $totalAmount = 0.0;

    foreach ($items as $item) {
        $itemId = (int) $item['id'];
        $orderedQty = (int) $item['ordered_qty'];
        $orderUnit = (string) $item['order_unit'];
        $loadedUnits = (int) $item['loaded_units'];
        $piecesPerCase = (int) ($item['pieces_per_case'] ?? 24);

        $deliveredQty = array_key_exists($itemId, $deliveredQtyByItem)
            ? (int) $deliveredQtyByItem[$itemId]
            : $orderedQty;

        if ($deliveredQty < 0 || $deliveredQty > $orderedQty) {
            json_response([
                'success' => false,
                'message' => sprintf('Delivered quantity for %s is invalid.', (string) $item['product_name']),
            ], 422);
        }

        $deliveredUnits = 0;
        if ($deliveredQty > 0) {
            $deliveredUnits = calculate_base_units($deliveredQty, $orderUnit, $piecesPerCase);
        }

        if ($deliveredUnits > $loadedUnits) {
            json_response([
                'success' => false,
                'message' => sprintf('Delivered quantity exceeds loaded stock for %s.', (string) $item['product_name']),
            ], 422);
        }

        $updateItem->execute([
            'id' => $itemId,
            'delivered_qty' => $deliveredQty,
            'delivered_units' => $deliveredUnits,
        ]);

        if (!isset($backloadByProduct[(int) $item['product_id']])) {
            $backloadByProduct[(int) $item['product_id']] = 0;
        }
        $backloadByProduct[(int) $item['product_id']] += ($loadedUnits - $deliveredUnits);

        if ($deliveredUnits <= 0) {
            continue;
        }

        $price = (float) $item['price'];
        $subtotal = $price * $deliveredUnits;
        $totalAmount += $subtotal;

        $saleRows[] = [
            'product_id' => (int) $item['product_id'],
            'ordered_qty' => $deliveredQty,
            'order_unit' => $orderUnit,
            'base_units' => $deliveredUnits,
            'price' => $price,
            'subtotal' => $subtotal,
        ];
    }

    $insertInventory = $db->prepare(
        'INSERT INTO inventory (product_id, quantity, type, notes, created_at)
         VALUES (:product_id, :quantity, :type, :notes, NOW())'
    );

    foreach ($backloadByProduct as $productId => $backloadUnits) {
        if ($backloadUnits <= 0) {
            continue;
        }

        $stockUpdated = adjust_product_stock($db, $productId, $backloadUnits);
        if (!$stockUpdated) {
            json_response(['success' => false, 'message' => 'Failed to return backload stock.'], 422);
        }

        $insertInventory->execute([
            'product_id' => $productId,
            'quantity' => $backloadUnits,
            'type' => 'in',
            'notes' => 'Backload ' . (string) $order['reference_no'],
        ]);
    }

    if (count($saleRows) === 0) {
        $cancelledUpdate = $db->prepare(
            'UPDATE ongoing_deliveries
             SET status = "cancelled",
                 cancelled_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $cancelledUpdate->execute(['id' => (int) $order['id']]);

        return [
            'message' => 'No items were delivered. Ongoing delivery was cancelled and stock was returned.',
        ];
    }

    $saleStatus = (string) $order['payment_type'] === 'cash' ? 'paid' : 'pending';

    $insertSale = $db->prepare(
        'INSERT INTO sales (customer_name, total_amount, payment_type, status, created_at)
         VALUES (:customer_name, :total_amount, :payment_type, :status, NOW())'
    );
    $insertSale->execute([
        'customer_name' => (string) $order['customer_name'],
        'total_amount' => round($totalAmount, 2),
        'payment_type' => (string) $order['payment_type'],
        'status' => $saleStatus,
    ]);

    $saleId = (int) $db->lastInsertId();

    $insertSaleItem = $db->prepare(
        'INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal, ordered_qty, order_unit, base_units)
         VALUES (:sale_id, :product_id, :quantity, :price, :subtotal, :ordered_qty, :order_unit, :base_units)'
    );

    foreach ($saleRows as $saleRow) {
        $insertSaleItem->execute([
            'sale_id' => $saleId,
            'product_id' => $saleRow['product_id'],
            'quantity' => $saleRow['base_units'],
            'price' => $saleRow['price'],
            'subtotal' => $saleRow['subtotal'],
            'ordered_qty' => $saleRow['ordered_qty'],
            'order_unit' => $saleRow['order_unit'],
            'base_units' => $saleRow['base_units'],
        ]);
    }

    ensure_customer_exists($db, (string) $order['customer_name']);

    $completeUpdate = $db->prepare(
        'UPDATE ongoing_deliveries
         SET status = "completed",
             sale_id = :sale_id,
             completed_at = NOW(),
             updated_at = NOW()
         WHERE id = :id'
    );
    $completeUpdate->execute([
        'id' => (int) $order['id'],
        'sale_id' => $saleId,
    ]);

    return [
        'message' => 'Delivery finalized. Sale has been posted based on actual delivered quantities.',
        'sale_id' => $saleId,
    ];
}

function lock_ongoing_delivery(PDO $db, int $id): ?array
{
    $statement = $db->prepare(
        'SELECT *
         FROM ongoing_deliveries
         WHERE id = :id
         LIMIT 1 FOR UPDATE'
    );
    $statement->execute(['id' => $id]);

    $order = $statement->fetch();
    return is_array($order) ? $order : null;
}

function calculate_base_units(int $orderedQty, string $orderUnit, int $piecesPerCase): int
{
    if ($orderedQty <= 0) {
        json_response(['success' => false, 'message' => 'Quantity must be at least 1.'], 422);
    }

    if ($piecesPerCase <= 0) {
        json_response(['success' => false, 'message' => 'Product pieces-per-case must be at least 1.'], 422);
    }

    if ($orderUnit === 'piece') {
        return $orderedQty;
    }

    if ($orderUnit === 'case') {
        return $orderedQty * $piecesPerCase;
    }

    $divisor = $orderUnit === 'half_case' ? 2 : 4;
    $numerator = $orderedQty * $piecesPerCase;

    if ($numerator % $divisor !== 0) {
        json_response([
            'success' => false,
            'message' => 'Case conversion produced a fractional piece count. Please adjust pieces-per-case.',
        ], 422);
    }

    return intdiv($numerator, $divisor);
}

function generate_next_ongoing_reference(PDO $db): string
{
    $year = date('Y');
    $prefix = 'ODL-' . $year . '-';

    $statement = $db->prepare(
        'SELECT reference_no
         FROM ongoing_deliveries
         WHERE reference_no LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute(['prefix' => $prefix . '%']);

    $latestReference = (string) ($statement->fetchColumn() ?: '');
    $nextNumber = 1;

    if (preg_match('/^ODL-\d{4}-(\d+)$/', $latestReference, $matches) === 1) {
        $nextNumber = ((int) $matches[1]) + 1;
    }

    return sprintf('%s%s', $prefix, str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT));
}

function ensure_customer_exists(PDO $db, string $customerName): void
{
    $name = trim($customerName);
    if ($name === '') {
        return;
    }

    $lookup = $db->prepare('SELECT id FROM customers WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $lookup->execute(['name' => $name]);

    if ($lookup->fetch()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO customers (name, created_at, updated_at)
         VALUES (:name, NOW(), NOW())'
    );
    $insert->execute(['name' => $name]);
}
