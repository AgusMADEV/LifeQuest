<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LifeAreaController.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$controller = new LifeAreaController();
$lifeAreaModel = new LifeArea();
$userModel = new User();

$userId = (int) $_SESSION['user_id'];
$user = $userModel->findById($userId);

$message = null;
$messageType = null;
$editingArea = null;

if (isset($_GET['edit'])) {
    $editingArea = $lifeAreaModel->findByIdAndUser((int) $_GET['edit'], $userId);
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
        header('Location: areas.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$areas = $controller->index($userId);

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Áreas | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <a href="dashboard.php" class="lq-logo"><span>Life<span>Quest</span><i>✦</i></span></a>
        <nav class="lq-nav">
<a href="dashboard.php"><span>🏠</span>Inicio</a>
<a href="goals.php"><span>🎯</span>Objetivos</a>
<a href="projects.php"><span>🚀</span>Misiones</a>
<a href="areas.php" class="active"><span>🧩</span>Áreas</a>
<a href="#"><span>💚</span>Hábitos</a>
<a href="#"><span>🛍️</span>Tienda</a>
<a href="#"><span>📊</span>Progreso</a>
</nav>

        <section class="lq-sidebar-card streak">
            <div class="streak-icon">🔥</div>
            <p>Racha actual</p>
            <strong><?= (int)($user['current_streak'] ?? 0) ?> días</strong>
            <small>¡Sigue así!</small>
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
                <input type="search" placeholder="Buscar áreas, objetivos o misiones..." disabled>
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
                    <p class="eyebrow">Mapa personal</p>
                    <h1>Áreas de vida</h1>
                    <p>Organiza tu progreso por categorías importantes: salud, estudios, trabajo, finanzas, relaciones o desarrollo personal.</p>
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
                            <h2><?= $editingArea ? 'Editar área' : 'Nueva área' ?></h2>
                            <p><?= $editingArea ? 'Ajusta esta categoría de progreso.' : 'Crea una categoría para ordenar tus objetivos.' ?></p>
                        </div>
                        <?php if ($editingArea): ?><a href="areas.php">Cancelar</a><?php endif; ?>
                    </div>

                    <form method="POST" class="lq-form">
                        <input type="hidden" name="action" value="<?= $editingArea ? 'update' : 'create' ?>">
                        <?php if ($editingArea): ?>
                            <input type="hidden" name="id" value="<?= (int) $editingArea['id'] ?>">
                        <?php endif; ?>

                        <label>
                            Nombre del área
                            <input type="text" name="name" placeholder="Ej: Salud" value="<?= e($editingArea['name'] ?? '') ?>" required>
                        </label>

                        <label>
                            Descripción
                            <textarea name="description" rows="4" placeholder="Describe qué representa esta área para ti."><?= e($editingArea['description'] ?? '') ?></textarea>
                        </label>

                        <div class="lq-form-row">
                            <label>
                                Icono
                                <input type="text" name="icon" placeholder="Ej: 💪" value="<?= e($editingArea['icon'] ?? '') ?>">
                            </label>

                            <label>
                                Color
                                <input type="color" name="color" value="<?= e($editingArea['color'] ?? '#16C79A') ?>">
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary full"><?= $editingArea ? 'Guardar cambios' : 'Crear área' ?></button>
                    </form>
                </article>

                <section class="lq-list-panel">
                    <div class="lq-panel-header">
                        <div>
                            <h2>Tus áreas</h2>
                            <p><?= count($areas) ?> áreas creadas</p>
                        </div>
                    </div>

                    <div class="lq-list-grid">
                        <?php if (empty($areas)): ?>
                            <article class="lq-empty">
                                <h2>No hay áreas todavía</h2>
                                <p>Empieza creando áreas como Salud, Estudios, Trabajo o Finanzas.</p>
                            </article>
                        <?php endif; ?>

                        <?php foreach ($areas as $area): ?>
                            <article class="lq-object-card">
                                <div class="lq-object-top">
                                    <div class="lq-object-icon" style="background: <?= e($area['color'] ?: '#16C79A') ?>;">
                                        <?= e($area['icon'] ?: '●') ?>
                                    </div>

                                    <div class="lq-object-title">
                                        <h2><?= e($area['name']) ?></h2>
                                        <p><?= e($area['description'] ?: 'Sin descripción.') ?></p>
                                    </div>

                                    <div class="lq-object-badges">
                                        <span class="lq-badge green">Activa</span>
                                    </div>
                                </div>

                                <div class="lq-object-footer">
                                    <div class="lq-object-meta">
                                        <span class="lq-badge">Progreso inicial: 0%</span>
                                        <span class="lq-badge">Creada el <?= date('d/m/Y', strtotime($area['created_at'])) ?></span>
                                    </div>

                                    <div class="lq-object-actions">
                                        <a href="areas.php?edit=<?= (int) $area['id'] ?>" class="btn btn-secondary">Editar</a>
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta área?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $area['id'] ?>">
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
