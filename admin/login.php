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
    $adminAssetIcons = [
        'users' => 'users.png',
        'gift' => 'gift.png',
        'database' => 'database.png',
        'shield' => 'shield.png',
    ];

    if (isset($adminAssetIcons[$name])) {
        return '<img src="../icons/admin/' . e($adminAssetIcons[$name]) . '" alt="" aria-hidden="true">';
    }

    return match ($name) {
        'brand' => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34V18" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M22 34V12" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M34 34V22" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M9 36h30" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M12 26.5L22 16.5L30 20.5L38 12.5" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="38" cy="12.5" r="3" fill="currentColor"/></svg>',
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
                            <small><?= e($feature['description']) ?></small>
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
                <!-- Moléculas -->
                <span class="admin-dust" style="--r:105px; --a:18deg;  --s:5px; --dur:7s;  --del:0s;    --dx:12px; --dy:-8px;"></span>
                <span class="admin-dust" style="--r:118px; --a:72deg;  --s:3px; --dur:9s;  --del:-2s;   --dx:-9px; --dy:6px;"></span>
                <span class="admin-dust" style="--r:95px;  --a:130deg; --s:4px; --dur:11s; --del:-4s;   --dx:7px;  --dy:10px;"></span>
                <span class="admin-dust" style="--r:125px; --a:195deg; --s:3px; --dur:8s;  --del:-1s;   --dx:-11px;--dy:-5px;"></span>
                <span class="admin-dust" style="--r:88px;  --a:245deg; --s:6px; --dur:13s; --del:-6s;   --dx:9px;  --dy:-12px;"></span>
                <span class="admin-dust" style="--r:115px; --a:300deg; --s:3px; --dur:10s; --del:-3s;   --dx:-6px; --dy:8px;"></span>
                <span class="admin-dust" style="--r:100px; --a:340deg; --s:4px; --dur:12s; --del:-5s;   --dx:13px; --dy:4px;"></span>
                <span class="admin-dust" style="--r:78px;  --a:55deg;  --s:3px; --dur:15s; --del:-7s;   --dx:-8px; --dy:-9px;"></span>
                <span class="admin-dust" style="--r:135px; --a:160deg; --s:5px; --dur:9s;  --del:-2.5s; --dx:6px;  --dy:11px;"></span>
                <span class="admin-dust" style="--r:92px;  --a:215deg; --s:3px; --dur:14s; --del:-8s;   --dx:-14px;--dy:3px;"></span>
                <span class="admin-dust" style="--r:122px; --a:275deg; --s:4px; --dur:11s; --del:-1.5s; --dx:10px; --dy:-7px;"></span>
                <span class="admin-dust" style="--r:82px;  --a:320deg; --s:3px; --dur:16s; --del:-9s;   --dx:-5px; --dy:13px;"></span>
                <span class="admin-dust" style="--r:110px; --a:95deg;  --s:4px; --dur:8s;  --del:-4.5s; --dx:8px;  --dy:-6px;"></span>
                <span class="admin-dust" style="--r:70px;  --a:150deg; --s:3px; --dur:18s; --del:-10s;  --dx:-7px; --dy:-10px;"></span>
                <span class="admin-dust" style="--r:128px; --a:230deg; --s:5px; --dur:10s; --del:-3.5s; --dx:11px; --dy:5px;"></span>
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
