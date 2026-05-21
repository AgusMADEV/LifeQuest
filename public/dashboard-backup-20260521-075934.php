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

// Obtener datos para el dashboard
$missionController = new MissionController();
$goalModel = new Goal();
$projectModel = new Project();
$taskModel = new Task();

// Misiones diarias
$dailyMissions = $missionController->index($userId, 'daily');
$completedMissions = array_filter($dailyMissions, fn($m) => (int)$m['completed'] === 1);
$pendingMissions = array_filter($dailyMissions, fn($m) => (int)$m['completed'] === 0);
$allMissions = array_merge($completedMissions, $pendingMissions);

// Metas activas (las 3 más importantes)
$activeGoals = array_filter($goalModel->getAllByUser($userId), fn($g) => 
    $g['status'] === 'in_progress' || $g['status'] === 'not_started'
);
$activeGoals = array_slice($activeGoals, 0, 5);

// Retos/Proyectos activos
$activeProjects = $projectModel->getActiveByUser($userId, 4);

// Tareas de hoy
$todayTasks = $taskModel->getTodayByUser($userId, 10);
$pendingTasks = array_filter($todayTasks, fn($t) => $t['status'] === 'pending' || $t['status'] === 'in_progress');
$completedTasks = array_filter($todayTasks, fn($t) => $t['status'] === 'completed');

// Estadísticas del usuario
$missionStats = $missionController->getStats($userId);
$currentLevel = (int)$user['level'];
$currentXp = (int)$user['xp'];
$xpForNextLevel = $currentLevel * 100;
$xpPercentage = min(100, (int)(($currentXp / $xpForNextLevel) * 100));
$lifeCoins = (int)$user['points'];
$gems = (int)($user['gems'] ?? 0);
$currentStreak = (int)$user['current_streak'];

