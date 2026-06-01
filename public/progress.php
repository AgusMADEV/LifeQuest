<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/Habit.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Support/StreakWeek.php';
require_once __DIR__ . '/../app/Support/XpEvolutionChart.php';

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
$goalModel = new Goal();
$projectModel = new Project();

$allowedMetricPeriods = ['week', 'month'];
$metricPeriodInput = (string) ($_GET['metric_period'] ?? 'month');
$metricPeriod = in_array($metricPeriodInput, $allowedMetricPeriods, true) ? $metricPeriodInput : 'month';

$periodStartDate = $metricPeriod === 'week'
    ? (new DateTimeImmutable('monday this week'))->setTime(0, 0)
    : (new DateTimeImmutable('first day of this month'))->setTime(0, 0);

$periodEndDate = $metricPeriod === 'week'
    ? (new DateTimeImmutable('sunday this week'))->setTime(23, 59, 59)
    : (new DateTimeImmutable('last day of this month'))->setTime(23, 59, 59);
$todayDate = new DateTimeImmutable('today');
$todayDateKey = $todayDate->format('Y-m-d');

$metricPeriodLabel = $metricPeriod === 'week' ? 'Esta semana' : 'Este mes';

$tasks = $taskModel->getAllByUser($userId);
$habits = $habitModel->getAllByUser($userId);
$goals = $goalModel->getAllByUser($userId);
$projects = $projectModel->getAllByUser($userId);

$xpCurrent = (int) ($user['xp'] ?? 0);
$level = max(1, (int) ($user['level'] ?? 1));
$xpPerLevel = 1000;
$xpFloor = ($level - 1) * $xpPerLevel;
$xpCurrentLevel = max(0, $xpCurrent - $xpFloor);
$xpPercent = min(100, (int) (($xpCurrentLevel / max(1, $xpPerLevel)) * 100));
$points = (int) ($user['points'] ?? 0);
$gems = max(0, intdiv($points, 20));
$currentStreak = (int) ($user['current_streak'] ?? 0);

$weekStart = (new DateTimeImmutable('monday this week'))->setTime(0, 0);
$weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);

$weeklyTaskXp = 0;
$weeklyTaskCoins = 0;
$weeklyDoneTasks = 0;

foreach ($tasks as $task) {
    $status = (string) ($task['status'] ?? '');

    if ($status !== 'completed') {
        continue;
    }

    $completedAtRaw = (string) ($task['completed_at'] ?? '');
    if ($completedAtRaw === '') {
        continue;
    }

    try {
        $completedAt = new DateTimeImmutable($completedAtRaw);
    } catch (Throwable $exception) {
        continue;
    }

    if ($completedAt < $periodStartDate || $completedAt > $periodEndDate) {
        continue;
    }

    $weeklyDoneTasks++;
    $weeklyTaskXp += (int) ($task['xp_reward'] ?? 0);
    $weeklyTaskCoins += (int) ($task['points_reward'] ?? 0);
}

$habitLogs = $habitModel->getLogsByRange($userId, $periodStartDate->format('Y-m-d'), $periodEndDate->format('Y-m-d'));
$habitMap = [];
foreach ($habits as $habit) {
    $habitMap[(int) $habit['id']] = $habit;
}

$weeklyHabitXp = 0;
$weeklyHabitCoins = 0;
$weeklyHabitChecks = 0;

foreach ($habitLogs as $habitId => $dateMap) {
    $checks = count($dateMap);
    if ($checks === 0) {
        continue;
    }

    $habit = $habitMap[(int) $habitId] ?? null;
    if (!$habit) {
        continue;
    }

    $weeklyHabitChecks += $checks;
    $weeklyHabitXp += $checks * (int) ($habit['xp_reward'] ?? 0);
    $weeklyHabitCoins += $checks * (int) ($habit['points_reward'] ?? 0);
}

$weeklyXpGain = $weeklyTaskXp + $weeklyHabitXp;
$weeklyCoinGain = $weeklyTaskCoins + $weeklyHabitCoins;

