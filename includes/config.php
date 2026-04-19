<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

const APP_NAME = 'RHEN MATTHEW STORE';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'rhen_matthew_store';
const DB_USER = 'root';
const DB_PASS = '';

function pdo(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

    try {
        $connection = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        http_response_code(500);
        exit('Database connection failed. Please check includes/config.php and create the database.');
    }

    return $connection;
}

function is_api_request(): bool
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    return strpos($scriptName, '/api/') !== false;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    if (is_api_request()) {
        json_response([
            'success' => false,
            'message' => 'Unauthorized request.',
        ], 401);
    }

    redirect('login.php');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function request_data(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos(strtolower($contentType), 'application/json') !== false) {
        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }

    parse_str($rawBody, $parsed);
    return is_array($parsed) ? $parsed : [];
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function adjust_product_stock(PDO $db, int $productId, int $delta): bool
{
    $statement = $db->prepare(
        'UPDATE products
         SET stock_quantity = stock_quantity + :delta_update,
             updated_at = NOW()
         WHERE id = :id
           AND (stock_quantity + :delta_guard) >= 0'
    );

    $statement->execute([
        'delta_update' => $delta,
        'delta_guard' => $delta,
        'id' => $productId,
    ]);

    return $statement->rowCount() > 0;
}
