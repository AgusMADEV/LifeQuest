<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/ProjectController.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$controller = new ProjectController();
$projectModel = new Project();
$goalModel = new Goal();
$lifeAreaModel = new LifeArea();
$userModel = new User();
$user = $userModel->findById($userId);

$message = null;
$messageType = null;
$editingProject = null;

if (isset($_GET['edit'])) {
    $editingProject = $projectModel->findByIdAndUser((int) $_GET['edit'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $controller->store($userId, $_POST);
    } elseif ($action === 'update') {
        $result = $controller->update($userId, $_POST);
    } elseif ($action === 'delete') {
        $result = $controller->destroy($userId, (int)($_POST['id'] ?? 0));
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida.'];
    }

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: projects.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$projects = $controller->index($userId);
$goals = $goalModel->getAllByUser($userId);
$areas = $lifeAreaModel->getAllByUser($userId);

function e(string|null $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function shortText(string|null $value, int $limit = 42): string {
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}
function statusLabel(string $status): string { return ['active'=>'Activa','completed'=>'Completada','paused'=>'Pausada','cancelled'=>'Cancelada'][$status] ?? $status; }
function statusClass(string $status): string { return ['active'=>'green','completed'=>'purple','paused'=>'orange','cancelled'=>'red'][$status] ?? 'blue'; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Misiones | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="lifequest-app">
    <?php
    $activePage = 'projects';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

<main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar retos...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

<section class="lq-page-shell">
            <header class="lq-page-hero">
                <div>
                    <p class="eyebrow">Ejecución real</p>
                    <h1>Misiones</h1>
                    <p>Convierte tus objetivos en proyectos ejecutables. Cada misión será la base para crear tareas y activar el Modo Batalla.</p>
                </div>
                <div class="lq-page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
                    <a href="goals.php" class="btn btn-primary">Crear objetivo</a>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="lq-crud-layout">
                <article class="lq-form-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2><?= $editingProject ? 'Editar misión' : 'Nueva misión' ?></h2>
                            <p><?= $editingProject ? 'Actualiza esta misión.' : 'Crea un bloque de trabajo conectado a un objetivo.' ?></p>
                        </div>
                        <?php if ($editingProject): ?><a href="projects.php">Cancelar</a><?php endif; ?>
                    </div>

                    <form method="POST" class="lq-form">
                        <input type="hidden" name="action" value="<?= $editingProject ? 'update' : 'create' ?>">
                        <?php if ($editingProject): ?><input type="hidden" name="id" value="<?= (int)$editingProject['id'] ?>"><?php endif; ?>

                        <label>Título
                            <input type="text" name="title" placeholder="Ej: Desarrollar módulo de usuarios" value="<?= e($editingProject['title'] ?? '') ?>" required>
                        </label>

                        <label>Descripción
                            <textarea name="description" rows="3" placeholder="Describe el alcance de esta misión."><?= e($editingProject['description'] ?? '') ?></textarea>
                        </label>

                        <label>Objetivo relacionado
                            <select name="goal_id">
                                <option value="">Sin objetivo</option>
                                <?php foreach ($goals as $goal): ?>
                                    <option value="<?= (int)$goal['id'] ?>" <?= selected($editingProject['goal_id'] ?? '', $goal['id']) ?>>
                                        <?= e($goal['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>Área de vida
                            <select name="area_id">
                                <option value="">Sin área</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int)$area['id'] ?>" <?= selected($editingProject['area_id'] ?? '', $area['id']) ?>>
                                        <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="lq-form-row">
                            <label>Estado
                                <select name="status">
                                    <option value="active" <?= selected($editingProject['status'] ?? 'active', 'active') ?>>Activa</option>
                                    <option value="paused" <?= selected($editingProject['status'] ?? 'active', 'paused') ?>>Pausada</option>
                                    <option value="completed" <?= selected($editingProject['status'] ?? 'active', 'completed') ?>>Completada</option>
                                    <option value="cancelled" <?= selected($editingProject['status'] ?? 'active', 'cancelled') ?>>Cancelada</option>
                                </select>
                            </label>

                            <label>Progreso %
                                <input type="number" name="progress" min="0" max="100" value="<?= e((string)($editingProject['progress'] ?? 0)) ?>">
                            </label>
                        </div>

                        <div class="lq-form-row">
                            <label>Fecha inicio <input type="date" name="start_date" value="<?= e($editingProject['start_date'] ?? '') ?>"></label>
                            <label>Fecha límite <input type="date" name="due_date" value="<?= e($editingProject['due_date'] ?? '') ?>"></label>
                        </div>

                        <button type="submit" class="btn btn-primary full"><?= $editingProject ? 'Guardar cambios' : 'Crear misión' ?></button>
                    </form>
                </article>

                <section class="lq-list-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2>Tus misiones</h2>
                            <p><?= count($projects) ?> misiones creadas</p>
                        </div>
                    </div>

                    <div class="lq-list-grid">
                        <?php if (empty($projects)): ?>
                            <article class="lq-empty">
                                <h2>No hay misiones todavía</h2>
                                <p>Crea tu primera misión y conecta tus objetivos con acciones concretas.</p>
                            </article>
                        <?php endif; ?>

                        <?php foreach ($projects as $project): ?>
                            <article class="lq-object-card">
                                <div class="lq-object-top">
                                    <div class="lq-object-icon" style="background: <?= e($project['area_color'] ?: '#16C79A') ?>;">
                                        <?= e($project['area_icon'] ?: '🚀') ?>
                                    </div>

                                    <div class="lq-object-title">
                                        <h2><?= e($project['title']) ?></h2>
                                        <p><?= e($project['description'] ?: 'Sin descripción.') ?></p>
                                    </div>

                                    <div class="lq-object-badges">
                                        <span class="lq-badge <?= statusClass($project['status']) ?>"><?= statusLabel($project['status']) ?></span>
                                    </div>
                                </div>

                                <div class="lq-progress-block">
                                    <div class="lq-progress-info">
                                        <span>Progreso</span>
                                        <span><?= (int)$project['progress'] ?>%</span>
                                    </div>
                                    <div class="lq-progress"><span style="width: <?= (int)$project['progress'] ?>%"></span></div>
                                </div>

                                <div class="lq-object-footer">
                                    <div class="lq-object-meta">
                                        <?php if (!empty($project['goal_title'])): ?>
                                            <span class="lq-badge blue">🎯 <?= e(shortText($project['goal_title'], 32)) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($project['area_name'])): ?>
                                            <span class="lq-badge green"><?= e(($project['area_icon'] ? $project['area_icon'] . ' ' : '') . $project['area_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($project['due_date'])): ?>
                                            <span class="lq-badge orange">📅 <?= e(date('d/m/Y', strtotime($project['due_date']))) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="lq-object-actions">
                                        <a href="projects.php?edit=<?= (int)$project['id'] ?>" class="btn btn-secondary">Editar</a>
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta misión?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
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
