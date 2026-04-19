<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $statement = $db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
            $statement->execute(['id' => (int) $_GET['id']]);
            $customer = $statement->fetch();

            if (!$customer) {
                json_response(['success' => false, 'message' => 'Customer not found.'], 404);
            }

            json_response(['success' => true, 'data' => $customer]);
        }

        $customers = $db->query(
            'SELECT id, name, phone, address, notes, created_at
             FROM customers
             ORDER BY name ASC, id ASC'
        )->fetchAll();

        json_response(['success' => true, 'data' => $customers]);
    }

    if ($method === 'POST') {
        $payload = validate_customer_payload(request_data());

        $insert = $db->prepare(
            'INSERT INTO customers (name, phone, address, notes, created_at, updated_at)
             VALUES (:name, :phone, :address, :notes, NOW(), NOW())'
        );
        $insert->execute($payload);

        json_response([
            'success' => true,
            'message' => 'Customer created successfully.',
            'id' => (int) $db->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid customer id is required.'], 422);
        }

        $payload = validate_customer_payload($data);
        $payload['id'] = $id;

        $update = $db->prepare(
            'UPDATE customers
             SET name = :name,
                 phone = :phone,
                 address = :address,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $update->execute($payload);

        if ($update->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Customer not found or no changes made.'], 404);
        }

        json_response(['success' => true, 'message' => 'Customer updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid customer id is required.'], 422);
        }

        $delete = $db->prepare('DELETE FROM customers WHERE id = :id');
        $delete->execute(['id' => $id]);

        if ($delete->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        json_response(['success' => true, 'message' => 'Customer deleted successfully.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling customer request.',
    ], 500);
}

function validate_customer_payload(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $notes = trim((string) ($data['notes'] ?? ''));

    if ($name === '') {
        json_response(['success' => false, 'message' => 'Customer name is required.'], 422);
    }

    if (strlen($name) > 140) {
        json_response(['success' => false, 'message' => 'Customer name is too long.'], 422);
    }

    if (strlen($phone) > 40) {
        json_response(['success' => false, 'message' => 'Phone number is too long.'], 422);
    }

    if (strlen($address) > 255) {
        json_response(['success' => false, 'message' => 'Address is too long.'], 422);
    }

    if (strlen($notes) > 255) {
        json_response(['success' => false, 'message' => 'Notes are too long.'], 422);
    }

    return [
        'name' => $name,
        'phone' => $phone !== '' ? $phone : null,
        'address' => $address !== '' ? $address : null,
        'notes' => $notes !== '' ? $notes : null,
    ];
}