// Porcentaje de completado hoy
$totalDailyActivities = count($dailyMissions) + count($pendingTasks);
$completedDailyActivities = count($completedMissions) + count($completedTasks);
$todayPercentage = $totalDailyActivities > 0 
    ? (int)(($completedDailyActivities / $totalDailyActivities) * 100) 
    : 0;

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(?string $value, int $limit = 40): string {
    $value = trim((string)$value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}

function timeOfDayGreeting(): string {
    $hour = (int)date('H');
    if ($hour < 12) return '¡Buenos días';
    if ($hour < 19) return '¡Buenas tardes';
    return '¡Buenas noches';
}

function motivationalMessage(int $percentage): string {
    if ($percentage === 0) return 'Empieza con una pequeña acción. ¡Tú puedes!';
    if ($percentage < 25) return '¡Buen inicio! Cada paso cuenta.';
    if ($percentage < 50) return '¡Vas por buen camino! Sigue así.';
    if ($percentage < 75) return '¡Excelente progreso! Ya casi lo tienes.';
    if ($percentage < 100) return '¡Increíble! Un último empujón.';
    return '¡Día completado! Eres increíble 🎉';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard-v2.css">
</head>
<body class="lifequest-app">
    <?php
    $activePage = 'dashboard';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

    <main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar en LifeQuest...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

        <div class="dashboard-v2">
            <!-- Encabezado Principal -->
            <header class="dashboard-hero">
                <div class="hero-text">
                    <h1><?= timeOfDayGreeting() ?>, <?= e(explode(' ', $user['name'])[0]) ?>! 👋</h1>
                    <p class="hero-subtitle"><?= motivationalMessage($todayPercentage) ?></p>
                </div>
                
                <div class="hero-stats-quick">
                    <div class="stat-badge">
                        <span class="stat-icon">🏆</span>
                        <div>
                            <strong>Nivel <?= $currentLevel ?></strong>
                            <small><?= number_format($currentXp) ?>/<?= number_format($xpForNextLevel) ?> XP</small>
                        </div>
                    </div>
                    
                    <div class="stat-badge">
                        <span class="stat-icon">🔥</span>
                        <div>
                            <strong><?= $currentStreak ?> días</strong>
                            <small>Racha actual</small>
                        </div>
                    </div>
                    
                    <div class="stat-badge">
                        <span class="stat-icon">🪙</span>
                        <div>
                            <strong><?= number_format($lifeCoins) ?></strong>
                            <small>LifeCoins</small>
                        </div>
                    </div>
                    
                    <?php if ($gems > 0): ?>
                    <div class="stat-badge">
                        <span class="stat-icon">💎</span>
                        <div>
                            <strong><?= $gems ?></strong>
                            <small>Gemas</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Grid Principal -->
            <div class="dashboard-grid">
                <!-- Columna Principal (Izquierda) -->
                <div class="dashboard-main">
                    
                    <!-- Tu Día Hoy - LO MÁS IMPORTANTE -->
                    <section class="dashboard-card today-summary">
                        <div class="card-header">
                            <h2>📅 Tu Día Hoy</h2>
                            <span class="progress-badge"><?= $todayPercentage ?>% completado</span>
                        </div>
                        
                        <div class="today-progress-ring">
                            <svg viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" class="ring-bg"></circle>
                                <circle cx="60" cy="60" r="54" class="ring-fill" 
                                        style="stroke-dashoffset: <?= 339.29 - (339.29 * $todayPercentage / 100) ?>"></circle>
                            </svg>
                            <div class="ring-content">
                                <strong><?= $todayPercentage ?>%</strong>
                                <small>Completado</small>
                            </div>
                        </div>
                        
                        <div class="today-stats">
                            <div class="today-stat">
                                <span class="stat-number"><?= count($completedMissions) ?>/<?= count($dailyMissions) ?></span>
                                <span class="stat-label">Misiones diarias</span>
                            </div>
                            <div class="today-stat">
                                <span class="stat-number"><?= count($completedTasks) ?>/<?= count($todayTasks) ?></span>
                                <span class="stat-label">Tareas de hoy</span>
                            </div>
                            <div class="today-stat">
                                <span class="stat-number"><?= count($activeGoals) ?></span>
                                <span class="stat-label">Metas activas</span>
                            </div>
                        </div>

                        <?php if ($todayPercentage < 100): ?>
                        <a href="missions.php" class="btn btn-primary btn-block">
                            Continuar con mis actividades →
                        </a>
                        <?php else: ?>
                        <div class="celebration-message">
                            <span class="celebration-icon">🎉</span>
                            <strong>¡Día completado!</strong>
                            <p>Has logrado todas tus actividades de hoy. ¡Increíble trabajo!</p>
                        </div>
                        <?php endif; ?>
                    </section>

                    <!-- Misiones Diarias Pendientes -->
                    <?php if (!empty($pendingMissions)): ?>
                    <section class="dashboard-card">
                        <div class="card-header">
                            <h2>🎮 Misiones Diarias Pendientes</h2>
                            <a href="missions.php" class="link-small">Ver todas</a>
                        </div>
                        
                        <div class="mission-quick-list">
                            <?php foreach (array_slice($pendingMissions, 0, 4) as $mission): ?>
                                <?php
                                    $progress = (int)$mission['current_progress'];
                                    $target = (int)$mission['target_value'];
                                    $percentage = $target > 0 ? (int)(($progress / $target) * 100) : 0;
                                ?>
                                <div class="mission-quick-item">
                                    <span class="mission-icon"><?= e($mission['icon']) ?></span>
                                    <div class="mission-details">
                                        <strong><?= e($mission['title']) ?></strong>
                                        <div class="mission-progress-mini">
                                            <div class="progress-bar-mini">
                                                <div class="progress-fill-mini" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <small><?= $progress ?>/<?= $target ?> <?= e($mission['target_unit']) ?></small>
                                        </div>
                                    </div>
                                    <button class="btn-quick-add" onclick="updateMissionProgress(<?= e($mission['id']) ?>)">+</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Tareas Para Hoy -->
                    <?php if (!empty($pendingTasks)): ?>
                    <section class="dashboard-card">
                        <div class="card-header">
                            <h2>✅ Tareas Para Hoy</h2>
                            <a href="tasks.php" class="link-small">Ver todas</a>
                        </div>
                        
                        <div class="task-list-compact">
                            <?php foreach (array_slice($pendingTasks, 0, 5) as $task): ?>
                                <div class="task-item-compact">
                                    <input type="checkbox" id="task-<?= e($task['id']) ?>" 
                                           onchange="completeTask(<?= e($task['id']) ?>)">
                                    <label for="task-<?= e($task['id']) ?>">
                                        <strong><?= e($task['title']) ?></strong>
                                        <?php if (!empty($task['project_title'])): ?>
                                            <small>Reto: <?= e(shortText($task['project_title'], 30)) ?></small>
                                        <?php endif; ?>
                                    </label>
                                    <?php if ((int)$task['estimated_minutes'] > 0): ?>
                                        <span class="task-duration">⏱️ <?= (int)$task['estimated_minutes'] ?> min</span>
                                    <?php endif; ?>
                                    <a href="battle.php?task_id=<?= e($task['id']) ?>" class="btn-mini-battle" title="Activar Modo Enfoque">
                                        ⚔️
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="battle.php" class="btn btn-secondary btn-block">
                            ⚔️ Activar Modo Enfoque
                        </a>
                    </section>
                    <?php endif; ?>

                    <?php if (empty($pendingMissions) && empty($pendingTasks)): ?>
                    <section class="dashboard-card empty-state-card">
                        <div class="empty-state">
                            <span class="empty-icon">🎉</span>
                            <h3>¡Todo completado por hoy!</h3>
                            <p>Has terminado todas tus misiones y tareas. ¡Excelente trabajo!</p>
                            <div class="empty-actions">
                                <a href="missions.php" class="btn btn-secondary">Ver Misiones</a>
                                <a href="goals.php" class="btn btn-primary">Crear Nueva Meta</a>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <!-- Columna Lateral (Derecha) -->
                <aside class="dashboard-sidebar">
                    
                    <!-- Tus Metas Activas -->
                    <section class="dashboard-card">
                        <div class="card-header">
                            <h3>🎯 Tus Metas Activas</h3>
                            <a href="goals.php" class="link-small">Ver todas</a>
                        </div>
                        
                        <?php if (empty($activeGoals)): ?>
                            <div class="empty-small">
                                <p>Aún no tienes metas activas</p>
                                <a href="goals.php" class="btn-text">Crear primera meta →</a>
                            </div>
                        <?php else: ?>
                            <div class="goals-compact-list">
                                <?php foreach ($activeGoals as $goal): ?>
                                    <div class="goal-compact-item">
                                        <div class="goal-compact-header">
                                            <strong><?= e(shortText($goal['title'], 35)) ?></strong>
                                            <span class="goal-progress-badge"><?= (int)$goal['progress'] ?>%</span>
                                        </div>
                                        <div class="progress-bar-thin">
                                            <div class="progress-fill-thin" style="width: <?= (int)$goal['progress'] ?>%"></div>
                                        </div>
                                        <?php if (!empty($goal['area_name'])): ?>
                                            <small class="goal-area">📦 <?= e($goal['area_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Retos en Progreso -->
                    <section class="dashboard-card">
                        <div class="card-header">
                            <h3>🎪 Retos en Progreso</h3>
                            <a href="projects.php" class="link-small">Ver todos</a>
                        </div>
                        
                        <?php if (empty($activeProjects)): ?>
                            <div class="empty-small">
                                <p>No tienes retos activos</p>
                                <a href="projects.php" class="btn-text">Crear primer reto →</a>
                            </div>
                        <?php else: ?>
                            <div class="projects-compact-list">
                                <?php foreach ($activeProjects as $project): ?>
                                    <div class="project-compact-item">
                                        <strong><?= e(shortText($project['title'], 35)) ?></strong>
                                        <div class="project-meta">
                                            <?php if (!empty($project['goal_title'])): ?>
                                                <small>🎯 <?= e(shortText($project['goal_title'], 30)) ?></small>
                                            <?php endif; ?>
                                            <span class="project-progress-mini"><?= (int)$project['progress'] ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Acceso Rápido -->
                    <section class="dashboard-card">
                        <h3>⚡ Acceso Rápido</h3>
                        <div class="quick-actions">
                            <a href="missions.php" class="quick-action-btn">
                                <span class="qa-icon">🎮</span>
                                <span class="qa-label">Misiones</span>
                            </a>
                            <a href="tasks.php" class="quick-action-btn">
                                <span class="qa-icon">✅</span>
                                <span class="qa-label">Tareas</span>
                            </a>
                            <a href="battle.php" class="quick-action-btn">
                                <span class="qa-icon">⚔️</span>
                                <span class="qa-label">Enfoque</span>
                            </a>
                            <a href="goals.php" class="quick-action-btn">
                                <span class="qa-icon">🎯</span>
                                <span class="qa-label">Metas</span>
                            </a>
                        </div>
                    </section>

                    <!-- Nivel y Progreso -->
                    <section class="dashboard-card level-card">
                        <div class="level-display">
                            <div class="level-number"><?= $currentLevel ?></div>
                            <div class="level-info">
                                <strong>Nivel <?= $currentLevel ?></strong>
                                <small>Camino a nivel <?= $currentLevel + 1 ?></small>
                            </div>
                        </div>
                        <div class="level-progress-bar">
                            <div class="level-progress-fill" style="width: <?= $xpPercentage ?>%"></div>
                        </div>
                        <small class="level-xp-text"><?= number_format($currentXp) ?> / <?= number_format($xpForNextLevel) ?> XP</small>
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

        function completeTask(taskId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'tasks.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="id" value="${taskId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
