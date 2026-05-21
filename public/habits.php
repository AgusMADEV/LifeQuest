<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/HabitController.php';
require_once __DIR__ . '/../app/Models/Habit.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int)$_SESSION['user_id'];
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$controller = new HabitController();
$habitModel = new Habit();
$areaModel = new LifeArea();
$goalModel = new Goal();

$message = null;
$messageType = null;
$editingHabit = null;

if (isset($_GET['edit'])) {
    $editingHabit = $habitModel->findByIdAndUser((int)$_GET['edit'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') $result = $controller->store($userId, $_POST);
    elseif ($action === 'update') $result = $controller->update($userId, $_POST);
    elseif ($action === 'delete') $result = $controller->destroy($userId, (int)($_POST['id'] ?? 0));
    elseif ($action === 'complete') $result = $controller->completeToday($userId, (int)($_POST['id'] ?? 0));
    else $result = ['success' => false, 'message' => 'Acción no válida.'];

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: habits.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$habits = $controller->index($userId);
$areas = $areaModel->getAllByUser($userId);
$goals = $goalModel->getAllByUser($userId);

$totalHabits = count($habits);
$completedToday = count(array_filter($habits, static fn($h) => (int)($h['completed_today'] ?? 0) === 1));
$activeHabits = count(array_filter($habits, static fn($h) => (int)($h['active'] ?? 0) === 1));

function e(string|null $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function checked(bool $value): string { return $value ? 'checked' : ''; }
function shortText(string|null $value, int $limit = 42): string {
    $value = trim((string)$value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}
function frequencyLabel(string $frequency): string {
    return ['daily' => 'Diario', 'weekly' => 'Semanal', 'custom' => 'Personalizado'][$frequency] ?? $frequency;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hábitos | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/habits.css">
</head>
<body class="lifequest-app">
    <?php
    $activePage = 'habits';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

    <main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar hábitos...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

        <section class="lq-page-shell">
            <header class="lq-page-hero habits-hero">
                <div>
                    <p class="eyebrow">Constancia diaria</p>
                    <h1>Hábitos</h1>
                    <p>Construye rutinas que sostengan tus metas. Completa hábitos cada día, aumenta tu racha y gana XP por tu constancia.</p>
                </div>

                <div class="habit-hero-stats">
                    <article><span>✅</span><strong><?= $completedToday ?>/<?= max(1, $activeHabits) ?></strong><small>completados hoy</small></article>
                    <article><span>🔥</span><strong><?= (int)($user['current_streak'] ?? 0) ?></strong><small>racha global</small></article>
                    <article><span>💚</span><strong><?= $totalHabits ?></strong><small>hábitos creados</small></article>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="lq-crud-layout">
                <article class="lq-form-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2><?= $editingHabit ? 'Editar hábito' : 'Nuevo hábito' ?></h2>
                            <p><?= $editingHabit ? 'Ajusta esta rutina.' : 'Crea una rutina sencilla y repetible.' ?></p>
                        </div>
                        <?php if ($editingHabit): ?><a href="habits.php">Cancelar</a><?php endif; ?>
                    </div>

                    <form method="POST" class="lq-form">
                        <input type="hidden" name="action" value="<?= $editingHabit ? 'update' : 'create' ?>">
                        <?php if ($editingHabit): ?><input type="hidden" name="id" value="<?= (int)$editingHabit['id'] ?>"><?php endif; ?>

                        <label>Nombre del hábito
                            <input type="text" name="name" placeholder="Ej: Entrenar 30 minutos" value="<?= e($editingHabit['name'] ?? '') ?>" required>
                        </label>

                        <label>Descripción
                            <textarea name="description" rows="3" placeholder="Describe cuándo, cómo o por qué harás este hábito."><?= e($editingHabit['description'] ?? '') ?></textarea>
                        </label>

                        <label>Área de vida
                            <select name="area_id">
                                <option value="">Sin área</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int)$area['id'] ?>" <?= selected($editingHabit['area_id'] ?? '', $area['id']) ?>>
                                        <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>Meta relacionada
                            <select name="goal_id">
                                <option value="">Sin meta</option>
                                <?php foreach ($goals as $goal): ?>
                                    <option value="<?= (int)$goal['id'] ?>" <?= selected($editingHabit['goal_id'] ?? '', $goal['id']) ?>>
                                        <?= e($goal['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="lq-form-row">
                            <label>Frecuencia
                                <select name="frequency">
                                    <option value="daily" <?= selected($editingHabit['frequency'] ?? 'daily', 'daily') ?>>Diario</option>
                                    <option value="weekly" <?= selected($editingHabit['frequency'] ?? 'daily', 'weekly') ?>>Semanal</option>
                                    <option value="custom" <?= selected($editingHabit['frequency'] ?? 'daily', 'custom') ?>>Personalizado</option>
                                </select>
                            </label>

                            <label>Estado
                                <span class="toggle-row">
                                    <input type="checkbox" name="active" value="1" <?= checked((bool)($editingHabit['active'] ?? true)) ?>>
                                    Activo
                                </span>
                            </label>
                        </div>

                        <div class="lq-form-row">
                            <label>Recompensa XP <input type="number" name="xp_reward" min="0" value="<?= e((string)($editingHabit['xp_reward'] ?? 10)) ?>"></label>
                            <label>LifeCoins <input type="number" name="points_reward" min="0" value="<?= e((string)($editingHabit['points_reward'] ?? 5)) ?>"></label>
                        </div>

                        <button type="submit" class="btn btn-primary full"><?= $editingHabit ? 'Guardar cambios' : 'Crear hábito' ?></button>
                    </form>
                </article>

                <section class="lq-list-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2>Tus hábitos</h2>
                            <p><?= $completedToday ?> completados hoy de <?= $activeHabits ?> activos</p>
                        </div>
                    </div>

                    <div class="habits-grid">
                        <?php if (empty($habits)): ?>
                            <article class="lq-empty">
                                <h2>No hay hábitos todavía</h2>
                                <p>Empieza con algo pequeño: leer 10 páginas, entrenar 20 minutos o revisar tu planificación.</p>
                            </article>
                        <?php endif; ?>

                        <?php foreach ($habits as $habit): ?>
                            <?php
                            $completed = (int)($habit['completed_today'] ?? 0) === 1;
                            $active = (int)($habit['active'] ?? 0) === 1;
                            ?>
                            <article class="habit-card <?= $completed ? 'completed' : '' ?> <?= !$active ? 'inactive' : '' ?>">
                                <div class="habit-card-top">
                                    <div class="habit-icon" style="background: <?= e($habit['area_color'] ?: '#16C79A') ?>;">
                                        <?= e($habit['area_icon'] ?: '💚') ?>
                                    </div>

                                    <div>
                                        <h2><?= e($habit['name']) ?></h2>
                                        <p><?= e($habit['description'] ?: 'Sin descripción.') ?></p>
                                    </div>

                                    <span class="habit-status <?= $completed ? 'done' : 'pending' ?>">
                                        <?= $completed ? 'Completado' : 'Pendiente' ?>
                                    </span>
                                </div>

                                <div class="habit-stats-row">
                                    <div><strong><?= (int)$habit['current_streak'] ?></strong><small>racha</small></div>
                                    <div><strong><?= (int)$habit['best_streak'] ?></strong><small>mejor</small></div>
                                    <div><strong>+<?= (int)$habit['xp_reward'] ?></strong><small>XP</small></div>
                                    <div><strong>+<?= (int)$habit['points_reward'] ?></strong><small>coins</small></div>
                                </div>

                                <div class="habit-meta">
                                    <span class="lq-badge green"><?= frequencyLabel($habit['frequency']) ?></span>
                                    <?php if (!empty($habit['area_name'])): ?>
                                        <span class="lq-badge blue"><?= e(($habit['area_icon'] ? $habit['area_icon'] . ' ' : '') . $habit['area_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($habit['goal_title'])): ?>
                                        <span class="lq-badge purple">🎯 <?= e(shortText($habit['goal_title'], 26)) ?></span>
                                    <?php endif; ?>
                                    <?php if (!$active): ?><span class="lq-badge red">Inactivo</span><?php endif; ?>
                                </div>

                                <div class="habit-actions">
                                    <?php if (!$completed && $active): ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="id" value="<?= (int)$habit['id'] ?>">
                                            <button type="submit" class="btn btn-primary">Completar hoy</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled><?= $completed ? 'Hecho por hoy' : 'Inactivo' ?></button>
                                    <?php endif; ?>

                                    <a href="habits.php?edit=<?= (int)$habit['id'] ?>" class="btn btn-secondary">Editar</a>

                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este hábito?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$habit['id'] ?>">
                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                    </form>
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
