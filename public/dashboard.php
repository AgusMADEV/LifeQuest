<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/Habit.php';
require_once __DIR__ . '/../app/Models/AreaProgression.php';
require_once __DIR__ . '/../app/Models/DailyObjective.php';
require_once __DIR__ . '/../app/Support/StreakWeek.php';
require_once __DIR__ . '/../app/Support/XpEvolutionChart.php';
require_once __DIR__ . '/../app/Support/AvatarLibrary.php';

AuthController::requireAuth();

$userModel = new User();
$user = $userModel->findById((int) $_SESSION['user_id']);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$lifeAreaModel = new LifeArea();
$areas = array_slice($lifeAreaModel->getAllByUser((int) $user['id']), 0, 6);

$goalModel = new Goal();
$mainGoals = $goalModel->getMainByUser((int) $user['id'], 4);

$projectModel = new Project();
$activeProjects = $projectModel->getActiveByUser((int) $user['id'], 4);

$taskModel = new Task();
$habitModel = new Habit();
$dailyObjectiveModel = new DailyObjective();
$todayTasks = $taskModel->getTodayByUser((int) $user['id'], 4);
$upcomingTasks = $taskModel->getUpcomingByUser((int) $user['id'], 5);
$taskDistribution = $taskModel->getDistributionByArea((int) $user['id']);
$weekActivity = buildWeeklyActivityByUser((int) $user['id']);

$chartTasks = $taskModel->getAllByUser((int) $user['id']);
$chartHabits = $habitModel->getAllByUser((int) $user['id']);

$weekStartDate = (new DateTimeImmutable('monday this week'))->setTime(0, 0);
$weekEndDate = $weekStartDate->modify('+6 days')->setTime(23, 59, 59);
$todayDateKey = (new DateTimeImmutable('today'))->format('Y-m-d');

$habitLogs = $habitModel->getLogsByRange(
    (int) $user['id'],
    $weekStartDate->format('Y-m-d'),
    $weekEndDate->format('Y-m-d')
);
$weeklyObjectives = $dailyObjectiveModel->getByRange(
    (int) $user['id'],
    $weekStartDate->format('Y-m-d'),
    $weekEndDate->format('Y-m-d')
);

$lineChartWidth = 420;
$lineChartHeight = 190;
$axisStep = 250;

$xpChart = XpEvolutionChart::build(
    $chartTasks,
    $chartHabits,
    $habitLogs,
    $weeklyObjectives,
    $weekStartDate,
    $weekEndDate,
    'week',
    $todayDateKey,
    $lineChartWidth,
    $lineChartHeight,
    $axisStep
);

$weeklyXpGain = $xpChart['periodXpGain'];
$linePadX = $xpChart['linePadX'];
$linePadTop = $xpChart['linePadTop'];
$linePadBottom = $xpChart['linePadBottom'];
$axisTicks = $xpChart['axisTicks'];
$lineCoords = $xpChart['lineCoords'];
$linePolyline = $xpChart['linePolyline'];
$futureLinePolyline = $xpChart['futureLinePolyline'];
$lineAreaPath = $xpChart['lineAreaPath'];
$futureAreaPath = $xpChart['futureAreaPath'];
$futureAreaStartX = $xpChart['futureAreaStartX'];
$futureAreaEndX = $xpChart['futureAreaEndX'];
$chartTotalXp = $xpChart['chartTotalXp'];

$xpCurrent = (int) $user['xp'];
$level = max(1, (int) $user['level']);
$xpPerLevel = 1000;
$xpFloor = ($level - 1) * $xpPerLevel;
$xpCurrentLevel = max(0, $xpCurrent - $xpFloor);
$xpPercent = min(100, (int) (($xpCurrentLevel / max(1, $xpPerLevel)) * 100));
$xpNext = $level * $xpPerLevel;
$points = (int) $user['points'];
$gems = max(0, intdiv($points, 20));
$currentStreak = (int) $user['current_streak'];
$completedTasks = 0;
$focusedMinutes = 0;

foreach ($chartTasks as $task) {
    if ((string) ($task['status'] ?? '') !== 'completed') {
        continue;
    }

    $completedTasks++;
    $focusedMinutes += max(0, (int) ($task['estimated_minutes'] ?? 0));
}

$focusHours = intdiv($focusedMinutes, 60);
$focusRemainderMinutes = $focusedMinutes % 60;
$focusLabel = $focusHours > 0
    ? $focusHours . 'h ' . str_pad((string) $focusRemainderMinutes, 2, '0', STR_PAD_LEFT) . 'm'
    : $focusRemainderMinutes . 'm';

$hpSystemEnabled = defined('FEATURE_HP_SYSTEM') ? (bool) FEATURE_HP_SYSTEM : false;
$baseHp = defined('PLAYER_BASE_HP') ? (int) PLAYER_BASE_HP : 1000;
$maxHp = max(1, (int) ($user['max_hp'] ?? $baseHp));
$hp = max(0, min($maxHp, (int) ($user['hp'] ?? $maxHp)));
$hpPercent = (int) round(($hp / max(1, $maxHp)) * 100);
$areaProgressionEnabled = defined('FEATURE_AREA_PROGRESSION') ? (bool) FEATURE_AREA_PROGRESSION : false;
$areaLevels = [];

