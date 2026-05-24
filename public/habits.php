<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/HabitController.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/User.php';
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

$habitController = new HabitController();
$lifeAreaModel = new LifeArea();
$goalModel = new Goal();

$allowedTabs = ['habits', 'stats', 'discover'];
$allowedPeriods = ['week', 'month'];

$tabInput = (string) ($_GET['tab'] ?? 'habits');
$periodInput = (string) ($_GET['period'] ?? 'week');

$tab = in_array($tabInput, $allowedTabs, true) ? $tabInput : 'habits';
$period = in_array($periodInput, $allowedPeriods, true) ? $periodInput : 'week';

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $habitController->store($userId, $_POST);
    } elseif ($action === 'toggle_today') {
        $result = $habitController->toggleToday($userId, (int) ($_POST['habit_id'] ?? 0));
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida.'];
    }

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        $postTab = in_array((string) ($_POST['current_tab'] ?? $tab), $allowedTabs, true) ? (string) ($_POST['current_tab'] ?? $tab) : 'habits';
        $postPeriod = in_array((string) ($_POST['current_period'] ?? $period), $allowedPeriods, true) ? (string) ($_POST['current_period'] ?? $period) : 'week';

        header('Location: habits.php?tab=' . urlencode($postTab) . '&period=' . urlencode($postPeriod) . '&message=' . urlencode($message) . '&type=' . $messageType);
        exit;
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
$possiblePeriod = max(1, count($habits) * max(1, count($periodDates)));
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
$negativeHabitsEnabled = defined('FEATURE_NEGATIVE_HABITS') ? (bool) FEATURE_NEGATIVE_HABITS : false;

foreach ($habits as $habit) {
    $hid = (int) $habit['id'];
    $habitHits = 0;

    foreach ($periodDates as $date) {
        if (!empty($rangeLogs[$hid][$date])) {
            $completedPeriod++;
            $habitHits++;
            $periodDailyTotals[$date] = (int) ($periodDailyTotals[$date] ?? 0) + 1;
        }
    }

    $habitPeriodHits[$hid] = $habitHits;

    if ($habitHits === 0) {
        $emptyPeriodHabits++;
    } elseif ($habitHits === count($periodDates)) {
        $fullPeriodHabits++;
    } else {
        $partialPeriodHabits++;
    }
}

$completedPct = $possiblePeriod > 0 ? (int) round(($completedPeriod / $possiblePeriod) * 100) : 0;
$totalHabitsForPct = max(1, count($habits));
$fullWeekPct = (int) round(($fullPeriodHabits / $totalHabitsForPct) * 100);
$partialWeekPct = (int) round(($partialPeriodHabits / $totalHabitsForPct) * 100);
$emptyWeekPct = max(0, 100 - $fullWeekPct - $partialWeekPct);

$periodChart = [];
foreach ($periodDailyTotals as $date => $total) {
    $dt = new DateTimeImmutable($date);
    $periodChart[] = [
        'date' => $date,
        'label' => $period === 'month' ? $dt->format('d') : ['L', 'M', 'X', 'J', 'V', 'S', 'D'][(int) $dt->format('N') - 1],
        'total' => (int) $total,
    ];
}

$maxChartTotal = 1;
foreach ($periodChart as $item) {
    if ($item['total'] > $maxChartTotal) {
        $maxChartTotal = $item['total'];
    }
}

$habitsRank = [];
foreach ($habits as $habit) {
    $hid = (int) $habit['id'];
    $hits = (int) ($habitPeriodHits[$hid] ?? 0);
    $ratio = count($periodDates) > 0 ? (int) round(($hits / count($periodDates)) * 100) : 0;
    $habitsRank[] = [
        'id' => $hid,
        'name' => (string) $habit['name'],
        'hits' => $hits,
        'ratio' => $ratio,
        'streak' => (int) ($habit['current_streak'] ?? 0),
    ];
}

usort($habitsRank, static fn(array $a, array $b): int => $b['ratio'] <=> $a['ratio']);

$kpiActive = (int) ($stats['active_total'] ?? 0);
$kpiActiveTarget = max(7, $kpiActive);

