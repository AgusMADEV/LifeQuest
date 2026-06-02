<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LifeAreaController.php';
require_once __DIR__ . '/../app/Models/AreaProgression.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$controller = new LifeAreaController();
$areaProgressionModel = new AreaProgression();
$lifeAreaModel = new LifeArea();
$userModel = new User();

$userId = (int) $_SESSION['user_id'];
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$message = null;
$messageType = null;
$editingArea = null;
$areaFormData = [];

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

    if (!$result['success'] && in_array($action, ['create', 'update'], true)) {
        $areaFormData = $_POST;
    }

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
$areaProgressionByArea = $areaProgressionModel->getByUser($userId);

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}

function formValue(array $formData, string $key, mixed $fallback = ''): mixed
{
    return array_key_exists($key, $formData) ? $formData[$key] : $fallback;
}

function areaIconOptions(): array
{
    $iconsDirectory = __DIR__ . '/../icons/areas';
    $labelMap = [
        'ChatGPT Image 3 jun 2026, 00_33_59 (1).png' => 'Libro',
        'ChatGPT Image 3 jun 2026, 00_33_59 (2).png' => 'Fitness',
        'ChatGPT Image 3 jun 2026, 00_33_59 (3).png' => 'Salud',
        'ChatGPT Image 3 jun 2026, 00_34_00 (4).png' => 'Estudio',
        'ChatGPT Image 3 jun 2026, 00_34_00 (5).png' => 'Trabajo',
        'ChatGPT Image 3 jun 2026, 00_34_00 (6).png' => 'Finanzas',
        'ChatGPT Image 3 jun 2026, 00_34_01 (7).png' => 'Relaciones',
        'ChatGPT Image 3 jun 2026, 00_34_01 (8).png' => 'Crecimiento',
    ];

    $options = [];
    $iconFiles = glob($iconsDirectory . '/*.png') ?: [];
    natsort($iconFiles);

    foreach ($iconFiles as $iconFile) {
        $fileName = basename($iconFile);
        $svgFile = pathinfo($fileName, PATHINFO_FILENAME) . '.svg';
        $svgUrl = '../icons/areas_svg/' . rawurlencode($svgFile);
        $previewUrl = is_file(__DIR__ . '/../icons/areas_svg/' . $svgFile)
            ? $svgUrl
            : '../icons/areas/' . rawurlencode($fileName);

        $options[] = [
            'value' => $fileName,
            'label' => $labelMap[$fileName] ?? 'Icono',
            'preview' => $previewUrl,
            'masked' => $previewUrl,
        ];
    }

    return $options;
}

function areaIconMaskedPath(string|null $iconValue, array $areaIconByValue): ?string
{
    $iconValue = trim((string) $iconValue);

    if ($iconValue === '' || !isset($areaIconByValue[$iconValue])) {
        return null;
    }

    $baseName = pathinfo($iconValue, PATHINFO_FILENAME);
    $svgFile = $baseName . '.svg';
    $svgPath = __DIR__ . '/../icons/areas_svg/' . $svgFile;
    if (is_file($svgPath)) {
        return '../icons/areas_svg/' . rawurlencode($svgFile);
    }

    return $areaIconByValue[$iconValue]['masked'] ?? null;
}

$modalIsOpen = $editingArea !== null || !empty($areaFormData);
$areaCurrent = !empty($areaFormData) ? $areaFormData : ($editingArea ?? []);
$areaIconOptions = areaIconOptions();
$areaIconByValue = [];

foreach ($areaIconOptions as $areaIconOption) {
    $areaIconByValue[$areaIconOption['value']] = $areaIconOption;
}

$selectedIconValue = (string) formValue($areaCurrent, 'icon', '');
$defaultIconValue = $areaIconOptions[0]['value'] ?? '';

if ($selectedIconValue === '' && !$editingArea) {
    $selectedIconValue = $defaultIconValue;
}

