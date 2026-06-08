<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Support/AvatarLibrary.php';

AuthController::requireAuth();

if (!AuthController::needsAvatarOnboarding()) {
    header('Location: dashboard.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$starterAvatars = AvatarLibrary::getStarterOptions();
$selectedAvatarFile = AvatarLibrary::normalizeAvatar($user['avatar'] ?? null) ?? AvatarLibrary::getDefaultAvatarFile();
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        $message = 'La sesión ha expirado. Vuelve a intentarlo.';
        $messageType = 'error';
    } else {
        $avatarFile = AvatarLibrary::normalizeAvatar((string) ($_POST['avatar'] ?? ''));
        $allowedFiles = array_column($starterAvatars, 'file');

        if ($avatarFile !== null && in_array($avatarFile, $allowedFiles, true) && $userModel->updateAvatar($userId, $avatarFile)) {
            $_SESSION['avatar_setup_completed'] = true;
            header('Location: dashboard.php');
            exit;
        }

        $message = 'Selecciona uno de los avatares iniciales para continuar.';
        $messageType = 'error';
    }
}

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);

    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '...';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Elige tu avatar | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/auth.css">
</head>
<body class="public-login-body avatar-onboarding-body">
    <main class="public-login-page avatar-onboarding-page">
        <header class="public-login-header" aria-label="Configuración inicial LifeQuest">
            <a href="index.php" class="public-login-brand" aria-label="Ir al inicio">
                <span>Life</span><strong>Quest</strong><i aria-hidden="true">++</i>
            </a>
            <span class="public-login-secure">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 2 4 5v6c0 5.5 3.7 9.6 8 11 4.3-1.4 8-5.5 8-11V5l-8-3Z"></path>
                    <path d="M9.5 12 11.5 14l3.5-4"></path>
                </svg>
                Tu cuenta está lista, falta elegir tu avatar
            </span>
        </header>

        <section class="avatar-onboarding-shell" aria-label="Selección inicial de avatar">
            <section class="avatar-onboarding-hero">
                <p class="eyebrow">Primer paso</p>
                <h1>Elige tu primer avatar</h1>
                <p class="hero-text">Este avatar será el que te represente al empezar. Después podrás cambiarlo desde tu perfil o desbloquear otros en la tienda.</p>
                <div class="avatar-onboarding-preview">
                    <div class="avatar-onboarding-badge">Nuevo usuario</div>
                    <div class="avatar-onboarding-card-preview">
                        <?php if ($selectedAvatarFile !== null): ?>
                            <img src="<?= e(AvatarLibrary::getAvatarSrc($selectedAvatarFile) ?? '') ?>" alt="Avatar actual" />
                        <?php endif; ?>
                    </div>
                    <p><?= e(shortText((string) ($user['name'] ?? 'Usuario'), 28)) ?></p>
                </div>
            </section>

            <section class="avatar-onboarding-card">
                <div class="card-head-row">
                    <h2>Avatares iniciales</h2>
                    <span><?= count($starterAvatars) ?> disponibles</span>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= e($messageType) ?> avatar-onboarding-alert"><?= e($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="avatar-onboarding-form">
                    <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">
                    <div class="avatar-onboarding-grid">
                        <?php foreach ($starterAvatars as $avatar): ?>
                            <?php $isSelected = $avatar['file'] === $selectedAvatarFile; ?>
                            <label class="avatar-onboarding-option<?= $isSelected ? ' selected' : '' ?>">
                                <input type="radio" name="avatar" value="<?= e($avatar['file']) ?>" <?= $isSelected ? 'checked' : '' ?> required>
                                <span class="avatar-face" aria-hidden="true">
                                    <img src="<?= e($avatar['src']) ?>" alt="" class="avatar-option-image">
                                </span>
                                <strong><?= e($avatar['label']) ?></strong>
                                <small>Gratis al inicio</small>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="avatar-onboarding-actions">
                        <p>Podrás cambiarlo más tarde desde el perfil o comprar otros en la tienda.</p>
                        <button type="submit" class="btn btn-primary full">Continuar al dashboard</button>
                    </div>
                </form>
            </section>
        </section>
    </main>
</body>
</html>
