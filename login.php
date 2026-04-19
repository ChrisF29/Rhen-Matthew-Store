<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errorMessage = '';
$infoMessage = isset($_GET['registered']) ? 'Account created. You can now log in.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMessage = 'Email and password are required.';
    } else {
        $statement = pdo()->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, (string) $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'name' => (string) $user['name'],
                'email' => (string) $user['email'],
                'role' => (string) $user['role'],
            ];

            redirect('index.php');
        }

        $errorMessage = 'Invalid login credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= h(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-backdrop" aria-hidden="true"></div>
    <section class="auth-card">
        <div class="auth-brand">
            <div class="brand-mark"><i data-lucide="bottle-wine"></i></div>
            <div>
                <h1><?= h(APP_NAME) ?></h1>
                <p>Inventory Management System</p>
            </div>
        </div>

        <h2>Welcome back</h2>
        <p class="muted">Sign in to manage products, stock, sales, and deliveries.</p>

        <?php if ($infoMessage !== ''): ?>
            <div class="alert alert-success"><?= h($infoMessage) ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error"><?= h($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" data-validate>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="name@example.com" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="Your password" required>

            <button type="submit" class="btn btn-primary btn-block">
                <i data-lucide="log-in"></i>
                Sign In
            </button>
        </form>

        <p class="auth-link">No account yet? <a href="register.php">Create one</a></p>
        <p class="auth-hint">Default admin: admin@store.local / admin12345</p>
    </section>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
