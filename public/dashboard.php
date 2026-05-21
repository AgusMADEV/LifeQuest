<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/MissionController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/Mission.php';

AuthController::requireAuth();

$userId = (int)$_SESSION['user_id'];
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

// Obtener datos
$missionController = new MissionController();
$goalModel = new Goal();
$projectModel = new Project();
$taskModel = new Task();

$dailyMissions = $missionController->index($userId, 'daily');
$completedMissions = array_filter($dailyMissions, fn($m) => (int)$m['completed'] === 1);
$allMissions = array_slice($dailyMissions, 0, 4);

$activeGoals = array_filter($goalModel->getAllByUser($userId), fn($g) => 
    in_array($g['status'], ['in_progress', 'not_started'])
);
$activeGoals = array_slice($activeGoals, 0, 4);

$activeProjects = $projectModel->getActiveByUser($userId, 4);

$missionStats = $missionController->getStats($userId);
$currentLevel = (int)$user['level'];
$currentXp = (int)$user['xp'];
$xpForNextLevel = $currentLevel * 100;
$xpPercentage = min(100, (int)(($currentXp / $xpForNextLevel) * 100));
$lifeCoins = (int)$user['points'];
$gems = (int)($user['gems'] ?? 0);
$currentStreak = (int)$user['current_streak'];

