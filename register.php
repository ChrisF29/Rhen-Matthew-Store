<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'staff');

    if ($name === '' || $email === '' || $password === '') {
        $errorMessage = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please provide a valid email address.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters long.';
    } elseif (!in_array($role, ['admin', 'staff'], true)) {
        $errorMessage = 'Invalid role selected.';
    } else {
        $checkStatement = pdo()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStatement->execute(['email' => $email]);

        if ($checkStatement->fetch()) {
            $errorMessage = 'Email address is already registered.';
        } else {
            $insertStatement = pdo()->prepare(
                'INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())'
            );
            $insertStatement->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => $role,
            ]);

            redirect('login.php?registered=1');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= h(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-backdrop" aria-hidden="true"></div>
    <section class="auth-card">
        <div class="auth-brand">
            <div class="brand-mark"><i data-lucide="badge-plus"></i></div>
            <div>
                <h1>Create Account</h1>
                <p>Set up a new IMS user</p>
            </div>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error"><?= h($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" data-validate>
            <label for="name">Full Name</label>
            <input id="name" name="name" type="text" placeholder="Juan Dela Cruz" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="name@example.com" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="8" placeholder="At least 8 characters" required>

            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" class="btn btn-primary btn-block">
                <i data-lucide="user-plus"></i>
                Register
            </button>
        </form>

        <p class="auth-link">Already have an account? <a href="login.php">Back to login</a></p>
    </section>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