if ($areaProgressionEnabled) {
    $areaProgressionModel = new AreaProgression();
    $areaLevels = $areaProgressionModel->getTopByUser((int) $user['id'], 4);
}

$dailyCompleted = count(array_filter($todayTasks, static fn($task) => ($task['status'] ?? '') === 'completed'));
$dailyTotal = max(4, count($todayTasks));
$objectivePercent = (int) (($dailyCompleted / max(1, $dailyTotal)) * 100);

// Calcular recompensa XP del objetivo diario
$dailyTotalXp = array_sum(array_map(static fn($task) => (int) ($task['xp_reward'] ?? 0), $todayTasks));
$dailyBonusXp = max(100, (int) round($dailyTotalXp * 0.25)); // Bonus del 25% del XP total (mínimo 100 XP)
$dailyBonusXp = min($dailyBonusXp, 500); // Máximo 500 XP de bonus

// Verificar si el objetivo ya se completó hoy
$objectiveCompletedToday = $dailyObjectiveModel->isCompletedToday((int) $user['id']);
$todayObjective = $dailyObjectiveModel->getTodayObjective((int) $user['id']);

// Si ya se completó, usar los valores reales
if ($objectiveCompletedToday && $todayObjective) {
    $dailyCompleted = (int) $todayObjective['tasks_completed'];
    $dailyTotal = (int) $todayObjective['tasks_required'];
    $dailyBonusXp = (int) $todayObjective['xp_bonus_awarded'];
    $objectivePercent = 100;
}

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function areaIconMaskUrl(string|null $iconValue): ?string
{
    $iconValue = trim((string) $iconValue);

    if ($iconValue === '') {
        return null;
    }

    $baseName = pathinfo($iconValue, PATHINFO_FILENAME);
    $svgFile = $baseName . '.svg';
    $svgPath = __DIR__ . '/../icons/areas_svg/' . $svgFile;
    if (is_file($svgPath)) {
        return '../icons/areas_svg/' . rawurlencode($svgFile);
    }

    $pngPath = __DIR__ . '/../icons/areas/' . $iconValue;
    if (is_file($pngPath)) {
        return '../icons/areas/' . rawurlencode($iconValue);
    }

    return null;
}

function statusLabelDashboard(string $status): string
{
    return [
        'not_started' => 'No iniciada',
        'in_progress' => 'En progreso',
        'paused' => 'Pausada',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ][$status] ?? $status;
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);
    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, $limit - 1) . '…';
}

function buildDonutGradient(array $distribution): string
{
    if (empty($distribution)) {
        return 'conic-gradient(#e2e8f0 0 100%)';
    }

    $gradientParts = [];
    $currentPercent = 0;

    foreach ($distribution as $area) {
        $percentage = (float) ($area['percentage'] ?? 0);
        $color = e($area['area_color'] ?? '#8b5cf6');
        $nextPercent = $currentPercent + $percentage;
        
        $gradientParts[] = "{$color} {$currentPercent}% {$nextPercent}%";
        $currentPercent = $nextPercent;
    }

    return 'conic-gradient(' . implode(', ', $gradientParts) . ')';
}

function hexToRgba(string $hex, float $alpha = 1.0): string
{
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    return "rgba({$r}, {$g}, {$b}, {$alpha})";
}