// Objetivo diario
$dailyGoal = count($dailyMissions);
$dailyCompleted = count($completedMissions);

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(?string $value, int $limit = 40): string {
    $value = trim((string)$value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}

// Días de la semana para la racha
$streakDays = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
$completedDays = min($currentStreak, 7);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard-visual.css">
</head>
<body class="lifequest-app">
    <?php
    $activePage = 'dashboard';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

    <main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar misiones, hábitos o recompensas...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

        <div class="dashboard-visual">
            <!-- Hero Section Grande con Personaje -->
            <section class="hero-mega">
                <div class="hero-character">
                    <div class="character-glow"></div>
                    <div class="character-avatar">
                        <div class="avatar-circle">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode($user['name']) ?>&style=circle" alt="Avatar">
                        </div>
                    </div>
                </div>

                <div class="hero-content">
                    <h1 class="hero-title">¡Sigue así, <?= e(explode(' ', $user['name'])[0]) ?>!</h1>
                    <p class="hero-subtitle">Cada misión completada te acerca a tu mejor versión.</p>

                    <div class="hero-stats-grid">
                        <div class="hero-stat">
                            <div class="stat-label">Nivel</div>
                            <div class="stat-value"><?= $currentLevel ?></div>
                            <div class="stat-mini">Camino a nivel <?= $currentLevel + 1 ?></div>
                            <div class="stat-progress">
                                <div class="progress-fill" style="width: <?= $xpPercentage . '%' ?>"></div>
                            </div>
                            <div class="stat-subtext"><?= number_format($currentXp) ?> / <?= number_format($xpForNextLevel) ?> XP</div>
                        </div>

                        <div class="hero-stat">
                            <div class="stat-label">XP actual</div>
                            <div class="stat-value"><?= number_format($currentXp) ?></div>
                            <div class="stat-mini">+<?= $xpForNextLevel - $currentXp ?> XP para subir</div>
                            <div class="stat-progress">
                                <div class="progress-fill" style="width: <?= $xpPercentage . '%' ?>"></div>
                            </div>
                        </div>

                        <div class="hero-stat">
                            <div class="stat-label">LifeCoins</div>
                            <div class="stat-value"><?= number_format($lifeCoins) ?></div>
                            <div class="stat-mini">Úsalos en la tienda</div>
                        </div>

                        <div class="hero-stat">
                            <div class="stat-label">Gemas</div>
                            <div class="stat-value"><?= $gems ?></div>
                            <div class="stat-mini">Para objetos únicos</div>
                        </div>
                    </div>

                    <div class="hero-footer">
                        <div class="streak-display">
                            <span class="streak-icon">🔥</span>
                            <div class="streak-info">
                                <div class="streak-label">Racha actual</div>
                                <div class="streak-value"><?= $currentStreak ?> días</div>
                            </div>
                            <div class="streak-calendar">
                                <?php foreach ($streakDays as $index => $day): ?>
                                    <div class="streak-day <?= $index < $completedDays ? 'completed' : '' ?>">
                                        <?= $day ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="motivation-message">
                            <strong>¡Increíble disciplina!</strong>
                            <span>Tu constancia te llevará lejos. 🎉</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Grid de 3 Columnas -->
            <div class="dashboard-three-cols">
                <!-- Columna Principal (Centro) -->
                <div class="col-main">
                    <!-- Misiones de hoy -->
                    <section class="card">
                        <div class="card-header">
                            <h2>Misiones de hoy <span class="badge"><?= count($allMissions) ?></span></h2>
                            <a href="missions.php" class="link">Ver todas</a>
                        </div>

                        <div class="missions-list">
                            <?php foreach ($allMissions as $mission): ?>
                                <?php
                                    $isCompleted = (int)$mission['completed'] === 1;
                                    $progress = (int)$mission['current_progress'];
                                    $target = (int)$mission['target_value'];
                                    $percentage = $target > 0 ? (int)(($progress / $target) * 100) : 0;
                                    $categories = ['Academia', 'Salud', 'Creatividad', 'Finanzas'];
                                    $colors = ['green', 'purple', 'orange', 'blue'];
                                    $idx = $mission['id'] % 4;
                                ?>
                                <div class="mission-item <?= $isCompleted ? 'completed' : '' ?>">
                                    <label class="mission-checkbox">
                                        <input type="checkbox" <?= $isCompleted ? 'checked' : '' ?> disabled>
                                        <span class="checkmark"></span>
                                    </label>

                                    <div class="mission-icon-wrapper">
                                        <div class="mission-icon <?= $colors[$idx] ?>">
                                            <?= e($mission['icon']) ?>
                                        </div>
                                    </div>

                                    <div class="mission-content">
                                        <div class="mission-name"><?= e(shortText($mission['title'], 40)) ?></div>
                                        <div class="mission-meta"><?= e($mission['description'] ?? 'Sin descripción') ?></div>
                                    </div>

                                    <span class="mission-category <?= $colors[$idx] ?>"><?= $categories[$idx] ?></span>

                                    <div class="mission-progress-wrapper">
                                        <div class="progress-text"><?= $progress ?> / <?= $target ?></div>
                                        <div class="progress-bar-small">
                                            <div class="progress-fill-small" style="width: <?= $percentage . '%' ?>"></div>
                                        </div>
                                    </div>

                                    <div class="mission-reward">
                                        <span class="xp-icon">✦</span>
                                        <strong>+<?= (int)$mission['xp_reward'] ?> XP</strong>
                                    </div>

                                    <button class="mission-flag" title="Marcar como importante">⚑</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Sección inferior con 3 cards -->
                    <div class="bottom-cards">
                        <!-- Metas del día -->
                        <section class="card compact">
                            <div class="card-header">
                                <h2>Metas del día</h2>
                                <span class="badge"><?= count($activeGoals) ?>/4</span>
                            </div>

                            <div class="goals-mini-list">
                                <?php foreach (array_slice($activeGoals, 0, 3) as $goal): ?>
                                    <div class="goal-mini-item">
                                        <span class="goal-mini-icon">🎯</span>
                                        <strong class="goal-mini-name"><?= e(shortText($goal['title'], 30)) ?></strong>
                                        <div class="progress-bar-mini">
                                            <div class="progress-fill-mini" style="width: <?= (int)$goal['progress'] . '%' ?>"></div>
                                        </div>
                                        <span class="goal-mini-percent"><?= (int)$goal['progress'] ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <!-- Progreso semanal -->
                        <section class="card compact">
                            <div class="card-header">
                                <h2>Progreso semanal</h2>
                                <select class="week-selector">
                                    <option>Esta semana</option>
                                </select>
                            </div>

                            <div class="chart-weekly">
                                <div class="chart-bar" style="height: 28%"><span>Lun</span></div>
                                <div class="chart-bar" style="height: 42%"><span>Mar</span></div>
                                <div class="chart-bar" style="height: 39%"><span>Mié</span></div>
                                <div class="chart-bar" style="height: 58%"><span>Jue</span></div>
                                <div class="chart-bar" style="height: 72%"><span>Vie</span></div>
                                <div class="chart-bar" style="height: 84%"><span>Sáb</span></div>
                                <div class="chart-bar active" style="height: 96%"><span>Dom</span></div>
                            </div>

                            <div class="chart-footer">
                                <strong><?= number_format($currentXp) ?> XP</strong>
                                <small>de <?= number_format($xpForNextLevel) ?> XP</small>
                            </div>
                        </section>

                        <!-- Resumen general -->
                        <section class="card compact">
                            <div class="card-header">
                                <h2>Resumen general</h2>
                            </div>

                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-icon">✅</span>
                                    <strong><?= $missionStats['total_completed'] ?></strong>
                                    <small>Misiones</small>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-icon">⚡</span>
                                    <strong><?= number_format($currentXp) ?></strong>
                                    <small>XP</small>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-icon">🪙</span>
                                    <strong><?= number_format($lifeCoins) ?></strong>
                                    <small>Coins</small>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-icon">⏱️</span>
                                    <strong>22h 30m</strong>
                                    <small>Enfoque</small>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Columna Derecha (Sidebar) -->
                <aside class="col-sidebar">
                    <!-- Objetivo diario -->
                    <section class="card">
                        <div class="card-header">
                            <h3>Objetivo diario</h3>
                        </div>

                        <p class="objective-text">Completa <?= $dailyGoal ?> misiones al día</p>

                        <div class="circle-progress">
                            <svg viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" class="circle-bg"></circle>
                                <circle cx="60" cy="60" r="54" class="circle-fill" 
                                        style="stroke-dashoffset: <?= 339.29 - (339.29 * ($dailyGoal > 0 ? ($dailyCompleted / $dailyGoal) : 0)) ?>"></circle>
                            </svg>
                            <div class="circle-content">
                                <strong><?= $dailyCompleted ?>/<?= $dailyGoal ?></strong>
                                <span>misiones</span>
                            </div>
                        </div>

                        <div class="objective-reward">✦ +200 XP</div>
                    </section>

                    <!-- Próximas misiones -->
                    <section class="card">
                        <div class="card-header">
                            <h3>Próximas misiones</h3>
                        </div>

                        <div class="upcoming-list">
                            <?php foreach (array_slice($activeGoals, 0, 3) as $goal): ?>
                                <div class="upcoming-item">
                                    <span class="upcoming-icon">🎯</span>
                                    <div class="upcoming-content">
                                        <strong><?= e(shortText($goal['title'], 28)) ?></strong>
                                        <small><?= $goal['status'] === 'in_progress' ? 'En progreso' : 'Por iniciar' ?></small>
                                    </div>
                                    <span class="upcoming-xp">+<?= (int)$goal['xp_reward'] ?> XP</span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <a href="goals.php" class="link-center">Ver calendario</a>
                    </section>

                    <!-- Tienda destacada -->
                    <section class="card">
                        <div class="card-header">
                            <h3>Tienda destacada</h3>
                            <a href="#" class="link-small">Ver todo</a>
                        </div>

                        <div class="shop-grid">
                            <div class="shop-item">
                                <div class="shop-image neon-wave">
                                    <span class="shop-badge red">NUEVO</span>
                                </div>
                                <div class="shop-name">Neon Wave</div>
                                <div class="shop-price"><span class="coin">🪙</span> 500</div>
                            </div>
                            <div class="shop-item">
                                <div class="shop-image halo-circle">
                                    <span class="shop-badge">-20%</span>
                                </div>
                                <div class="shop-name">Halo Circle</div>
                                <div class="shop-price"><span class="coin">🪙</span> 250</div>
                            </div>
                            <div class="shop-item">
                                <div class="shop-image mood-set"></div>
                                <div class="shop-name">Mood Set</div>
                                <div class="shop-price"><span class="coin">💎</span> 200</div>
                            </div>
                        </div>
                    </section>

                    <!-- Distribución de misiones -->
                    <section class="card">
                        <div class="card-header">
                            <h3>Distribución de misiones</h3>
                        </div>

                        <div class="distribution-chart">
                            <svg viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#10b981" stroke-width="20"
                                        stroke-dasharray="88 226" transform="rotate(-90 60 60)"></circle>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#3b82f6" stroke-width="20"
                                        stroke-dasharray="88 226" stroke-dashoffset="-88" transform="rotate(-90 60 60)"></circle>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#a855f7" stroke-width="20"
                                        stroke-dasharray="70 246" stroke-dashoffset="-176" transform="rotate(-90 60 60)"></circle>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#f59e0b" stroke-width="20"
                                        stroke-dasharray="56 260" stroke-dashoffset="-246" transform="rotate(-90 60 60)"></circle>
                            </svg>
                        </div>

                        <div class="distribution-legend">
                            <div class="legend-item">
                                <span class="legend-dot green"></span>
                                <span class="legend-label">Salud</span>
                                <span class="legend-value">28%</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot blue"></span>
                                <span class="legend-label">Aprendizaje</span>
                                <span class="legend-value">28%</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot purple"></span>
                                <span class="legend-label">Hábitos</span>
                                <span class="legend-value">22%</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot orange"></span>
                                <span class="legend-label">Enfoque</span>
                                <span class="legend-value">18%</span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <script>
        function updateMissionProgress(missionId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'missions.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_progress">
                <input type="hidden" name="id" value="${missionId}">
                <input type="hidden" name="progress" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
