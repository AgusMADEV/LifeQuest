<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/Habit.php';
require_once __DIR__ . '/../app/Support/StreakWeek.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$taskModel = new Task();
$habitModel = new Habit();

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

$displayName = e(shortText($user['name'] ?? 'Usuario', 18));
$usernameSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', (string) ($user['name'] ?? 'usuario')) ?: 'usuario');
$motivationalLine = $completedTasks > 0 || $completedHabitChecks > 0
    ? 'Un 1% mejor cada dia.'
    : 'Hoy es un gran dia para empezar.';

$avatarOptions = [
    ['name' => 'Neo', 'emoji' => '🧑', 'tone' => 'tone-green', 'active' => true],
    ['name' => 'Kai', 'emoji' => '👦', 'tone' => 'tone-gray', 'active' => false],
    ['name' => 'Lia', 'emoji' => '👧', 'tone' => 'tone-lavender', 'active' => false],
    ['name' => 'Ezra', 'emoji' => '🧒', 'tone' => 'tone-blue', 'active' => false],
    ['name' => 'Nora', 'emoji' => '👩', 'tone' => 'tone-gold', 'active' => false],
];

$badges = [
    ['title' => 'Primer paso', 'xp' => '+50 XP', 'icon' => '👟', 'tone' => 'tone-green'],
    ['title' => 'Constancia', 'xp' => '+100 XP', 'icon' => '🔥', 'tone' => 'tone-orange'],
    ['title' => 'Estudioso', 'xp' => '+150 XP', 'icon' => '📘', 'tone' => 'tone-purple'],
    ['title' => 'Enfoque total', 'xp' => '+200 XP', 'icon' => '🎯', 'tone' => 'tone-blue'],
    ['title' => 'Imparable', 'xp' => '+250 XP', 'icon' => '🏆', 'tone' => 'tone-amber'],
];

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
            <div class="streak-icon">🔥</div>
            <p>Racha actual</p>
            <strong><?= $currentStreak ?> días</strong>
            <small>Tu evolucion sigue en marcha.</small>
            <div class="week-dots week-stack">
                <?php foreach ($weekActivity as $day): ?>
                    <div class="week-day" title="<?= e($day['date']) ?>">
                        <span class="week-dot <?= $day['done'] ? 'done' : '' ?>"><?= $day['done'] ? '✓' : '' ?></span>
                        <small class="week-label"><?= e($day['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main profile-main">
        <header class="lq-topbar profile-topbar">
            <div></div>
            <div class="profile-top-stats">
                <div class="currency-pill coin"><span>🪙</span><strong><?= number_format($points, 0, ',', '.') ?></strong></div>
                <div class="currency-pill gem"><span>💎</span><strong><?= $gems ?></strong></div>
                <?php if ($hpSystemEnabled): ?>
                    <div class="currency-pill hp"><span>❤️</span><strong><?= number_format($hp, 0, ',', '.') ?>/<?= number_format($maxHp, 0, ',', '.') ?></strong></div>
                <?php endif; ?>
                <button class="notify-pill" type="button" aria-label="Notificaciones">🔔</button>
                <div class="profile-pill">
                    <div class="mini-avatar image-like"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
                    <strong>¡Hola, <?= $displayName ?>!</strong>
                </div>
            </div>
        </header>

        <section class="profile-shell">
            <section class="profile-grid-top">
                <article class="profile-card identity-card">
                    <div class="identity-main">
                        <div class="hero-avatar" aria-hidden="true"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
                        <div>
                            <div class="identity-header">
                                <h1><?= e(shortText($user['name'] ?? 'Usuario', 20)) ?></h1>
                                <button class="edit-avatar" type="button" aria-label="Editar avatar">✎</button>
                            </div>
                            <div class="level-line">
                                <strong>Nivel <?= $level ?></strong>
                                <span class="level-badge"><?= $level ?></span>
                                <div class="xp-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $xpPercent ?>">
                                    <i style="width: <?= $xpPercent ?>%"></i>
                                </div>
                                <small><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</small>
                            </div>
                            <p class="identity-bio">Apasionado por aprender, mejorar y convertirme en mi mejor version cada dia.</p>
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

                <article class="profile-card avatars-card">
                    <div class="card-head-row">
                        <h2>Avatares</h2>
                        <a href="#">Ver todos</a>
                    </div>
                    <div class="avatar-carousel">
                        <button class="carousel-arrow" type="button" aria-label="Anterior">‹</button>
                        <div class="avatar-options" role="list">
                            <?php foreach ($avatarOptions as $avatar): ?>
                                <article class="avatar-option <?= e($avatar['tone']) ?><?= $avatar['active'] ? ' active' : '' ?>" role="listitem">
                                    <div class="avatar-face" aria-hidden="true"><?= e($avatar['emoji']) ?></div>
                                    <?php if ($avatar['active']): ?>
                                        <span class="avatar-tag">Actual</span>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-arrow" type="button" aria-label="Siguiente">›</button>
                    </div>
                </article>
            </section>

            <section class="profile-grid-bottom">
                <article class="profile-card badges-card">
                    <div class="card-head-row">
                        <h2>Insignias</h2>
                        <a href="#">Ver todas</a>
                    </div>
                    <div class="badge-row">
                        <?php foreach ($badges as $badge): ?>
                            <article class="badge-item">
                                <div class="badge-medal <?= e($badge['tone']) ?>" aria-hidden="true"><?= e($badge['icon']) ?></div>
                                <strong><?= e($badge['title']) ?></strong>
                                <small><?= e($badge['xp']) ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="profile-card personalization-card">
                    <div class="card-head-row">
                        <h2>Personalizacion</h2>
                    </div>
                    <div class="settings-list">
                        <article><span>👤 Nombre de usuario</span><strong><?= e($usernameSlug) ?></strong></article>
                        <article><span>❝ Frase motivacional</span><strong><?= e(shortText($motivationalLine, 24)) ?></strong></article>
                        <article><span>🎨 Tema de la app</span><strong>Claro</strong></article>
                        <article><span>🔔 Notificaciones</span><strong class="ok">Activadas</strong></article>
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
</body>
</html>
