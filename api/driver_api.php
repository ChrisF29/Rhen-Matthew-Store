<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $statement = $db->prepare('SELECT * FROM drivers WHERE id = :id LIMIT 1');
            $statement->execute(['id' => (int) $_GET['id']]);
            $driver = $statement->fetch();

            if (!$driver) {
                json_response(['success' => false, 'message' => 'Driver not found.'], 404);
            }

            json_response(['success' => true, 'data' => $driver]);
        }

        $drivers = $db->query(
            'SELECT *
             FROM drivers
             ORDER BY FIELD(status, "active", "on_leave", "inactive"), full_name ASC'
        )->fetchAll();

        json_response(['success' => true, 'data' => $drivers]);
    }

    if ($method === 'POST') {
        $payload = validate_driver_payload(request_data());

        $exists = $db->prepare('SELECT id FROM drivers WHERE license_no = :license_no LIMIT 1');
        $exists->execute(['license_no' => $payload['license_no']]);
        if ($exists->fetch()) {
            json_response(['success' => false, 'message' => 'License number is already registered.'], 409);
        }

        $insert = $db->prepare(
            'INSERT INTO drivers (full_name, phone, license_no, vehicle_assigned, status, hired_date, notes, created_at, updated_at)
             VALUES (:full_name, :phone, :license_no, :vehicle_assigned, :status, :hired_date, :notes, NOW(), NOW())'
        );
        $insert->execute($payload);

        json_response([
            'success' => true,
            'message' => 'Driver added successfully.',
            'id' => (int) $db->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid driver id is required.'], 422);
        }

        $payload = validate_driver_payload($data);
        $payload['id'] = $id;

        $exists = $db->prepare('SELECT id FROM drivers WHERE license_no = :license_no AND id != :id LIMIT 1');
        $exists->execute([
            'license_no' => $payload['license_no'],
            'id' => $id,
        ]);

        if ($exists->fetch()) {
            json_response(['success' => false, 'message' => 'License number is already used by another driver.'], 409);
        }

        $update = $db->prepare(
            'UPDATE drivers
             SET full_name = :full_name,
                 phone = :phone,
                 license_no = :license_no,
                 vehicle_assigned = :vehicle_assigned,
                 status = :status,
                 hired_date = :hired_date,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $update->execute($payload);

        if ($update->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Driver not found or no changes made.'], 404);
        }

        json_response(['success' => true, 'message' => 'Driver updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid driver id is required.'], 422);
        }

        $delete = $db->prepare('DELETE FROM drivers WHERE id = :id');
        $delete->execute(['id' => $id]);

        if ($delete->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Driver not found.'], 404);
        }

        json_response(['success' => true, 'message' => 'Driver deleted successfully.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling driver request.',
    ], 500);
}

function validate_driver_payload(array $data): array
{
    $fullName = trim((string) ($data['full_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $licenseNo = trim((string) ($data['license_no'] ?? ''));
    $vehicleAssigned = trim((string) ($data['vehicle_assigned'] ?? ''));
    $status = (string) ($data['status'] ?? 'active');
    $hiredDate = (string) ($data['hired_date'] ?? '');
    $notes = trim((string) ($data['notes'] ?? ''));

    if ($fullName === '' || $phone === '' || $licenseNo === '' || $hiredDate === '') {
        json_response(['success' => false, 'message' => 'Name, phone, license, and hired date are required.'], 422);
    }

    if (!in_array($status, ['active', 'on_leave', 'inactive'], true)) {
        json_response(['success' => false, 'message' => 'Invalid driver status.'], 422);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hiredDate)) {
        json_response(['success' => false, 'message' => 'Hired date must be in YYYY-MM-DD format.'], 422);
    }

    return [
        'full_name' => $fullName,
        'phone' => $phone,
        'license_no' => $licenseNo,
        'vehicle_assigned' => $vehicleAssigned,
        'status' => $status,
        'hired_date' => $hiredDate,
        'notes' => $notes,
    ];
}
