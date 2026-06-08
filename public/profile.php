<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/Habit.php';
require_once __DIR__ . '/../app/Models/Badge.php';
require_once __DIR__ . '/../app/Models/Reward.php';
require_once __DIR__ . '/../app/Support/StreakWeek.php';
require_once __DIR__ . '/../app/Support/AvatarLibrary.php';

AuthController::requireAuth();

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

$profileTheme = 'light';
$profileThemeLabel = 'Claro';

$apellidos = trim((string) ($user['apellidos'] ?? ''));
$profileBio = trim((string) ($user['profile_bio'] ?? ''));
$profileBioFallback = 'Apasionado por aprender, mejorar y convertirme en mi mejor version cada dia.';
$displayBio = $profileBio !== '' ? $profileBio : $profileBioFallback;
$storedMotivationalLine = trim((string) ($user['motivational_line'] ?? ''));
$motivationalLine = $storedMotivationalLine;

$profileNotificationsEnabled = !isset($user['profile_notifications_enabled'])
    || (int) $user['profile_notifications_enabled'] === 1;

$rewardModel = new Reward();
$inventoryCosmetics = $rewardModel->getInventoryCosmetics($userId);

$defaultAvatarFile = AvatarLibrary::normalizeAvatar($user['initial_avatar'] ?? null)
    ?? AvatarLibrary::normalizeAvatar($user['avatar'] ?? null)
    ?? AvatarLibrary::getDefaultAvatarFile();
$ownedAvatarOptions = [];
foreach ($inventoryCosmetics as $item) {
    if (strtolower((string) ($item['category'] ?? '')) !== 'avatar') {
        continue;
    }

    $avatarFile = basename(str_replace('\\', '/', (string) ($item['image_path'] ?? '')));
    $normalizedAvatarFile = AvatarLibrary::normalizeAvatar($avatarFile);

    if ($normalizedAvatarFile === null) {
        continue;
    }

    $ownedAvatarOptions[$normalizedAvatarFile] = [
        'file' => $normalizedAvatarFile,
        'label' => trim((string) ($item['name'] ?? $normalizedAvatarFile)),
        'src' => AvatarLibrary::getAvatarSrc($normalizedAvatarFile),
        'owned' => true,
    ];
}

$selectedAvatarFile = AvatarLibrary::normalizeAvatar($user['avatar'] ?? null) ?? $defaultAvatarFile;
$selectedAvatarSrc = AvatarLibrary::getAvatarSrc($selectedAvatarFile);

$avatarOptions = [];
if ($defaultAvatarFile !== null) {
    $avatarOptions[$defaultAvatarFile] = [
        'file' => $defaultAvatarFile,
        'label' => 'Predeterminado',
        'src' => AvatarLibrary::getAvatarSrc($defaultAvatarFile),
        'owned' => true,
    ];
}

foreach ($ownedAvatarOptions as $avatarFile => $avatarOption) {
    $avatarOptions[$avatarFile] = $avatarOption;
}

if ($selectedAvatarFile !== null && !isset($avatarOptions[$selectedAvatarFile])) {
    $avatarOptions[$selectedAvatarFile] = [
        'file' => $selectedAvatarFile,
        'label' => 'Actual',
        'src' => AvatarLibrary::getAvatarSrc($selectedAvatarFile),
        'owned' => true,
    ];
}

$allowedAvatarFiles = array_keys($avatarOptions);

$feedbackKey = '';
$feedbackMessage = null;
$feedbackType = 'success';