$discoverTemplates = [
    ['name' => 'Beber 2L de agua', 'description' => 'Mantén hidratación diaria para energía constante.', 'xp' => 8, 'points' => 4],
    ['name' => 'Leer 20 minutos', 'description' => 'Avanza en aprendizaje cada día sin saturarte.', 'xp' => 10, 'points' => 5],
    ['name' => 'Entrenar 30 minutos', 'description' => 'Movimiento diario para salud física y mental.', 'xp' => 12, 'points' => 6],
    ['name' => 'Plan diario de 5 minutos', 'description' => 'Define foco del día antes de empezar.', 'xp' => 7, 'points' => 4],
    ['name' => 'Dormir 8 horas', 'description' => 'Prioriza descanso para rendir mejor mañana.', 'xp' => 9, 'points' => 5],
    ['name' => 'Escribir diario breve', 'description' => 'Reflexiona al final del día y ajusta rumbo.', 'xp' => 8, 'points' => 4],
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
    $emojis = ['🧘', '💧', '📖', '🏋️', '✍️', '😴', '🚫'];

    return $emojis[$index % count($emojis)];
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
            <div class="streak-icon">🔥</div>
            <p>Racha actual</p>
            <strong><?= $currentStreak ?> días</strong>
            <small>¡Sigue así!</small>
            <div class="week-dots week-stack">
                <?php foreach ($weekActivity as $day): ?>
                    <div class="week-day" title="<?= e($day['date']) ?>">
                        <span class="week-dot <?= $day['done'] ? 'done' : '' ?>"><?= $day['done'] ? '✓' : '' ?></span>
                        <small class="week-label"><?= $day['label'] ?></small>
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
            <div class="mini-avatar"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
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
        <header class="lq-topbar">
            <button class="icon-btn">☰</button>
            <div class="search-box">
                <span>🔎</span>
                <input type="search" placeholder="Buscar hábitos..." disabled>
                <kbd>⌘ K</kbd>
            </div>
            <div class="top-stats">
                <div class="currency-pill coin"><span>🪙</span><strong><?= number_format((int) ($user['points'] ?? 0), 0, ',', '.') ?></strong></div>
                <div class="currency-pill gem"><span>💎</span><strong><?= max(0, intdiv((int) ($user['points'] ?? 0), 20)) ?></strong></div>
                <div class="profile-pill">
                    <div class="mini-avatar image-like"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
                    <strong>¡Hola, <?= e(shortText($user['name'] ?? 'Usuario', 12)) ?>! 👋</strong>
                </div>
            </div>
        </header>

        <section class="lq-page-shell habits-shell">
            <header class="lq-page-hero habits-hero">
                <div>
                    <p class="eyebrow">Rutinas cotidianas</p>
                    <h1>Hábitos</h1>
                    <p>Construye constancia diaria. Marca cada día cumplido y deja que tu progreso hable por ti.</p>
                </div>
                <div class="habit-hero-controls">
                    <div class="habit-tabs">
                        <a href="habits.php?tab=habits&amp;period=<?= e($period) ?>" class="<?= $tab === 'habits' ? 'active' : '' ?>">Mis hábitos</a>
                        <a href="habits.php?tab=stats&amp;period=<?= e($period) ?>" class="<?= $tab === 'stats' ? 'active' : '' ?>">Estadísticas</a>
                        <a href="habits.php?tab=discover&amp;period=<?= e($period) ?>" class="<?= $tab === 'discover' ? 'active' : '' ?>">Descubrir</a>
                    </div>
                    <form method="GET" class="habit-period-form">
                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <select name="period" onchange="this.form.submit()">
                            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Esta semana</option>
                            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Este mes</option>
                        </select>
                    </form>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="habit-kpis">
                <article class="habit-kpi-card">
                    <div class="habit-kpi-visual ring" style="--ring: <?= min(100, (int) round(($kpiActive / max(1, $kpiActiveTarget)) * 100)) ?>;"></div>
                    <div>
                        <strong><?= $kpiActive ?>/<?= $kpiActiveTarget ?></strong>
                        <small>Hábitos activos</small>
                        <em>¡Vas genial!</em>
                    </div>
                </article>
                <article class="habit-kpi-card">
                    <div class="habit-kpi-visual flame">🔥</div>
                    <div>
                        <strong><?= (int) ($stats['best_streak'] ?? 0) ?> días</strong>
                        <small>Mejor racha</small>
                        <em>Nueva marca</em>
                    </div>
                </article>
                <article class="habit-kpi-card">
                    <div class="habit-kpi-visual trophy">🏆</div>
                    <div>
                        <strong><?= $completedPeriod ?></strong>
                        <small>Check-ins completados</small>
                        <em><?= e($periodLabel) ?></em>
                    </div>
                </article>
                <article class="habit-kpi-card">
                    <div class="habit-kpi-visual star">⭐</div>
                    <div>
                        <strong>+<?= (int) ($stats['daily_xp'] ?? 0) ?> XP</strong>
                        <small>Gana más XP</small>
                        <em>Sigue así</em>
                    </div>
                </article>
            </section>

            <?php if ($tab === 'habits'): ?>
                <section class="habits-layout">
                    <article class="habits-main-card">
                        <div class="habit-table-header">
                            <h2>Mis hábitos</h2>
                            <div class="habit-week-head">
                                <?php foreach ($weekLabels as $label): ?>
                                    <span><?= $label ?></span>
                                <?php endforeach; ?>
                                <span>Racha</span>
                            </div>
                        </div>

                        <div class="habit-list">
                            <?php if (empty($habits)): ?>
                                <article class="lq-empty">
                                    <h2>No hay hábitos todavía</h2>
                                    <p>Crea tus hábitos cotidianos para empezar a construir constancia real.</p>
                                </article>
                            <?php endif; ?>

                            <?php foreach ($habits as $index => $habit): ?>
                                <?php $hid = (int) $habit['id']; ?>
                                <article class="habit-row <?= (int) $habit['active'] === 0 ? 'is-inactive' : '' ?>">
                                    <div class="habit-title-wrap">
                                        <div class="habit-icon"><?= habitEmojiByIndex($index) ?></div>
                                        <div>
                                            <strong><?= e($habit['name']) ?></strong>
                                            <?php if (!empty($habit['is_negative']) && $negativeHabitsEnabled): ?>
                                                <small>Hábito de riesgo · -<?= (int) ($habit['hp_penalty'] ?? 0) ?> HP</small>
                                            <?php endif; ?>
                                            <small><?= e($habit['description'] ?: 'Hábito sin descripción.') ?></small>
                                        </div>
                                    </div>

                                    <div class="habit-week-cells">
                                        <?php foreach ($weekDates as $date): ?>
                                            <?php $done = !empty($weekLogs[$hid][$date]); ?>
                                            <?php if ($date === $today && (int) $habit['active'] === 1): ?>
                                                <form method="POST" class="habit-toggle-form">
                                                    <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
                                                    <input type="hidden" name="current_period" value="<?= e($period) ?>">
                                                    <input type="hidden" name="action" value="toggle_today">
                                                    <input type="hidden" name="habit_id" value="<?= $hid ?>">
                                                    <button type="submit" class="habit-check <?= $done ? 'done today' : 'today' ?>" title="<?= $done ? 'Desmarcar hoy' : 'Marcar hoy' ?>">
                                                        <?= $done ? '✓' : '' ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="habit-check <?= $done ? 'done' : '' ?>"><?= $done ? '✓' : '' ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <strong class="habit-streak"><?= (int) $habit['current_streak'] ?> 🔥</strong>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" class="habit-add-bar">
                            <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
                            <input type="hidden" name="current_period" value="<?= e($period) ?>">
                            <input type="hidden" name="action" value="create">
                            <input type="text" name="name" placeholder="Añadir nuevo hábito" required>
                            <input type="text" name="description" placeholder="Descripción corta (opcional)">
                            <select name="area_id">
                                <option value="">Área</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int) $area['id'] ?>"><?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="goal_id">
                                <option value="">Meta</option>
                                <?php foreach ($goals as $goal): ?>
                                    <option value="<?= (int) $goal['id'] ?>"><?= e(shortText($goal['title'], 26)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($negativeHabitsEnabled): ?>
                                <label class="habit-negative-switch" for="is_negative_habit">
                                    <input type="checkbox" id="is_negative_habit" name="is_negative" value="1">
                                    <span>Es un hábito de riesgo</span>
                                </label>
                                <input type="number" name="hp_penalty" min="1" max="500" value="15" placeholder="Daño HP">
                            <?php endif; ?>
                            <button type="submit">+ Añadir</button>
                        </form>
                    </article>

                    <aside class="habits-aside">
                        <article class="habit-side-card profile">
                            <div class="habit-avatar">🧑</div>
                            <strong>Nivel <?= $level ?></strong>
                            <div class="lq-progress"><span style="width: <?= $xpPercent ?>%"></span></div>
                            <small><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</small>
                        </article>

                        <article class="habit-side-card impact-card">
                            <div class="impact-icon">🪴</div>
                            <div>
                                <h3>Tu impacto</h3>
                                <p>Llevas <?= $completedPeriod ?> check-ins completados en <?= e($periodLabel) ?>.</p>
                            </div>
                        </article>

                        <article class="habit-side-card donut-card-mini">
                            <div class="donut-head">
                                <h3>Hábitos completados</h3>
                                <span><?= e($periodLabel) ?></span>
                            </div>
                            <div class="habit-donut" style="--seg-a: <?= $fullWeekPct ?>; --seg-b: <?= $partialWeekPct ?>; --seg-c: <?= $emptyWeekPct ?>;"></div>
                            <div class="habit-donut-legend">
                                <span><i class="dot done"></i>Completados <b><?= $fullWeekPct ?>%</b></span>
                                <span><i class="dot partial"></i>Parciales <b><?= $partialWeekPct ?>%</b></span>
                                <span><i class="dot empty"></i>No completados <b><?= $emptyWeekPct ?>%</b></span>
                            </div>
                        </article>

                        <article class="habit-side-card tip-card">
                            <h3>Consejo del día</h3>
                            <p>La disciplina es hacer lo que debes hacer, incluso cuando no tienes ganas.</p>
                        </article>
                    </aside>
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
                                    <?php $height = max(6, (int) round(($bar['total'] / max(1, $maxChartTotal)) * 100)); ?>
                                    <article class="habit-bar-item">
                                        <div class="habit-bar-track"><i style="height: <?= $height ?>%"></i></div>
                                        <small><?= e($bar['label']) ?></small>
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
                                    <strong>#<?= $rank + 1 ?> <?= e(shortText($item['name'], 26)) ?></strong>
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
</body>
</html>
