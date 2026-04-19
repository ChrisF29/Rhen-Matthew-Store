<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_login();

$db = pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$currentUser = current_user() ?? [];

try {
    if ($method === 'GET') {
        $users = $db->query(
            'SELECT id, name, email, role, created_at
             FROM users
             ORDER BY created_at DESC, id DESC'
        )->fetchAll();

        json_response(['success' => true, 'data' => $users]);
    }

    if ($method === 'POST') {
        $data = request_data();
        $payload = validate_user_payload($data, false);

        $check = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $payload['email']]);
        if ($check->fetch()) {
            json_response(['success' => false, 'message' => 'Email is already in use.'], 409);
        }

        $insert = $db->prepare(
            'INSERT INTO users (name, email, password, role, created_at)
             VALUES (:name, :email, :password, :role, NOW())'
        );
        $insert->execute([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => password_hash($payload['password'], PASSWORD_BCRYPT),
            'role' => $payload['role'],
        ]);

        json_response([
            'success' => true,
            'message' => 'User created successfully.',
            'id' => (int) $db->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid user id is required.'], 422);
        }

        $payload = validate_user_payload($data, true);

        $check = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $check->execute([
            'email' => $payload['email'],
            'id' => $id,
        ]);

        if ($check->fetch()) {
            json_response(['success' => false, 'message' => 'Email is already used by another account.'], 409);
        }

        $fields = [
            'name = :name',
            'email = :email',
            'role = :role',
        ];

        $params = [
            'id' => $id,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
        ];

        if (isset($payload['password']) && $payload['password'] !== '') {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($payload['password'], PASSWORD_BCRYPT);
        }

        $update = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $update->execute($params);

        if ($update->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'User not found or no changes made.'], 404);
        }

        json_response(['success' => true, 'message' => 'User updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Valid user id is required.'], 422);
        }

        if ((int) ($currentUser['id'] ?? 0) === $id) {
            json_response(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }

        $delete = $db->prepare('DELETE FROM users WHERE id = :id');
        $delete->execute(['id' => $id]);

        if ($delete->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'User not found.'], 404);
        }

        json_response(['success' => true, 'message' => 'User deleted successfully.']);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => 'Unexpected server error while handling user request.',
    ], 500);
}

function validate_user_payload(array $data, bool $isUpdate): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $role = (string) ($data['role'] ?? 'staff');
    $password = (string) ($data['password'] ?? '');

    if ($name === '' || $email === '') {
        json_response(['success' => false, 'message' => 'Name and email are required.'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email address.'], 422);
    }

    if (!in_array($role, ['admin', 'staff'], true)) {
        json_response(['success' => false, 'message' => 'Invalid user role.'], 422);
    }

    if (!$isUpdate && strlen($password) < 8) {
        json_response(['success' => false, 'message' => 'Password must be at least 8 characters for new users.'], 422);
    }

    if ($isUpdate && $password !== '' && strlen($password) < 8) {
        json_response(['success' => false, 'message' => 'Updated password must be at least 8 characters.'], 422);
    }

    $payload = [
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ];

    if ($password !== '') {
        $payload['password'] = $password;
    }

    return $payload;
}
