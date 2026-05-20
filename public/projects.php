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
    <title>Retos | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <a href="dashboard.php" class="lq-logo"><span>Life<span>Quest</span><i>✦</i></span></a>
        <nav class="lq-nav">
            <a href="dashboard.php"><span>🏠</span>Inicio</a>
            <a href="goals.php"><span>🎯</span>Metas</a>
            <a href="projects.php" class="active"><span>🚀</span>Retos</a>
            <a href="tasks.php"><span>✅</span>Misiones</a>
            <a href="areas.php"><span>🧩</span>Áreas</a>
            <a href="#"><span>💚</span>Hábitos</a>
            <a href="#"><span>🛍️</span>Tienda</a>
            <a href="#"><span>📊</span>Progreso</a>
        </nav>

        <section class="lq-sidebar-card unlock">
            <div>
                <strong>Modo reto</strong>
                <p>Divide tus metas en retos claros.</p>
                <a href="goals.php" class="mini-btn">Ver metas</a>
            </div>
            <span class="bag">🚀</span>
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
                <input type="search" placeholder="Buscar retos..." disabled>
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
                    <p class="eyebrow">Bloques de avance</p>
                    <h1>Retos</h1>
                    <p>Divide tus metas en retos concretos. Cada reto agrupa varias misiones diarias para avanzar sin perder claridad.</p>
                </div>
                <div class="lq-page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
                    <a href="goals.php" class="btn btn-primary">Crear meta</a>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="lq-crud-layout">
                <article class="lq-form-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2><?= $editingProject ? 'Editar reto' : 'Nueva reto' ?></h2>
                            <p><?= $editingProject ? 'Actualiza esta reto.' : 'Crea un bloque de trabajo conectado a un meta.' ?></p>
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
                            <textarea name="description" rows="3" placeholder="Describe el alcance de esta reto."><?= e($editingProject['description'] ?? '') ?></textarea>
                        </label>

                        <label>Meta relacionado
                            <select name="goal_id">
                                <option value="">Sin meta</option>
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

                        <button type="submit" class="btn btn-primary full"><?= $editingProject ? 'Guardar cambios' : 'Crear reto' ?></button>
                    </form>
                </article>

                <section class="lq-list-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2>Tus retos</h2>
                            <p><?= count($projects) ?> retos creadas</p>
                        </div>
                    </div>

                    <div class="lq-list-grid">
                        <?php if (empty($projects)): ?>
                            <article class="lq-empty">
                                <h2>No hay retos todavía</h2>
                                <p>Crea tu primera reto y conecta tus metas con acciones concretas.</p>
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
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta reto?');">
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
