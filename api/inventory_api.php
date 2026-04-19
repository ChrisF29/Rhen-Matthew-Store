<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $statement = $db->query(
            'SELECT i.id,
                    i.product_id,
                    p.name AS product_name,
                    p.stock_quantity,
                    i.quantity,
                    i.type,
                    i.notes,
                    i.created_at
             FROM inventory i
             INNER JOIN products p ON p.id = i.product_id
             ORDER BY i.created_at DESC, i.id DESC'
        );

        json_response([
            'success' => true,
            'data' => $statement->fetchAll(),
        ]);
    }

    if ($method === 'POST') {
        $data = request_data();
        $productId = (int) ($data['product_id'] ?? 0);
        $type = (string) ($data['type'] ?? '');
        $direction = (string) ($data['direction'] ?? 'increase');
        $quantityUnit = (string) ($data['quantity_unit'] ?? 'piece');
        $quantity = (int) ($data['quantity'] ?? 0);
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($productId <= 0 || $quantity <= 0) {
            json_response(['success' => false, 'message' => 'Product and quantity are required.'], 422);
        }

        if (!in_array($type, ['in', 'out', 'adjustment'], true)) {
            json_response(['success' => false, 'message' => 'Invalid movement type.'], 422);
        }

        if (!in_array($direction, ['increase', 'decrease'], true)) {
            json_response(['success' => false, 'message' => 'Invalid adjustment direction.'], 422);
        }

        if (!in_array($quantityUnit, ['piece', 'case'], true)) {
            json_response(['success' => false, 'message' => 'Invalid quantity unit.'], 422);
        }

        $db->beginTransaction();

        $productStatement = $db->prepare('SELECT id, pieces_per_case FROM products WHERE id = :id LIMIT 1');
        $productStatement->execute(['id' => $productId]);
        $product = $productStatement->fetch();
        if (!$product) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $piecesPerCase = (int) ($product['pieces_per_case'] ?? 24);
        if ($piecesPerCase <= 0) {
            $piecesPerCase = 1;
        }

        $baseQuantity = $quantityUnit === 'case' ? ($quantity * $piecesPerCase) : $quantity;

        $signedQuantity = $baseQuantity;
        if ($type === 'out') {
            $signedQuantity = -$baseQuantity;
        }

        if ($type === 'adjustment' && $direction === 'decrease') {
            $signedQuantity = -$baseQuantity;
        }

        $insert = $db->prepare(
            'INSERT INTO inventory (product_id, quantity, type, notes, created_at)
             VALUES (:product_id, :quantity, :type, :notes, NOW())'
        );
        $insert->execute([
            'product_id' => $productId,
            'quantity' => $signedQuantity,
            'type' => $type,
            'notes' => $notes,
        ]);

        $stockUpdated = adjust_product_stock($db, $productId, $signedQuantity);
        if (!$stockUpdated) {
            $db->rollBack();
            json_response([
                'success' => false,
                'message' => 'Stock adjustment failed. Check available stock before stock out/decrease.',
            ], 422);
        }

        $db->commit();
        json_response(['success' => true, 'message' => 'Inventory movement saved.'], 201);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid movement id is required.'], 422);
        }

        $lookup = $db->prepare('SELECT id, product_id, quantity FROM inventory WHERE id = :id LIMIT 1');
        $lookup->execute(['id' => $id]);
        $entry = $lookup->fetch();

        if (!$entry) {
            json_response(['success' => false, 'message' => 'Inventory entry not found.'], 404);
        }

        $db->beginTransaction();

        $reverseSuccess = adjust_product_stock($db, (int) $entry['product_id'], -((int) $entry['quantity']));
        if (!$reverseSuccess) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Could not reverse stock for this movement.'], 422);
        }

        $delete = $db->prepare('DELETE FROM inventory WHERE id = :id');
        $delete->execute(['id' => $id]);

        $db->commit();
        json_response(['success' => true, 'message' => 'Inventory movement deleted and stock restored.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling inventory request.',
    ], 500);
}