if (isset($_GET['profile'])) {
    $feedbackKey = 'profile';
    $feedbackType = (string) ($_GET['profile'] === 'updated' ? 'success' : 'error');
    $feedbackMessage = match ((string) $_GET['profile']) {
        'updated' => 'La configuracion del perfil se ha guardado.',
        'invalid' => 'Revisa los campos de configuracion e intenta de nuevo.',
        'csrf' => 'La sesion de configuracion ha expirado. Vuelve a intentarlo.',
        default => null,
    };
} elseif (isset($_GET['avatar'])) {
    $feedbackKey = 'avatar';
    $feedbackType = (string) ($_GET['avatar'] === 'updated' ? 'success' : 'error');
    $feedbackMessage = match ((string) $_GET['avatar']) {
        'updated' => 'El avatar se ha actualizado.',
        'invalid' => 'No se pudo actualizar el avatar seleccionado.',
        'csrf' => 'La sesion de avatar ha expirado. Vuelve a intentarlo.',
        default => null,
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        $targetQuery = $action === 'update_profile' ? 'profile=csrf' : 'avatar=csrf';
        header('Location: profile.php?' . $targetQuery);
        exit;
    }

    if ($action === 'update_avatar') {
        $avatarFile = AvatarLibrary::normalizeAvatar((string) ($_POST['avatar'] ?? ''));

        if ($avatarFile !== null && in_array($avatarFile, $allowedAvatarFiles, true)) {
            if ($userModel->updateAvatar($userId, $avatarFile)) {
                header('Location: profile.php?avatar=updated');
                exit;
            }
        }

        header('Location: profile.php?avatar=invalid');
        exit;
    }

    if ($action === 'update_profile') {
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $apellidosInput = trim((string) ($_POST['apellidos'] ?? ''));
        $profileBioInput = trim((string) ($_POST['profile_bio'] ?? ''));
        $motivationalLine = trim((string) ($_POST['motivational_line'] ?? ''));
        $notificationsEnabled = isset($_POST['profile_notifications_enabled']);

        $isNameValid = $displayName !== '' && mb_strlen($displayName) <= 100;
        $isApellidosValid = $apellidosInput === '' || mb_strlen($apellidosInput) <= 100;
        $isProfileBioValid = $profileBioInput === '' || mb_strlen($profileBioInput) <= 255;
        $isMotivationalLineValid = $motivationalLine === '' || mb_strlen($motivationalLine) <= 160;

        if ($isNameValid && $isApellidosValid && $isProfileBioValid && $isMotivationalLineValid) {
            $saved = $userModel->updateProfilePreferences(
                $userId,
                $displayName,
                $apellidosInput,
                $profileBioInput,
                $motivationalLine,
                $notificationsEnabled
            );

            if ($saved) {
                header('Location: profile.php?profile=updated');
                exit;
            }
        }

        header('Location: profile.php?profile=invalid');
        exit;
    }
}

$taskModel = new Task();
$habitModel = new Habit();
$badgeModel = new Badge();

$tasks = $taskModel->getAllByUser($userId);
$habits = $habitModel->getAllByUser($userId);

$today = new DateTimeImmutable('today');
$weekStart = (new DateTimeImmutable('monday this week'))->setTime(0, 0);
$weekActivity = buildWeeklyActivityByUser($userId, $weekStart);
$habitLogs = $habitModel->getLogsByRange($userId, '2000-01-01', $today->format('Y-m-d'));

$xpCurrent = (int) ($user['xp'] ?? 0);
$level = max(1, (int) ($user['level'] ?? 1));
$xpPerLevel = 1000;
$xpFloor = ($level - 1) * $xpPerLevel;
$xpCurrentLevel = max(0, $xpCurrent - $xpFloor);
$xpPercent = min(100, (int) (($xpCurrentLevel / max(1, $xpPerLevel)) * 100));
$points = (int) ($user['points'] ?? 0);
$gems = max(0, intdiv($points, 20));
$currentStreak = (int) ($user['current_streak'] ?? 0);
$hpSystemEnabled = defined('FEATURE_HP_SYSTEM') ? (bool) FEATURE_HP_SYSTEM : false;
$baseHp = defined('PLAYER_BASE_HP') ? (int) PLAYER_BASE_HP : 1000;
$maxHp = max(1, (int) ($user['max_hp'] ?? $baseHp));
$hp = max(0, min($maxHp, (int) ($user['hp'] ?? $maxHp)));

$completedTasks = 0;
$focusedMinutes = 0;

foreach ($tasks as $task) {
    if ((string) ($task['status'] ?? '') !== 'completed') {
        continue;
    }

    $completedTasks++;
    $focusedMinutes += max(0, (int) ($task['estimated_minutes'] ?? 0));
}

$completedHabitChecks = 0;
foreach ($habitLogs as $dateMap) {
    $completedHabitChecks += count($dateMap);
}

