<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/HabitController.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/User.php';
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

$habitController = new HabitController();
$lifeAreaModel = new LifeArea();
$goalModel = new Goal();

$allowedTabs = ['positive', 'control', 'stats', 'discover'];
$allowedPeriods = ['week', 'month'];
$mainHabitTabs = ['positive', 'control'];

$tabInput = (string) ($_GET['tab'] ?? 'positive');
$periodInput = (string) ($_GET['period'] ?? 'week');

$tab = in_array($tabInput, $allowedTabs, true) ? $tabInput : 'positive';
$period = in_array($periodInput, $allowedPeriods, true) ? $periodInput : 'week';
$activeHabitTab = in_array($tab, $mainHabitTabs, true) ? $tab : 'positive';

$message = null;
$messageType = null;
$habitFormData = [];
$habitModalShouldOpen = false;
$habitModalMode = 'create';

function habitFormValue(array $data, string $key, string $default = ''): string
{
    return e((string) ($data[$key] ?? $default));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $habitFormData = $_POST;
    $habitModalMode = $action === 'update' ? 'edit' : 'create';

    if ($action === 'create') {
        $result = $habitController->store($userId, $_POST);
    } elseif ($action === 'update') {
        $result = $habitController->update($userId, (int) ($_POST['habit_id'] ?? 0), $_POST);
    } elseif ($action === 'delete') {
        $result = $habitController->delete($userId, (int) ($_POST['habit_id'] ?? 0));
    } elseif ($action === 'toggle_today') {
        $result = $habitController->toggleToday(
            $userId,
            (int) ($_POST['habit_id'] ?? 0),
            isset($_POST['status']) ? (string) $_POST['status'] : null
        );
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida.'];
    }

    $message = (string) ($result['message'] ?? '');
    $messageType = !empty($result['success']) ? 'success' : 'error';

    if (!empty($result['success'])) {
        $postTab = in_array((string) ($_POST['current_tab'] ?? $tab), $allowedTabs, true)
            ? (string) ($_POST['current_tab'] ?? $tab)
            : 'positive';
        $postPeriod = in_array((string) ($_POST['current_period'] ?? $period), $allowedPeriods, true)
            ? (string) ($_POST['current_period'] ?? $period)
            : 'week';

        $redirect = 'habits.php?tab=' . urlencode($postTab)
            . '&period=' . urlencode($postPeriod)
            . '&message=' . urlencode($message)
            . '&type=' . urlencode($messageType);

        $badgeToasts = $_SESSION['badge_unlock_toasts'] ?? [];
        if (is_array($badgeToasts) && !empty($badgeToasts)) {
            $payload = base64_encode((string) json_encode($badgeToasts));
            if ($payload !== '') {
                $redirect .= '&badge_toasts=' . urlencode($payload);
            }
        }

        header('Location: ' . $redirect);
        exit;
    }

    if (in_array($action, ['create', 'update'], true)) {
        $habitModalShouldOpen = true;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = (string) $_GET['message'];
    $messageType = (string) $_GET['type'];
}

$habits = $habitController->index($userId);
$stats = $habitController->stats($userId);
$areas = $lifeAreaModel->getAllByUser($userId);
$goals = $goalModel->getAllByUser($userId);

$positiveHabits = [];
$controlHabits = [];

foreach ($habits as $habit) {
    if (!empty($habit['is_negative'])) {
        $controlHabits[] = $habit;
    } else {
        $positiveHabits[] = $habit;
    }
}

$visibleHabits = $activeHabitTab === 'control' ? $controlHabits : $positiveHabits;

$periodStartDate = $period === 'month'
    ? new DateTimeImmutable('first day of this month')
    : new DateTimeImmutable('monday this week');
$periodEndDate = $period === 'month'
    ? new DateTimeImmutable('last day of this month')
    : new DateTimeImmutable('sunday this week');

$periodStart = $periodStartDate->format('Y-m-d');
$periodEnd = $periodEndDate->format('Y-m-d');
$periodLabel = $period === 'month' ? 'Este mes' : 'Esta semana';
$periodDates = [];

for ($cursor = $periodStartDate; $cursor <= $periodEndDate; $cursor = $cursor->modify('+1 day')) {
    $periodDates[] = $cursor->format('Y-m-d');
}

$rangeLogs = $habitController->logsByRange($userId, $periodStart, $periodEnd);

$weekStart = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
$weekEnd = (new DateTimeImmutable('sunday this week'))->format('Y-m-d');
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$weekDates = [];

for ($i = 0; $i < 7; $i++) {
    $weekDates[] = (new DateTimeImmutable($weekStart))->modify('+' . $i . ' day')->format('Y-m-d');
}

$weekLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
$weekLogs = $habitController->weekLogs($userId, $weekStart, $weekEnd);
$weekActivity = buildWeeklyActivityByUser($userId, new DateTimeImmutable($weekStart));

$currentStreak = (int) ($user['current_streak'] ?? 0);
$completedPeriod = 0;
$fullPeriodHabits = 0;
$partialPeriodHabits = 0;
$emptyPeriodHabits = 0;
$periodDailyTotals = array_fill_keys($periodDates, 0);
$habitPeriodHits = [];

$xpCurrent = (int) ($user['xp'] ?? 0);
$level = max(1, (int) ($user['level'] ?? 1));
$xpPerLevel = 1000;
$xpFloor = ($level - 1) * $xpPerLevel;
$xpCurrentLevel = max(0, $xpCurrent - $xpFloor);
$xpPercent = min(100, (int) (($xpCurrentLevel / max(1, $xpPerLevel)) * 100));
$habitAvatarSrc = AvatarLibrary::getAvatarSrc($user['avatar'] ?? null);

foreach ($habits as $habit) {
    $habitId = (int) ($habit['id'] ?? 0);
    $completedHits = 0;
    $partialHits = 0;

    foreach ($periodDates as $date) {
        $status = (string) ($rangeLogs[$habitId][$date] ?? '');

        if ($status === 'completed') {
            $completedPeriod++;
            $completedHits++;
            $periodDailyTotals[$date] = (int) ($periodDailyTotals[$date] ?? 0) + 1;
        } elseif ($status === 'partial') {
            $partialHits++;
        }
    }

    $habitPeriodHits[$habitId] = $completedHits;

    if ($completedHits === 0 && $partialHits === 0) {
        $emptyPeriodHabits++;
    } elseif ($partialHits === 0 && $completedHits === count($periodDates)) {
        $fullPeriodHabits++;
    } else {
        $partialPeriodHabits++;
    }
}

$periodChart = [];
foreach ($periodDailyTotals as $date => $total) {
    $dt = new DateTimeImmutable($date);
    $periodChart[] = [
        'date' => $date,
        'label' => $period === 'month' ? $dt->format('d') : $weekLabels[(int) $dt->format('N') - 1],
        'total' => (int) $total,
    ];
}

$maxChartTotal = 1;
foreach ($periodChart as $item) {
    if ((int) $item['total'] > $maxChartTotal) {
        $maxChartTotal = (int) $item['total'];
    }
}

$habitsRank = [];
foreach ($habits as $habit) {
    $habitId = (int) ($habit['id'] ?? 0);
    $hits = (int) ($habitPeriodHits[$habitId] ?? 0);
    $ratio = count($periodDates) > 0 ? (int) round(($hits / count($periodDates)) * 100) : 0;
    $habitsRank[] = [
        'id' => $habitId,
        'name' => (string) ($habit['name'] ?? ''),
        'hits' => $hits,
        'ratio' => $ratio,
        'streak' => (int) ($habit['current_streak'] ?? 0),
    ];
}

usort($habitsRank, static fn(array $a, array $b): int => $b['ratio'] <=> $a['ratio']);

$activeVisibleCount = count($visibleHabits);
$visiblePossible = max(1, $activeVisibleCount * max(1, count($periodDates)));
$visibleCompleted = 0;
$visiblePartial = 0;
$visibleBestStreak = 0;
$visibleDailyXp = 0;

foreach ($visibleHabits as $habit) {
    $habitId = (int) ($habit['id'] ?? 0);
    $visibleBestStreak = max($visibleBestStreak, (int) ($habit['best_streak'] ?? 0));
    $visibleDailyXp += (int) ($habit['xp_reward'] ?? 0);

    foreach ($periodDates as $date) {
        $status = (string) ($rangeLogs[$habitId][$date] ?? '');
        if ($status === 'completed') {
            $visibleCompleted++;
        } elseif ($status === 'partial') {
            $visiblePartial++;
        }
    }
}

$visibleAveragePerHabit = $activeVisibleCount > 0 ? (int) round($visibleCompleted / max(1, $activeVisibleCount)) : 0;
$visibleXpGain = (int) round(($visibleCompleted * 12) + ($visiblePartial * 4));

$visibleCompletedPct = $visiblePossible > 0 ? (int) round(($visibleCompleted / $visiblePossible) * 100) : 0;
$visiblePartialPct = $visiblePossible > 0 ? (int) round(($visiblePartial / $visiblePossible) * 100) : 0;
$visibleRemainingPct = max(0, 100 - $visibleCompletedPct - $visiblePartialPct);

$tabConfig = [
    'positive' => [
        'title' => 'Hábitos positivos',
        'subtitle' => 'Pequeñas acciones repetidas construyen grandes cambios.',
        'accent' => 'positive',
        'cta' => 'Añadir hábito positivo',
        'metricLabel' => 'Hábitos positivos',
        'metricEmphasis' => 'Constancia viva',
        'streakLabel' => 'Mejor racha',
        'streakSuffix' => 'días',
        'periodHint' => 'Cada día suma',
        'legendCompleted' => 'Hecho',
        'legendPartial' => 'Parcial',
        'legendEmpty' => 'Pendiente',
        'emptyTitle' => 'Aún no tienes hábitos positivos',
        'emptyText' => 'Crea rutinas que quieras repetir cada día y empieza a ver tu racha crecer.',
        'rowLabel' => 'Racha',
        'microcopy' => 'Marca lo que sí quieres cultivar cada día.',
    ],
    'control' => [
        'title' => 'Hábitos en control',
        'subtitle' => 'Pequeñas decisiones que te ayudan a recuperar equilibrio.',
        'accent' => 'control',
        'cta' => 'Añadir hábito en control',
        'metricLabel' => 'Hábitos en control',
        'metricEmphasis' => 'Equilibrio activo',
        'streakLabel' => 'Días en control',
        'streakSuffix' => 'días',
        'periodHint' => 'Cada día en control también cuenta',
        'legendCompleted' => 'Día controlado',
        'legendPartial' => 'Recaída parcial',
        'legendEmpty' => 'Sin registrar',
        'emptyTitle' => 'Aún no tienes hábitos en control',
        'emptyText' => 'Añade hábitos que quieras mantener en equilibrio sin usar un tono de castigo.',
        'rowLabel' => 'En control',
        'microcopy' => 'No se trata de hacerlo perfecto, sino de volver a elegir mejor.',
    ],
];

$currentTabConfig = $tabConfig[$activeHabitTab];

$dayStateUi = [
    'completed' => ['class' => 'is-completed', 'icon' => '✓'],
    'partial' => ['class' => 'is-partial', 'icon' => '◐'],
    'empty' => ['class' => 'is-empty', 'icon' => '○'],
];

$discoverTemplates = [
    ['name' => 'Beber 2L de agua', 'description' => 'Mantén hidratación diaria para energía constante.'],
    ['name' => 'Leer 20 minutos', 'description' => 'Avanza en aprendizaje cada día sin saturarte.'],
    ['name' => 'Entrenar 30 minutos', 'description' => 'Movimiento diario para salud física y mental.'],
    ['name' => 'Plan diario de 5 minutos', 'description' => 'Define foco del día antes de empezar.'],
    ['name' => 'Dormir 8 horas', 'description' => 'Prioriza descanso para rendir mejor mañana.'],
    ['name' => 'Escribir diario breve', 'description' => 'Reflexiona al final del día y ajusta rumbo.'],
];

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);

    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}

