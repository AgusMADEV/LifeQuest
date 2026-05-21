<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/TaskController.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$controller = new TaskController();
$taskModel = new Task();
$projectModel = new Project();
$goalModel = new Goal();
$lifeAreaModel = new LifeArea();
$userModel = new User();
$user = $userModel->findById($userId);

$message = null;
$messageType = null;
$editingTask = null;

if (isset($_GET['edit'])) {
    $editingTask = $taskModel->findByIdAndUser((int) $_GET['edit'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $controller->store($userId, $_POST);
    } elseif ($action === 'update') {
        $result = $controller->update($userId, $_POST);
    } elseif ($action === 'delete') {
        $result = $controller->destroy($userId, (int) ($_POST['id'] ?? 0));
    } elseif ($action === 'complete') {
        $result = $controller->complete($userId, (int) ($_POST['id'] ?? 0));
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida.'];
    }

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: tasks.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$tasks = $controller->index($userId);
$projects = $projectModel->getAllByUser($userId);
$goals = $goalModel->getAllByUser($userId);
$areas = $lifeAreaModel->getAllByUser($userId);

$completedCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'completed'));
$pendingCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'pending'));
$progressCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'in_progress'));

function e(string|null $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function selected(mixed $a, mixed $b): string { return (string) $a === (string) $b ? 'selected' : ''; }
function shortText(string|null $value, int $limit = 42): string {
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}
function priorityLabel(string $priority): string {
    return ['low'=>'Baja','medium'=>'Media','high'=>'Alta','critical'=>'Crítica'][$priority] ?? $priority;
}
function statusLabel(string $status): string {
    return ['pending'=>'Pendiente','in_progress'=>'En progreso','completed'=>'Completada','cancelled'=>'Cancelada'][$status] ?? $status;
}
function statusClass(string $status): string { return 'task-status-' . $status; }
function priorityClass(string $priority): string { return 'task-priority-' . $priority; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Misiones de hoy | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="lifequest-app">
    <?php
    $activePage = 'tasks';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

<main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar misiones...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

<section class="lq-page-shell">
            <header class="lq-page-hero">
                <div>
                    <p class="eyebrow">Acción diaria</p>
                    <h1>Misiones de hoy</h1>
                    <p>Crea tareas concretas, complétalas y gana XP y LifeCoins. Estas misiones serán la base del futuro Modo Batalla.</p>
                </div>
                <div class="lq-page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
                    <a href="projects.php" class="btn btn-primary">Ver retos</a>
                </div>
            </header>

            <section class="task-today-strip">
                <div class="lq-mini-stat"><strong><?= count($tasks) ?></strong><small>Total misiones</small></div>
                <div class="lq-mini-stat"><strong><?= $pendingCount ?></strong><small>Pendientes</small></div>
                <div class="lq-mini-stat"><strong><?= $progressCount ?></strong><small>En progreso</small></div>
                <div class="lq-mini-stat"><strong><?= $completedCount ?></strong><small>Completadas</small></div>
            </section>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="lq-crud-layout">
                <article class="lq-form-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2><?= $editingTask ? 'Editar misión' : 'Nueva misión' ?></h2>
                            <p><?= $editingTask ? 'Ajusta esta tarea diaria.' : 'Crea una acción concreta para avanzar hoy.' ?></p>
                        </div>
                        <?php if ($editingTask): ?><a href="tasks.php">Cancelar</a><?php endif; ?>
                    </div>

                    <form method="POST" class="lq-form">
                        <input type="hidden" name="action" value="<?= $editingTask ? 'update' : 'create' ?>">
                        <?php if ($editingTask): ?><input type="hidden" name="id" value="<?= (int) $editingTask['id'] ?>"><?php endif; ?>

                        <label>Título
                            <input type="text" name="title" placeholder="Ej: Programar módulo de tareas" value="<?= e($editingTask['title'] ?? '') ?>" required>
                        </label>

                        <label>Descripción
                            <textarea name="description" rows="3" placeholder="Define exactamente qué hay que hacer."><?= e($editingTask['description'] ?? '') ?></textarea>
                        </label>

                        <label>Reto
                            <select name="project_id">
                                <option value="">Sin reto</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= (int) $project['id'] ?>" <?= selected($editingTask['project_id'] ?? '', $project['id']) ?>>
                                        <?= e($project['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>Meta
                            <select name="goal_id">
                                <option value="">Sin meta</option>
                                <?php foreach ($goals as $goal): ?>
                                    <option value="<?= (int) $goal['id'] ?>" <?= selected($editingTask['goal_id'] ?? '', $goal['id']) ?>>
                                        <?= e($goal['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>Área
                            <select name="area_id">
                                <option value="">Sin área</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int) $area['id'] ?>" <?= selected($editingTask['area_id'] ?? '', $area['id']) ?>>
                                        <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="lq-form-row">
                            <label>Prioridad
                                <select name="priority">
                                    <option value="low" <?= selected($editingTask['priority'] ?? 'medium', 'low') ?>>Baja</option>
                                    <option value="medium" <?= selected($editingTask['priority'] ?? 'medium', 'medium') ?>>Media</option>
                                    <option value="high" <?= selected($editingTask['priority'] ?? 'medium', 'high') ?>>Alta</option>
                                    <option value="critical" <?= selected($editingTask['priority'] ?? 'medium', 'critical') ?>>Crítica</option>
                                </select>
                            </label>

                            <label>Estado
                                <select name="status">
                                    <option value="pending" <?= selected($editingTask['status'] ?? 'pending', 'pending') ?>>Pendiente</option>
                                    <option value="in_progress" <?= selected($editingTask['status'] ?? 'pending', 'in_progress') ?>>En progreso</option>
                                    <option value="completed" <?= selected($editingTask['status'] ?? 'pending', 'completed') ?>>Completada</option>
                                    <option value="cancelled" <?= selected($editingTask['status'] ?? 'pending', 'cancelled') ?>>Cancelada</option>
                                </select>
                            </label>
                        </div>

                        <div class="lq-form-row">
                            <label>Tiempo estimado
                                <input type="number" name="estimated_minutes" min="0" placeholder="Ej: 45" value="<?= e((string) ($editingTask['estimated_minutes'] ?? 25)) ?>">
                            </label>
                            <label>Fecha límite
                                <input type="date" name="due_date" value="<?= e($editingTask['due_date'] ?? date('Y-m-d')) ?>">
                            </label>
                        </div>

                        <div class="lq-form-row">
                            <label>Recompensa XP
                                <input type="number" name="xp_reward" min="0" value="<?= e((string) ($editingTask['xp_reward'] ?? 50)) ?>">
                            </label>
                            <label>LifeCoins
                                <input type="number" name="points_reward" min="0" value="<?= e((string) ($editingTask['points_reward'] ?? 15)) ?>">
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary full"><?= $editingTask ? 'Guardar cambios' : 'Crear misión' ?></button>
                    </form>
                </article>

                <section class="lq-list-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2>Tus misiones</h2>
                            <p><?= count($tasks) ?> misiones creadas</p>
                        </div>
                    </div>

                    <div class="lq-list-grid">
                        <?php if (empty($tasks)): ?>
                            <article class="lq-empty">
                                <h2>No hay misiones todavía</h2>
                                <p>Crea tu primera tarea accionable. Después podrás completarla y ganar XP y LifeCoins.</p>
                            </article>
                        <?php endif; ?>

                        <?php foreach ($tasks as $task): ?>
                            <article class="lq-object-card">
                                <div class="lq-object-top">
                                    <div class="lq-object-icon" style="background: <?= e($task['area_color'] ?: '#16C79A') ?>;">
                                        <?= e($task['area_icon'] ?: '✅') ?>
                                    </div>

                                    <div class="lq-object-title">
                                        <h2><?= e($task['title']) ?></h2>
                                        <p><?= e($task['description'] ?: 'Sin descripción.') ?></p>
                                    </div>

                                    <div class="lq-object-badges">
                                        <span class="lq-badge <?= priorityClass($task['priority']) ?>"><?= priorityLabel($task['priority']) ?></span>
                                        <span class="lq-badge <?= statusClass($task['status']) ?>"><?= statusLabel($task['status']) ?></span>
                                    </div>
                                </div>

                                <div class="lq-object-footer">
                                    <div class="lq-object-meta">
                                        <?php if (!empty($task['project_title'])): ?>
                                            <span class="lq-badge blue">🚀 <?= e(shortText($task['project_title'], 28)) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($task['goal_title'])): ?>
                                            <span class="lq-badge purple">🎯 <?= e(shortText($task['goal_title'], 28)) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($task['area_name'])): ?>
                                            <span class="lq-badge green"><?= e(($task['area_icon'] ? $task['area_icon'] . ' ' : '') . $task['area_name']) ?></span>
                                        <?php endif; ?>
                                        <span class="lq-badge">⏱️ <?= (int) $task['estimated_minutes'] ?> min</span>
                                        <?php if (!empty($task['due_date'])): ?>
                                            <span class="lq-badge orange">📅 <?= e(date('d/m/Y', strtotime($task['due_date']))) ?></span>
                                        <?php endif; ?>
                                        <span class="lq-badge purple">✦ +<?= (int) $task['xp_reward'] ?> XP</span>
                                        <span class="lq-badge orange">🪙 +<?= (int) $task['points_reward'] ?></span>
                                    </div>

                                    <div class="task-main-actions">
                                        <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                                            <form method="POST" onsubmit="return confirm('¿Marcar esta misión como completada? Ganarás XP y LifeCoins.');">
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                <button type="submit" class="btn lq-task-complete">Completar</button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="tasks.php?edit=<?= (int) $task['id'] ?>" class="btn btn-secondary">Editar</a>

                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta misión?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                            <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </section>
        </section>
    </main>
</body>
</html>
