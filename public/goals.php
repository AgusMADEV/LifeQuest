<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/GoalController.php';
require_once __DIR__ . '/../app/Controllers/ProjectController.php';
require_once __DIR__ . '/../app/Controllers/TaskController.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$goalController = new GoalController();
$projectController = new ProjectController();
$taskController = new TaskController();

$goalModel = new Goal();
$projectModel = new Project();
$taskModel = new Task();
$lifeAreaModel = new LifeArea();

$validSections = ['goals', 'projects', 'tasks'];
$sectionInput = $_GET['section'] ?? 'goals';
$section = in_array($sectionInput, $validSections, true) ? $sectionInput : 'goals';

$validPeriods = ['daily', 'weekly', 'monthly'];
$periodInput = $_GET['period'] ?? 'daily';
$period = in_array($periodInput, $validPeriods, true) ? $periodInput : 'daily';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = null;
$messageType = null;

$editingGoal = null;
$editingProject = null;
$editingTask = null;

$goalFormData = [];
$projectFormData = [];
$taskFormData = [];

$goalFormErrors = [];
$projectFormErrors = [];
$taskFormErrors = [];

if ($section === 'goals' && isset($_GET['edit_goal'])) {
    $editingGoal = $goalModel->findByIdAndUser((int) $_GET['edit_goal'], $userId);
}
if ($section === 'projects' && isset($_GET['edit_project'])) {
    $editingProject = $projectModel->findByIdAndUser((int) $_GET['edit_project'], $userId);
}
if ($section === 'tasks' && isset($_GET['edit_task'])) {
    $editingTask = $taskModel->findByIdAndUser((int) $_GET['edit_task'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entityInput = $_POST['entity'] ?? $section;
    $entity = in_array($entityInput, $validSections, true) ? $entityInput : 'goals';
    $action = $_POST['action'] ?? '';

    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
        $result = ['success' => false, 'message' => 'Sesión expirada o token inválido. Recarga la página e inténtalo de nuevo.'];
    } else {
        if ($entity === 'goals') {
            if ($action === 'create') {
                $result = $goalController->store($userId, $_POST);
            } elseif ($action === 'update') {
                $result = $goalController->update($userId, $_POST);
            } elseif ($action === 'delete') {
                $result = $goalController->destroy($userId, (int) ($_POST['id'] ?? 0));
            } else {
                $result = ['success' => false, 'message' => 'Acción no válida.'];
            }
        } elseif ($entity === 'projects') {
            if ($action === 'create') {
                $result = $projectController->store($userId, $_POST);
            } elseif ($action === 'update') {
                $result = $projectController->update($userId, $_POST);
            } elseif ($action === 'delete') {
                $result = $projectController->destroy($userId, (int) ($_POST['id'] ?? 0));
            } else {
                $result = ['success' => false, 'message' => 'Acción no válida.'];
            }
        } else {
            if ($action === 'create') {
                $result = $taskController->store($userId, $_POST);
            } elseif ($action === 'update') {
                $result = $taskController->update($userId, $_POST);
            } elseif ($action === 'delete') {
                $result = $taskController->destroy($userId, (int) ($_POST['id'] ?? 0));
            } elseif ($action === 'complete') {
                $result = $taskController->complete($userId, (int) ($_POST['id'] ?? 0));
            } else {
                $result = ['success' => false, 'message' => 'Acción no válida.'];
            }
        }
    }

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if (!$result['success']) {
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];
        if ($entity === 'goals') {
            $goalFormData = $_POST;
            $goalFormErrors = $errors;
        } elseif ($entity === 'projects') {
            $projectFormData = $_POST;
            $projectFormErrors = $errors;
        } else {
            $taskFormData = $_POST;
            $taskFormErrors = $errors;
        }
    }

    if ($result['success']) {
        $redirect = 'goals.php?section=' . $entity;
        if ($entity === 'tasks') {
            $redirect .= '&period=' . urlencode($period);
        }
        $redirect .= '&message=' . urlencode($message) . '&type=' . $messageType;
        header('Location: ' . $redirect);
        exit;
    }

    $section = $entity;
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$goals = $goalController->index($userId);
$projects = $projectController->index($userId);
$tasks = $taskController->index($userId);
$areas = $lifeAreaModel->getAllByUser($userId);

if ($section === 'tasks') {
    $now = new DateTimeImmutable('today');
    $weekStart = $now->modify('monday this week');
    $weekEnd = $weekStart->modify('+6 days');

    $tasks = array_values(array_filter(
        $tasks,
        static function (array $task) use ($period, $now, $weekStart, $weekEnd): bool {
            $referenceDate = $task['due_date'] ?: ($task['created_at'] ?? null);
            if (!$referenceDate) {
                return $period === 'daily';
            }

            try {
                $date = new DateTimeImmutable((string) $referenceDate);
            } catch (Throwable) {
                return false;
            }

            if ($period === 'daily') {
                return $date->format('Y-m-d') === $now->format('Y-m-d');
            }
            if ($period === 'weekly') {
                return $date >= $weekStart && $date <= $weekEnd;
            }

            return $date->format('Y-m') === $now->format('Y-m');
        }
    ));
}

$completedCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'completed'));
$pendingCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'pending'));
$progressCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'in_progress'));
$dailyGoal = max(3, count($tasks));
$dailyProgressPercent = (int) min(100, round(($completedCount / max(1, $dailyGoal)) * 100));
$focusMinutes = array_reduce(
    $tasks,
    static fn(int $carry, array $task) => $carry + (int) ($task['estimated_minutes'] ?? 0),
    0
);
$focusHours = intdiv($focusMinutes, 60);
$focusRemainder = $focusMinutes % 60;