$areaCounter = [];
$registerAreaUsage = static function (array $items) use (&$areaCounter, $periodStartDate, $periodEndDate): void {
    foreach ($items as $item) {
        $createdAtRaw = (string) ($item['created_at'] ?? '');
        if ($createdAtRaw !== '') {
            try {
                $createdAt = new DateTimeImmutable($createdAtRaw);
                if ($createdAt < $periodStartDate || $createdAt > $periodEndDate) {
                    continue;
                }
            } catch (Throwable $exception) {
                continue;
            }
        }

        $areaName = trim((string) ($item['area_name'] ?? ''));
        $areaKey = $areaName !== '' ? $areaName : 'Sin área';
        $areaCounter[$areaKey] = (int) ($areaCounter[$areaKey] ?? 0) + 1;
    }
};

$registerAreaUsage($goals);
$registerAreaUsage($habits);
$registerAreaUsage($tasks);
$registerAreaUsage($projects);

foreach ($habitLogs as $habitId => $dateMap) {
    $habit = $habitMap[(int) $habitId] ?? null;
    if (!$habit) {
        continue;
    }

    $areaName = trim((string) ($habit['area_name'] ?? ''));
    $areaKey = $areaName !== '' ? $areaName : 'Sin área';
    $areaCounter[$areaKey] = (int) ($areaCounter[$areaKey] ?? 0) + count($dateMap);
}

$totalByAreas = array_sum($areaCounter);
arsort($areaCounter);
$topAreas = array_slice($areaCounter, 0, 4, true);

if ($totalByAreas <= 0) {
    $topAreas = ['General' => 1];
    $totalByAreas = 1;
}

$palette = ['#16c79a', '#3b82f6', '#8b5cf6', '#ffb020'];
$areaChart = [];
$chartSegments = [];
$index = 0;

foreach ($topAreas as $areaName => $count) {
    $percent = max(1, (int) round(($count / $totalByAreas) * 100));
    $color = $palette[$index % count($palette)];
    $areaChart[] = [
        'name' => $areaName,
        'percent' => $percent,
        'color' => $color,
    ];
    $chartSegments[] = $color . ' ' . $percent . '%';
    $index++;
}

$sumPercent = array_sum(array_map(static fn(array $item): int => (int) $item['percent'], $areaChart));
if ($sumPercent !== 100 && !empty($areaChart)) {
    $areaChart[0]['percent'] = max(1, $areaChart[0]['percent'] + (100 - $sumPercent));
}

$chartSegments = [];
$segmentStart = 0;

foreach ($areaChart as $item) {
    $segmentEnd = $segmentStart + (int) $item['percent'];
    $chartSegments[] = $item['color'] . ' ' . $segmentStart . '% ' . $segmentEnd . '%';
    $segmentStart = $segmentEnd;
}

$lastIndex = count($chartSegments) - 1;
if ($lastIndex >= 0 && $segmentStart !== 100) {
    $areaChart[$lastIndex]['percent'] += 100 - $segmentStart;
    $segmentStartFix = 0;
    $chartSegments = [];

    foreach ($areaChart as $item) {
        $segmentEndFix = $segmentStartFix + (int) $item['percent'];
        $chartSegments[] = $item['color'] . ' ' . $segmentStartFix . '% ' . $segmentEndFix . '%';
        $segmentStartFix = $segmentEndFix;
    }
}

$donutGradient = 'conic-gradient(' . implode(', ', $chartSegments) . ')';

$weekActivity = buildWeeklyActivityByUser($userId, $weekStart);
$lineChartWidth = 620;
$lineChartHeight = 220;
$axisStep = 500;

$xpChart = XpEvolutionChart::build(
    $tasks,
    $habits,
    $habitLogs,
    $periodStartDate,
    $periodEndDate,
    $metricPeriod,
    $todayDateKey,
    $lineChartWidth,
    $lineChartHeight,
    $axisStep
);

$xpLinePoints = $xpChart['xpLinePoints'];
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

$trendHeights = [];

$trendSource = array_slice($xpLinePoints, -7);
foreach ($trendSource as $index => $point) {
    $trendHeights[] = min(88, max(20, 20 + (int) round(($point['gain'] / max(1, $weeklyXpGain + 1)) * 64) + ($index * 2)));
}

$trendSeriesXp = $trendHeights;
$trendSeriesCoins = array_map(static fn(int $value): int => max(16, $value - 8), $trendHeights);
$trendSeriesGems = array_map(static fn(int $value): int => max(14, $value - 14), $trendHeights);

