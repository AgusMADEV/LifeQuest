<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . (AuthController::needsAvatarOnboarding() ? 'avatar_setup.php' : 'dashboard.php'));
    exit;
}

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $result = $auth->login($_POST);

    if ($result['success']) {
        header('Location: ' . (AuthController::needsAvatarOnboarding() ? 'avatar_setup.php' : 'dashboard.php'));
        exit;
    }

    $message = $result['message'];
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/auth.css">
</head>
<body class="public-login-body">
    <main class="public-login-page">
        <header class="public-login-header" aria-label="Acceso LifeQuest">
            <a href="index.php" class="public-login-brand" aria-label="Ir al inicio">
                <span>Life</span><strong>Quest</strong><i aria-hidden="true">++</i>
            </a>
            <a href="index.php" class="public-login-help">
                <span>¿Necesitas ayuda?</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9.1 9a3 3 0 1 1 4.8 2.4c-1.1.8-1.9 1.4-1.9 2.6" />
                    <path d="M12 17h.01" />
                    <circle cx="12" cy="12" r="9" />
                </svg>
            </a>
        </header>

        <section class="public-login-shell" aria-label="Inicio de sesión LifeQuest">
            <aside class="public-login-hero" aria-label="Resumen de LifeQuest">
                <div class="public-login-orbit" aria-hidden="true"></div>
                <div class="public-login-spark spark-one" aria-hidden="true"></div>
                <div class="public-login-spark spark-two" aria-hidden="true"></div>
                <div class="public-login-spark spark-three" aria-hidden="true"></div>

                <img class="public-login-avatar" src="../referencias/avatares/jacob.png" alt="Aventurero de LifeQuest">

                <div class="public-login-copy">
                    <h1>Bienvenido <span>de nuevo</span> <em aria-hidden="true">👋</em></h1>
                    <p>Convierte tus metas en progreso diario.</p>

                    <div class="public-login-progress-card" aria-label="Progreso de hoy">
                        <span class="public-login-gem" aria-hidden="true"></span>
                        <div>
                            <small>Tu progreso de hoy</small>
                            <strong>3/4 misiones</strong>
                            <span class="public-login-progress-bar"><i></i></span>
                        </div>
                    </div>
                </div>

                <div class="public-login-plant" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>

                <div class="public-login-feature-panel" aria-label="Funciones principales">
                    <article>
                        <span class="feature-icon missions">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0-8.7-6.7"/><path d="M12 7v5l3 2"/><path d="m3 17 2 2 4-5"/></svg>
                        </span>
                        <strong>Misiones</strong>
                        <p>Completa misiones diarias y semanales.</p>
                    </article>
                    <article>
                        <span class="feature-icon habits">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                        </span>
                        <strong>Hábitos</strong>
                        <p>Crea rutinas positivas y mantén tu racha.</p>
                    </article>
                    <article>
                        <span class="feature-icon progress">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 7-8"/><path d="M14 7h6v6"/></svg>
                        </span>
                        <strong>Progreso</strong>
                        <p>Visualiza tu avance y celebra logros.</p>
                    </article>
                    <article>
                        <span class="feature-icon rewards">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M5 6H3v3a4 4 0 0 0 4 4"/><path d="M19 6h2v3a4 4 0 0 1-4 4"/></svg>
                        </span>
                        <strong>Recompensas</strong>
                        <p>Gana LifeCoins, gemas y objetos únicos.</p>
                    </article>
                </div>
            </aside>

            <section class="public-login-card">
                <div class="public-login-card-head">
                    <h2>Iniciar sesión</h2>
                    <p>Tu misión de hoy empieza aquí.</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="public-login-form">
                    <label class="public-login-field">
                        <span>Correo electrónico</span>
                        <span class="public-login-input">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                            <input type="email" name="email" placeholder="ejemplo@correo.com" autocomplete="email" required>
                        </span>
                    </label>

                    <label class="public-login-field">
                        <span>Contraseña</span>
                        <span class="public-login-input">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 10h12v10H6z"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/><path d="M12 15v1"/></svg>
                            <input id="login-password" type="password" name="password" placeholder="••••••••••••" autocomplete="current-password" required>
                            <button class="public-login-eye" type="button" aria-label="Mostrar contraseña" aria-controls="login-password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 10.6A2 2 0 0 0 13.4 13.4"/><path d="M9.9 5.2A10.4 10.4 0 0 1 12 5c5 0 8.5 4.5 9.5 7a11.8 11.8 0 0 1-2.8 3.9"/><path d="M6.2 6.2A12.2 12.2 0 0 0 2.5 12c1 2.5 4.5 7 9.5 7a10.7 10.7 0 0 0 4.2-.9"/></svg>
                            </button>
                        </span>
                    </label>

                    <div class="public-login-meta">
                        <label class="public-login-remember">
                            <input type="checkbox" name="remember" checked>
                            <span>Recordarme</span>
                        </label>
                        <a href="login.php" class="public-login-forgot">¿Has olvidado tu contraseña?</a>
                    </div>

                    <button type="submit" class="public-login-submit">
                        <span>Entrar</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </button>
                </form>

                <div class="public-login-divider"><span>o continúa con</span></div>

                <div class="public-login-socials" aria-label="Otros métodos de acceso">
                    <button type="button">
                        <span class="google-mark" aria-hidden="true">G</span>
                        Continuar con Google
                    </button>
                    <button type="button">
                        <span class="apple-mark" aria-hidden="true">●</span>
                        Continuar con Apple
                    </button>
                </div>

                <p class="public-login-create">¿No tienes cuenta? <a href="register.php">Crear cuenta</a></p>
            </section>
        </section>

        <footer class="public-login-footer">
            <button type="button" class="public-login-language">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/></svg>
                Español
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <nav aria-label="Enlaces legales">
                <a href="index.php">Términos de servicio</a>
                <a href="index.php">Privacidad</a>
                <a href="index.php">Contacto</a>
            </nav>
            <span class="public-login-secure">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 10h12v10H6z"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/></svg>
                Seguro y confiable
            </span>
        </footer>
    </main>

    <script>
        const passwordToggle = document.querySelector('.public-login-eye');
        const passwordInput = document.getElementById('login-password');

        passwordToggle?.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    </script>
</body>
</html>