$searchPlaceholder = [
    'goals' => 'Buscar metas...',
    'projects' => 'Buscar retos...',
    'tasks' => 'Buscar misiones...',
][$section];

$heroTitle = [
    'goals' => 'Metas',
    'projects' => 'Retos',
    'tasks' => 'Misiones',
][$section];

$heroEyebrow = [
    'goals' => 'Dirección y progreso',
    'projects' => 'Bloques de avance',
    'tasks' => 'Acción diaria',
][$section];

$heroDescription = [
    'goals' => 'Define lo que quieres conseguir y mide tu avance con progreso, prioridad y recompensas.',
    'projects' => 'Convierte tus metas en retos accionables para mantener enfoque y claridad.',
    'tasks' => 'Completa misiones diarias para sumar XP, LifeCoins y progreso real.',
][$section];

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? 'selected' : '';
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '...';
}

function goalTypeLabel(string $type): string
{
    return ['daily' => 'Diaria', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual', 'future' => 'Futuro'][$type] ?? $type;
}

function priorityLabel(string $priority): string
{
    return ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'][$priority] ?? $priority;
}

function goalStatusLabel(string $status): string
{
    return ['not_started' => 'No iniciada', 'in_progress' => 'En progreso', 'paused' => 'Pausada', 'completed' => 'Completada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function projectStatusLabel(string $status): string
{
    return ['active' => 'Activa', 'completed' => 'Completada', 'paused' => 'Pausada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function taskStatusLabel(string $status): string
{
    return ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function statusClass(string $status): string
{
    return [
        'not_started' => 'blue',
        'in_progress' => 'green',
        'paused' => 'orange',
        'completed' => 'purple',
        'cancelled' => 'red',
        'active' => 'green',
        'pending' => 'blue',
    ][$status] ?? 'blue';
}

function priorityClass(string $priority): string
{
    return ['low' => 'green', 'medium' => 'orange', 'high' => 'red', 'critical' => 'red'][$priority] ?? 'blue';
}

function taskVisualProgress(string $status): int
{
    return [
        'completed' => 100,
        'in_progress' => 65,
        'pending' => 25,
        'cancelled' => 10,
    ][$status] ?? 20;
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') . '">';
}

function formValue(array $formData, string $key, mixed $fallback = ''): mixed
{
    return array_key_exists($key, $formData) ? $formData[$key] : $fallback;
}

function fieldError(array $errors, string $key): string
{
    return (string) ($errors[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Metas | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/tasks.css">
    <link rel="stylesheet" href="../assets/css/modules/metas.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'goals'; ?>
        <?php require __DIR__ . '/partials/sidebar_nav.php'; ?>

        <section class="lq-sidebar-card unlock">
            <div>
                <strong>Tu centro de avance</strong>
                <p>Todo tu sistema en un solo módulo: metas, retos y misiones.</p>
                <a href="goals.php?section=tasks&period=<?= e($period) ?>" class="mini-btn">Ir a misiones</a>
            </div>
            <span class="bag">🧠</span>
        </section>

        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <header class="lq-topbar">
            <button class="icon-btn">☰</button>
            <div class="search-box">
                <span>🔎</span>
                <input type="search" placeholder="<?= e($searchPlaceholder) ?>" disabled>
                <kbd>⌘ K</kbd>
            </div>
            <div class="top-stats">
                <div class="xp-pill">
                    <span>✦</span>
                    <strong><?= number_format((int) ($user['xp'] ?? 0), 0, ',', '.') ?> XP</strong>
                    <div class="mini-progress"><i style="width: 35%"></i></div>
                    <small>Nivel <?= (int) ($user['level'] ?? 1) ?></small>
                </div>
                <div class="currency-pill coin"><span>🪙</span><strong><?= number_format((int) ($user['points'] ?? 0), 0, ',', '.') ?></strong></div>
                <div class="profile-pill">
                    <div class="mini-avatar image-like"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
                    <strong>¡Hola, <?= e(shortText($user['name'] ?? 'Usuario', 12)) ?>! 👋</strong>
                </div>
            </div>
        </header>

        <section class="lq-page-shell metas-shell">
            <header class="lq-page-hero metas-hero">
                <div>
                    <p class="eyebrow"><?= e($heroEyebrow) ?></p>
                    <h1><?= e($heroTitle) ?></h1>
                    <p><?= e($heroDescription) ?></p>
                </div>
                <div class="lq-page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
                    <a href="goals.php?section=tasks&period=<?= e($period) ?>" class="btn btn-primary">Abrir misiones</a>
                </div>
            </header>

            <nav class="metas-tabs" aria-label="Navegación metas">
                <a href="goals.php?section=goals" class="<?= $section === 'goals' ? 'active' : '' ?>">Metas</a>
                <a href="goals.php?section=projects" class="<?= $section === 'projects' ? 'active' : '' ?>">Retos</a>
                <a href="goals.php?section=tasks&period=<?= e($period) ?>" class="<?= $section === 'tasks' ? 'active' : '' ?>">Misiones</a>
            </nav>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="hub-layout <?= $section === 'tasks' ? 'tasks-mode' : '' ?>">
                <section>
                    <?php if ($section === 'goals'): ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel">
                                <?php $goalCurrent = !empty($goalFormData) ? $goalFormData : ($editingGoal ?? []); ?>
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingGoal ? 'Editar meta' : 'Nueva meta' ?></h2>
                                        <p><?= $editingGoal ? 'Ajusta el progreso y la prioridad.' : 'Crea una meta clara y medible.' ?></p>
                                    </div>
                                    <?php if ($editingGoal): ?><a href="goals.php?section=goals">Cancelar</a><?php endif; ?>
                                </div>

                                <form method="POST" class="lq-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="entity" value="goals">
                                    <input type="hidden" name="action" value="<?= $editingGoal ? 'update' : 'create' ?>">
                                    <?php if ($editingGoal): ?><input type="hidden" name="id" value="<?= (int) $editingGoal['id'] ?>"><?php endif; ?>

                                    <label>Título
                                        <input type="text" name="title" placeholder="Ej: Terminar el TFG" value="<?= e((string) formValue($goalCurrent, 'title', '')) ?>" required>
                                        <?php if (fieldError($goalFormErrors, 'title') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($goalFormErrors, 'title')) ?></small>
                                        <?php endif; ?>
                                    </label>

                                    <label>Descripción
                                        <textarea name="description" rows="3" placeholder="Describe qué quieres conseguir."><?= e((string) formValue($goalCurrent, 'description', '')) ?></textarea>
                                    </label>

                                    <label>Área de vida
                                        <select name="area_id">
                                            <option value="">Sin área</option>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= (int) $area['id'] ?>" <?= selected(formValue($goalCurrent, 'area_id', ''), $area['id']) ?>>
                                                    <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (fieldError($goalFormErrors, 'area_id') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($goalFormErrors, 'area_id')) ?></small>
                                        <?php endif; ?>
                                    </label>

                                    <div class="lq-form-row">
                                        <label>Tipo
                                            <select name="type">
                                                <option value="daily" <?= selected(formValue($goalCurrent, 'type', 'monthly'), 'daily') ?>>Diaria</option>
                                                <option value="weekly" <?= selected(formValue($goalCurrent, 'type', 'monthly'), 'weekly') ?>>Semanal</option>
                                                <option value="monthly" <?= selected(formValue($goalCurrent, 'type', 'monthly'), 'monthly') ?>>Mensual</option>
                                                <option value="quarterly" <?= selected(formValue($goalCurrent, 'type', 'monthly'), 'quarterly') ?>>Trimestral</option>
                                                <option value="yearly" <?= selected(formValue($goalCurrent, 'type', 'monthly'), 'yearly') ?>>Anual</option>
                                                <option value="future" <?= selected(formValue($goalCurrent, 'type', 'monthly'), 'future') ?>>Futuro</option>
                                            </select>
                                        </label>

                                        <label>Prioridad
                                            <select name="priority">
                                                <option value="low" <?= selected(formValue($goalCurrent, 'priority', 'medium'), 'low') ?>>Baja</option>
                                                <option value="medium" <?= selected(formValue($goalCurrent, 'priority', 'medium'), 'medium') ?>>Media</option>
                                                <option value="high" <?= selected(formValue($goalCurrent, 'priority', 'medium'), 'high') ?>>Alta</option>
                                                <option value="critical" <?= selected(formValue($goalCurrent, 'priority', 'medium'), 'critical') ?>>Crítica</option>
                                            </select>
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Estado
                                            <select name="status">
                                                <option value="not_started" <?= selected(formValue($goalCurrent, 'status', 'not_started'), 'not_started') ?>>No iniciada</option>
                                                <option value="in_progress" <?= selected(formValue($goalCurrent, 'status', 'not_started'), 'in_progress') ?>>En progreso</option>
                                                <option value="paused" <?= selected(formValue($goalCurrent, 'status', 'not_started'), 'paused') ?>>Pausada</option>
                                                <option value="completed" <?= selected(formValue($goalCurrent, 'status', 'not_started'), 'completed') ?>>Completada</option>
                                                <option value="cancelled" <?= selected(formValue($goalCurrent, 'status', 'not_started'), 'cancelled') ?>>Cancelada</option>
                                            </select>
                                        </label>

                                        <label>Progreso %
                                            <input type="number" name="progress" min="0" max="100" value="<?= e((string) formValue($goalCurrent, 'progress', 0)) ?>">
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Fecha inicio <input type="date" name="start_date" value="<?= e((string) formValue($goalCurrent, 'start_date', '')) ?>"></label>
                                        <label>Fecha límite <input type="date" name="due_date" value="<?= e((string) formValue($goalCurrent, 'due_date', '')) ?>"></label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Recompensa
                                            <input type="text" value="Automática según tipo y prioridad" disabled>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary full"><?= $editingGoal ? 'Guardar cambios' : 'Crear meta' ?></button>
                                </form>
                            </article>

                            <section class="lq-list-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2>Tus metas</h2>
                                        <p><?= count($goals) ?> metas creadas</p>
                                    </div>
                                </div>

                                <div class="lq-list-grid">
                                    <?php if (empty($goals)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay metas todavía</h2>
                                            <p>Crea tu primera meta y luego desglósala en retos y misiones.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($goals as $goal): ?>
                                        <article class="lq-object-card">
                                            <div class="lq-object-top">
                                                <div class="lq-object-icon" style="background: <?= e($goal['area_color'] ?: '#16C79A') ?>;"><?= e($goal['area_icon'] ?: '🎯') ?></div>
                                                <div class="lq-object-title">
                                                    <h2><?= e($goal['title']) ?></h2>
                                                    <p><?= e($goal['description'] ?: 'Sin descripción.') ?></p>
                                                </div>
                                                <div class="lq-object-badges">
                                                    <span class="lq-badge <?= priorityClass($goal['priority']) ?>"><?= priorityLabel($goal['priority']) ?></span>
                                                    <span class="lq-badge <?= statusClass($goal['status']) ?>"><?= goalStatusLabel($goal['status']) ?></span>
                                                </div>
                                            </div>

                                            <div class="lq-progress-block">
                                                <div class="lq-progress-info"><span>Progreso</span><span><?= (int) $goal['progress'] ?>%</span></div>
                                                <div class="lq-progress"><span style="width: <?= (int) $goal['progress'] ?>%"></span></div>
                                            </div>

                                            <div class="lq-object-footer">
                                                <div class="lq-object-meta">
                                                    <span class="lq-badge"><?= goalTypeLabel($goal['type']) ?></span>
                                                    <?php if (!empty($goal['area_name'])): ?>
                                                        <span class="lq-badge green"><?= e(($goal['area_icon'] ? $goal['area_icon'] . ' ' : '') . $goal['area_name']) ?></span>
                                                    <?php endif; ?>
                                                    <span class="lq-badge purple">XP +<?= (int) $goal['xp_reward'] ?></span>
                                                    <span class="lq-badge orange">LC +<?= (int) $goal['points_reward'] ?></span>
                                                </div>

                                                <div class="lq-object-actions">
                                                    <a href="goals.php?section=goals&edit_goal=<?= (int) $goal['id'] ?>" class="btn btn-secondary">Editar</a>
                                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta meta?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="entity" value="goals">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $goal['id'] ?>">
                                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </section>
                    <?php elseif ($section === 'projects'): ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel">
                                <?php $projectCurrent = !empty($projectFormData) ? $projectFormData : ($editingProject ?? []); ?>
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingProject ? 'Editar reto' : 'Nuevo reto' ?></h2>
                                        <p><?= $editingProject ? 'Actualiza este reto.' : 'Crea un bloque conectado a una meta.' ?></p>
                                    </div>
                                    <?php if ($editingProject): ?><a href="goals.php?section=projects">Cancelar</a><?php endif; ?>
                                </div>

                                <form method="POST" class="lq-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="entity" value="projects">
                                    <input type="hidden" name="action" value="<?= $editingProject ? 'update' : 'create' ?>">
                                    <?php if ($editingProject): ?><input type="hidden" name="id" value="<?= (int) $editingProject['id'] ?>"><?php endif; ?>

                                    <label>Título
                                        <input type="text" name="title" placeholder="Ej: Lanzar módulo de hábitos" value="<?= e((string) formValue($projectCurrent, 'title', '')) ?>" required>
                                        <?php if (fieldError($projectFormErrors, 'title') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($projectFormErrors, 'title')) ?></small>
                                        <?php endif; ?>
                                    </label>

                                    <label>Descripción
                                        <textarea name="description" rows="3" placeholder="Describe el alcance del reto."><?= e((string) formValue($projectCurrent, 'description', '')) ?></textarea>
                                    </label>

                                    <label>Meta relacionada
                                        <select name="goal_id">
                                            <option value="">Sin meta</option>
                                            <?php foreach ($goals as $goal): ?>
                                                <option value="<?= (int) $goal['id'] ?>" <?= selected(formValue($projectCurrent, 'goal_id', ''), $goal['id']) ?>><?= e($goal['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (fieldError($projectFormErrors, 'goal_id') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($projectFormErrors, 'goal_id')) ?></small>
                                        <?php endif; ?>
                                    </label>

                                    <label>Área de vida
                                        <select name="area_id">
                                            <option value="">Sin área</option>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= (int) $area['id'] ?>" <?= selected(formValue($projectCurrent, 'area_id', ''), $area['id']) ?>><?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (fieldError($projectFormErrors, 'area_id') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($projectFormErrors, 'area_id')) ?></small>
                                        <?php endif; ?>
                                    </label>

                                    <div class="lq-form-row">
                                        <label>Estado
                                            <select name="status">
                                                <option value="active" <?= selected(formValue($projectCurrent, 'status', 'active'), 'active') ?>>Activa</option>
                                                <option value="paused" <?= selected(formValue($projectCurrent, 'status', 'active'), 'paused') ?>>Pausada</option>
                                                <option value="completed" <?= selected(formValue($projectCurrent, 'status', 'active'), 'completed') ?>>Completada</option>
                                                <option value="cancelled" <?= selected(formValue($projectCurrent, 'status', 'active'), 'cancelled') ?>>Cancelada</option>
                                            </select>
                                        </label>

                                        <label>Progreso %
                                            <input type="number" name="progress" min="0" max="100" value="<?= e((string) formValue($projectCurrent, 'progress', 0)) ?>">
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Fecha inicio <input type="date" name="start_date" value="<?= e((string) formValue($projectCurrent, 'start_date', '')) ?>"></label>
                                        <label>Fecha límite <input type="date" name="due_date" value="<?= e((string) formValue($projectCurrent, 'due_date', '')) ?>"></label>
                                    </div>

                                    <button type="submit" class="btn btn-primary full"><?= $editingProject ? 'Guardar cambios' : 'Crear reto' ?></button>
                                </form>
                            </article>

                            <section class="lq-list-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2>Tus retos</h2>
                                        <p><?= count($projects) ?> retos creados</p>
                                    </div>
                                </div>

                                <div class="lq-list-grid">
                                    <?php if (empty($projects)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay retos todavía</h2>
                                            <p>Crea tu primer reto para dividir metas complejas en pasos.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($projects as $project): ?>
                                        <article class="lq-object-card">
                                            <div class="lq-object-top">
                                                <div class="lq-object-icon" style="background: <?= e($project['area_color'] ?: '#16C79A') ?>;"><?= e($project['area_icon'] ?: '🚀') ?></div>
                                                <div class="lq-object-title">
                                                    <h2><?= e($project['title']) ?></h2>
                                                    <p><?= e($project['description'] ?: 'Sin descripción.') ?></p>
                                                </div>
                                                <div class="lq-object-badges"><span class="lq-badge <?= statusClass($project['status']) ?>"><?= projectStatusLabel($project['status']) ?></span></div>
                                            </div>

                                            <div class="lq-progress-block">
                                                <div class="lq-progress-info"><span>Progreso</span><span><?= (int) $project['progress'] ?>%</span></div>
                                                <div class="lq-progress"><span style="width: <?= (int) $project['progress'] ?>%"></span></div>
                                            </div>

                                            <div class="lq-object-footer">
                                                <div class="lq-object-meta">
                                                    <?php if (!empty($project['goal_title'])): ?><span class="lq-badge blue">🎯 <?= e(shortText($project['goal_title'], 32)) ?></span><?php endif; ?>
                                                    <?php if (!empty($project['area_name'])): ?><span class="lq-badge green"><?= e(($project['area_icon'] ? $project['area_icon'] . ' ' : '') . $project['area_name']) ?></span><?php endif; ?>
                                                    <?php if (!empty($project['due_date'])): ?><span class="lq-badge orange">📅 <?= e(date('d/m/Y', strtotime($project['due_date']))) ?></span><?php endif; ?>
                                                </div>

                                                <div class="lq-object-actions">
                                                    <a href="goals.php?section=projects&edit_project=<?= (int) $project['id'] ?>" class="btn btn-secondary">Editar</a>
                                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este reto?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="entity" value="projects">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </section>
                    <?php else: ?>
                        <section class="missions-board">
                            <article class="missions-main-card">
                                <header class="missions-main-header">
                                    <div>
                                        <h2>Misiones</h2>
                                        <p>Completa misiones cada día y gana XP, LifeCoins y recompensas.</p>
                                    </div>
                                    <nav class="missions-period-tabs" aria-label="Período de misiones">
                                        <a href="goals.php?section=tasks&period=daily" class="<?= $period === 'daily' ? 'active' : '' ?>">Diarias</a>
                                        <a href="goals.php?section=tasks&period=weekly" class="<?= $period === 'weekly' ? 'active' : '' ?>">Semanales</a>
                                        <a href="goals.php?section=tasks&period=monthly" class="<?= $period === 'monthly' ? 'active' : '' ?>">Mensuales</a>
                                    </nav>
                                </header>

                                <div class="missions-list">
                                    <?php if (empty($tasks)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay misiones todavía</h2>
                                            <p>Crea tu primera misión para empezar a sumar progreso diario.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($tasks as $task): ?>
                                        <?php $taskProgress = taskVisualProgress((string) $task['status']); ?>
                                        <article class="mission-item-row">
                                            <div class="mission-item-left">
                                                <div class="mission-item-icon" style="background: <?= e($task['area_color'] ?: '#1f335e') ?>;"><?= e($task['area_icon'] ?: '✅') ?></div>
                                                <div class="mission-item-copy">
                                                    <h3><?= e($task['title']) ?></h3>
                                                    <p><?= e(shortText($task['description'] ?: 'Sin descripción.', 64)) ?></p>
                                                    <small>+<?= (int) $task['xp_reward'] ?> XP · +<?= (int) $task['points_reward'] ?> LC</small>
                                                </div>
                                            </div>

                                            <div class="mission-item-progress">
                                                <div class="lq-progress"><span style="width: <?= $taskProgress ?>%"></span></div>
                                                <span><?= taskStatusLabel((string) $task['status']) ?></span>
                                            </div>

                                            <div class="mission-item-actions">
                                                <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                                                    <form method="POST">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="entity" value="tasks">
                                                        <input type="hidden" name="action" value="complete">
                                                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                        <button type="submit" class="btn lq-task-complete">Completar</button>
                                                    </form>
                                                <?php endif; ?>

                                                <a href="goals.php?section=tasks&period=<?= e($period) ?>&edit_task=<?= (int) $task['id'] ?>" class="btn btn-secondary">Editar</a>

                                                <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta misión?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="entity" value="tasks">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                    <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                </form>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </article>

                            <aside class="missions-side-panel">
                                <article class="mission-side-card level">
                                    <small>Nivel</small>
                                    <strong><?= (int) ($user['level'] ?? 1) ?></strong>
                                    <span><?= number_format((int) ($user['xp'] ?? 0), 0, ',', '.') ?> / 2.000 XP</span>
                                    <div class="lq-progress"><span style="width: <?= min(100, (int) (((int) ($user['xp'] ?? 0) % 2000) / 20)) ?>%"></span></div>
                                </article>

                                <article class="mission-side-card streak">
                                    <small>Racha actual</small>
                                    <strong><?= (int) ($user['current_streak'] ?? 0) ?> días</strong>
                                    <span>¡Sigue así, lo estás logrando!</span>
                                </article>

                                <article class="mission-side-card stats">
                                    <div>
                                        <small>Misiones completadas</small>
                                        <strong><?= $completedCount ?></strong>
                                    </div>
                                    <div>
                                        <small>Tiempo enfocado</small>
                                        <strong><?= $focusHours ?>h <?= $focusRemainder ?>m</strong>
                                    </div>
                                </article>

                                <article class="mission-side-card daily-goal">
                                    <div class="daily-goal-head">
                                        <small>Meta diaria</small>
                                        <strong><?= $completedCount ?>/<?= $dailyGoal ?></strong>
                                    </div>
                                    <div class="lq-progress"><span style="width: <?= $dailyProgressPercent ?>%"></span></div>
                                    <ul>
                                        <?php foreach (array_slice($tasks, 0, 4) as $miniTask): ?>
                                            <li>
                                                <span><?= e(shortText($miniTask['title'], 22)) ?></span>
                                                <strong><?= taskVisualProgress((string) $miniTask['status']) ?>%</strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </article>
                            </aside>
                        </section>

                        <section class="missions-editor-card lq-form-panel">
                            <?php $taskCurrent = !empty($taskFormData) ? $taskFormData : ($editingTask ?? []); ?>
                            <div class="lq-panel-header">
                                <div>
                                    <h2><?= $editingTask ? 'Editar misión' : 'Nueva misión' ?></h2>
                                    <p><?= $editingTask ? 'Ajusta esta misión para mantener el ritmo.' : 'Añade una misión concreta para hoy.' ?></p>
                                </div>
                                <?php if ($editingTask): ?><a href="goals.php?section=tasks&period=<?= e($period) ?>">Cancelar</a><?php endif; ?>
                            </div>

                            <form method="POST" class="lq-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="entity" value="tasks">
                                <input type="hidden" name="action" value="<?= $editingTask ? 'update' : 'create' ?>">
                                <?php if ($editingTask): ?><input type="hidden" name="id" value="<?= (int) $editingTask['id'] ?>"><?php endif; ?>

                                <label>Título
                                    <input type="text" name="title" placeholder="Ej: Entrenar 30 minutos" value="<?= e((string) formValue($taskCurrent, 'title', '')) ?>" required>
                                    <?php if (fieldError($taskFormErrors, 'title') !== ''): ?>
                                        <small class="field-error"><?= e(fieldError($taskFormErrors, 'title')) ?></small>
                                    <?php endif; ?>
                                </label>

                                <label>Descripción
                                    <textarea name="description" rows="3" placeholder="Define exactamente qué hay que hacer."><?= e((string) formValue($taskCurrent, 'description', '')) ?></textarea>
                                </label>

                                <div class="lq-form-row">
                                    <label>Reto
                                        <select name="project_id">
                                            <option value="">Sin reto</option>
                                            <?php foreach ($projects as $project): ?>
                                                <option value="<?= (int) $project['id'] ?>" <?= selected(formValue($taskCurrent, 'project_id', ''), $project['id']) ?>><?= e($project['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (fieldError($taskFormErrors, 'project_id') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($taskFormErrors, 'project_id')) ?></small>
                                        <?php endif; ?>
                                    </label>

                                    <label>Meta
                                        <select name="goal_id">
                                            <option value="">Sin meta</option>
                                            <?php foreach ($goals as $goal): ?>
                                                <option value="<?= (int) $goal['id'] ?>" <?= selected(formValue($taskCurrent, 'goal_id', ''), $goal['id']) ?>><?= e($goal['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (fieldError($taskFormErrors, 'goal_id') !== ''): ?>
                                            <small class="field-error"><?= e(fieldError($taskFormErrors, 'goal_id')) ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>

                                <label>Área
                                    <select name="area_id">
                                        <option value="">Sin área</option>
                                        <?php foreach ($areas as $area): ?>
                                            <option value="<?= (int) $area['id'] ?>" <?= selected(formValue($taskCurrent, 'area_id', ''), $area['id']) ?>><?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (fieldError($taskFormErrors, 'area_id') !== ''): ?>
                                        <small class="field-error"><?= e(fieldError($taskFormErrors, 'area_id')) ?></small>
                                    <?php endif; ?>
                                </label>

                                <div class="lq-form-row">
                                    <label>Prioridad
                                        <select name="priority">
                                            <option value="low" <?= selected(formValue($taskCurrent, 'priority', 'medium'), 'low') ?>>Baja</option>
                                            <option value="medium" <?= selected(formValue($taskCurrent, 'priority', 'medium'), 'medium') ?>>Media</option>
                                            <option value="high" <?= selected(formValue($taskCurrent, 'priority', 'medium'), 'high') ?>>Alta</option>
                                            <option value="critical" <?= selected(formValue($taskCurrent, 'priority', 'medium'), 'critical') ?>>Crítica</option>
                                        </select>
                                    </label>

                                    <label>Estado
                                        <select name="status">
                                            <option value="pending" <?= selected(formValue($taskCurrent, 'status', 'pending'), 'pending') ?>>Pendiente</option>
                                            <option value="in_progress" <?= selected(formValue($taskCurrent, 'status', 'pending'), 'in_progress') ?>>En progreso</option>
                                            <option value="completed" <?= selected(formValue($taskCurrent, 'status', 'pending'), 'completed') ?>>Completada</option>
                                            <option value="cancelled" <?= selected(formValue($taskCurrent, 'status', 'pending'), 'cancelled') ?>>Cancelada</option>
                                        </select>
                                    </label>
                                </div>

                                <div class="lq-form-row">
                                    <label>Tiempo estimado
                                        <input type="number" name="estimated_minutes" min="0" value="<?= e((string) formValue($taskCurrent, 'estimated_minutes', 25)) ?>">
                                    </label>
                                    <label>Fecha límite
                                        <input type="date" name="due_date" value="<?= e((string) formValue($taskCurrent, 'due_date', date('Y-m-d'))) ?>">
                                    </label>
                                </div>

                                <div class="lq-form-row">
                                    <label>Recompensa
                                        <input type="text" value="Automática según prioridad y tiempo estimado" disabled>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary full"><?= $editingTask ? 'Guardar cambios' : 'Crear misión' ?></button>
                            </form>
                        </section>
                    <?php endif; ?>
                </section>

                <?php if ($section !== 'tasks'): ?>
                    <aside class="metas-side-summary">
                        <article>
                            <small>Nivel</small>
                            <strong><?= (int) ($user['level'] ?? 1) ?></strong>
                            <span><?= number_format((int) ($user['xp'] ?? 0), 0, ',', '.') ?> XP</span>
                        </article>
                        <article>
                            <small>Racha actual</small>
                            <strong><?= (int) ($user['current_streak'] ?? 0) ?> días</strong>
                            <span>Constancia diaria</span>
                        </article>
                        <article>
                            <small>Resumen rápido</small>
                            <strong><?= count($goals) ?> metas</strong>
                            <span><?= count($projects) ?> retos, <?= count($tasks) ?> misiones</span>
                        </article>
                    </aside>
                <?php endif; ?>
            </section>
        </section>
    </main>
</body>
</html>
