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
require_once __DIR__ . '/../app/Support/AvatarLibrary.php';
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

        $badgeToasts = $_SESSION['badge_unlock_toasts'] ?? [];
        if (is_array($badgeToasts) && !empty($badgeToasts)) {
            $payload = base64_encode((string) json_encode($badgeToasts));
            if ($payload !== '') {
                $redirect .= '&badge_toasts=' . urlencode($payload);
            }
        }

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
$focusMinutes = array_reduce(
    $tasks,
    static fn(int $carry, array $task) => $carry + (int) ($task['estimated_minutes'] ?? 0),
    0
);
$focusHours = intdiv($focusMinutes, 60);
$focusRemainder = $focusMinutes % 60;

$sectionItems = [
    'goals' => $goals,
    'projects' => $projects,
    'tasks' => $tasks,
][$section];

$sectionCompletedCount = count(array_filter(
    $sectionItems,
    static function (array $item) use ($section): bool {
        if ($section === 'tasks') {
            return ($item['status'] ?? '') === 'completed';
        }

        return ($item['status'] ?? '') === 'completed' || (int) ($item['progress'] ?? 0) >= 100;
    }
));

$sectionTotalCount = count($sectionItems);
$sectionProgressPercent = (int) min(100, round(($sectionCompletedCount / max(1, $sectionTotalCount)) * 100));
$sectionAverageProgress = $sectionTotalCount > 0
    ? (int) round(array_reduce(
        $sectionItems,
        static fn(int $carry, array $item) => $carry + (int) ($item['progress'] ?? 0),
        0
    ) / $sectionTotalCount)
    : 0;
$sectionPreviewItems = array_slice($sectionItems, 0, 4);
$sectionStatsLabel = [
    'goals' => 'Metas completadas',
    'projects' => 'Retos completados',
    'tasks' => 'Misiones completadas',
][$section];
$sectionGoalTitle = [
    'goals' => 'Avance de metas',
    'projects' => 'Avance de retos',
    'tasks' => 'Meta diaria',
][$section];
$sectionGoalSubtitle = [
    'goals' => 'Sigue el progreso de tus objetivos principales.',
    'projects' => 'Mide el avance de tus bloques de trabajo.',
    'tasks' => 'Completa misiones diarias para sumar XP y LifeCoins.',
][$section];
$sectionStatsSecondary = $section === 'tasks'
    ? $focusHours . 'h ' . $focusRemainder . 'm'
    : $sectionAverageProgress . '%';

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
$sectionConfig = [
    'goals' => [
        'button' => '+ meta',
        'singular' => 'meta',
        'plural' => 'metas',
    ],
    'projects' => [
        'button' => '+ reto',
        'singular' => 'reto',
        'plural' => 'retos',
    ],
    'tasks' => [
        'button' => '+ misión',
        'singular' => 'misión',
        'plural' => 'misiones',
    ],
];

$currentConfig = $sectionConfig[$section];

$modalIsOpen =
    $editingGoal ||
    $editingProject ||
    $editingTask ||
    !empty($goalFormErrors) ||
    !empty($projectFormErrors) ||
    !empty($taskFormErrors);

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

function areaIconMaskUrl(string|null $iconValue): ?string
{
    $iconValue = trim((string) $iconValue);

    if ($iconValue === '') {
        return null;
    }

    $baseName = pathinfo($iconValue, PATHINFO_FILENAME);
    $svgFile = $baseName . '.svg';
    $svgPath = __DIR__ . '/../icons/areas_svg/' . $svgFile;
    if (is_file($svgPath)) {
        return '../icons/areas_svg/' . rawurlencode($svgFile);
    }

    $pngPath = __DIR__ . '/../icons/areas/' . $iconValue;
    if (is_file($pngPath)) {
        return '../icons/areas/' . rawurlencode($iconValue);
    }

    return null;
}

function hexToRgba(string $hex, float $alpha = 1.0): string
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $alpha . ')';
}

