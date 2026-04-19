<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $statement = $db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
            $statement->execute(['id' => (int) $_GET['id']]);
            $product = $statement->fetch();

            if (!$product) {
                json_response(['success' => false, 'message' => 'Product not found.'], 404);
            }

            json_response(['success' => true, 'data' => $product]);
        }

        $products = $db->query('SELECT * FROM products ORDER BY name ASC, size ASC, id ASC')->fetchAll();
        json_response(['success' => true, 'data' => $products]);
    }

    if ($method === 'POST') {
        $payload = validate_product_payload(request_data());

        $insert = $db->prepare(
              'INSERT INTO products (name, category, size, price, pieces_per_case, stock_quantity, created_at, updated_at)
               VALUES (:name, :category, :size, :price, :pieces_per_case, :stock_quantity, NOW(), NOW())'
        );
        $insert->execute($payload);

        json_response([
            'success' => true,
            'message' => 'Product created successfully.',
            'id' => (int) $db->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid product id is required.'], 422);
        }

        $payload = validate_product_payload($data);
        $payload['id'] = $id;

        $update = $db->prepare(
            'UPDATE products
             SET name = :name,
                 category = :category,
                 size = :size,
                 price = :price,
                 pieces_per_case = :pieces_per_case,
                 stock_quantity = :stock_quantity,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $update->execute($payload);

        if ($update->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Product not found or no changes made.'], 404);
        }

        json_response(['success' => true, 'message' => 'Product updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid product id is required.'], 422);
        }

        $usageStatement = $db->prepare('SELECT COUNT(*) FROM sales_items WHERE product_id = :id');
        $usageStatement->execute(['id' => $id]);

        if ((int) $usageStatement->fetchColumn() > 0) {
            json_response([
                'success' => false,
                'message' => 'Product cannot be deleted because it already has sales history.',
            ], 409);
        }

        $delete = $db->prepare('DELETE FROM products WHERE id = :id');
        $delete->execute(['id' => $id]);

        if ($delete->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Product not found.'], 404);
        }

        json_response(['success' => true, 'message' => 'Product deleted successfully.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling product request.',
    ], 500);
}

function validate_product_payload(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));
    $size = trim((string) ($data['size'] ?? ''));
    $price = (float) ($data['price'] ?? 0);
    $piecesPerCase = (int) ($data['pieces_per_case'] ?? 24);
    $stockQuantity = (int) ($data['stock_quantity'] ?? 0);

    if ($name === '' || $category === '' || $size === '') {
        json_response(['success' => false, 'message' => 'Name, category, and size are required.'], 422);
    }

    if ($price < 0) {
        json_response(['success' => false, 'message' => 'Price must be zero or greater.'], 422);
    }

    if ($piecesPerCase <= 0) {
        json_response(['success' => false, 'message' => 'Pieces per case must be at least 1.'], 422);
    }

    if ($stockQuantity < 0) {
        json_response(['success' => false, 'message' => 'Stock quantity cannot be negative.'], 422);
    }

    return [
        'name' => $name,
        'category' => $category,
        'size' => $size,
        'price' => $price,
        'pieces_per_case' => $piecesPerCase,
        'stock_quantity' => $stockQuantity,
    ];
}
