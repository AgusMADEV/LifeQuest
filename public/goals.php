<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/GoalController.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$controller = new GoalController();
$goalModel = new Goal();
$lifeAreaModel = new LifeArea();
$userModel = new User();
$user = $userModel->findById($userId);

$message = null;
$messageType = null;
$editingGoal = null;

if (isset($_GET['edit'])) {
    $editingGoal = $goalModel->findByIdAndUser((int) $_GET['edit'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $controller->store($userId, $_POST);
    } elseif ($action === 'update') {
        $result = $controller->update($userId, $_POST);
    } elseif ($action === 'delete') {
        $result = $controller->destroy($userId, (int) ($_POST['id'] ?? 0));
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida.'];
    }

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
function selected(mixed $a, mixed $b): string { return (string) $a === (string) $b ? 'selected' : ''; }
function shortText(string|null $value, int $limit = 42): string {
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}
function goalTypeLabel(string $type): string {
    return ['daily'=>'Diaria','weekly'=>'Semanal','monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual','future'=>'Futuro'][$type] ?? $type;
}
function priorityLabel(string $priority): string {
    return ['low'=>'Baja','medium'=>'Media','high'=>'Alta','critical'=>'Crítica'][$priority] ?? $priority;
}
function statusLabel(string $status): string {
    return ['not_started'=>'No iniciada','in_progress'=>'En progreso','paused'=>'Pausada','completed'=>'Completada','cancelled'=>'Cancelada'][$status] ?? $status;
}
function priorityClass(string $priority): string {
    return ['low'=>'green','medium'=>'orange','high'=>'red','critical'=>'red'][$priority] ?? 'blue';
}
function statusClass(string $status): string {
    return ['not_started'=>'blue','in_progress'=>'green','paused'=>'orange','completed'=>'purple','cancelled'=>'red'][$status] ?? 'blue';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Objetivos | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <a href="dashboard.php" class="lq-logo"><span>Life<span>Quest</span><i>✦</i></span></a>
        <nav class="lq-nav">
<a href="dashboard.php"><span>🏠</span>Inicio</a>
<a href="goals.php" class="active"><span>🎯</span>Objetivos</a>
<a href="projects.php"><span>🚀</span>Misiones</a>
<a href="areas.php"><span>🧩</span>Áreas</a>
<a href="#"><span>💚</span>Hábitos</a>
<a href="#"><span>🛍️</span>Tienda</a>
<a href="#"><span>📊</span>Progreso</a>
</nav>

        <section class="lq-sidebar-card unlock">
            <div>
                <strong>Objetivos claros</strong>
                <p>Convierte metas grandes en misiones diarias.</p>
                <a href="projects.php" class="mini-btn">Crear misión</a>
            </div>
            <span class="bag">🎯</span>
        </section>

        <section class="lq-user-mini">
            <div class="mini-avatar"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
            <div>
                <strong><?= e(shortText($user['name'] ?? 'Usuario', 18)) ?></strong>
                <small>Ver perfil</small>
            </div>
            <span>⌄</span>
        </section>

        <div class="lq-sidebar-bottom">
            <a href="#">⚙️</a>
            <a href="#">?</a>
            <a href="logout.php">↪</a>
        </div>
    </aside>

    <main class="lq-main">
        <header class="lq-topbar">
            <button class="icon-btn">☰</button>
            <div class="search-box">
                <span>🔎</span>
                <input type="search" placeholder="Buscar objetivos..." disabled>
                <kbd>⌘ K</kbd>
            </div>
            <div class="top-stats">
                <div class="xp-pill">
                    <span>✦</span>
                    <strong><?= number_format((int)($user['xp'] ?? 0), 0, ',', '.') ?> XP</strong>
                    <div class="mini-progress"><i style="width: 35%"></i></div>
                    <small>Nivel <?= (int)($user['level'] ?? 1) ?></small>
                </div>
                <div class="currency-pill coin"><span>🪙</span><strong><?= number_format((int)($user['points'] ?? 0), 0, ',', '.') ?></strong></div>
                <div class="profile-pill">
                    <div class="mini-avatar image-like"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
                    <strong>¡Hola, <?= e(shortText($user['name'] ?? 'Usuario', 12)) ?>! 👋</strong>
                </div>
            </div>
        </header>

        <section class="lq-page-shell">
            <header class="lq-page-hero">
                <div>
                    <p class="eyebrow">Dirección y progreso</p>
                    <h1>Objetivos</h1>
                    <p>Define lo que quieres conseguir y mide tu avance con progreso, prioridad, recompensas y áreas de vida.</p>
                </div>
                <div class="lq-page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
                    <a href="projects.php" class="btn btn-primary">Crear misión</a>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="lq-crud-layout">
                <article class="lq-form-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2><?= $editingGoal ? 'Editar objetivo' : 'Nuevo objetivo' ?></h2>
                            <p><?= $editingGoal ? 'Ajusta el progreso y la prioridad.' : 'Crea una meta clara y medible.' ?></p>
                        </div>
                        <?php if ($editingGoal): ?><a href="goals.php">Cancelar</a><?php endif; ?>
                    </div>

                    <form method="POST" class="lq-form">
                        <input type="hidden" name="action" value="<?= $editingGoal ? 'update' : 'create' ?>">
                        <?php if ($editingGoal): ?><input type="hidden" name="id" value="<?= (int) $editingGoal['id'] ?>"><?php endif; ?>

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
                                    <option value="<?= (int) $area['id'] ?>" <?= selected($editingGoal['area_id'] ?? '', $area['id']) ?>>
                                        <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="lq-form-row">
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

                        <div class="lq-form-row">
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
                                <input type="number" name="progress" min="0" max="100" value="<?= e((string) ($editingGoal['progress'] ?? 0)) ?>">
                            </label>
                        </div>

                        <div class="lq-form-row">
                            <label>Fecha inicio <input type="date" name="start_date" value="<?= e($editingGoal['start_date'] ?? '') ?>"></label>
                            <label>Fecha límite <input type="date" name="due_date" value="<?= e($editingGoal['due_date'] ?? '') ?>"></label>
                        </div>

                        <div class="lq-form-row">
                            <label>Recompensa XP <input type="number" name="xp_reward" min="0" value="<?= e((string) ($editingGoal['xp_reward'] ?? 50)) ?>"></label>
                            <label>LifeCoins <input type="number" name="points_reward" min="0" value="<?= e((string) ($editingGoal['points_reward'] ?? 25)) ?>"></label>
                        </div>

                        <button type="submit" class="btn btn-primary full"><?= $editingGoal ? 'Guardar cambios' : 'Crear objetivo' ?></button>
                    </form>
                </article>

                <section class="lq-list-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2>Tus objetivos</h2>
                            <p><?= count($goals) ?> objetivos creados</p>
                        </div>
                    </div>

                    <div class="lq-list-grid">
                        <?php if (empty($goals)): ?>
                            <article class="lq-empty">
                                <h2>No hay objetivos todavía</h2>
                                <p>Crea tu primer objetivo. Después podrás conectarlo con misiones y hábitos.</p>
                            </article>
                        <?php endif; ?>

                        <?php foreach ($goals as $goal): ?>
                            <article class="lq-object-card">
                                <div class="lq-object-top">
                                    <div class="lq-object-icon" style="background: <?= e($goal['area_color'] ?: '#16C79A') ?>;">
                                        <?= e($goal['area_icon'] ?: '🎯') ?>
                                    </div>

                                    <div class="lq-object-title">
                                        <h2><?= e($goal['title']) ?></h2>
                                        <p><?= e($goal['description'] ?: 'Sin descripción.') ?></p>
                                    </div>

                                    <div class="lq-object-badges">
                                        <span class="lq-badge <?= priorityClass($goal['priority']) ?>"><?= priorityLabel($goal['priority']) ?></span>
                                        <span class="lq-badge <?= statusClass($goal['status']) ?>"><?= statusLabel($goal['status']) ?></span>
                                    </div>
                                </div>

                                <div class="lq-progress-block">
                                    <div class="lq-progress-info">
                                        <span>Progreso</span>
                                        <span><?= (int) $goal['progress'] ?>%</span>
                                    </div>
                                    <div class="lq-progress"><span style="width: <?= (int) $goal['progress'] ?>%"></span></div>
                                </div>

                                <div class="lq-object-footer">
                                    <div class="lq-object-meta">
                                        <span class="lq-badge"><?= goalTypeLabel($goal['type']) ?></span>
                                        <?php if (!empty($goal['area_name'])): ?>
                                            <span class="lq-badge green"><?= e(($goal['area_icon'] ? $goal['area_icon'] . ' ' : '') . $goal['area_name']) ?></span>
                                        <?php endif; ?>
                                        <span class="lq-badge purple">✦ +<?= (int) $goal['xp_reward'] ?> XP</span>
                                        <span class="lq-badge orange">🪙 +<?= (int) $goal['points_reward'] ?></span>
                                    </div>

                                    <div class="lq-object-actions">
                                        <a href="goals.php?edit=<?= (int) $goal['id'] ?>" class="btn btn-secondary">Editar</a>
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este objetivo?');">
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
        </section>
    </main>
</body>
</html>