function projectStatusLabel(string $status): string
{
    return ['active' => 'Activa', 'completed' => 'Completada', 'paused' => 'Pausada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function taskStatusLabel(string $status): string
{
    return ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function statusAccentColor(string $status): string
{
    return [
        'not_started' => '#2f7cff',
        'pending' => '#2f7cff',
        'in_progress' => '#15c98a',
        'active' => '#15c98a',
        'paused' => '#f08c00',
        'completed' => '#15c98a',
        'cancelled' => '#ef6b6b',
    ][$status] ?? '#2f7cff';
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

function sectionItemProgress(array $item, string $section): int
{
    if ($section === 'tasks') {
        return taskVisualProgress((string) ($item['status'] ?? ''));
    }

    return max(0, min(100, (int) ($item['progress'] ?? 0)));
}

function rewardIcon(string $type, string $suffix = ''): string
{
    $suffix = preg_replace('/[^a-zA-Z0-9_-]/', '', $suffix) ?: '';
    $coinOuterId = 'rewardCoinOuter' . $suffix;
    $coinInnerId = 'rewardCoinInner' . $suffix;
    $coinShadowId = 'rewardCoinShadow' . $suffix;

    return <<<HTML
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="{$coinOuterId}" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse">
            <stop stop-color="#FFE27A"/>
            <stop offset="0.45" stop-color="#FFC93A"/>
            <stop offset="1" stop-color="#F59F00"/>
        </linearGradient>
        <linearGradient id="{$coinInnerId}" x1="7" y1="6" x2="17" y2="18" gradientUnits="userSpaceOnUse">
            <stop stop-color="#FFD85C"/>
            <stop offset="1" stop-color="#F08C00"/>
        </linearGradient>
        <filter id="{$coinShadowId}" x="0" y="0" width="24" height="24" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
            <feDropShadow dx="0" dy="1" stdDeviation="0.8" flood-color="#C76B00" flood-opacity="0.35"/>
        </filter>
    </defs>
    <g filter="url(#{$coinShadowId})">
        <circle cx="12" cy="12" r="10" fill="url(#{$coinOuterId})"/>
        <circle cx="12" cy="12" r="8.1" fill="url(#{$coinInnerId})" stroke="#FFB11A" stroke-width="0.9"/>
        <path d="M5.8 7.5C7.1 5.4 9.34 4 11.9 4" stroke="#FFF4BF" stroke-width="1.4" stroke-linecap="round" opacity="0.9"/>
        <path d="M12.1 7.1C10.8 7.1 9.9 7.75 9.9 8.72C9.9 9.73 10.87 10.2 12.22 10.58C13.64 10.98 14.4 11.47 14.4 12.58C14.4 13.73 13.42 14.55 12 14.69V15.6C12 15.93 11.73 16.2 11.4 16.2C11.07 16.2 10.8 15.93 10.8 15.6V14.63C9.89 14.48 9.04 13.99 8.49 13.25C8.29 12.98 8.34 12.61 8.61 12.42C8.87 12.22 9.25 12.27 9.44 12.54C9.91 13.18 10.69 13.56 11.47 13.56H12C13 13.56 13.2 12.98 13.2 12.62C13.2 12.05 12.87 11.72 11.9 11.44C10.43 11.02 8.7 10.4 8.7 8.77C8.7 7.43 9.69 6.47 10.8 6.22V5.4C10.8 5.07 11.07 4.8 11.4 4.8C11.73 4.8 12 5.07 12 5.4V6.15C12.78 6.21 13.47 6.48 14.07 6.95C14.33 7.15 14.37 7.53 14.17 7.79C13.97 8.05 13.59 8.09 13.33 7.89C12.96 7.6 12.52 7.43 12.1 7.1Z" fill="#FFF9EA"/>
    </g>
</svg>
HTML;
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
        <?php $topbarSearchPlaceholder = (string) $searchPlaceholder; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <section class="lq-page-shell metas-shell metas-missions-look">
            <section class="hub-layout metas-missions-layout tasks-mode metas-sidepanel-mode">
                <section class="metas-main-column">
                    <header class="lq-page-hero metas-hero metas-clean-hero">
                        <div>
                            <h1><?= e($heroTitle) ?></h1>
                            <p>Completa acciones, registra progreso y gana XP, LifeCoins y recompensas.</p>
                        </div>
                    </header>

                    <div class="metas-tabs-bar">
                        <nav class="metas-tabs" aria-label="Navegación metas">
                            <a href="goals.php?section=goals" class="<?= $section === 'goals' ? 'active' : '' ?>">Metas</a>
                            <a href="goals.php?section=projects" class="<?= $section === 'projects' ? 'active' : '' ?>">Retos</a>
                            <a href="goals.php?section=tasks&period=<?= e($period) ?>" class="<?= $section === 'tasks' ? 'active' : '' ?>">Misiones</a>
                        </nav>
                    </div>

                    <?php if ($message): ?>
                        <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
                    <?php endif; ?>
                    <div class="metas-modal-backdrop <?= $modalIsOpen ? 'is-open' : '' ?>" data-goal-modal-close></div>

                    <?php if ($section === 'goals'): ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel metas-form-modal <?= ($editingGoal || !empty($goalFormErrors)) ? 'is-open' : '' ?>" data-goal-form-modal>
                                <?php $goalCurrent = !empty($goalFormData) ? $goalFormData : ($editingGoal ?? []); ?>
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingGoal ? 'Editar meta' : 'Nueva meta' ?></h2>
                                        <p><?= $editingGoal ? 'Ajusta el progreso y la prioridad.' : 'Crea una meta clara y medible.' ?></p>
                                    </div>
                                    <a href="goals.php?section=goals" class="metas-modal-close" data-goal-modal-close>×</a>
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
                                                    <?= e($area['name']) ?>
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
                                    <button type="button" class="btn btn-primary metas-add-btn" data-goal-modal-open>
                                        <?= e($currentConfig['button']) ?>
                                    </button>
                                </div>

                                <div class="lq-list-grid">
                                    <?php if (empty($goals)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay metas todavía</h2>
                                            <p>Crea tu primera meta y luego desglósala en retos y misiones.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($goals as $goal): ?>
                                        <?php
                                            $goalAreaIcon = areaIconMaskUrl($goal['area_icon'] ?? null);
                                            $goalAreaColor = (string) ($goal['area_color'] ?? '');
                                            $goalAreaBg = hexToRgba($goalAreaColor !== '' ? $goalAreaColor : '#16C79A', 0.15);
                                        ?>
                                        <article class="mission-item-row">
                                            <div class="mission-item-left">
                                                <?php if ($goalAreaIcon): ?>
                                                    <div class="mission-item-icon mission-item-icon--area" style="--area-bg: <?= e($goalAreaBg) ?>;">
                                                        <span class="mission-item-icon-mask" style="--area-color: <?= e($goalAreaColor !== '' ? $goalAreaColor : '#16C79A') ?>; -webkit-mask-image: url('<?= e($goalAreaIcon) ?>'); mask-image: url('<?= e($goalAreaIcon) ?>');"></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mission-item-icon" style="background: <?= e($goal['area_color'] ?: '#16C79A') ?>;"><?= e($goal['area_icon'] ?: '🎯') ?></div>
                                                <?php endif; ?>
                                                <div class="mission-item-copy">
                                                    <h3><?= e($goal['title']) ?></h3>
                                                    <p><?= e($goal['description'] ?: 'Sin descripción.') ?></p>
                                                    <small><?= e(goalTypeLabel($goal['type'])) ?> · +<?= (int) $goal['xp_reward'] ?> XP · <span class="reward-chip coins"><?= rewardIcon('coins', 'goal' . (int) $goal['id']) ?><span class="reward-amount">+<?= (int) $goal['points_reward'] ?></span></span></small>
                                                </div>
                                            </div>

                                            <div class="mission-item-progress">
                                                <small class="mission-progress-label"><?= (int) $goal['progress'] ?>%</small>
                                                <div class="lq-progress"><span style="width: <?= (int) $goal['progress'] ?>%"></span></div>
                                                <div class="mission-status-bubble <?= (int) $goal['progress'] >= 100 ? 'mission-status-completed' : '' ?>" style="--mission-progress: <?= (int) $goal['progress'] ?>%; --mission-accent: <?= e(statusAccentColor((string) $goal['status'])) ?>;">
                                                    <span class="sr-only"><?= e(goalStatusLabel($goal['status'])) ?></span>
                                                </div>
                                            </div>

                                            <div class="mission-item-actions">
                                                    <a href="goals.php?section=goals&edit_goal=<?= (int) $goal['id'] ?>" class="btn btn-secondary">Editar</a>
                                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta meta?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="entity" value="goals">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $goal['id'] ?>">
                                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                    </form>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </section>
                    <?php elseif ($section === 'projects'): ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel metas-form-modal <?= ($editingProject || !empty($projectFormErrors)) ? 'is-open' : '' ?>" data-goal-form-modal>
                                <?php $projectCurrent = !empty($projectFormData) ? $projectFormData : ($editingProject ?? []); ?>
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingProject ? 'Editar reto' : 'Nuevo reto' ?></h2>
                                        <p><?= $editingProject ? 'Actualiza este reto.' : 'Crea un bloque conectado a una meta.' ?></p>
                                    </div>
                                    <a href="goals.php?section=projects" class="metas-modal-close" data-goal-modal-close>×</a>
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
                                                <option value="<?= (int) $area['id'] ?>" <?= selected(formValue($projectCurrent, 'area_id', ''), $area['id']) ?>><?= e($area['name']) ?></option>
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
                                    <button type="button" class="btn btn-primary metas-add-btn" data-goal-modal-open>
                                        <?= e($currentConfig['button']) ?>
                                    </button>
                                </div>

                                <div class="lq-list-grid">
                                    <?php if (empty($projects)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay retos todavía</h2>
                                            <p>Crea tu primer reto para dividir metas complejas en pasos.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($projects as $project): ?>
                                        <?php
                                            $projectAreaIcon = areaIconMaskUrl($project['area_icon'] ?? null);
                                            $projectAreaColor = (string) ($project['area_color'] ?? '');
                                            $projectAreaBg = hexToRgba($projectAreaColor !== '' ? $projectAreaColor : '#16C79A', 0.15);
                                        ?>
                                        <article class="mission-item-row">
                                            <div class="mission-item-left">
                                                <?php if ($projectAreaIcon): ?>
                                                    <div class="mission-item-icon mission-item-icon--area" style="--area-bg: <?= e($projectAreaBg) ?>;">
                                                        <span class="mission-item-icon-mask" style="--area-color: <?= e($projectAreaColor !== '' ? $projectAreaColor : '#16C79A') ?>; -webkit-mask-image: url('<?= e($projectAreaIcon) ?>'); mask-image: url('<?= e($projectAreaIcon) ?>');"></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mission-item-icon" style="background: <?= e($project['area_color'] ?: '#16C79A') ?>;"><?= e($project['area_icon'] ?: '🚀') ?></div>
                                                <?php endif; ?>
                                                <div class="mission-item-copy">
                                                    <h3><?= e($project['title']) ?></h3>
                                                    <p><?= e($project['description'] ?: 'Sin descripción.') ?></p>
                                                    <small><?php if (!empty($project['goal_title'])): ?>🎯 <?= e(shortText($project['goal_title'], 32)) ?> · <?php endif; ?>Progreso <?= (int) $project['progress'] ?>%<?php if (!empty($project['due_date'])): ?> · <?= e(date('d/m/Y', strtotime($project['due_date']))) ?><?php endif; ?></small>
                                                </div>
                                            </div>

                                            <div class="mission-item-progress">
                                                <small class="mission-progress-label"><?= (int) $project['progress'] ?>%</small>
                                                <div class="lq-progress"><span style="width: <?= (int) $project['progress'] ?>%"></span></div>
                                                <div class="mission-status-bubble <?= (int) $project['progress'] >= 100 ? 'mission-status-completed' : '' ?>" style="--mission-progress: <?= (int) $project['progress'] ?>%; --mission-accent: <?= e(statusAccentColor((string) $project['status'])) ?>;">
                                                    <span class="sr-only"><?= e(projectStatusLabel($project['status'])) ?></span>
                                                </div>
                                            </div>

                                            <div class="mission-item-actions">
                                                    <a href="goals.php?section=projects&edit_project=<?= (int) $project['id'] ?>" class="btn btn-secondary">Editar</a>
                                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este reto?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="entity" value="projects">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                    </form>
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
                                    <button type="button" class="btn btn-primary metas-add-btn" data-goal-modal-open>
                                        <?= e($currentConfig['button']) ?>
                                    </button>
                                </header>

                                <div class="missions-list">
                                    <?php if (empty($tasks)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay misiones todavía</h2>
                                            <p>Crea tu primera misión para empezar a sumar progreso diario.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($tasks as $task): ?>
                                        <?php
                                            $taskProgress = taskVisualProgress((string) $task['status']);
                                            $taskStatus = (string) $task['status'];
                                            $taskAreaIcon = areaIconMaskUrl($task['area_icon'] ?? null);
                                            $taskAreaColor = (string) ($task['area_color'] ?? '');
                                            $taskAreaBg = hexToRgba($taskAreaColor !== '' ? $taskAreaColor : '#1f335e', 0.15);
                                        ?>
                                        <article class="mission-item-row task-status-<?= e($taskStatus) ?>">
                                            <div class="mission-item-left">
                                                <?php if ($taskAreaIcon): ?>
                                                    <div class="mission-item-icon mission-item-icon--area" style="--area-bg: <?= e($taskAreaBg) ?>;">
                                                        <span class="mission-item-icon-mask" style="--area-color: <?= e($taskAreaColor !== '' ? $taskAreaColor : '#1f335e') ?>; -webkit-mask-image: url('<?= e($taskAreaIcon) ?>'); mask-image: url('<?= e($taskAreaIcon) ?>'); -webkit-mask-position: center; mask-position: center; -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat; -webkit-mask-size: 66%; mask-size: 66%;"></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mission-item-icon" style="background: <?= e($task['area_color'] ?: '#1f335e') ?>;">
                                                        <?= e($task['area_icon'] ?: '✅') ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mission-item-copy">
                                                    <h3><?= e($task['title']) ?></h3>
                                                    <p><?= e(shortText($task['description'] ?: 'Sin descripción.', 64)) ?></p>
                                                    <small>+<?= (int) $task['xp_reward'] ?> XP · <span class="reward-chip coins"><?= rewardIcon('coins', 'task' . (int) $task['id']) ?><span class="reward-amount">+<?= (int) $task['points_reward'] ?></span></span></small>
                                                </div>
                                            </div>

                                            <div class="mission-item-progress">
                                                <small class="mission-progress-label"><?= $taskProgress ?>%</small>
                                                <div class="lq-progress"><span style="width: <?= $taskProgress ?>%"></span></div>
                                                <?php if ($taskStatus !== 'completed' && $taskStatus !== 'cancelled'): ?>
                                                    <form method="POST" class="mission-status-action">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="entity" value="tasks">
                                                        <input type="hidden" name="action" value="complete">
                                                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                        <button type="submit" class="mission-status-bubble mission-status-<?= e($taskStatus) ?> mission-status-action" aria-label="Completar misión">
                                                            <span class="sr-only">Completar misión</span>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="mission-status-bubble mission-status-<?= e($taskStatus) ?>" style="--mission-progress: <?= $taskProgress ?>%;">
                                                        <span class="sr-only"><?= e(taskStatusLabel($taskStatus)) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mission-item-actions">
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

                        </section>

                        <section class="missions-editor-card lq-form-panel metas-form-modal <?= ($editingTask || !empty($taskFormErrors)) ? 'is-open' : '' ?>" data-goal-form-modal>
                            <?php $taskCurrent = !empty($taskFormData) ? $taskFormData : ($editingTask ?? []); ?>
                            <div class="lq-panel-header">
                                <div>
                                    <h2><?= $editingTask ? 'Editar misión' : 'Nueva misión' ?></h2>
                                    <p><?= $editingTask ? 'Ajusta esta misión para mantener el ritmo.' : 'Añade una misión concreta para hoy.' ?></p>
                                </div>
                                <a href="goals.php?section=tasks&period=<?= e($period) ?>" class="metas-modal-close" data-goal-modal-close>×</a>
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
                                                <option value="<?= (int) $area['id'] ?>" <?= selected(formValue($taskCurrent, 'area_id', ''), $area['id']) ?>><?= e($area['name']) ?></option>
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
                <aside class="missions-side-panel">
                    <article class="mission-side-card level">
                        <small>Nivel</small>
                        <strong><?= (int) ($user['level'] ?? 1) ?></strong>
                        <span><?= number_format((int) ($user['xp'] ?? 0), 0, ',', '.') ?> / 2.000 XP</span>
                        <div class="lq-progress">
                            <span style="width: <?= min(100, (int) (((int) ($user['xp'] ?? 0) % 2000) / 20)) ?>%"></span>
                        </div>
                    </article>

                    <article class="mission-side-card streak">
                        <small>Racha actual</small>
                        <strong><?= (int) ($user['current_streak'] ?? 0) ?> días</strong>
                        <span>¡Sigue así, lo estás logrando!</span>
                    </article>

                    <article class="mission-side-card stats">
                        <div>
                            <small><?= e($sectionStatsLabel) ?></small>
                            <strong><?= $sectionCompletedCount ?></strong>
                        </div>
                        <div>
                            <small><?= $section === 'tasks' ? 'Tiempo enfocado' : 'Progreso medio' ?></small>
                            <strong><?= e($sectionStatsSecondary) ?></strong>
                        </div>
                    </article>

                    <article class="mission-side-card daily-goal">
                        <div class="daily-goal-head">
                            <small><?= e($sectionGoalTitle) ?></small>
                            <strong><?= $sectionCompletedCount ?>/<?= max(1, $sectionTotalCount) ?></strong>
                        </div>

                        <span><?= e($sectionGoalSubtitle) ?></span>

                        <div class="lq-progress">
                            <span style="width: <?= $sectionProgressPercent ?>%"></span>
                        </div>

                        <ul>
                            <?php foreach ($sectionPreviewItems as $miniTask): ?>
                                <li>
                                    <span><?= e(shortText($miniTask['title'], 22)) ?></span>
                                    <strong><?= sectionItemProgress($miniTask, $section) ?>%</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </aside>
            </section>
        </section>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