$stylesCssVersion = (int) (@filemtime(__DIR__ . '/../assets/css/styles.css') ?: time());
$dashboardCssVersion = (int) (@filemtime(__DIR__ . '/../assets/css/modules/dashboard.css') ?: time());
$heroAvatarSrc = AvatarLibrary::getAvatarSrc($user['avatar'] ?? null);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?= $stylesCssVersion ?>">
    <link rel="stylesheet" href="../assets/css/modules/dashboard.css?v=<?= $dashboardCssVersion ?>">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'dashboard'; ?>
        <?php require __DIR__ . '/partials/sidebar_nav.php'; ?>

        <section class="lq-sidebar-card streak">
            <div class="streak-summary">
                <div class="streak-icon" aria-hidden="true">
                    <img src="../icons/flame.png" alt="" class="streak-flame-image">
                </div>
                <div class="streak-copy">
                    <p>Racha actual</p>
                    <strong><?= $currentStreak ?> días</strong>
                    <small>¡Sigue así!</small>
                </div>
            </div>
            <div class="week-dots week-stack">
                <?php foreach ($weekActivity as $day): ?>
                    <div class="week-day" title="<?= e($day['date']) ?>">
                        <span class="week-dot <?= $day['done'] ? 'done' : '' ?>"><?= $day['done'] ? '✓' : '' ?></span>
                        <small class="week-label"><?= $day['label'] ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="lq-sidebar-card unlock">
            <div>
                <strong>¡Desbloquea más!</strong>
                <p>Completa misiones y consigue recompensas exclusivas.</p>
                <a href="shop.php" class="mini-btn">Ver tienda</a>
            </div>
            <span class="bag" aria-hidden="true">
                <img src="../icons/bag.png" alt="" class="bag-image">
            </span>
        </section>

        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <?php $topbarSearchPlaceholder = 'Buscar misiones, hábitos o recompensas...'; ?>
        <?php $topbarShowHp = $hpSystemEnabled; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div class="lq-dashboard-grid">
            <section class="lq-center">
                <section class="hero-panel<?= $hpSystemEnabled ? ' hero-panel--with-hp' : '' ?>">
                    <div class="hero-avatar-wrap">
                        <div class="hero-glow"></div>
                        <div class="hero-avatar">
                            <?php if ($heroAvatarSrc !== null): ?>
                                <img src="<?= e($heroAvatarSrc) ?>" alt="Avatar de <?= e($user['name']) ?>" class="hero-avatar-image">
                            <?php else: ?>
                                <span class="hero-avatar-fallback"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="hero-content">
                        <h1>¡Sigue así, <?= e(shortText($user['name'], 18)) ?>!</h1>
                        <p>Cada misión completada te acerca a tu mejor versión.</p>

                        <div class="hero-stats">
                            <article>
                                <small>
                                    <span class="hero-stat-icon" aria-hidden="true">
                                        <svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="heroLevelXpOuter" x1="4" y1="4" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#3cffb0"/>
                                                    <stop offset="1" stop-color="#0bb86c"/>
                                                </linearGradient>
                                                <linearGradient id="heroLevelXpInner" x1="8" y1="8" x2="24" y2="24" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#2be98a"/>
                                                    <stop offset="1" stop-color="#0e9e4a"/>
                                                </linearGradient>
                                                <radialGradient id="heroLevelXpGlow" cx="16" cy="16" r="16" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#baffc9" stop-opacity=".7"/>
                                                    <stop offset="1" stop-color="#00ffb0" stop-opacity="0"/>
                                                </radialGradient>
                                            </defs>
                                            <polygon points="16,3 29,11 29,25 16,31 3,25 3,11" fill="url(#heroLevelXpOuter)" stroke="#0bb86c" stroke-width="1.5"/>
                                            <polygon points="16,6.5 26,13 26,23 16,28 6,23 6,13" fill="url(#heroLevelXpInner)"/>
                                            <circle cx="16" cy="16" r="10" fill="url(#heroLevelXpGlow)"/>
                                            <g>
                                                <path d="M16 10V21" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                                <path d="M16 10L12.5 14M16 10L19.5 14" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                            </g>
                                            <g opacity=".7">
                                                <circle cx="11" cy="13" r="1.1" fill="#fff"/>
                                                <circle cx="21" cy="12" r="0.7" fill="#fff"/>
                                                <circle cx="19" cy="19" r="0.5" fill="#fff"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="hero-stat-label-text">Nivel</span>
                                </small>
                                <strong><?= $level ?></strong>
                                <span>Camino a nivel <?= $level + 1 ?></span>
                                <div class="mini-progress"><i style="width: <?= $xpPercent ?>%"></i></div>
                                <em><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</em>
                            </article>

                            <article>
                                <small>
                                    <span class="hero-stat-icon" aria-hidden="true">
                                        <svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="heroCurrentXpOuter" x1="4" y1="4" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#3cffb0"/>
                                                    <stop offset="1" stop-color="#0bb86c"/>
                                                </linearGradient>
                                                <linearGradient id="heroCurrentXpInner" x1="8" y1="8" x2="24" y2="24" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#2be98a"/>
                                                    <stop offset="1" stop-color="#0e9e4a"/>
                                                </linearGradient>
                                                <radialGradient id="heroCurrentXpGlow" cx="16" cy="16" r="16" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#baffc9" stop-opacity=".7"/>
                                                    <stop offset="1" stop-color="#00ffb0" stop-opacity="0"/>
                                                </radialGradient>
                                            </defs>
                                            <polygon points="16,3 29,11 29,25 16,31 3,25 3,11" fill="url(#heroCurrentXpOuter)" stroke="#0bb86c" stroke-width="1.5"/>
                                            <polygon points="16,6.5 26,13 26,23 16,28 6,23 6,13" fill="url(#heroCurrentXpInner)"/>
                                            <circle cx="16" cy="16" r="10" fill="url(#heroCurrentXpGlow)"/>
                                            <g>
                                                <path d="M16 10V21" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                                <path d="M16 10L12.5 14M16 10L19.5 14" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                            </g>
                                            <g opacity=".7">
                                                <circle cx="11" cy="13" r="1.1" fill="#fff"/>
                                                <circle cx="21" cy="12" r="0.7" fill="#fff"/>
                                                <circle cx="19" cy="19" r="0.5" fill="#fff"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="hero-stat-label-text">XP actual</span>
                                </small>
                                <strong><?= number_format($xpCurrent, 0, ',', '.') ?></strong>
                                <span><?= number_format(max(0, $xpNext - $xpCurrent), 0, ',', '.') ?> XP para subir</span>
                                <div class="mini-progress"><i style="width: <?= $xpPercent ?>%"></i></div>
                            </article>

                            <article>
                                <small>
                                    <span class="hero-stat-icon" aria-hidden="true">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="heroCoinOuter" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FFE27A"/>
                                                    <stop offset="0.45" stop-color="#FFC93A"/>
                                                    <stop offset="1" stop-color="#F59F00"/>
                                                </linearGradient>
                                                <linearGradient id="heroCoinInner" x1="7" y1="6" x2="17" y2="18" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FFD85C"/>
                                                    <stop offset="1" stop-color="#F08C00"/>
                                                </linearGradient>
                                                <filter id="heroCoinShadow" x="0" y="0" width="24" height="24" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                                    <feDropShadow dx="0" dy="1" stdDeviation="0.8" flood-color="#C76B00" flood-opacity="0.35"/>
                                                </filter>
                                            </defs>
                                            <g filter="url(#heroCoinShadow)">
                                                <circle cx="12" cy="12" r="10" fill="url(#heroCoinOuter)"/>
                                                <circle cx="12" cy="12" r="8.1" fill="url(#heroCoinInner)" stroke="#FFB11A" stroke-width="0.9"/>
                                                <path d="M5.8 7.5C7.1 5.4 9.34 4 11.9 4" stroke="#FFF4BF" stroke-width="1.4" stroke-linecap="round" opacity="0.9"/>
                                                <path d="M12.1 7.1C10.8 7.1 9.9 7.75 9.9 8.72C9.9 9.73 10.87 10.2 12.22 10.58C13.64 10.98 14.4 11.47 14.4 12.58C14.4 13.73 13.42 14.55 12 14.69V15.6C12 15.93 11.73 16.2 11.4 16.2C11.07 16.2 10.8 15.93 10.8 15.6V14.63C9.89 14.48 9.04 13.99 8.49 13.25C8.29 12.98 8.34 12.61 8.61 12.42C8.87 12.22 9.25 12.27 9.44 12.54C9.91 13.18 10.69 13.56 11.47 13.56H12C13 13.56 13.2 12.98 13.2 12.62C13.2 12.05 12.87 11.72 11.9 11.44C10.43 11.02 8.7 10.4 8.7 8.77C8.7 7.43 9.69 6.47 10.8 6.22V5.4C10.8 5.07 11.07 4.8 11.4 4.8C11.73 4.8 12 5.07 12 5.4V6.15C12.78 6.21 13.47 6.48 14.07 6.95C14.33 7.15 14.37 7.53 14.17 7.79C13.97 8.05 13.59 8.09 13.33 7.89C12.96 7.6 12.52 7.43 12.1 7.1Z" fill="#FFF9EA"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="hero-stat-label-text">LifeCoins</span>
                                </small>
                                <strong><?= number_format($points, 0, ',', '.') ?></strong>
                                <span>Úsalos en la tienda</span>
                            </article>

                            <article>
                                <small>
                                    <span class="hero-stat-icon" aria-hidden="true">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="heroGemTopLeft" x1="4.5" y1="5" x2="11" y2="13" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#D8B8FF"/>
                                                    <stop offset="1" stop-color="#A45CFF"/>
                                                </linearGradient>
                                                <linearGradient id="heroGemTopCenter" x1="12" y1="4" x2="12" y2="13" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#C99CFF"/>
                                                    <stop offset="1" stop-color="#A66BFF"/>
                                                </linearGradient>
                                                <linearGradient id="heroGemTopRight" x1="18.5" y1="5" x2="13" y2="13" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#9B4DFF"/>
                                                    <stop offset="1" stop-color="#7B2CF3"/>
                                                </linearGradient>
                                                <linearGradient id="heroGemBottomLeft" x1="4.5" y1="12" x2="12" y2="22" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#8B3FFF"/>
                                                    <stop offset="1" stop-color="#6622D7"/>
                                                </linearGradient>
                                                <linearGradient id="heroGemBottomCenter" x1="12" y1="12" x2="12" y2="23" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#BC84FF"/>
                                                    <stop offset="1" stop-color="#7A35EA"/>
                                                </linearGradient>
                                                <linearGradient id="heroGemBottomRight" x1="19.5" y1="12" x2="12" y2="22" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#6C1DE5"/>
                                                    <stop offset="1" stop-color="#4F0FC0"/>
                                                </linearGradient>
                                                <filter id="heroGemShadow" x="1" y="2" width="22" height="21" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                                    <feDropShadow dx="0" dy="1" stdDeviation="0.8" flood-color="#4E17A8" flood-opacity="0.28"/>
                                                </filter>
                                            </defs>
                                            <g filter="url(#heroGemShadow)">
                                                <path d="M6.2 5H17.8L21 9.1L12 20L3 9.1L6.2 5Z" fill="#9D5CFF"/>
                                                <path d="M6.2 5L3 9.1H8.1L12 5H6.2Z" fill="url(#heroGemTopLeft)"/>
                                                <path d="M12 5L8.1 9.1H15.9L12 5Z" fill="url(#heroGemTopCenter)"/>
                                                <path d="M17.8 5L12 5L15.9 9.1H21L17.8 5Z" fill="url(#heroGemTopRight)"/>
                                                <path d="M3 9.1H8.1L12 20L3 9.1Z" fill="url(#heroGemBottomLeft)"/>
                                                <path d="M8.1 9.1H15.9L12 20L8.1 9.1Z" fill="url(#heroGemBottomCenter)"/>
                                                <path d="M15.9 9.1H21L12 20L15.9 9.1Z" fill="url(#heroGemBottomRight)"/>
                                                <path d="M6.2 5H17.8" stroke="#C794FF" stroke-width="0.7" stroke-linecap="round" opacity="0.9"/>
                                                <path d="M8.1 9.1H15.9" stroke="#C48AFF" stroke-width="0.7" stroke-linecap="round" opacity="0.9"/>
                                                <path d="M6.7 5.8L8.1 9.1L12 5.2" stroke="#F6ECFF" stroke-width="0.9" stroke-linecap="round" stroke-linejoin="round" opacity="0.9"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="hero-stat-label-text">Gemas</span>
                                </small>
                                <strong><?= $gems ?></strong>
                                <span>Para objetos únicos</span>
                            </article>

                            <?php if ($hpSystemEnabled): ?>
                                <article>
                                    <small>
                                        <span class="hero-stat-icon hero-stat-icon--hp" aria-hidden="true">♥</span>
                                        <span class="hero-stat-label-text">Vida</span>
                                    </small>
                                    <strong><?= number_format($hp, 0, ',', '.') ?></strong>
                                    <span><?= number_format($maxHp, 0, ',', '.') ?> HP máximos</span>
                                    <div class="mini-progress"><i style="width: <?= $hpPercent ?>%"></i></div>
                                </article>
                            <?php endif; ?>
                        </div>

                        <div class="hero-bottom">
                            <div class="streak-row">
                                <span class="streak-row-icon" aria-hidden="true">
                                    <img src="../icons/flame.png" alt="" class="streak-flame-image">
                                </span>
                                <div>
                                    <small>Racha actual</small>
                                    <strong><?= $currentStreak ?> días</strong>
                                </div>
                                <div class="week-mini week-stack">
                                    <?php foreach ($weekActivity as $day): ?>
                                        <div class="week-day" title="<?= e($day['date']) ?>">
                                            <span class="week-dot <?= $day['done'] ? 'done' : '' ?>"><?= $day['done'] ? '✓' : '' ?></span>
                                            <small class="week-label"><?= $day['label'] ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="motivation-chip">
                                <strong>¡Increíble disciplina!</strong>
                                <span>Tu constancia te llevará lejos. 🎉</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="lq-card missions-card">
                    <div class="lq-card-header">
                        <h2>Misiones de hoy <span><?= count($todayTasks) ?></span></h2>
                        <a href="goals.php?section=tasks">Ver todas</a>
                    </div>

                    <?php if (empty($todayTasks)): ?>
                        <div class="friendly-empty">
                            <strong>No hay misiones para hoy todavía.</strong>
                            <p>Crea tareas concretas para avanzar en tus retos y metas.</p>
                            <a href="goals.php?section=tasks" class="mini-btn">Crear misión</a>
                        </div>
                    <?php else: ?>
                        <div class="mission-list">
                            <?php foreach ($todayTasks as $task): ?>
                                <?php
                                $done = $task['status'] === 'completed';
                                // Usar datos del área de vida o valores por defecto
                                $areaIconValue = !empty($task['area_icon']) ? (string) $task['area_icon'] : '';
                                $areaIcon = areaIconMaskUrl($areaIconValue);
                                $areaColor = !empty($task['area_color']) ? $task['area_color'] : '#8b5cf6';
                                $areaName = !empty($task['area_name']) ? e(shortText($task['area_name'], 14)) : 'General';
                                
                                // Colores con transparencia para backgrounds
                                $iconBgColor = hexToRgba($areaColor, 0.15);
                                $tagBgColor = hexToRgba($areaColor, 0.12);
                                $tagBorderColor = hexToRgba($areaColor, 0.25);
                                $taskXpIconSuffix = (int) $task['id'];
                                ?>
                                <article class="mission-item">
                                    <div class="mission-item-left">
                                        <label class="check-wrap">
                                            <input type="checkbox" <?= $done ? 'checked' : '' ?> disabled>
                                            <span></span>
                                        </label>

                                        <div class="mission-icon" style="background-color: <?= $iconBgColor ?>;">
                                            <?php if ($areaIcon): ?>
                                                <span class="mission-icon-mask" style="--area-color: <?= e($areaColor) ?>; -webkit-mask-image: url('<?= e($areaIcon) ?>'); mask-image: url('<?= e($areaIcon) ?>');"></span>
                                            <?php else: ?>
                                                <?= e($areaIconValue !== '' ? $areaIconValue : '📋') ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mission-info">
                                            <strong><?= e(shortText($task['title'], 36)) ?></strong>
                                            <small><?= !empty($task['project_title']) ? e(shortText($task['project_title'], 42)) : 'Misión independiente' ?></small>
                                        </div>
                                    </div>
                                    <div class="mission-item-center">               
                                        <span class="mission-tag" style="background-color: <?= $tagBgColor ?>; color: <?= e($areaColor) ?>; border-color: <?= $tagBorderColor ?>;">
                                            <?= $areaName ?>
                                        </span>

                                        <div class="mission-progress">
                                            <small><?= (int) $task['estimated_minutes'] ?> min</small>
                                            <div class="mini-progress"><i style="width: <?= $done ? 100 : 35 ?>%"></i></div>
                                        </div>
                                    </div>
                                    <div class="mission-item-right">
                                        <strong class="reward">
                                            <span class="hero-stat-icon" aria-hidden="true">
                                                <svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <defs>
                                                        <linearGradient id="dashboardRewardXpOuter<?= $taskXpIconSuffix ?>" x1="4" y1="4" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#3cffb0"/>
                                                            <stop offset="1" stop-color="#0bb86c"/>
                                                        </linearGradient>
                                                        <linearGradient id="dashboardRewardXpInner<?= $taskXpIconSuffix ?>" x1="8" y1="8" x2="24" y2="24" gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#2be98a"/>
                                                            <stop offset="1" stop-color="#0e9e4a"/>
                                                        </linearGradient>
                                                        <radialGradient id="dashboardRewardXpGlow<?= $taskXpIconSuffix ?>" cx="16" cy="16" r="16" gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#baffc9" stop-opacity=".7"/>
                                                            <stop offset="1" stop-color="#00ffb0" stop-opacity="0"/>
                                                        </radialGradient>
                                                    </defs>
                                                    <polygon points="16,3 29,11 29,25 16,31 3,25 3,11" fill="url(#dashboardRewardXpOuter<?= $taskXpIconSuffix ?>)" stroke="#0bb86c" stroke-width="1.5"/>
                                                    <polygon points="16,6.5 26,13 26,23 16,28 6,23 6,13" fill="url(#dashboardRewardXpInner<?= $taskXpIconSuffix ?>)"/>
                                                    <circle cx="16" cy="16" r="10" fill="url(#dashboardRewardXpGlow<?= $taskXpIconSuffix ?>)"/>
                                                    <g>
                                                        <path d="M16 10V21" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                                        <path d="M16 10L12.5 14M16 10L19.5 14" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                                    </g>
                                                    <g opacity=".7">
                                                        <circle cx="11" cy="13" r="1.1" fill="#fff"/>
                                                        <circle cx="21" cy="12" r="0.7" fill="#fff"/>
                                                        <circle cx="19" cy="19" r="0.5" fill="#fff"/>
                                                    </g>
                                                </svg>
                                            </span>
                                            +<?= (int) $task['xp_reward'] ?> XP
                                        </strong>
                                        <span class="flag" aria-hidden="true">
                                            <img src="../icons/<?= $done ? 'flag-complete.png' : 'flag.png' ?>" alt="" class="flag-image">
                                        </span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="bottom-widgets">
                    <article class="lq-card compact">
                        <div class="lq-card-header">
                            <h2>Metas del día</h2>
                            <span><?= count($mainGoals) ?>/4</span>
                        </div>

                        <?php if (empty($mainGoals)): ?>
                            <div class="mini-empty">
                                <p>Crea metas para empezar tu camino.</p>
                                <a href="goals.php">Crear meta →</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($mainGoals as $goal): ?>
                                <div class="mini-goal">
                                    <span>🎯</span>
                                    <strong><?= e(shortText($goal['title'], 28)) ?></strong>
                                    <div class="mini-progress"><i style="width: <?= (int) $goal['progress'] ?>%"></i></div>
                                    <small><?= (int) $goal['progress'] ?>%</small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </article>

                    <article class="lq-card compact chart-card">
                        <div class="lq-card-header">
                            <h2>Progreso semanal</h2>
                        </div>
                        <div class="dashboard-weekly-chart">
                            <svg viewBox="0 0 <?= $lineChartWidth ?> <?= $lineChartHeight ?>" aria-label="Gráfico semanal de XP">
                                <defs>
                                    <linearGradient id="dashboardXpLine" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#1ed7a5" stop-opacity="1" />
                                        <stop offset="100%" stop-color="#16c79a" stop-opacity="1" />
                                    </linearGradient>
                                    <linearGradient id="dashboardXpArea" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#16c79a" stop-opacity="0.22" />
                                        <stop offset="100%" stop-color="#16c79a" stop-opacity="0.03" />
                                    </linearGradient>
                                    <?php if ($futureAreaPath !== '' && $futureAreaEndX > $futureAreaStartX): ?>
                                        <linearGradient id="dashboardXpAreaFutureFade" gradientUnits="userSpaceOnUse" x1="<?= $futureAreaStartX ?>" x2="<?= $futureAreaEndX ?>" y1="0" y2="0">
                                            <stop offset="0%" stop-color="#ffffff" stop-opacity="0" />
                                            <stop offset="100%" stop-color="#ffffff" stop-opacity="0.62" />
                                        </linearGradient>
                                    <?php endif; ?>
                                </defs>

                                <?php foreach ($axisTicks as $tick): ?>
                                    <line x1="<?= $linePadX ?>" y1="<?= $tick['y'] ?>" x2="<?= $lineChartWidth - $linePadX ?>" y2="<?= $tick['y'] ?>" class="grid-line"></line>
                                    <text x="8" y="<?= $tick['y'] + 4 ?>" class="y-axis-label"><?= e($tick['label']) ?></text>
                                <?php endforeach; ?>

                                <path d="<?= e($lineAreaPath) ?>" fill="url(#dashboardXpArea)"></path>
                                <?php if ($futureAreaPath !== '' && $futureAreaEndX > $futureAreaStartX): ?>
                                    <path d="<?= e($futureAreaPath) ?>" fill="url(#dashboardXpAreaFutureFade)"></path>
                                <?php endif; ?>
                                <polyline points="<?= e($linePolyline) ?>" fill="none" stroke="url(#dashboardXpLine)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                <?php if ($futureLinePolyline !== ''): ?>
                                    <polyline class="future-line" points="<?= e($futureLinePolyline) ?>" fill="none" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                <?php endif; ?>

                                <?php foreach ($lineCoords as $point): ?>
                                    <circle class="dot" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="3.2"></circle>
                                    <title><?= e($point['label'] . ': ' . number_format((int) $point['value'], 0, ',', '.') . ' XP acumulada') ?></title>
                                <?php endforeach; ?>

                                <?php foreach ($lineCoords as $point): ?>
                                    <text x="<?= $point['x'] ?>" y="<?= $lineChartHeight - 10 ?>" text-anchor="middle" class="axis-label"><?= e($point['label']) ?></text>
                                <?php endforeach; ?>
                            </svg>
                        </div>
                        <strong><?= number_format($chartTotalXp, 0, ',', '.') ?> XP</strong>
                        <small>+<?= number_format($weeklyXpGain, 0, ',', '.') ?> esta semana</small>
                    </article>

                    <article class="lq-card compact summary-card">
                        <div class="lq-card-header">
                            <h2>Resumen general</h2>
                        </div>
                        <div class="summary-mini-grid">
                            <div><span>✅</span><strong><?= number_format($completedTasks, 0, ',', '.') ?></strong><small>Completadas</small></div>
                            <div><span>🪙</span><strong><?= number_format($points, 0, ',', '.') ?></strong><small>LifeCoins</small></div>
                            <div><span>⚡</span><strong><?= number_format($xpCurrent, 0, ',', '.') ?></strong><small>XP</small></div>
                            <div><span>⏱️</span><strong><?= e($focusLabel) ?></strong><small>Enfoque</small></div>
                        </div>
                    </article>
                </section>
            </section>

            <aside class="lq-right">
                <section class="lq-card objective-card<?= $objectiveCompletedToday ? ' objective-completed' : '' ?>">
                    <?php if ($objectiveCompletedToday): ?>
                        <div class="objective-completed-badge">✅ Completado</div>
                    <?php endif; ?>
                    <div class="objective-card-main">
                        <div class="objective-copy">
                            <div class="objective-title-row">
                                <span class="objective-title-icon" aria-hidden="true">
                                    <svg viewBox="0 0 62 62" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
                                        <circle cx="29" cy="35" r="24" stroke="currentColor" stroke-width="4.5"/>
                                        <circle cx="29" cy="35" r="14" stroke="currentColor" stroke-width="4.5"/>
                                        <circle cx="29" cy="35" r="5.5" fill="currentColor"/>
                                        <path d="M33 31L45 19" stroke="currentColor" stroke-width="4.5" stroke-linecap="round"/>
                                        <path d="M44 11V19H52" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M45 19L53 11" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <h2>Objetivo diario</h2>
                            </div>
                            <p><?= $objectiveCompletedToday ? '¡Objetivo cumplido!' : "Completa {$dailyTotal} misiones al día" ?></p>
                        </div>
                        <div class="circle-progress" style="--value: <?= $objectivePercent ?>;">
                            <strong><?= $dailyCompleted ?><span>/<?= $dailyTotal ?></span></strong>
                            <span>misiones</span>
                        </div>
                    </div>
                    <small>
                        <span aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <defs>
                                    <linearGradient id="xpHexOuter" x1="4" y1="4" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#3cffb0"/>
                                        <stop offset="1" stop-color="#0bb86c"/>
                                    </linearGradient>
                                    <linearGradient id="xpHexInner" x1="8" y1="8" x2="24" y2="24" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#2be98a"/>
                                        <stop offset="1" stop-color="#0e9e4a"/>
                                    </linearGradient>
                                    <radialGradient id="xpGlow" cx="16" cy="16" r="16" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#baffc9" stop-opacity=".7"/>
                                        <stop offset="1" stop-color="#00ffb0" stop-opacity="0"/>
                                    </radialGradient>
                                </defs>
                                <polygon points="16,3 29,11 29,25 16,31 3,25 3,11" fill="url(#xpHexOuter)" stroke="#0bb86c" stroke-width="1.5"/>
                                <polygon points="16,6.5 26,13 26,23 16,28 6,23 6,13" fill="url(#xpHexInner)"/>
                                <circle cx="16" cy="16" r="10" fill="url(#xpGlow)"/>
                                <g>
                                    <path d="M16 10V21" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                    <path d="M16 10L12.5 14M16 10L19.5 14" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                </g>
                                <g opacity=".7">
                                    <circle cx="11" cy="13" r="1.1" fill="#fff"/>
                                    <circle cx="21" cy="12" r="0.7" fill="#fff"/>
                                    <circle cx="19" cy="19" r="0.5" fill="#fff"/>
                                </g>
                            </svg>
                    </span> +<?= number_format($dailyBonusXp, 0, ',', '.') ?> XP</small>
                </section>

                <section class="lq-card upcoming-card">
                    <div class="lq-card-header">
                        <h2>Próximas misiones</h2>
                    </div>

                    <?php if (empty($upcomingTasks)): ?>
                        <p class="muted">No hay misiones próximas con fecha de vencimiento. <a href="goals.php?section=tasks">Crea una misión</a></p>
                    <?php else: ?>
                        <?php
                        function formatDaysUntil(string $dueDate): string {
                            $due = new DateTimeImmutable($dueDate);
                            $today = new DateTimeImmutable('today');
                            $diff = $today->diff($due);
                            
                            if ($diff->days === 0) {
                                return 'Hoy';
                            } elseif ($diff->days === 1) {
                                return 'Mañana';
                            } elseif ($diff->days <= 7) {
                                return 'En ' . $diff->days . ' días';
                            } else {
                                return $due->format('d/m');
                            }
                        }
                        
                        function priorityIcon(string $priority): string {
                            return [
                                'low' => '📌',
                                'medium' => '⚡',
                                'high' => '🔥',
                                'critical' => '⚠️',
                            ][$priority] ?? '📋';
                        }
                        ?>
                        
                        <?php foreach (array_slice($upcomingTasks, 0, 5) as $task): ?>
                            <div class="upcoming-item">
                                <span><?= priorityIcon($task['priority']) ?></span>
                                <div>
                                    <strong><?= e(shortText($task['title'], 26)) ?></strong>
                                    <small><?= formatDaysUntil($task['due_date']) ?><?= !empty($task['area_name']) ? ' · ' . e($task['area_name']) : '' ?></small>
                                </div>
                                <em>+<?= (int) $task['xp_reward'] ?> XP</em>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <a href="goals.php?section=tasks" class="center-link">Ver todas las misiones</a>
                </section>

                <?php if ($areaProgressionEnabled): ?>
                    <section class="lq-card area-levels-card">
                        <div class="lq-card-header">
                            <h2>Nivel por áreas</h2>
                            <a href="areas.php">Ver áreas</a>
                        </div>

                        <?php if (empty($areaLevels)): ?>
                            <p class="muted">Completa hábitos o misiones con área para empezar a subir nivel por áreas.</p>
                        <?php else: ?>
                            <div class="area-levels-list">
                                <?php foreach ($areaLevels as $areaLevel): ?>
                                    <?php $areaLevelIcon = areaIconMaskUrl($areaLevel['icon'] ?? null); ?>
                                    <article class="area-level-item">
                                        <?php if ($areaLevelIcon): ?>
                                            <span class="area-level-icon area-level-icon-mask" aria-hidden="true" style="-webkit-mask-image: url('<?= e($areaLevelIcon) ?>'); mask-image: url('<?= e($areaLevelIcon) ?>');"></span>
                                        <?php else: ?>
                                            <span class="area-level-icon" aria-hidden="true"><?= e($areaLevel['icon']) ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= e(shortText($areaLevel['name'], 20)) ?> · Lv <?= (int) $areaLevel['level'] ?></strong>
                                            <div class="mini-progress"><i style="width: <?= (int) $areaLevel['level_percent'] ?>%"></i></div>
                                            <small><?= number_format((int) $areaLevel['level_xp'], 0, ',', '.') ?> / <?= number_format((int) $areaLevel['level_xp_target'], 0, ',', '.') ?> XP</small>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <section class="lq-card shop-card">
                    <div class="lq-card-header">
                        <h2>Tienda destacada</h2>
                        <a href="shop.php">Ver todo</a>
                    </div>
                    <div class="shop-grid">
                        <article class="shop-item neon">
                            <strong>Tema<br>Neon Wave</strong>
                            <span>🪙 500</span>
                        </article>
                        <article class="shop-item ring">
                            <strong>Marco<br>Holo Circle</strong>
                            <span>🪙 250</span>
                        </article>
                        <article class="shop-item mood">
                            <b>NUEVO</b>
                            <strong>Sticker<br>Mood Set</strong>
                            <span>🪙 200</span>
                        </article>
                    </div>
                </section>

                <section class="lq-card donut-card">
                    <div class="lq-card-header">
                        <h2>Distribución de misiones</h2>
                    </div>
                    
                    <?php if (empty($taskDistribution)): ?>
                        <p class="muted">Crea misiones con áreas de vida asignadas para ver tu distribución.</p>
                    <?php else: ?>
                        <div class="donut-wrap">
                            <div class="donut" style="background: radial-gradient(circle, #fff 55%, transparent 56%), <?= buildDonutGradient($taskDistribution) ?>;"></div>
                            <div class="donut-legend">
                                <?php foreach ($taskDistribution as $area): ?>
                                    <span>
                                        <i style="background: <?= e($area['area_color']) ?>;"></i>
                                        <?= e(shortText($area['area_name'], 15)) ?> <?= number_format($area['percentage'], 0) ?>%
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
