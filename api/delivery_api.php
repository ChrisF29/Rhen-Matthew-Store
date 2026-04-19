<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $deliveries = $db->query('SELECT * FROM deliveries ORDER BY created_at DESC, id DESC')->fetchAll();
        json_response(['success' => true, 'data' => $deliveries]);
    }

    if ($method === 'POST') {
        $payload = validate_delivery_payload(request_data());

        $insert = $db->prepare(
            'INSERT INTO deliveries (reference_no, customer_name, address, scheduled_date, status, created_at)
             VALUES (:reference_no, :customer_name, :address, :scheduled_date, :status, NOW())'
        );
        $insert->execute($payload);

        json_response([
            'success' => true,
            'message' => 'Delivery created successfully.',
            'id' => (int) $db->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid delivery id is required.'], 422);
        }

        $payload = validate_delivery_payload($data);
        $payload['id'] = $id;
        $payload['status_check'] = $payload['status'];

        $update = $db->prepare(
            'UPDATE deliveries
             SET reference_no = :reference_no,
                 customer_name = :customer_name,
                 address = :address,
                 scheduled_date = :scheduled_date,
                 status = :status,
                 delivered_at = CASE WHEN :status_check = "delivered" THEN NOW() ELSE NULL END
             WHERE id = :id'
        );
        $update->execute($payload);

        if ($update->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Delivery not found or no changes made.'], 404);
        }

        json_response(['success' => true, 'message' => 'Delivery updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid delivery id is required.'], 422);
        }

        $delete = $db->prepare('DELETE FROM deliveries WHERE id = :id');
        $delete->execute(['id' => $id]);

        if ($delete->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Delivery not found.'], 404);
        }

        json_response(['success' => true, 'message' => 'Delivery deleted successfully.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling delivery request.',
    ], 500);
}

function validate_delivery_payload(array $data): array
{
    $reference = trim((string) ($data['reference_no'] ?? ''));
    $customer = trim((string) ($data['customer_name'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $scheduledDate = (string) ($data['scheduled_date'] ?? '');
    $status = (string) ($data['status'] ?? 'pending');

    if ($reference === '' || $customer === '' || $address === '' || $scheduledDate === '') {
        json_response(['success' => false, 'message' => 'Reference, customer, address, and date are required.'], 422);
    }

    if (!in_array($status, ['pending', 'in_transit', 'delivered', 'cancelled'], true)) {
        json_response(['success' => false, 'message' => 'Invalid delivery status.'], 422);
    }

    return [
        'reference_no' => $reference,
        'customer_name' => $customer,
        'address' => $address,
        'scheduled_date' => $scheduledDate,
        'status' => $status,
    ];
}
