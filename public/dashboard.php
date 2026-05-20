<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';

AuthController::requireAuth();

$userModel = new User();
$user = $userModel->findById((int) $_SESSION['user_id']);
if (!$user) { AuthController::logout(); header('Location: login.php'); exit; }

$lifeAreaModel = new LifeArea();
$areas = array_slice($lifeAreaModel->getAllByUser((int)$user['id']), 0, 6);
$goalModel = new Goal();
$mainGoals = $goalModel->getMainByUser((int)$user['id'], 3);

$xpCurrent = (int)$user['xp'];
$xpNext = 100;
$xpPercent = min(100, (int)(($xpCurrent / $xpNext) * 100));

function e(string|null $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function statusLabelDashboard(string $status): string { return ['not_started'=>'No iniciada','in_progress'=>'En progreso','paused'=>'Pausada','completed'=>'Completada','cancelled'=>'Cancelada'][$status] ?? $status; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <div class="sidebar-brand">QuestBoard</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">Inicio</a>
            <a href="areas.php">Áreas</a>
            <a href="goals.php">Metas</a>
            <a href="#">Proyectos</a>
            <a href="#">Tareas</a>
            <a href="#">Hábitos</a>
            <a href="#">Stats</a>
        </nav>
        <a href="logout.php" class="logout">Cerrar sesión</a>
    </aside>

    <main class="dashboard">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">Panel principal</p>
                <h1>Buenos días, <?= e($user['name']) ?>.</h1>
                <p class="muted">Convierte tus metas en resultados. Prioriza, ejecuta y mide.</p>
            </div>
            <a href="goals.php" class="btn btn-primary">+ Nueva meta</a>
        </header>

        <section class="summary-grid">
            <article class="card">
                <span class="card-label">Nivel</span>
                <div class="level-row"><strong><?= (int)$user['level'] ?></strong><span><?= $xpCurrent ?> / <?= $xpNext ?> XP</span></div>
                <div class="progress"><div style="width: <?= $xpPercent ?>%"></div></div>
            </article>
            <article class="card"><span class="card-label">Puntos</span><strong class="metric"><?= (int)$user['points'] ?></strong><p class="muted">Disponibles para La Tiendita.</p></article>
            <article class="card"><span class="card-label">Racha</span><strong class="metric"><?= (int)$user['current_streak'] ?> días</strong><p class="muted">Todavía no hay hábitos registrados.</p></article>
            <article class="card priority-card"><span class="card-label">Prioridad del día</span><strong>Define tu primera tarea importante.</strong><p class="muted">Este bloque se conectará con tareas y Modo Batalla.</p></article>
        </section>

        <section class="content-grid">
            <article class="card large">
                <div class="card-header"><h2>Áreas de vida</h2><a href="areas.php">Ver todas</a></div>
                <?php if (empty($areas)): ?>
                    <div class="empty-state"><p>Aún no tienes áreas creadas.</p><a href="areas.php" class="btn btn-secondary">Crear área</a></div>
                <?php else: ?>
                    <div class="dashboard-areas">
                        <?php foreach ($areas as $area): ?>
                            <div class="dashboard-area-item"><span style="background: <?= e($area['color'] ?: '#16A34A') ?>;"><?= e($area['icon'] ?: '●') ?></span><strong><?= e($area['name']) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card large">
                <div class="card-header"><h2>Metas principales</h2><a href="goals.php">Ver todas</a></div>
                <?php if (empty($mainGoals)): ?>
                    <div class="empty-state"><p>Aún no tienes metas. Empieza creando una meta clara.</p><a href="goals.php" class="btn btn-secondary">Crear meta</a></div>
                <?php else: ?>
                    <div class="dashboard-goals">
                        <?php foreach ($mainGoals as $goal): ?>
                            <div class="dashboard-goal-item">
                                <strong><?= e($goal['title']) ?></strong>
                                <div class="dashboard-goal-meta"><span><?= statusLabelDashboard($goal['status']) ?></span><span><?= (int)$goal['progress'] ?>%</span></div>
                                <div class="progress"><div style="width: <?= (int)$goal['progress'] ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card"><div class="card-header"><h2>Hábitos de hoy</h2><a href="#">Ver</a></div><p class="muted">Aquí aparecerán tus hábitos diarios.</p></article>
            <article class="card"><div class="card-header"><h2>Tareas pendientes</h2><a href="#">Ver</a></div><p class="muted">Aquí aparecerán tus próximas acciones.</p></article>
            <article class="card"><div class="card-header"><h2>Stats rápidas</h2><a href="#">Ver</a></div><p class="muted">Todavía no hay datos suficientes.</p></article>
        </section>

        <section class="action-grid">
            <article class="action-card battle"><h3>Modo Batalla</h3><p>Entra en enfoque total y ejecuta una tarea sin distracciones.</p></article>
            <article class="action-card shop"><h3>La Tiendita</h3><p>Canjea tus puntos por recompensas personales.</p></article>
            <article class="action-card danger"><h3>Zona Peligrosa</h3><p>Acciones críticas con confirmación doble.</p></article>
        </section>
    </main>
</body>
</html>