if (empty($trendSeriesXp)) {
    $trendSeriesXp = [35, 42, 48, 46, 52, 58, 66];
    $trendSeriesCoins = [28, 30, 35, 33, 37, 42, 49];
    $trendSeriesGems = [24, 23, 30, 32, 30, 34, 40];
}

function buildSparkline(array $series, int $width = 180, int $height = 56): array
{
    $count = max(2, count($series));
    $stepX = ($width - 8) / ($count - 1);
    $points = [];

    foreach ($series as $index => $value) {
        $x = 4 + ($stepX * $index);
        $y = $height - 4 - (((int) $value / 100) * ($height - 10));
        $points[] = [
            'x' => (int) round($x),
            'y' => (int) round($y),
        ];
    }

    $line = implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $points));
    $first = $points[0];
    $last = $points[count($points) - 1];
    $area = 'M' . $first['x'] . ' ' . ($height - 3)
        . ' L' . $first['x'] . ' ' . $first['y']
        . ' L' . implode(' L', array_map(static fn(array $p): string => $p['x'] . ' ' . $p['y'], $points))
        . ' L' . $last['x'] . ' ' . ($height - 3)
        . ' Z';

    return [
        'line' => $line,
        'area' => $area,
    ];
}

$sparkXp = buildSparkline($trendSeriesXp);
$sparkCoins = buildSparkline($trendSeriesCoins);
$sparkGems = buildSparkline($trendSeriesGems);

