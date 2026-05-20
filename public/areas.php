<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LifeAreaController.php';

AuthController::requireAuth();

$controller = new LifeAreaController();
$userId = (int) $_SESSION['user_id'];

$message = null;
$messageType = null;
$editingArea = null;

if (isset($_GET['edit'])) {
    require_once __DIR__ . '/../app/Models/LifeArea.php';

    $lifeAreaModel = new LifeArea();
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
        $result = [
            'success' => false,
            'message' => 'Acción no válida.'
        ];
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

function activeNav(string $page): string
{
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Áreas de vida | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <div class="sidebar-brand">QuestBoard</div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= activeNav('dashboard.php') ?>">Inicio</a>
            <a href="areas.php" class="<?= activeNav('areas.php') ?>">Áreas</a>
            <a href="#">Metas</a>
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
                <p class="eyebrow">Organización personal</p>
                <h1>Áreas de vida</h1>
                <p class="muted">Divide tu progreso en bloques importantes: salud, estudios, trabajo, finanzas o desarrollo personal.</p>
            </div>

            <a href="dashboard.php" class="btn btn-secondary">Volver al dashboard</a>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?= e($messageType) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <section class="areas-layout">
            <article class="card">
                <div class="card-header">
                    <h2><?= $editingArea ? 'Editar área' : 'Crear nueva área' ?></h2>

                    <?php if ($editingArea): ?>
                        <a href="areas.php">Cancelar</a>
                    <?php endif; ?>
                </div>

                <form method="POST" class="form compact-form">
                    <input type="hidden" name="action" value="<?= $editingArea ? 'update' : 'create' ?>">

                    <?php if ($editingArea): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingArea['id'] ?>">
                    <?php endif; ?>

                    <label>
                        Nombre del área
                        <input
                            type="text"
                            name="name"
                            placeholder="Ej: Salud"
                            value="<?= e($editingArea['name'] ?? '') ?>"
                            required
                        >
                    </label>

                    <label>
                        Descripción
                        <textarea name="description" rows="4" placeholder="Describe qué representa esta área para ti."><?= e($editingArea['description'] ?? '') ?></textarea>
                    </label>

                    <div class="form-row">
                        <label>
                            Icono
                            <input
                                type="text"
                                name="icon"
                                placeholder="Ej: 💪"
                                value="<?= e($editingArea['icon'] ?? '') ?>"
                            >
                        </label>

                        <label>
                            Color
                            <input
                                type="color"
                                name="color"
                                value="<?= e($editingArea['color'] ?? '#16A34A') ?>"
                            >
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary full">
                        <?= $editingArea ? 'Guardar cambios' : 'Crear área' ?>
                    </button>
                </form>
            </article>

            <section class="areas-list">
                <?php if (empty($areas)): ?>
                    <article class="card empty-state">
                        <h2>No hay áreas todavía</h2>
                        <p>Empieza creando tus primeras áreas: salud, estudios, trabajo, finanzas o desarrollo personal.</p>
                    </article>
                <?php endif; ?>

                <?php foreach ($areas as $area): ?>
                    <article class="area-card">
                        <div class="area-icon" style="background: <?= e($area['color'] ?: '#16A34A') ?>;">
                            <?= e($area['icon'] ?: '●') ?>
                        </div>

                        <div class="area-content">
                            <h2><?= e($area['name']) ?></h2>

                            <?php if (!empty($area['description'])): ?>
                                <p><?= e($area['description']) ?></p>
                            <?php else: ?>
                                <p class="muted">Sin descripción.</p>
                            <?php endif; ?>

                            <div class="area-meta">
                                <span>Progreso inicial: 0%</span>
                                <span>Creada el <?= date('d/m/Y', strtotime($area['created_at'])) ?></span>
                            </div>
                        </div>

                        <div class="area-actions">
                            <a href="areas.php?edit=<?= (int) $area['id'] ?>" class="btn btn-secondary">Editar</a>

                            <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta área? Las metas, proyectos o hábitos asociados quedarán sin área.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $area['id'] ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </section>
    </main>
</body>
</html>