$bestStreak = $currentStreak;
foreach ($habits as $habit) {
    $bestStreak = max($bestStreak, (int) ($habit['best_streak'] ?? 0), (int) ($habit['current_streak'] ?? 0));
}

$focusHours = intdiv($focusedMinutes, 60);
$focusRemainderMinutes = $focusedMinutes % 60;
$focusLabel = $focusHours > 0
    ? $focusHours . 'h ' . str_pad((string) $focusRemainderMinutes, 2, '0', STR_PAD_LEFT) . 'm'
    : $focusRemainderMinutes . 'm';

if ($motivationalLine === '') {
    $motivationalLine = $completedTasks > 0 || $completedHabitChecks > 0
        ? 'Un 1% mejor cada dia.'
        : 'Hoy es un gran dia para empezar.';
}

$displayName = e(shortText($user['name'] ?? 'Usuario', 18));

$badges = $badgeModel->syncAndGetByUser($userId, [
    'completed_tasks' => $completedTasks,
    'completed_habit_checks' => $completedHabitChecks,
    'best_streak' => $bestStreak,
    'focused_minutes' => $focusedMinutes,
    'level' => $level,
]);

$unlockedBadges = count(array_filter($badges, static fn(array $badge): bool => !empty($badge['unlocked'])));

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);

    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '...';
}