function habitEmojiByIndex(int $index): string
{
    $emojis = ['🧘', '💧', '📖', '🏋️', '✍️', '😴', '🌱'];

    return $emojis[$index % count($emojis)];
}

function controlHabitEmojiByIndex(int $index): string
{
    $emojis = ['📵', '🌙', '🎮', '🧁', '☕', '🛒', '📺'];

    return $emojis[$index % count($emojis)];
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

function hexToRgba(string $hex, float $alpha = 1.0): string
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $alpha . ')';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hábitos | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/habits.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'habits'; ?>
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
                        <small class="week-label"><?= e($day['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="lq-sidebar-card habit-side-promo">
            <strong>¡Construye hábitos que cambian tu vida!</strong>
            <p>Cuida de ti cada día y alcanza tu mejor versión.</p>
            <span class="habit-side-promo-icon">🪴</span>
        </section>

        <section class="lq-sidebar-card habit-side-user">
            <div class="mini-avatar">
                <?php if ($habitAvatarSrc !== null): ?>
                    <img src="<?= e($habitAvatarSrc) ?>" alt="" class="mini-avatar-image">
                <?php else: ?>
                    <?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <strong>¡Hola, <?= e(shortText($user['name'] ?? 'Usuario', 12)) ?>!</strong>
                <small>Nivel <?= $level ?></small>
            </div>
            <div class="lq-progress"><span style="width: <?= $xpPercent ?>%"></span></div>
        </section>

        <?php $sidebarUserSubtitle = 'Nivel ' . (int) ($user['level'] ?? 1); ?>
        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <?php $topbarSearchPlaceholder = 'Buscar hábitos...'; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <section class="lq-page-shell habits-shell">
            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <?php if (in_array($tab, $mainHabitTabs, true)): ?>
                <section class="habits-board habits-board--<?= e($activeHabitTab) ?>">
                    <header class="lq-page-hero habits-hero">
                        <div>
                            <p class="eyebrow">Rutinas cotidianas</p>
                            <h1><span class="habit-title-icon" aria-hidden="true">♡</span> Hábitos</h1>
                            <p>Construye rutinas positivas y transforma tu día a día.</p>
                        </div>
                        <div class="habit-hero-controls">
                            <div class="habit-tabs habit-tabs-main">
                                <a href="habits.php?tab=positive&amp;period=<?= e($period) ?>" class="<?= $activeHabitTab === 'positive' ? 'active' : '' ?>"><span aria-hidden="true">♡</span> Hábitos positivos</a>
                                <a href="habits.php?tab=control&amp;period=<?= e($period) ?>" class="<?= $activeHabitTab === 'control' ? 'active' : '' ?>"><span aria-hidden="true">🛡</span> Hábitos en control</a>
                            </div>
                            <form method="GET" class="habit-period-form">
                                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                <select name="period" onchange="this.form.submit()">
                                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Esta semana</option>
                                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Este mes</option>
                                </select>
                            </form>
                            <?php if (in_array($tab, $mainHabitTabs, true)): ?>
                                <button type="button" class="habit-create-btn habit-create-btn--<?= e($activeHabitTab) ?>" data-habit-modal-open><?= e($currentTabConfig['cta']) ?></button>
                            <?php endif; ?>
                        </div>
                    </header>
                    <div class="habits-content-column">
                        <section class="habit-kpis habit-kpis--<?= e($activeHabitTab) ?>">
                            <article class="habit-kpi-card habit-kpi-card--accent">
                                <div class="habit-kpi-visual ring habit-kpi-visual--<?= e($currentTabConfig['accent']) ?>" style="--ring: <?= min(100, (int) round((($visibleCompleted + ($activeHabitTab === 'control' ? $visiblePartial * 0.5 : 0)) / max(1, $visiblePossible)) * 100)) ?>;"></div>
                                <div>
                                    <strong><?= $activeVisibleCount ?>/<?= max(1, count($habits)) ?></strong>
                                    <small><?= e($currentTabConfig['metricLabel']) ?></small>
                                    <em><?= $activeHabitTab === 'control' ? 'Vas genial' : 'Mantén el ritmo' ?></em>
                                </div>
                            </article>
                            <article class="habit-kpi-card">
                                <div class="habit-kpi-visual <?= $activeHabitTab === 'control' ? 'shield' : 'flame' ?>"><?= $activeHabitTab === 'control' ? '🛡️' : '🔥' ?></div>
                                <div>
                                    <strong><?= $visibleAveragePerHabit ?> <?= e($currentTabConfig['streakSuffix']) ?></strong>
                                    <small><?= $activeHabitTab === 'control' ? 'Promedio de control' : 'Promedio de racha' ?></small>
                                    <em><?= $activeHabitTab === 'control' ? 'Esta semana' : 'Este mes' ?></em>
                                </div>
                            </article>
                            <article class="habit-kpi-card">
                                <div class="habit-kpi-visual <?= $activeHabitTab === 'control' ? 'compass' : 'trophy' ?>"><?= $activeHabitTab === 'control' ? '🧭' : '🏆' ?></div>
                                <div>
                                    <strong>+<?= max($visibleDailyXp, $visibleXpGain) ?> XP</strong>
                                    <small><?= $activeHabitTab === 'control' ? 'Fortaleza mental' : 'Energía acumulada' ?></small>
                                    <em><?= $activeHabitTab === 'control' ? 'Sigue así' : 'Cada avance cuenta' ?></em>
                                </div>
                            </article>
                            <article class="habit-kpi-card">
                                <div class="habit-kpi-visual star"><?= $activeHabitTab === 'control' ? '🌿' : '✨' ?></div>
                                <div>
                                    <strong><?= $activeHabitTab === 'control' ? 'Equilibrio' : 'Constancia' ?></strong>
                                    <small><?= e($currentTabConfig['periodHint']) ?></small>
                                    <em><?= $activeHabitTab === 'control' ? 'Cada decisión cuenta' : 'Cada decisión cuenta' ?></em>
                                </div>
                            </article>
                        </section>

                        <article class="habits-main-card">
                            <div class="habit-section-head">
                                <div class="habit-section-title">
                                    <span class="habit-section-badge" aria-hidden="true"><?= $activeHabitTab === 'control' ? '🛡️' : '💚' ?></span>
                                    <div>
                                        <h2><?= e($currentTabConfig['title']) ?></h2>
                                        <p class="habit-table-subtitle"><?= e($currentTabConfig['subtitle']) ?></p>
                                    </div>
                                </div>
                                <div class="habit-table-legend">
                                    <span><i class="dot done"></i><?= e($activeHabitTab === 'control' ? 'Día controlado' : 'Hecho') ?></span>
                                    <span><i class="dot partial"></i><?= e($currentTabConfig['legendPartial']) ?></span>
                                    <span><i class="dot empty"></i><?= e($currentTabConfig['legendEmpty']) ?></span>
                                </div>
                            </div>

                            <div class="habit-table-frame">
                                <div class="habit-table-columns">
                                    <strong><?= e($activeHabitTab === 'control' ? 'Hábito en control' : 'Hábito positivo') ?></strong>
                                    <div class="habit-week-head">
                                        <?php foreach ($weekLabels as $label): ?>
                                            <span><?= e($label) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <strong class="habit-metric-head"><?= e($currentTabConfig['rowLabel']) ?></strong>
                                </div>

                                <div class="habit-list habit-list--<?= e($activeHabitTab) ?>">
                                    <?php if (empty($visibleHabits)): ?>
                                        <article class="lq-empty habit-empty-state">
                                            <h2><?= e($currentTabConfig['emptyTitle']) ?></h2>
                                            <p><?= e($currentTabConfig['emptyText']) ?></p>
                                            <button type="button" class="habit-create-btn habit-create-btn--<?= e($activeHabitTab) ?>" data-habit-modal-open><?= e($currentTabConfig['cta']) ?></button>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($visibleHabits as $index => $habit): ?>
                                        <?php
                                            $hid = (int) ($habit['id'] ?? 0);
                                            $habitType = !empty($habit['is_negative']) ? 'control' : 'positive';
                                            $metricText = (int) ($habit['current_streak'] ?? 0) . ' días';
                                            $habitAreaIconValue = trim((string) ($habit['area_icon'] ?? ''));
                                            $habitAreaIcon = areaIconMaskUrl($habitAreaIconValue);
                                            $habitAreaColor = (string) ($habit['area_color'] ?? '');
                                            $habitAreaBg = hexToRgba($habitAreaColor !== '' ? $habitAreaColor : '#16C79A', 0.15);
                                        ?>
                                        <article
                                            class="habit-row habit-row--<?= e($habitType) ?> <?= (int) ($habit['active'] ?? 1) === 0 ? 'is-inactive' : '' ?>"
                                            tabindex="0"
                                            role="button"
                                            data-habit-edit-open
                                            data-habit-id="<?= $hid ?>"
                                            data-habit-name="<?= e((string) ($habit['name'] ?? '')) ?>"
                                            data-habit-description="<?= e((string) ($habit['description'] ?? '')) ?>"
                                            data-habit-frequency="<?= e((string) ($habit['frequency'] ?? 'daily')) ?>"
                                            data-habit-area-id="<?= (int) ($habit['area_id'] ?? 0) ?>"
                                            data-habit-goal-id="<?= (int) ($habit['goal_id'] ?? 0) ?>"
                                            data-habit-kind="<?= e($habitType) ?>"
                                            data-habit-active="<?= (int) ($habit['active'] ?? 1) ?>"
                                            data-habit-xp="<?= (int) ($habit['xp_reward'] ?? 0) ?>"
                                            data-habit-points="<?= (int) ($habit['points_reward'] ?? 0) ?>"
                                        >
                                            <div class="habit-title-wrap">
                                                <?php if ($habitAreaIcon): ?>
                                                    <div class="habit-icon habit-icon--area" style="--area-bg: <?= e($habitAreaBg) ?>;">
                                                        <span class="habit-icon-mask" style="--area-color: <?= e($habitAreaColor !== '' ? $habitAreaColor : '#16C79A') ?>; -webkit-mask-image: url('<?= e($habitAreaIcon) ?>'); mask-image: url('<?= e($habitAreaIcon) ?>');"></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="habit-icon habit-icon--<?= e($habitType) ?>"><?= $habitType === 'control' ? controlHabitEmojiByIndex($index) : habitEmojiByIndex($index) ?></div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= e((string) ($habit['name'] ?? '')) ?></strong>
                                                    <small><?= e((string) ($habit['description'] ?: ($habitType === 'control' ? 'Pequeñas decisiones que te ayudan a volver al centro.' : 'Hábito sin descripción.'))) ?></small>
                                                </div>
                                            </div>

                                            <div class="habit-week-cells">
                                                <?php foreach ($weekDates as $date): ?>
                                                    <?php
                                                        $status = (string) ($weekLogs[$hid][$date] ?? '');
                                                        $stateKey = $status === 'completed' || $status === 'partial' ? $status : 'empty';
                                                        $stateMeta = $dayStateUi[$stateKey];
                                                        $isToday = $date === $today;
                                                    ?>
                                                    <?php if ($isToday && (int) ($habit['active'] ?? 1) === 1): ?>
                                                                <?php if ($habitType === 'control'): ?>
                                                                    <button
                                                                        type="button"
                                                                        class="habit-day <?= e($stateMeta['class']) ?> habit-day--today"
                                                                        title="Seleccionar estado de hoy"
                                                                        aria-expanded="false"
                                                                        data-habit-state-open
                                                                        data-habit-id="<?= $hid ?>"
                                                                        data-habit-name="<?= e((string) ($habit['name'] ?? '')) ?>"
                                                                        data-habit-current-status="<?= e($status !== '' ? $status : 'empty') ?>"
                                                                    >
                                                                        <?= e($stateMeta['icon']) ?>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <form method="POST" class="habit-toggle-form">
                                                                        <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
                                                                        <input type="hidden" name="current_period" value="<?= e($period) ?>">
                                                                        <input type="hidden" name="action" value="toggle_today">
                                                                        <input type="hidden" name="habit_id" value="<?= $hid ?>">
                                                                        <button type="submit" class="habit-day <?= e($stateMeta['class']) ?> habit-day--today" title="<?= $status === 'completed' ? 'Desmarcar hoy' : 'Marcar hoy' ?>">
                                                                            <?= e($stateMeta['icon']) ?>
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="habit-day <?= e($stateMeta['class']) ?>" title="<?= e($date) ?>">
                                                            <?= e($stateMeta['icon']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>

                                            <div class="habit-row-metric habit-row-metric--<?= e($habitType) ?>">
                                                <span class="habit-row-metric-icon" aria-hidden="true"><?= $habitType === 'control' ? '🛡️' : '🔥' ?></span>
                                                <div>
                                                    <strong><?= e($metricText) ?></strong>
                                                    <small><?= e($activeHabitTab === 'control' ? 'en control' : 'de racha') ?></small>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
                <section class="habits-aside">
                    <article class="habit-side-card profile habit-side-card--<?= e($activeHabitTab) ?>">
                        <div class="habit-avatar habit-avatar--<?= e($activeHabitTab) ?>">
                            <?php if ($habitAvatarSrc !== null): ?>
                                <img src="<?= e($habitAvatarSrc) ?>" alt="" class="mini-avatar-image">
                            <?php else: ?>
                                🧑
                            <?php endif; ?>
                        </div>
                        <strong><?= $activeHabitTab === 'control' ? $visibleBestStreak . ' días en control' : 'Nivel ' . $level ?></strong>
                        <div class="lq-progress"><span style="width: <?= $xpPercent ?>%"></span></div>
                        <small><?= $activeHabitTab === 'control' ? 'Cada día en control cuenta' : number_format($xpCurrentLevel, 0, ',', '.') . ' / ' . number_format($xpPerLevel, 0, ',', '.') . ' XP' ?></small>
                    </article>

                    <article class="habit-side-card impact-card habit-side-card--<?= e($activeHabitTab) ?>">
                        <div class="impact-icon"><?= $activeHabitTab === 'control' ? '🪴' : '✨' ?></div>
                        <div>
                            <h3>Tu impacto</h3>
                            <p><?= $activeHabitTab === 'control' ? 'Llevas ' . $visibleCompleted . ' días cuidando de ti. Sigue así, cada pequeño paso cuenta.' : 'Llevas ' . $visibleCompleted . ' check-ins completados en ' . e($periodLabel) . '.' ?></p>
                        </div>
                    </article>

                    <article class="habit-side-card donut-card-mini habit-side-card--<?= e($activeHabitTab) ?>">
                        <div class="donut-head">
                            <h3>Hábitos completados</h3>
                            <span><?= e($periodLabel) ?></span>
                        </div>
                        <div class="habit-donut habit-donut--<?= e($activeHabitTab) ?>" style="--seg-a: <?= $visibleCompletedPct ?>; --seg-b: <?= $visiblePartialPct ?>; --seg-c: <?= $visibleRemainingPct ?>;"></div>
                        <div class="habit-donut-legend">
                            <span><i class="dot done"></i><?= e($currentTabConfig['legendCompleted']) ?> <b><?= $visibleCompletedPct ?>%</b></span>
                            <span><i class="dot partial"></i><?= e($currentTabConfig['legendPartial']) ?> <b><?= $visiblePartialPct ?>%</b></span>
                            <span><i class="dot empty"></i><?= e($currentTabConfig['legendEmpty']) ?> <b><?= $visibleRemainingPct ?>%</b></span>
                        </div>
                    </article>

                    <article class="habit-side-card tip-card habit-side-card--<?= e($activeHabitTab) ?>">
                        <h3>Consejo del día</h3>
                        <p><?= e($activeHabitTab === 'control' ? 'No se trata de hacerlo perfecto, sino de volver a elegir mejor.' : 'La disciplina también es una forma de cariño propio.') ?></p>
                    </article>
                            </section>
            <?php elseif ($tab === 'stats'): ?>
                <section class="habit-stats-layout">
                    <article class="habit-side-card stats-chart-card">
                        <div class="donut-head">
                            <h3>Actividad diaria</h3>
                            <span><?= e($periodLabel) ?></span>
                        </div>
                        <div class="habit-bars-scroll">
                            <div class="habit-bars">
                                <?php foreach ($periodChart as $bar): ?>
                                    <?php $height = max(6, (int) round((((int) $bar['total']) / max(1, $maxChartTotal)) * 100)); ?>
                                    <article class="habit-bar-item">
                                        <div class="habit-bar-track"><i style="height: <?= $height ?>%"></i></div>
                                        <small><?= e((string) $bar['label']) ?></small>
                                        <span><?= (int) $bar['total'] ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>

                    <article class="habit-side-card stats-ranking-card">
                        <h3>Ranking de hábitos</h3>
                        <div class="stats-ranking-list">
                            <?php if (empty($habitsRank)): ?>
                                <p class="muted">Aún no hay hábitos para mostrar.</p>
                            <?php endif; ?>
                            <?php foreach (array_slice($habitsRank, 0, 7) as $rank => $item): ?>
                                <article class="stats-ranking-item">
                                    <strong>#<?= $rank + 1 ?> <?= e(shortText((string) $item['name'], 26)) ?></strong>
                                    <span><?= (int) $item['hits'] ?>/<?= count($periodDates) ?> días</span>
                                    <div class="lq-progress"><span style="width: <?= (int) $item['ratio'] ?>%"></span></div>
                                    <small><?= (int) $item['ratio'] ?>% · racha <?= (int) $item['streak'] ?> 🔥</small>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>
            <?php else: ?>
                <section class="habit-discover-grid">
                    <?php foreach ($discoverTemplates as $template): ?>
                        <article class="habit-side-card discover-card">
                            <h3><?= e($template['name']) ?></h3>
                            <p><?= e($template['description']) ?></p>
                            <div class="discover-meta">
                                <span class="lq-badge purple">✦ Recompensa automática</span>
                                <span class="lq-badge orange">🪙 Balance dinámico</span>
                            </div>
                            <form method="POST" class="discover-add-form">
                                <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
                                <input type="hidden" name="current_period" value="<?= e($period) ?>">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="kind" value="positive">
                                <input type="hidden" name="name" value="<?= e($template['name']) ?>">
                                <input type="hidden" name="description" value="<?= e($template['description']) ?>">
                                <input type="hidden" name="frequency" value="daily">
                                <button type="submit" class="btn btn-primary">Añadir a mis hábitos</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </section>
        
    </main>

    <div class="habit-modal-overlay<?= $habitModalShouldOpen ? ' is-open' : '' ?>" data-habit-modal<?= $habitModalShouldOpen ? '' : ' hidden' ?>>
        <div class="habit-modal-card" role="dialog" aria-modal="true" aria-labelledby="habit-modal-title">
            <div class="habit-modal-head">
                <div>
                    <p class="habit-modal-eyebrow" data-habit-modal-eyebrow><?= $habitModalMode === 'edit' ? 'Editar hábito' : 'Nuevo hábito' ?></p>
                    <h2 id="habit-modal-title" data-habit-modal-title><?= $habitModalMode === 'edit' ? 'Editar hábito' : 'Crear nuevo hábito' ?></h2>
                </div>
                <button type="button" class="habit-modal-close" data-habit-modal-close aria-label="Cerrar modal">×</button>
            </div>

            <p class="habit-modal-sub" data-habit-modal-sub><?= $habitModalMode === 'edit' ? 'Actualiza la información de este hábito o elimínalo si ya no lo necesitas.' : 'Añádelo a tu rutina y empieza a seguir su progreso desde hoy.' ?></p>

            <form method="POST" class="habit-modal-form" data-habit-modal-form>
                <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
                <input type="hidden" name="current_period" value="<?= e($period) ?>">
                <input type="hidden" name="action" value="<?= $habitModalMode === 'edit' ? 'update' : 'create' ?>" data-habit-modal-action>
                <input type="hidden" name="habit_id" value="<?= (int) ($habitFormData['habit_id'] ?? 0) ?>" data-habit-modal-id>

                <div class="habit-modal-grid">
                    <label>
                        <span>Nombre del hábito</span>
                        <input type="text" name="name" placeholder="Ej. Leer 20 minutos" value="<?= habitFormValue($habitFormData, 'name') ?>" required data-habit-field="name">
                    </label>

                    <label>
                        <span>Frecuencia</span>
                        <select name="frequency" data-habit-field="frequency">
                            <option value="daily" <?= (($habitFormData['frequency'] ?? 'daily') === 'daily') ? 'selected' : '' ?>>Diaria</option>
                            <option value="weekly" <?= (($habitFormData['frequency'] ?? '') === 'weekly') ? 'selected' : '' ?>>Semanal</option>
                            <option value="custom" <?= (($habitFormData['frequency'] ?? '') === 'custom') ? 'selected' : '' ?>>Personalizada</option>
                        </select>
                    </label>

                    <label class="habit-modal-span-2">
                        <span>Descripción</span>
                        <input type="text" name="description" placeholder="Descripción corta (opcional)" value="<?= habitFormValue($habitFormData, 'description') ?>" data-habit-field="description">
                    </label>

                    <label>
                        <span>Área</span>
                        <select name="area_id" data-habit-field="area_id">
                            <option value="">Área</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= (int) $area['id'] ?>" <?= ((string) ($habitFormData['area_id'] ?? '') === (string) $area['id']) ? 'selected' : '' ?>><?= e((string) $area['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Meta</span>
                        <select name="goal_id" data-habit-field="goal_id">
                            <option value="">Meta</option>
                            <?php foreach ($goals as $goal): ?>
                                <option value="<?= (int) $goal['id'] ?>" <?= ((string) ($habitFormData['goal_id'] ?? '') === (string) $goal['id']) ? 'selected' : '' ?>><?= e(shortText((string) $goal['title'], 26)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="habit-modal-span-2 habit-kind-field" data-habit-kind-field>
                        <span>Tipo de hábito</span>
                        <div class="habit-kind-options">
                            <label class="habit-kind-option habit-kind-option--positive <?= (($habitFormData['kind'] ?? $activeHabitTab) === 'positive') ? 'is-selected' : '' ?>" data-habit-kind-option>
                                <input type="radio" name="kind" value="positive" <?= (($habitFormData['kind'] ?? $activeHabitTab) === 'positive') ? 'checked' : '' ?>>
                                <strong>Hábitos positivos</strong>
                                <small>Acciones que quieres repetir y fortalecer.</small>
                            </label>
                            <label class="habit-kind-option habit-kind-option--control <?= (($habitFormData['kind'] ?? $activeHabitTab) === 'control') ? 'is-selected' : '' ?>" data-habit-kind-option>
                                <input type="radio" name="kind" value="control" <?= (($habitFormData['kind'] ?? $activeHabitTab) === 'control') ? 'checked' : '' ?>>
                                <strong>Hábitos en control</strong>
                                <small>Rutinas que te ayudan a volver al equilibrio.</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="habit-modal-actions">
                    <button type="button" class="habit-modal-danger" data-habit-modal-delete hidden>Eliminar hábito</button>
                    <div class="habit-modal-actions-right">
                        <button type="button" class="habit-modal-secondary" data-habit-modal-close>Cancelar</button>
                        <button type="submit" class="habit-modal-primary" data-habit-modal-submit><?= $habitModalMode === 'edit' ? 'Guardar cambios' : 'Crear hábito' ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="habit-state-popover" data-habit-state-popover hidden>
        <div class="habit-state-popover-head">
            <p class="habit-modal-eyebrow">Estado de hoy</p>
            <button type="button" class="habit-state-popover-close" data-habit-state-popover-close aria-label="Cerrar menú">×</button>
        </div>

        <h2 class="habit-state-popover-title" data-habit-state-popover-title>Seleccionar estado</h2>
        <p class="habit-state-popover-summary" data-habit-state-popover-summary>Elige un estado para hoy.</p>

        <form method="POST" class="habit-state-form" data-habit-state-form>
            <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
            <input type="hidden" name="current_period" value="<?= e($period) ?>">
            <input type="hidden" name="action" value="toggle_today">
            <input type="hidden" name="habit_id" value="">
            <input type="hidden" name="current_status" value="">
            <input type="hidden" name="status" value="">

            <div class="habit-state-options">
                <button type="button" class="habit-state-option habit-state-option--completed" data-habit-state-option data-status="completed">
                    <strong>Día controlado</strong>
                    <small>Sumar XP y dejar el día en orden.</small>
                </button>
                <button type="button" class="habit-state-option habit-state-option--partial" data-habit-state-option data-status="partial">
                    <strong>Recaída</strong>
                    <small>Registrar una recaída parcial y restar HP.</small>
                </button>
                <button type="button" class="habit-state-option habit-state-option--empty" data-habit-state-option data-status="empty">
                    <strong>Sin registrar</strong>
                    <small>Quitar el estado de hoy.</small>
                </button>
            </div>
        </form>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