$currentAreaColor = (string) formValue($areaCurrent, 'color', '#16C79A');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Áreas | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'areas'; ?>
        <?php require __DIR__ . '/partials/sidebar_nav.php'; ?>

        <section class="lq-sidebar-card streak">
            <div class="streak-summary">
                <div class="streak-icon" aria-hidden="true">
                    <img src="../icons/flame.png" alt="" class="streak-flame-image">
                </div>
                <div class="streak-copy">
                    <p>Racha actual</p>
                    <strong><?= (int)($user['current_streak'] ?? 0) ?> días</strong>
                    <small>¡Sigue así!</small>
                </div>
            </div>
        </section>

        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <?php $topbarSearchPlaceholder = 'Buscar áreas, metas o misiones...'; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <section class="lq-page-shell">
            <header class="lq-page-hero">
                <div>
                    <p class="eyebrow">Mapa personal</p>
                    <h1>Áreas de vida</h1>
                    <p>Organiza tu progreso por categorías importantes: salud, estudios, trabajo, finanzas, relaciones o desarrollo personal.</p>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <div class="metas-modal-backdrop <?= $modalIsOpen ? 'is-open' : '' ?>" data-goal-modal-close></div>

            <article class="lq-form-panel metas-form-modal <?= $modalIsOpen ? 'is-open' : '' ?>" data-goal-form-modal>
                <div class="lq-panel-header">
                    <div>
                        <h2><?= $editingArea ? 'Editar área' : 'Nueva área' ?></h2>
                        <p><?= $editingArea ? 'Ajusta esta categoría de progreso.' : 'Crea una categoría para ordenar tus metas.' ?></p>
                    </div>
                    <a href="areas.php" class="metas-modal-close" data-goal-modal-close>×</a>
                </div>

                <form method="POST" class="lq-form">
                    <input type="hidden" name="action" value="<?= $editingArea ? 'update' : 'create' ?>">
                    <?php if ($editingArea): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingArea['id'] ?>">
                    <?php endif; ?>

                    <label>
                        Nombre del área
                        <input type="text" name="name" placeholder="Ej: Salud" value="<?= e((string) formValue($areaCurrent, 'name', '')) ?>" required>
                    </label>

                    <label>
                        Descripción
                        <textarea name="description" rows="4" placeholder="Describe qué representa esta área para ti."><?= e((string) formValue($areaCurrent, 'description', '')) ?></textarea>
                    </label>

                    <div class="lq-form-row">
                        <label>
                            Color
                            <input type="color" name="color" value="<?= e((string) formValue($areaCurrent, 'color', '#16C79A')) ?>">
                        </label>
                    </div>

                    <fieldset class="lq-icon-picker">
                        <legend>Icono</legend>
                        <p class="lq-icon-picker-help">Elige un icono para esta área. Se pintará con el color seleccionado.</p>

                        <div class="lq-icon-picker-grid" data-area-icon-picker style="--picker-color: <?= e($currentAreaColor) ?>;">
                            <?php foreach ($areaIconOptions as $areaIconOption): ?>
                                <?php $isChecked = $selectedIconValue === $areaIconOption['value']; ?>
                                <label class="lq-icon-option" title="<?= e($areaIconOption['label']) ?>">
                                    <input type="radio" name="icon" value="<?= e($areaIconOption['value']) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <span class="lq-icon-option-preview" style="-webkit-mask-image: url('<?= e($areaIconOption['masked']) ?>'); mask-image: url('<?= e($areaIconOption['masked']) ?>');"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <button type="submit" class="btn btn-primary full"><?= $editingArea ? 'Guardar cambios' : 'Crear área' ?></button>
                </form>
            </article>

            <section class="lq-list-panel">
                <div class="lq-panel-header">
                    <div>
                        <h2>Tus áreas</h2>
                        <p><?= count($areas) ?> áreas creadas</p>
                    </div>
                    <button type="button" class="btn btn-primary metas-add-btn" data-goal-modal-open>+ área</button>
                </div>

                <div class="lq-list-grid">
                    <?php if (empty($areas)): ?>
                        <article class="lq-empty">
                            <h2>No hay áreas todavía</h2>
                            <p>Empieza creando áreas como Salud, Estudios, Trabajo o Finanzas.</p>
                        </article>
                    <?php endif; ?>

                    <?php foreach ($areas as $area): ?>
                        <?php $areaLevel = (int) ($areaProgressionByArea[(int) $area['id']]['level'] ?? 1); ?>
                        <?php $areaIconPath = areaIconMaskedPath((string) ($area['icon'] ?? ''), $areaIconByValue); ?>
                        <article class="lq-object-card">
                            <div class="lq-object-top">
                                <?php if ($areaIconPath): ?>
                                    <div class="lq-object-icon lq-object-icon-mask" style="--area-color: <?= e($area['color'] ?: '#16C79A') ?>; -webkit-mask-image: url('<?= e($areaIconPath) ?>'); mask-image: url('<?= e($areaIconPath) ?>');"></div>
                                <?php else: ?>
                                    <div class="lq-object-icon" style="background: <?= e($area['color'] ?: '#16C79A') ?>;">
                                        <?= e($area['icon'] ?: '●') ?>
                                    </div>
                                <?php endif; ?>

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
                                    <span class="lq-badge">Nivel actual: <?= $areaLevel ?></span>
                                    <span class="lq-badge">Creada el <?= date('d/m/Y', strtotime($area['created_at'])) ?></span>
                                </div>

                                <div class="mission-item-actions">
                                    <a href="areas.php?edit=<?= (int) $area['id'] ?>" class="btn btn-secondary lq-icon-btn lq-icon-edit" aria-label="Editar área"></a>
                                    <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta área?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $area['id'] ?>">
                                        <button type="submit" class="btn lq-btn-danger lq-icon-btn lq-icon-delete" aria-label="Eliminar área"></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
