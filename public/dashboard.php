<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/AreaProgression.php';
require_once __DIR__ . '/../app/Support/StreakWeek.php';

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
$todayTasks = $taskModel->getTodayByUser((int) $user['id'], 4);
$weekActivity = buildWeeklyActivityByUser((int) $user['id']);

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

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/dashboard.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'dashboard'; ?>
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

        <section class="lq-sidebar-card unlock">
            <div>
                <strong>¡Desbloquea más!</strong>
                <p>Completa misiones y consigue recompensas exclusivas.</p>
                <a href="shop.php" class="mini-btn">Ver tienda</a>
            </div>
            <span class="bag">🎒</span>
        </section>

        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <header class="lq-topbar">
            <button class="icon-btn">☰</button>

            <div class="search-box">
                <span>
                    <svg id="Search" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11.2481" cy="10.7887" r="8.03854" stroke="#7b86a3" stroke-width="1.5" stroke-linecap="square"></circle>
                        <path d="M16.7369 16.7083L21.2904 21.2499" stroke="#7b86a3" stroke-width="1.5" stroke-linecap="square"></path>
                    </svg>
                </span>
                <input type="search" placeholder="Buscar misiones, hábitos o recompensas..." disabled>
                <kbd>⌘ K</kbd>
            </div>

            <div class="top-stats">
                <div class="xp-pill">
                    <span>✦</span>
                    <strong><?= number_format($xpCurrent, 0, ',', '.') ?> XP</strong>
                    <div class="mini-progress"><i style="width: <?= $xpPercent ?>%"></i></div>
                    <small>Nivel <?= $level ?></small>
                </div>

                <div class="currency-pill coin">
                    <span>🪙</span>
                    <strong><?= number_format($points, 0, ',', '.') ?></strong>
                </div>

                <div class="currency-pill gem">
                    <span>💎</span>
                    <strong><?= $gems ?></strong>
                </div>

                <?php if ($hpSystemEnabled): ?>
                    <div class="currency-pill hp">
                        <span>❤️</span>
                        <strong><?= number_format($hp, 0, ',', '.') ?>/<?= number_format($maxHp, 0, ',', '.') ?></strong>
                    </div>
                <?php endif; ?>

                <button class="icon-btn">🔔</button>

                <div class="profile-pill">
                    <div class="mini-avatar image-like"><?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
                    <strong>¡Hola, <?= e(shortText($user['name'], 12)) ?>! 👋</strong>
                </div>
            </div>
        </header>

        <div class="lq-dashboard-grid">
            <section class="lq-center">
                <section class="hero-panel">
                    <div class="hero-avatar-wrap">
                        <div class="hero-glow"></div>
                        <div class="hero-avatar">
                            <div class="avatar-hair"></div>
                            <div class="avatar-face">😊</div>
                            <div class="avatar-body">LQ</div>
                        </div>
                    </div>

                    <div class="hero-content">
                        <h1>¡Sigue así, <?= e(shortText($user['name'], 18)) ?>!</h1>
                        <p>Cada misión completada te acerca a tu mejor versión.</p>

                        <div class="hero-stats">
                            <article>
                                <small>Nivel</small>
                                <strong><?= $level ?></strong>
                                <span>Camino a nivel <?= $level + 1 ?></span>
                                <div class="mini-progress"><i style="width: <?= $xpPercent ?>%"></i></div>
                                <em><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</em>
                            </article>

                            <article>
                                <small>XP actual</small>
                                <strong><?= number_format($xpCurrent, 0, ',', '.') ?></strong>
                                <span><?= number_format(max(0, $xpNext - $xpCurrent), 0, ',', '.') ?> XP para subir</span>
                                <div class="mini-progress"><i style="width: <?= $xpPercent ?>%"></i></div>
                            </article>

                            <article>
                                <small>LifeCoins</small>
                                <strong><?= number_format($points, 0, ',', '.') ?></strong>
                                <span>Úsalos en la tienda</span>
                            </article>

                            <article>
                                <small>Gemas</small>
                                <strong><?= $gems ?></strong>
                                <span>Para objetos únicos</span>
                            </article>

                            <?php if ($hpSystemEnabled): ?>
                                <article>
                                    <small>Vida</small>
                                    <strong><?= number_format($hp, 0, ',', '.') ?></strong>
                                    <span><?= number_format($maxHp, 0, ',', '.') ?> HP máximos</span>
                                    <div class="mini-progress"><i style="width: <?= $hpPercent ?>%"></i></div>
                                </article>
                            <?php endif; ?>
                        </div>

                        <div class="hero-bottom">
                            <div class="streak-row">
                                <span>🔥</span>
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
                            <?php foreach ($todayTasks as $index => $task): ?>
                                <?php
                                $missionIcons = ['📚', '🏋️', '✍️', '📈'];
                                $categoryColors = ['green', 'purple', 'orange', 'blue'];
                                $done = $task['status'] === 'completed';
                                ?>
                                <article class="mission-item">
                                    <label class="check-wrap">
                                        <input type="checkbox" <?= $done ? 'checked' : '' ?> disabled>
                                        <span></span>
                                    </label>

                                    <div class="mission-icon <?= $categoryColors[$index % count($categoryColors)] ?>">
                                        <?= $missionIcons[$index % count($missionIcons)] ?>
                                    </div>

                                    <div class="mission-info">
                                        <strong><?= e(shortText($task['title'], 36)) ?></strong>
                                        <small><?= !empty($task['project_title']) ? e(shortText($task['project_title'], 42)) : 'Misión independiente' ?></small>
                                    </div>

                                    <span class="mission-tag <?= $categoryColors[$index % count($categoryColors)] ?>">
                                        <?= !empty($task['area_name']) ? e(shortText($task['area_name'], 14)) : 'General' ?>
                                    </span>

                                    <div class="mission-progress">
                                        <small><?= (int) $task['estimated_minutes'] ?> min</small>
                                        <div class="mini-progress"><i style="width: <?= $done ? 100 : 35 ?>%"></i></div>
                                    </div>

                                    <strong class="reward">✦ +<?= (int) $task['xp_reward'] ?> XP</strong>
                                    <span class="flag"><?= $done ? '✅' : '⚑' ?></span>
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
                            <select disabled>
                                <option>Esta semana</option>
                            </select>
                        </div>
                        <div class="fake-chart">
                            <span style="height: 28%"></span>
                            <span style="height: 42%"></span>
                            <span style="height: 39%"></span>
                            <span style="height: 58%"></span>
                            <span style="height: 72%"></span>
                            <span style="height: 84%"></span>
                            <span style="height: 96%"></span>
                        </div>
                        <strong><?= number_format($xpCurrent + 1250, 0, ',', '.') ?> XP</strong>
                        <small>de <?= number_format($xpNext, 0, ',', '.') ?> XP</small>
                    </article>

                    <article class="lq-card compact summary-card">
                        <div class="lq-card-header">
                            <h2>Resumen general</h2>
                        </div>
                        <div class="summary-mini-grid">
                            <div><span>✅</span><strong><?= count($mainGoals) + count($activeProjects) ?></strong><small>Misiones</small></div>
                            <div><span>⚡</span><strong><?= $xpCurrent ?></strong><small>XP</small></div>
                            <div><span>🪙</span><strong><?= $points ?></strong><small>Coins</small></div>
                            <div><span>⏱️</span><strong>0h</strong><small>Enfoque</small></div>
                        </div>
                    </article>
                </section>
            </section>

            <aside class="lq-right">
                <section class="lq-card objective-card">
                    <div class="lq-card-header">
                        <h2>Meta diario</h2>
                    </div>
                    <p>Completa <?= $dailyTotal ?> misiones al día</p>
                    <div class="circle-progress" style="--value: <?= $objectivePercent ?>;">
                        <strong><?= $dailyCompleted ?>/<?= $dailyTotal ?></strong>
                        <span>misiones</span>
                    </div>
                    <small>✦ +200 XP</small>
                </section>

                <section class="lq-card upcoming-card">
                    <div class="lq-card-header">
                        <h2>Próximas misiones</h2>
                    </div>

                    <?php if (empty($mainGoals)): ?>
                        <p class="muted">Crea metas para generar próximas misiones.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($mainGoals, 0, 3) as $goal): ?>
                            <div class="upcoming-item">
                                <span>🎯</span>
                                <div>
                                    <strong><?= e(shortText($goal['title'], 24)) ?></strong>
                                    <small><?= statusLabelDashboard($goal['status']) ?></small>
                                </div>
                                <em>+<?= (int) $goal['xp_reward'] ?> XP</em>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <a href="goals.php" class="center-link">Ver calendario</a>
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
                                    <article class="area-level-item">
                                        <span class="area-level-icon"><?= e($areaLevel['icon']) ?></span>
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
                    <div class="donut-wrap">
                        <div class="donut"></div>
                        <div class="donut-legend">
                            <span><i class="green-dot"></i>Salud 28%</span>
                            <span><i class="blue-dot"></i>Aprendizaje 28%</span>
                            <span><i class="purple-dot"></i>Hábitos 22%</span>
                            <span><i class="orange-dot"></i>Enfoque 18%</span>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </main>
</body>
</html>
