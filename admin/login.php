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

function adminLoginIcon(string $name): string
{
    return match ($name) {
        'brand' => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34V18" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M22 34V12" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M34 34V22" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M9 36h30" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M12 26.5L22 16.5L30 20.5L38 12.5" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="38" cy="12.5" r="3" fill="currentColor"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16.5 19.5v-1.2c0-1.84-1.49-3.33-3.33-3.33H10.8c-1.84 0-3.33 1.49-3.33 3.33v1.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 11.8a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 19.5v-1.04c0-1.45-.8-2.73-2-3.41" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M15.7 6.33a3.2 3.2 0 0 1 0 6.34" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'gift' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4.5 9.5h15v10a1 1 0 0 1-1 1h-13a1 1 0 0 1-1-1v-10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3.9 8.2h16.2v3H3.9v-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 8.2v12.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 8.2c-1.8 0-3.2-1-3.2-2.3S10 3.8 11.4 4.7C12.2 5.2 12.6 6.4 12 8.2Zm0 0c1.8 0 3.2-1 3.2-2.3S14 3.8 12.6 4.7C11.8 5.2 11.4 6.4 12 8.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        'database' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><ellipse cx="12" cy="6.5" rx="7" ry="3" stroke="currentColor" stroke-width="1.8"/><path d="M5 6.5v11c0 1.66 3.13 3 7 3s7-1.34 7-3v-11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 11c0 1.66 3.13 3 7 3s7-1.34 7-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 3.5 19 6v5.1c0 4.6-3 8.4-7 9.9-4-1.5-7-5.3-7-9.9V6l7-2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10.1v3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="15.6" r="1" fill="currentColor"/></svg>',
        'lock' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7.5 10V8.2a4.5 4.5 0 0 1 9 0V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6.8 10h10.4c.7 0 1.3.6 1.3 1.3v6.4c0 .7-.6 1.3-1.3 1.3H6.8c-.7 0-1.3-.6-1.3-1.3v-6.4c0-.7.6-1.3 1.3-1.3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 13v2.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'lock-mini' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 10V8.8a4 4 0 1 1 8 0V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><rect x="5.5" y="10" width="13" height="9" rx="2.2" stroke="currentColor" stroke-width="1.6"/><path d="M12 13v2.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15.5 18.5v-1.2A3.3 3.3 0 0 0 12.2 14H10.8a3.3 3.3 0 0 0-3.3 3.3v1.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 11.6a3.1 3.1 0 1 0 0-6.2 3.1 3.1 0 0 0 0 6.2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2.8 12s2.8-6 9.2-6 9.2 6 9.2 6-2.8 6-9.2 6-9.2-6-9.2-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.4" stroke="currentColor" stroke-width="1.8"/></svg>',
        'eye-off' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 4l16 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.2 9.2A3.5 3.5 0 0 0 12 16a3.5 3.5 0 0 0 2.8-5.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.1 6.9C3.9 8.4 2.8 12 2.8 12s2.8 6 9.2 6c1.4 0 2.6-.2 3.6-.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M17.9 16.9C20.1 15.4 21.2 12 21.2 12s-2.8-6-9.2-6c-1.1 0-2.2.1-3.2.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        default => '',
    };
}