function badgeProgressLabel(array $badge): string
{
    if (!empty($badge['unlocked'])) {
        $earnedAt = trim((string) ($badge['earned_at'] ?? ''));
        if ($earnedAt === '') {
            return 'Desbloqueada';
        }

        try {
            $date = new DateTimeImmutable($earnedAt);
            return 'Desbloqueada: ' . $date->format('d/m/Y');
        } catch (Throwable) {
            return 'Desbloqueada';
        }
    }

    $value = max(0, (int) ($badge['progress_value'] ?? 0));
    $target = max(1, (int) ($badge['target'] ?? 1));
    $metric = trim((string) ($badge['metric_label'] ?? ''));

    return $value . '/' . $target . ($metric !== '' ? ' ' . $metric : '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/profile.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'profile'; ?>
        <?php require __DIR__ . '/partials/sidebar_nav.php'; ?>

        <section class="lq-sidebar-card streak">
            <div class="streak-summary">
                <div class="streak-icon" aria-hidden="true">
                    <img src="../icons/flame.png" alt="" class="streak-flame-image">
                </div>
                <div class="streak-copy">
                    <p>Racha actual</p>
                    <strong><?= $currentStreak ?> días</strong>
                    <small>Tu evolucion sigue en marcha.</small>
                </div>
            </div>
            <div class="week-dots week-stack">
                <?php foreach ($weekActivity as $day): ?>
                    <div class="week-day" title="<?= e($day['date']) ?>">
                        <span class="week-dot <?= $day['done'] ? 'done' : '' ?>"><?= $day['done'] ? '✓' : '' ?></span>
                        <small class="week-label"><?= e($day['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main profile-main">
        <?php $topbarSearchPlaceholder = 'Buscar perfil, estadísticas o insignias...'; ?>
        <?php $topbarShowHp = $hpSystemEnabled; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <?php if ($feedbackMessage !== null): ?>
            <div class="lq-alert <?= e($feedbackType) ?> profile-feedback" role="status" aria-live="polite">
                <?= e($feedbackMessage) ?>
            </div>
        <?php endif; ?>

        <section class="profile-shell">
            <section class="profile-grid-top">
                <article class="profile-card identity-card">
                    <div class="identity-main">
                        <div class="hero-avatar" aria-hidden="true">
                            <?php if ($selectedAvatarSrc !== null): ?>
                                <img src="<?= e($selectedAvatarSrc) ?>" alt="" class="hero-avatar-image">
                            <?php else: ?>
                                <span class="hero-avatar-fallback"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="identity-header">
                                <h1><?= e(shortText(trim(($user['name'] ?? '') . ' ' . ($user['apellidos'] ?? ''), ' '), 32)) ?></h1>
                                <button class="edit-avatar" type="button" aria-label="Editar perfil" data-open-profile-modal>✎</button>
                            </div>
                            <div class="level-line">
                                <strong>Nivel <?= $level ?></strong>
                                <span class="level-badge"><?= $level ?></span>
                                <div class="xp-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $xpPercent ?>">
                                    <i style="width: <?= $xpPercent ?>%"></i>
                                </div>
                                <small><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</small>
                            </div>
                            <p class="identity-bio"><?= e(shortText($displayBio, 120)) ?></p>
                        </div>
                    </div>
                    <div class="identity-stats">
                        <article>
                            <small>LifeCoins</small>
                            <strong><?= number_format($points, 0, ',', '.') ?></strong>
                        </article>
                        <article>
                            <small>Gemas</small>
                            <strong><?= $gems ?></strong>
                        </article>
                        <article>
                            <small>XP total</small>
                            <strong><?= number_format($xpCurrent, 0, ',', '.') ?></strong>
                        </article>
                        <?php if ($hpSystemEnabled): ?>
                            <article>
                                <small>Vida</small>
                                <strong><?= number_format($hp, 0, ',', '.') ?>/<?= number_format($maxHp, 0, ',', '.') ?></strong>
                            </article>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="profile-card stats-card">
                    <div class="card-head-row">
                        <h2>Estadisticas personales</h2>
                    </div>
                    <div class="stats-list">
                        <article><span>✅ Misiones completadas</span><strong><?= $completedTasks ?></strong></article>
                        <article><span>💚 Habitos completados</span><strong><?= $completedHabitChecks ?></strong></article>
                        <article><span>🔥 Dias de racha mas larga</span><strong><?= $bestStreak ?> dias</strong></article>
                        <article><span>⏱ Tiempo enfocado total</span><strong><?= e($focusLabel) ?></strong></article>
                    </div>
                </article>

            </section>

            <section class="profile-grid-bottom">
                <article class="profile-card badges-card">
                    <div class="card-head-row">
                        <h2>Insignias <span class="badges-counter"><?= $unlockedBadges ?>/<?= count($badges) ?> desbloqueadas</span></h2>
                        <button type="button" class="link-like-btn" data-open-badges-modal>Ver todas</button>
                    </div>
                    <div class="badge-row">
                        <?php foreach ($badges as $badge): ?>
                            <article class="badge-item<?= !empty($badge['unlocked']) ? ' unlocked' : ' locked' ?>">
                                <div class="badge-medal <?= e($badge['tone']) ?>" aria-hidden="true"><?= e($badge['icon']) ?></div>
                                <strong><?= e($badge['title']) ?></strong>
                                <small><?= e(badgeProgressLabel($badge)) ?></small>
                                <?php if (empty($badge['unlocked'])): ?>
                                    <div class="badge-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) ($badge['progress_percent'] ?? 0) ?>">
                                        <i style="width: <?= (int) ($badge['progress_percent'] ?? 0) ?>%"></i>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="profile-card personalization-card">
                    <div class="card-head-row">
                        <h2>Perfil</h2>
                        <span class="card-head-note">Guardado en tu perfil</span>
                    </div>
                    <div class="settings-preview">
                        <article>
                            <span>👤 Nombre de usuario</span>
                            <strong><?= e((string) ($user['name'] ?? '')) ?></strong>
                        </article>
                        <article>
                            <span>🧾 Apellidos</span>
                            <strong><?= e($apellidos !== '' ? $apellidos : 'Sin definir') ?></strong>
                        </article>
                        <article>
                            <span>📝 Bio del perfil</span>
                            <strong><?= e(shortText($displayBio, 26)) ?></strong>
                        </article>
                        <article>
                            <span>❝ Frase motivacional</span>
                            <strong><?= e(shortText($motivationalLine, 26)) ?></strong>
                        </article>
                        <article>
                            <span>🔔 Notificaciones</span>
                            <strong class="<?= $profileNotificationsEnabled ? 'ok' : 'off' ?>"><?= $profileNotificationsEnabled ? 'Activadas' : 'Desactivadas' ?></strong>
                        </article>
                    </div>
                </article>

                <article class="profile-card connections-card">
                    <div class="card-head-row">
                        <h2>Conexiones</h2>
                    </div>
                    <p class="card-sub">Conecta tu cuenta y sincroniza tu progreso.</p>
                    <div class="connect-list">
                        <article>
                            <span class="connect-name"><i class="brand-dot google">G</i>Google</span>
                            <button type="button">Conectar</button>
                        </article>
                        <article>
                            <span class="connect-name"><i class="brand-dot apple"></i>Apple</span>
                            <button type="button">Conectar</button>
                        </article>
                        <article>
                            <span class="connect-name"><i class="brand-dot discord">D</i>Discord</span>
                            <button type="button">Conectar</button>
                        </article>
                    </div>
                </article>
            </section>
        </section>
    </main>

    <div class="profile-modal-overlay" id="badges-modal" hidden>
        <div class="profile-modal-card" role="dialog" aria-modal="true" aria-labelledby="badges-modal-title">
            <div class="profile-modal-head">
                <h2 id="badges-modal-title">Todas tus insignias</h2>
                <button type="button" class="profile-modal-close" data-close-badges-modal aria-label="Cerrar modal">×</button>
            </div>

            <p class="profile-modal-sub"><?= $unlockedBadges ?>/<?= count($badges) ?> desbloqueadas</p>

            <div class="profile-modal-grid">
                <?php foreach ($badges as $badge): ?>
                    <article class="profile-modal-badge<?= !empty($badge['unlocked']) ? ' unlocked' : ' locked' ?>">
                        <div class="badge-medal <?= e($badge['tone']) ?>" aria-hidden="true"><?= e($badge['icon']) ?></div>
                        <strong><?= e($badge['title']) ?></strong>
                        <small><?= e((string) ($badge['description'] ?? '')) ?></small>
                        <span class="profile-modal-badge-meta"><?= e(badgeProgressLabel($badge)) ?></span>
                        <?php if (empty($badge['unlocked'])): ?>
                            <div class="badge-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) ($badge['progress_percent'] ?? 0) ?>">
                                <i style="width: <?= (int) ($badge['progress_percent'] ?? 0) ?>%"></i>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="profile-modal-overlay" id="profile-modal" hidden>
        <div class="profile-modal-card profile-settings-modal" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">
            <div class="profile-modal-head">
                <h2 id="settings-modal-title">Editar perfil</h2>
                <button type="button" class="profile-modal-close" data-close-profile-modal aria-label="Cerrar perfil">×</button>
            </div>

            <p class="profile-modal-sub">Actualiza tu avatar, nombre, apellidos, bio y frase personal desde un solo lugar.</p>

            <div class="profile-modal-grid">
                <div class="profile-modal-panel profile-modal-avatar-panel">
                    <div class="card-head-row">
                        <h2>Avatar</h2>
                        <span><?= count($avatarOptions) ?> disponibles</span>
                    </div>
                    <form class="avatar-selector-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_avatar">
                        <div class="avatar-options" role="list">
                            <?php foreach ($avatarOptions as $avatar): ?>
                                    <?php
                                    $isCurrentAvatar = $avatar['file'] === $selectedAvatarFile;
                                    $isOwnedAvatar = !empty($avatar['owned']);
                                    ?>
                                <button
                                    class="avatar-option<?= $isCurrentAvatar ? ' active' : '' ?>"
                                    type="submit"
                                    name="avatar"
                                    value="<?= e($avatar['file']) ?>"
                                    role="listitem"
                                    aria-label="Seleccionar avatar <?= e($avatar['label']) ?>"
                                >
                                    <span class="avatar-face" aria-hidden="true">
                                        <img src="<?= e($avatar['src']) ?>" alt="" class="avatar-option-image">
                                    </span>
                                    <span class="avatar-name"><?= e($avatar['label']) ?></span>
                                    <?php if ($isCurrentAvatar): ?>
                                        <span class="avatar-tag">Actual</span>
                                    <?php elseif ($isOwnedAvatar): ?>
                                        <span class="avatar-tag">Comprado</span>
                                    <?php else: ?>
                                        <span class="avatar-tag avatar-tag--ghost">Elegir</span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>

                <section class="profile-modal-panel profile-modal-fields-panel">
                    <form class="profile-settings-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <label class="settings-field">
                            <span>👤 Nombre de usuario</span>
                            <input type="text" name="display_name" maxlength="100" value="<?= e((string) ($user['name'] ?? '')) ?>" required>
                            <small>Se mostrara en tu perfil y paneles principales.</small>
                        </label>

                        <label class="settings-field">
                            <span>🧾 Apellidos</span>
                            <input type="text" name="apellidos" maxlength="100" value="<?= e($apellidos) ?>" placeholder="Tus apellidos">
                            <small>Opcional. Se mostrara junto al nombre en el perfil.</small>
                        </label>

                        <label class="settings-field">
                            <span>📝 Bio del perfil</span>
                            <textarea name="profile_bio" maxlength="255" rows="3" placeholder="Escribe una breve descripcion sobre ti."><?= e($profileBio) ?></textarea>
                            <small>Aparece como la descripcion principal de tu perfil.</small>
                        </label>

                        <label class="settings-field">
                            <span>❝ Frase motivacional</span>
                            <textarea name="motivational_line" maxlength="160" rows="3" placeholder="Escribe tu frase o deja este campo vacio para usar la dinamica del progreso."><?= e($storedMotivationalLine) ?></textarea>
                            <small>Si la dejas vacia, el perfil usara una frase dinamica segun tu progreso.</small>
                        </label>

                        <div class="settings-row">
                            <article class="settings-fixed-theme">
                                <span>🎨 Tema de la app</span>
                                <strong><?= e($profileThemeLabel) ?></strong>
                                <small>De momento se mantiene fijo en modo claro.</small>
                            </article>

                            <label class="settings-toggle">
                                <input type="checkbox" name="profile_notifications_enabled" value="1"<?= $profileNotificationsEnabled ? ' checked' : '' ?>>
                                <span>
                                    <strong>🔔 Notificaciones activadas</strong>
                                    <small>Controla si quieres recibir avisos del perfil.</small>
                                </span>
                            </label>
                        </div>

                        <div class="settings-actions">
                            <div class="settings-summary">
                                <strong>Vista actual</strong>
                                <p><?= e($profileThemeLabel) ?> · <?= $profileNotificationsEnabled ? 'Notificaciones activadas' : 'Notificaciones desactivadas' ?></p>
                            </div>
                            <button type="submit" class="settings-save">Guardar cambios</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var overlays = {
                badges: document.getElementById('badges-modal'),
                profile: document.getElementById('profile-modal')
            };
            var openButtons = {
                badges: document.querySelector('[data-open-badges-modal]'),
                profile: document.querySelector('[data-open-profile-modal]')
            };
            var closeButtons = {
                badges: document.querySelector('[data-close-badges-modal]'),
                profile: document.querySelector('[data-close-profile-modal]')
            };
            var activeOverlay = null;

            var openModal = function (overlay) {
                if (!overlay) {
                    return;
                }

                overlay.hidden = false;
                overlay.classList.add('is-open');
                document.body.classList.add('modal-open');
                activeOverlay = overlay;
            };

            var closeModal = function () {
                if (!activeOverlay) {
                    return;
                }

                activeOverlay.hidden = true;
                activeOverlay.classList.remove('is-open');
                activeOverlay = null;
                document.body.classList.remove('modal-open');
            };

            if (openButtons.badges && overlays.badges) {
                openButtons.badges.addEventListener('click', function () {
                    openModal(overlays.badges);
                });
            }

            if (openButtons.profile && overlays.profile) {
                openButtons.profile.addEventListener('click', function () {
                    openModal(overlays.profile);
                });
            }

            if (closeButtons.badges) {
                closeButtons.badges.addEventListener('click', closeModal);
            }

            if (closeButtons.profile) {
                closeButtons.profile.addEventListener('click', closeModal);
            }

            Object.keys(overlays).forEach(function (key) {
                var overlay = overlays[key];

                if (!overlay) {
                    return;
                }

                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) {
                        closeModal();
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && activeOverlay && !activeOverlay.hidden) {
                    closeModal();
                }
            });

            if ((<?= json_encode(($feedbackKey === 'profile' || $feedbackKey === 'avatar') && $feedbackType === 'error') ?>) && overlays.profile) {
                openModal(overlays.profile);
            }
        })();

    </script>
    <script src="../assets/js/app.js"></script>
</body>
</html>
