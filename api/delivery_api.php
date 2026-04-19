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
                    'reference_no' => generate_next_delivery_reference($db),
                ],
            ]);
        }

        $deliveries = $db->query('SELECT * FROM deliveries ORDER BY created_at DESC, id DESC')->fetchAll();
        json_response(['success' => true, 'data' => $deliveries]);
    }

    if ($method === 'POST') {
        $data = request_data();
        if (trim((string) ($data['reference_no'] ?? '')) === '') {
            $data['reference_no'] = generate_next_delivery_reference($db);
        }

        $payload = validate_delivery_payload($data);

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
    if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
        json_response([
            'success' => false,
            'message' => 'Reference No. already exists. Please use a different one.',
        ], 409);
    }

    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling delivery request.',
    ], 500);
}

function generate_next_delivery_reference(PDO $db): string
{
    $year = date('Y');
    $prefix = 'DLV-' . $year . '-';

    $statement = $db->prepare(
        'SELECT reference_no
         FROM deliveries
         WHERE reference_no LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute(['prefix' => $prefix . '%']);

    $latestReference = (string) ($statement->fetchColumn() ?: '');
    $nextNumber = 1;

    if (preg_match('/^DLV-\d{4}-(\d+)$/', $latestReference, $matches) === 1) {
        $nextNumber = ((int) $matches[1]) + 1;
    }

    return sprintf('%s%s', $prefix, str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT));
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