$adminFeatures = [
    [
        'icon' => 'users',
        'title' => 'Gestión de usuarios',
        'description' => 'Administra cuentas y permisos',
    ],
    [
        'icon' => 'gift',
        'title' => 'Balance y recompensas',
        'description' => 'Ajusta economía y recompensas',
    ],
    [
        'icon' => 'database',
        'title' => 'Control de base de datos',
        'description' => 'Monitorea y gestiona la información',
    ],
    [
        'icon' => 'shield',
        'title' => 'Seguridad',
        'description' => 'Protege el sistema y los datos',
    ],
];
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
<body class="lifequest-auth admin-auth-page">
    <main class="admin-auth-shell">
        <aside class="admin-auth-rail" aria-label="Información del panel de administración">
            <div class="admin-auth-brand">
                <span class="admin-auth-brand-icon">
                    <img src="../icons/admin/chart.png" alt="" aria-hidden="true">
                </span>
                <div class="admin-auth-brand-text">
                    <p class="admin-auth-eyebrow">LifeQuest Admin</p>
                    <h1>Data Control Panel</h1>
                </div>
            </div>
            <div class="admin-auth-feature-list">
                <?php foreach ($adminFeatures as $feature): ?>
                    <article class="admin-auth-feature">
                        <span class="admin-auth-feature-icon"><?= adminLoginIcon($feature['icon']) ?></span>
                        <div>
                            <strong><?= e($feature['title']) ?></strong>
                            <span><?= e($feature['description']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="admin-auth-rail-visual" aria-hidden="true">
                <span class="admin-auth-rail-ring"></span>
                <span class="admin-auth-rail-ring admin-auth-rail-ring-secondary"></span>
                <span class="admin-auth-rail-shield">
                    <img src="../icons/admin/secure.png" alt="" aria-hidden="true">
                </span>
            </div>
        </aside>

        <section class="admin-auth-stage">
            <div class="admin-auth-card">
                <header class="admin-auth-card-head">
                    <h2>Iniciar sesión</h2>
                    <p>Accede al panel de administración</p>
                </header>

                <?php if ($message): ?>
                    <div class="lq-alert <?= e((string) $messageType) ?>"><?= e($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="admin-auth-form">
                    <label class="admin-field">
                        <span class="admin-field-label">Usuario o correo</span>
                        <span class="admin-input-shell">
                            <span class="admin-input-icon"><?= adminLoginIcon('user') ?></span>
                            <input type="text" name="username" placeholder="Ingresa tu usuario o correo" required autocomplete="username">
                        </span>
                    </label>

                    <label class="admin-field">
                        <span class="admin-field-label">Contraseña</span>
                        <span class="admin-input-shell">
                            <span class="admin-input-icon"><?= adminLoginIcon('lock') ?></span>
                            <input id="admin-password" type="password" name="password" placeholder="Ingresa tu contraseña" required autocomplete="current-password">
                            <button type="button" class="admin-password-toggle" data-password-toggle aria-label="Mostrar contraseña" aria-pressed="false">
                                <span class="admin-password-toggle-icon" data-password-icon><?= adminLoginIcon('eye') ?></span>
                            </button>
                        </span>
                    </label>

                    <div class="admin-auth-meta">
                        <label class="admin-remember">
                            <input type="checkbox" name="remember" value="1">
                            <span>Recordarme</span>
                        </label>

                        <a class="admin-forgot-link" href="mailto:soporte@lifequest.app?subject=Recuperar%20acceso%20admin">¿Has olvidado tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn btn-primary admin-submit">Entrar al panel</button>
                    <a href="../public/index.php" class="btn btn-secondary admin-back">Volver a LifeQuest</a>
                </form>
            </div>

            <p class="admin-auth-footnote">
                <span class="admin-auth-footnote-icon"><?= adminLoginIcon('lock-mini') ?></span>
                Acceso restringido solo a administradores
            </p>
        </section>
    </main>

    <script>
        (function () {
            const passwordInput = document.getElementById('admin-password');
            const toggleButton = document.querySelector('[data-password-toggle]');
            const passwordIcon = document.querySelector('[data-password-icon]');

            if (!passwordInput || !toggleButton || !passwordIcon) {
                return;
            }

            const eyeIcon = <?= json_encode(adminLoginIcon('eye')) ?>;
            const eyeOffIcon = <?= json_encode(adminLoginIcon('eye-off')) ?>;

            toggleButton.addEventListener('click', function () {
                const isHidden = passwordInput.type === 'password';

                passwordInput.type = isHidden ? 'text' : 'password';
                toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggleButton.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
                passwordIcon.innerHTML = isHidden ? eyeOffIcon : eyeIcon;
            });
        })();
    </script>
</body>
</html>
