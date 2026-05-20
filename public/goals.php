<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/GoalController.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$controller = new GoalController();
$goalModel = new Goal();
$lifeAreaModel = new LifeArea();
$message = null;
$messageType = null;
$editingGoal = null;

if (isset($_GET['edit'])) {
    $editingGoal = $goalModel->findByIdAndUser((int) $_GET['edit'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') $result = $controller->store($userId, $_POST);
    elseif ($action === 'update') $result = $controller->update($userId, $_POST);
    elseif ($action === 'delete') $result = $controller->destroy($userId, (int)($_POST['id'] ?? 0));
    else $result = ['success' => false, 'message' => 'Acción no válida.'];

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: goals.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$goals = $controller->index($userId);
$areas = $lifeAreaModel->getAllByUser($userId);

function e(string|null $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function activeNav(string $page): string { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
function goalTypeLabel(string $type): string { return ['daily'=>'Diaria','weekly'=>'Semanal','monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual','future'=>'Futuro'][$type] ?? $type; }
function priorityLabel(string $priority): string { return ['low'=>'Baja','medium'=>'Media','high'=>'Alta','critical'=>'Crítica'][$priority] ?? $priority; }
function statusLabel(string $status): string { return ['not_started'=>'No iniciada','in_progress'=>'En progreso','paused'=>'Pausada','completed'=>'Completada','cancelled'=>'Cancelada'][$status] ?? $status; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Metas | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <div class="sidebar-brand">QuestBoard</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= activeNav('dashboard.php') ?>">Inicio</a>
            <a href="areas.php" class="<?= activeNav('areas.php') ?>">Áreas</a>
            <a href="goals.php" class="<?= activeNav('goals.php') ?>">Metas</a>
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
                <p class="eyebrow">Dirección y progreso</p>
                <h1>Metas</h1>
                <p class="muted">Define objetivos claros y conéctalos después con proyectos, tareas y hábitos.</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Volver al dashboard</a>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="areas-layout">
            <article class="card">
                <div class="card-header">
                    <h2><?= $editingGoal ? 'Editar meta' : 'Crear nueva meta' ?></h2>
                    <?php if ($editingGoal): ?><a href="goals.php">Cancelar</a><?php endif; ?>
                </div>

                <form method="POST" class="form compact-form">
                    <input type="hidden" name="action" value="<?= $editingGoal ? 'update' : 'create' ?>">
                    <?php if ($editingGoal): ?><input type="hidden" name="id" value="<?= (int)$editingGoal['id'] ?>"><?php endif; ?>

                    <label>Título
                        <input type="text" name="title" placeholder="Ej: Terminar el TFG" value="<?= e($editingGoal['title'] ?? '') ?>" required>
                    </label>

                    <label>Descripción
                        <textarea name="description" rows="3" placeholder="Describe qué quieres conseguir y por qué."><?= e($editingGoal['description'] ?? '') ?></textarea>
                    </label>

                    <label>Área de vida
                        <select name="area_id">
                            <option value="">Sin área</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= (int)$area['id'] ?>" <?= selected($editingGoal['area_id'] ?? '', $area['id']) ?>><?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="form-row">
                        <label>Tipo
                            <select name="type">
                                <option value="daily" <?= selected($editingGoal['type'] ?? 'monthly', 'daily') ?>>Diaria</option>
                                <option value="weekly" <?= selected($editingGoal['type'] ?? 'monthly', 'weekly') ?>>Semanal</option>
                                <option value="monthly" <?= selected($editingGoal['type'] ?? 'monthly', 'monthly') ?>>Mensual</option>
                                <option value="quarterly" <?= selected($editingGoal['type'] ?? 'monthly', 'quarterly') ?>>Trimestral</option>
                                <option value="yearly" <?= selected($editingGoal['type'] ?? 'monthly', 'yearly') ?>>Anual</option>
                                <option value="future" <?= selected($editingGoal['type'] ?? 'monthly', 'future') ?>>Futuro</option>
                            </select>
                        </label>
                        <label>Prioridad
                            <select name="priority">
                                <option value="low" <?= selected($editingGoal['priority'] ?? 'medium', 'low') ?>>Baja</option>
                                <option value="medium" <?= selected($editingGoal['priority'] ?? 'medium', 'medium') ?>>Media</option>
                                <option value="high" <?= selected($editingGoal['priority'] ?? 'medium', 'high') ?>>Alta</option>
                                <option value="critical" <?= selected($editingGoal['priority'] ?? 'medium', 'critical') ?>>Crítica</option>
                            </select>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Estado
                            <select name="status">
                                <option value="not_started" <?= selected($editingGoal['status'] ?? 'not_started', 'not_started') ?>>No iniciada</option>
                                <option value="in_progress" <?= selected($editingGoal['status'] ?? 'not_started', 'in_progress') ?>>En progreso</option>
                                <option value="paused" <?= selected($editingGoal['status'] ?? 'not_started', 'paused') ?>>Pausada</option>
                                <option value="completed" <?= selected($editingGoal['status'] ?? 'not_started', 'completed') ?>>Completada</option>
                                <option value="cancelled" <?= selected($editingGoal['status'] ?? 'not_started', 'cancelled') ?>>Cancelada</option>
                            </select>
                        </label>
                        <label>Progreso %
                            <input type="number" name="progress" min="0" max="100" value="<?= e((string)($editingGoal['progress'] ?? 0)) ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Fecha inicio <input type="date" name="start_date" value="<?= e($editingGoal['start_date'] ?? '') ?>"></label>
                        <label>Fecha límite <input type="date" name="due_date" value="<?= e($editingGoal['due_date'] ?? '') ?>"></label>
                    </div>

                    <div class="form-row">
                        <label>Recompensa XP <input type="number" name="xp_reward" min="0" value="<?= e((string)($editingGoal['xp_reward'] ?? 50)) ?>"></label>
                        <label>Puntos <input type="number" name="points_reward" min="0" value="<?= e((string)($editingGoal['points_reward'] ?? 25)) ?>"></label>
                    </div>

                    <button type="submit" class="btn btn-primary full"><?= $editingGoal ? 'Guardar cambios' : 'Crear meta' ?></button>
                </form>
            </article>

            <section class="areas-list">
                <?php if (empty($goals)): ?>
                    <article class="card empty-state"><h2>No hay metas todavía</h2><p>Crea tu primera meta. Luego la conectaremos con proyectos, tareas y hábitos.</p></article>
                <?php endif; ?>

                <?php foreach ($goals as $goal): ?>
                    <article class="goal-card">
                        <div class="goal-top">
                            <div class="goal-title-group">
                                <h2><?= e($goal['title']) ?></h2>
                                <p><?= e($goal['description'] ?: 'Sin descripción.') ?></p>
                            </div>
                            <div class="goal-badges">
                                <span class="badge badge-<?= e($goal['priority']) ?>"><?= priorityLabel($goal['priority']) ?></span>
                                <span class="badge badge-status"><?= statusLabel($goal['status']) ?></span>
                            </div>
                        </div>

                        <div class="goal-progress-row">
                            <div class="goal-progress-info"><span>Progreso</span><span><?= (int)$goal['progress'] ?>%</span></div>
                            <div class="progress"><div style="width: <?= (int)$goal['progress'] ?>%"></div></div>
                        </div>

                        <div class="goal-footer">
                            <div class="goal-meta">
                                <span class="badge"><?= goalTypeLabel($goal['type']) ?></span>
                                <?php if (!empty($goal['area_name'])): ?><span class="badge"><?= e(($goal['area_icon'] ? $goal['area_icon'] . ' ' : '') . $goal['area_name']) ?></span><?php endif; ?>
                                <span class="badge">+<?= (int)$goal['xp_reward'] ?> XP</span>
                                <span class="badge">+<?= (int)$goal['points_reward'] ?> puntos</span>
                            </div>
                            <div class="goal-actions">
                                <a href="goals.php?edit=<?= (int)$goal['id'] ?>" class="btn btn-secondary">Editar</a>
                                <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta meta?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$goal['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </section>
    </main>
</body>
</html>