$achievements = [];
if ($currentStreak > 0) {
    $achievements[] = ['title' => 'Constancia activa', 'desc' => 'Racha vigente de ' . $currentStreak . ' dias', 'xp' => '+100 XP'];
}
if ($weeklyDoneTasks > 0) {
    $achievements[] = ['title' => 'Misiones completadas', 'desc' => $weeklyDoneTasks . ' misiones completadas esta semana', 'xp' => '+80 XP'];
}
if ($weeklyHabitChecks > 0) {
    $achievements[] = ['title' => 'Habitos marcados', 'desc' => $weeklyHabitChecks . ' check-ins de habitos esta semana', 'xp' => '+50 XP'];
}
if (empty($achievements)) {
    $achievements[] = ['title' => 'Primer paso', 'desc' => 'Completa una mision o habito para iniciar tu progreso', 'xp' => '+20 XP'];
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
    <title>Progreso | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/progress.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'progress'; ?>
        <?php require __DIR__ . '/partials/sidebar_nav.php'; ?>

        <section class="lq-sidebar-card streak">
            <div class="streak-summary">
                <div class="streak-icon">🔥</div>
                <div class="streak-copy">
                    <p>Racha actual</p>
                    <strong><?= $currentStreak ?> dias</strong>
                    <small>Tu evolucion continua.</small>
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

        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <?php $topbarSearchPlaceholder = 'Buscar métricas o logros...'; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <section class="progress-shell">
            <header class="progress-head">
                <div>
                    <h1>Progreso</h1>
                    <p>Tu evolución en números. Sigue así.</p>
                </div>
                <form method="GET" class="progress-filter">
                    <label for="metric_period">Periodo</label>
                    <select id="metric_period" name="metric_period" onchange="this.form.submit()">
                        <option value="week" <?= $metricPeriod === 'week' ? 'selected' : '' ?>>Esta semana</option>
                        <option value="month" <?= $metricPeriod === 'month' ? 'selected' : '' ?>>Este mes</option>
                    </select>
                </form>
            </header>

            <section class="progress-kpis">
                <article class="progress-card level-card">
                    <div>
                        <small>Nivel actual</small>
                        <strong><?= $level ?></strong>
                        <p><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</p>
                        <div class="mini-progress"><i style="width: <?= $xpPercent ?>%"></i></div>
                    </div>
                    <div class="level-avatar">LQ</div>
                </article>

                <article class="progress-card metric-card">
                    <div class="metric-head">
                        <span class="metric-icon xp">✦</span>
                        <small>XP total ganada</small>
                    </div>
                    <strong><?= number_format($xpCurrent, 0, ',', '.') ?></strong>
                    <em>+<?= number_format($weeklyXpGain, 0, ',', '.') ?> en <?= mb_strtolower($metricPeriodLabel) ?></em>
                    <div class="mini-sparkline trend-xp" aria-hidden="true">
                        <svg viewBox="0 0 180 56">
                            <path class="area" d="<?= e($sparkXp['area']) ?>"></path>
                            <polyline class="line" points="<?= e($sparkXp['line']) ?>"></polyline>
                        </svg>
                    </div>
                </article>

                <article class="progress-card metric-card">
                    <div class="metric-head">
                        <span class="metric-icon coins">🪙</span>
                        <small>LifeCoins totales</small>
                    </div>
                    <strong><?= number_format($points, 0, ',', '.') ?></strong>
                    <em>+<?= number_format($weeklyCoinGain, 0, ',', '.') ?> en <?= mb_strtolower($metricPeriodLabel) ?></em>
                    <div class="mini-sparkline trend-coins" aria-hidden="true">
                        <svg viewBox="0 0 180 56">
                            <path class="area" d="<?= e($sparkCoins['area']) ?>"></path>
                            <polyline class="line" points="<?= e($sparkCoins['line']) ?>"></polyline>
                        </svg>
                    </div>
                </article>

                <article class="progress-card metric-card">
                    <div class="metric-head">
                        <span class="metric-icon activity">⚡</span>
                        <small>Actividad del periodo</small>
                    </div>
                    <strong><?= number_format($weeklyDoneTasks + $weeklyHabitChecks, 0, ',', '.') ?></strong>
                    <em><?= $weeklyDoneTasks ?> misiones + <?= $weeklyHabitChecks ?> checks</em>
                    <div class="mini-sparkline trend-gems" aria-hidden="true">
                        <svg viewBox="0 0 180 56">
                            <path class="area" d="<?= e($sparkGems['area']) ?>"></path>
                            <polyline class="line" points="<?= e($sparkGems['line']) ?>"></polyline>
                        </svg>
                    </div>
                </article>
            </section>

            <section class="progress-grid">
                <article class="progress-card chart-card">
                    <div class="card-head">
                        <h2>Evolución de XP</h2>
                        <span><?= e($metricPeriodLabel) ?></span>
                    </div>
                    <div class="xp-chart">
                        <svg viewBox="0 0 <?= $lineChartWidth ?> <?= $lineChartHeight ?>" aria-label="Gráfico de evolución de XP">
                            <defs>
                                <linearGradient id="xpLine" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#1ed7a5" stop-opacity="1" />
                                    <stop offset="100%" stop-color="#16c79a" stop-opacity="1" />
                                </linearGradient>
                                <linearGradient id="xpArea" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#16c79a" stop-opacity="0.22" />
                                    <stop offset="100%" stop-color="#16c79a" stop-opacity="0.03" />
                                </linearGradient>
                                <?php if ($futureAreaPath !== '' && $futureAreaEndX > $futureAreaStartX): ?>
                                    <linearGradient id="xpAreaFutureFade" gradientUnits="userSpaceOnUse" x1="<?= $futureAreaStartX ?>" x2="<?= $futureAreaEndX ?>" y1="0" y2="0">
                                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0" />
                                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0.62" />
                                    </linearGradient>
                                <?php endif; ?>
                            </defs>

                            <?php foreach ($axisTicks as $tick): ?>
                                <line x1="<?= $linePadX ?>" y1="<?= $tick['y'] ?>" x2="<?= $lineChartWidth - $linePadX ?>" y2="<?= $tick['y'] ?>" class="grid-line"></line>
                                <text x="8" y="<?= $tick['y'] + 4 ?>" class="y-axis-label"><?= e($tick['label']) ?></text>
                            <?php endforeach; ?>

                            <path d="<?= e($lineAreaPath) ?>" fill="url(#xpArea)"></path>
                            <?php if ($futureAreaPath !== '' && $futureAreaEndX > $futureAreaStartX): ?>
                                <path d="<?= e($futureAreaPath) ?>" fill="url(#xpAreaFutureFade)"></path>
                            <?php endif; ?>
                            <polyline points="<?= e($linePolyline) ?>" fill="none" stroke="url(#xpLine)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            <?php if ($futureLinePolyline !== ''): ?>
                                <polyline class="future-line" points="<?= e($futureLinePolyline) ?>" fill="none" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            <?php endif; ?>

                            <?php foreach ($lineCoords as $point): ?>
                                <?php
                                $pointLabel = number_format((int) $point['value'], 0, ',', '.') . ' XP';
                                $bubbleWidth = max(84, (int) round((mb_strlen($pointLabel) * 8.1) + 22));
                                $bubbleHeight = 34;
                                $tipHeight = 8;
                                $pointGap = 11;
                                $rawBubbleX = (int) round($point['x'] - ($bubbleWidth / 2));
                                $bubbleX = max(4, min($lineChartWidth - $bubbleWidth - 4, $rawBubbleX));
                                $bubbleY = (int) round($point['y'] - ($bubbleHeight + $tipHeight + $pointGap));
                                $tipCenter = max(12, min($bubbleWidth - 12, $point['x'] - $bubbleX));
                                $isFuturePoint = (bool) ($point['is_future'] ?? false);
                                ?>
                                <g class="data-point<?= $isFuturePoint ? ' future' : '' ?>">
                                    <?php if (!$isFuturePoint): ?>
                                        <g class="point-tooltip" transform="translate(<?= $bubbleX ?> <?= $bubbleY ?>)">
                                            <rect class="bubble" width="<?= $bubbleWidth ?>" height="<?= $bubbleHeight ?>" rx="11" ry="11"></rect>
                                            <path class="bubble-tip" d="M<?= (int) round($tipCenter - 8) ?> <?= $bubbleHeight ?> L<?= (int) round($tipCenter) ?> <?= $bubbleHeight + $tipHeight ?> L<?= (int) round($tipCenter + 8) ?> <?= $bubbleHeight ?> Z"></path>
                                            <text x="<?= (int) round($bubbleWidth / 2) ?>" y="23" text-anchor="middle" class="bubble-text"><?= e($pointLabel) ?></text>
                                        </g>
                                    <?php endif; ?>
                                    <circle class="dot-hit" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="13" fill="transparent"></circle>
                                    <circle class="dot" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="3.5" fill="#ffffff" stroke="#16c79a" stroke-width="2.2"></circle>
                                    <title><?= e(
                                        $isFuturePoint
                                        ? ($point['label'] . ': dia pendiente')
                                        : (
                                            $point['label']
                                            . ': '
                                            . number_format((int) $point['value'], 0, ',', '.')
                                            . ' XP acumulada · +'
                                            . number_format((int) $point['gain'], 0, ',', '.')
                                            . ' XP'
                                        )
                                    ) ?></title>
                                </g>
                            <?php endforeach; ?>

                            <?php foreach ($lineCoords as $point): ?>
                                <text x="<?= $point['x'] ?>" y="<?= $lineChartHeight - 10 ?>" text-anchor="middle" class="axis-label"><?= e($point['label']) ?></text>
                            <?php endforeach; ?>
                        </svg>
                    </div>
                </article>

                <article class="progress-card categories-card">
                    <div class="card-head">
                        <h2>Categorías más activas</h2>
                        <span><?= e($metricPeriodLabel) ?></span>
                    </div>
                    <div class="categories-wrap">
                        <div class="donut" style="--donut: <?= e($donutGradient) ?>;"></div>
                        <div class="categories-list">
                            <?php foreach ($areaChart as $item): ?>
                                <div>
                                    <span><i style="background: <?= e($item['color']) ?>;"></i><?= e(shortText($item['name'], 18)) ?></span>
                                    <strong><?= (int) $item['percent'] ?>%</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>

                <article class="progress-card achievements-card">
                    <div class="card-head">
                        <h2>Logros recientes</h2>
                        <a href="#">Ver todos</a>
                    </div>
                    <div class="achievements-list">
                        <?php foreach (array_slice($achievements, 0, 3) as $achievement): ?>
                            <article>
                                <div class="badge">✓</div>
                                <div>
                                    <strong><?= e($achievement['title']) ?></strong>
                                    <small><?= e($achievement['desc']) ?></small>
                                </div>
                                <em><?= e($achievement['xp']) ?></em>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>
        </section>
    </main>
</body>
</html>
