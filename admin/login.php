<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/AdminPortalUser.php';
require_once __DIR__ . '/session_guard.php';

if (!defined('ADMIN_PORTAL_ENABLED') || ADMIN_PORTAL_ENABLED !== true) {
    http_response_code(404);
    exit('Not Found');
}

if (!empty($_SESSION['admin_portal_user_id'])) {
    if (isAdminPortalSessionExpired()) {
        clearAdminPortalSession();
        header('Location: login.php?message=' . urlencode('Sesion expirada por inactividad.') . '&type=error');
        exit;
    }

    $_SESSION['admin_portal_logged_at'] = time();
    header('Location: database.php?section=db');
    exit;
}

$message = isset($_GET['message']) ? (string) $_GET['message'] : null;
$messageType = isset($_GET['type']) ? (string) $_GET['type'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = 'Usuario y contraseña son obligatorios.';
        $messageType = 'error';
    } else {
        $adminUserModel = new AdminPortalUser();

        if (!$adminUserModel->hasUsersTable()) {
            $message = 'Falta la tabla admin_portal_users. Ejecuta database/admin_portal_auth_migration.sql.';
            $messageType = 'error';
        } else {
            $admin = $adminUserModel->verifyCredentials($username, $password);

            if ($admin === null) {
                $message = 'Credenciales incorrectas.';
                $messageType = 'error';
            } else {
                session_regenerate_id(true);
                $_SESSION['admin_portal_user_id'] = $admin['id'];
                $_SESSION['admin_portal_username'] = $admin['username'];
                $_SESSION['admin_portal_logged_at'] = time();

                header('Location: database.php?section=db');
                exit;
            }
        }
    }
}

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/auth.css">
</head>
<body class="lifequest-auth">
    <main class="auth-shell">
        <section class="auth-card">
            <h1>Admin Portal</h1>
            <p>Acceso privado de administración.</p>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <label>Usuario
                    <input type="text" name="username" required autocomplete="username">
                </label>
                <label>Contraseña
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
                <button type="submit" class="btn btn-primary full">Entrar</button>
            </form>
        </section>
    </main>
</body>
</html>
