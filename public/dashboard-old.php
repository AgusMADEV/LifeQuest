<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';

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

$xpCurrent = (int) $user['xp'];
$xpNext = 2000;
$xpPercent = min(100, (int) (($xpCurrent / max(1, $xpNext)) * 100));

$level = max(1, (int) $user['level']);
$points = (int) $user['points'];
$gems = max(0, intdiv($points, 20));
$currentStreak = (int) $user['current_streak'];

$dailyCompleted = min(4, count(array_filter($mainGoals, static fn($goal) => (int) ($goal['progress'] ?? 0) >= 100)));
$dailyTotal = max(4, min(6, count($mainGoals) + 1));
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
</head>
<body class="lifequest-app">
    <?php
    $activePage = 'dashboard';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

<main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar metas, retos, misiones o recompensas...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

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
                                <em><?= number_format($xpCurrent, 0, ',', '.') ?> / <?= number_format($xpNext, 0, ',', '.') ?> XP</em>
                            </article>

                            <article>
                                <small>XP actual</small>
                                <strong><?= number_format($xpCurrent, 0, ',', '.') ?></strong>
                                <span>+200 XP para subir</span>
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
                        </div>

                        <div class="hero-bottom">
                            <div class="streak-row">
                                <span>🔥</span>
                                <div>
                                    <small>Racha actual</small>
                                    <strong><?= $currentStreak ?> días</strong>
                                </div>
                                <div class="week-mini">
                                    <i class="done">L</i><i class="done">M</i><i class="done">X</i><i class="done">J</i><i class="done">V</i><i>S</i><i>D</i>
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
                        <h2>Misiones de hoy <span><?= count($activeProjects) ?></span></h2>
                        <a href="projects.php">Ver todas</a>
                    </div>

                    <?php if (empty($activeProjects)): ?>
                        <div class="friendly-empty">
                            <strong>No hay misiones activas todavía.</strong>
                            <p>Crea un proyecto para convertir tus metas en acciones reales.</p>
                            <a href="projects.php" class="mini-btn">Crear misión</a>
                        </div>
                    <?php else: ?>
                        <div class="mission-list">
                            <?php foreach ($activeProjects as $index => $project): ?>
                                <?php
                                $progress = (int) ($project['progress'] ?? 0);
                                $missionIcons = ['📚', '🏋️', '✍️', '📈'];
                                $categoryColors = ['green', 'purple', 'orange', 'blue'];
                                ?>
                                <article class="mission-item">
                                    <label class="check-wrap">
                                        <input type="checkbox" <?= $progress >= 100 ? 'checked' : '' ?> disabled>
                                        <span></span>
                                    </label>

                                    <div class="mission-icon <?= $categoryColors[$index % count($categoryColors)] ?>">
                                        <?= $missionIcons[$index % count($missionIcons)] ?>
                                    </div>

                                    <div class="mission-info">
                                        <strong><?= e(shortText($project['title'], 36)) ?></strong>
                                        <small><?= !empty($project['goal_title']) ? e(shortText($project['goal_title'], 42)) : 'Misión independiente' ?></small>
                                    </div>

                                    <span class="mission-tag <?= $categoryColors[$index % count($categoryColors)] ?>">
                                        <?= !empty($project['area_name']) ? e(shortText($project['area_name'], 14)) : 'General' ?>
                                    </span>

                                    <div class="mission-progress">
                                        <small><?= $progress ?> / 100</small>
                                        <div class="mini-progress"><i style="width: <?= $progress ?>%"></i></div>
                                    </div>

                                    <strong class="reward">✦ +<?= 80 + ($index * 20) ?> XP</strong>
                                    <span class="flag">⚑</span>
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
                        <h2>Objetivo diario</h2>
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

                <section class="lq-card shop-card">
                    <div class="lq-card-header">
                        <h2>Tienda destacada</h2>
                        <a href="#">Ver todo</a>
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
