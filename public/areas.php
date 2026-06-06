<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LifeAreaController.php';
require_once __DIR__ . '/../app/Models/AreaProgression.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Support/AvatarLibrary.php';

AuthController::requireAuth();

$controller = new LifeAreaController();
$areaProgressionModel = new AreaProgression();
$lifeAreaModel = new LifeArea();
$taskModel = new Task();
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
$taskDistribution = $taskModel->getDistributionByArea($userId);

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

function buildDonutGradient(array $distribution): string
{
    if (empty($distribution)) {
        return 'conic-gradient(#e2e8f0 0 100%)';
    }

    $gradientParts = [];
    $currentPercent = 0;

    foreach ($distribution as $area) {
        $percentage = (float) ($area['percentage'] ?? 0);
        $color = e($area['area_color'] ?? '#8b5cf6');
        $nextPercent = $currentPercent + $percentage;

        $gradientParts[] = "{$color} {$currentPercent}% {$nextPercent}%";
        $currentPercent = $nextPercent;
    }

    return 'conic-gradient(' . implode(', ', $gradientParts) . ')';
}

function hexToRgba(string $hex, float $alpha = 1.0): string
{
    $hex = trim($hex);

    if (!preg_match('/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $hex)) {
        $hex = '#16C79A';
    }

    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));

    return "rgba({$red}, {$green}, {$blue}, {$alpha})";
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

$xpCurrent = (int) ($user['xp'] ?? 0);
$level = max(1, (int) ($user['level'] ?? 1));
$xpPerLevel = 1000;
$xpCurrentLevel = $xpCurrent % $xpPerLevel;
$xpPercent = min(100, (int) round(($xpCurrentLevel / max(1, $xpPerLevel)) * 100));
$points = (int) ($user['points'] ?? 0);
$gems = max(0, intdiv($points, 20));
$heroAvatarSrc = AvatarLibrary::getAvatarSrc($user['avatar'] ?? null);

$areaRows = [];
$totalAreaXp = 0;
$levelSum = 0;

foreach ($areas as $area) {
    $progression = $areaProgressionByArea[(int) $area['id']] ?? [];
    $areaLevel = max(1, (int) ($progression['level'] ?? 1));
    $areaXp = max(0, (int) ($progression['xp'] ?? 0));
    $levelXp = max(0, (int) ($progression['level_xp'] ?? ($areaXp % 1000)));
    $levelTarget = max(1, (int) ($progression['level_xp_target'] ?? 1000));
    $levelPercent = min(100, max(0, (int) ($progression['level_percent'] ?? round(($levelXp / $levelTarget) * 100))));

    $areaRows[] = [
        'area' => $area,
        'level' => $areaLevel,
        'xp' => $areaXp,
        'level_xp' => $levelXp,
        'level_target' => $levelTarget,
        'level_percent' => $levelPercent,
        'icon_path' => areaIconMaskedPath((string) ($area['icon'] ?? ''), $areaIconByValue),
    ];

    $totalAreaXp += $areaXp;
    $levelSum += $areaLevel;
}

usort($areaRows, static function (array $left, array $right): int {
    return [$right['level'], $right['xp'], (int) $right['area']['id']]
        <=> [$left['level'], $left['xp'], (int) $left['area']['id']];
});

$featuredAreaRow = $areaRows[0] ?? null;
$averageAreaLevel = count($areaRows) > 0 ? round($levelSum / count($areaRows), 1) : 0;
$activeAreasTab = (string) ($_GET['tab'] ?? 'summary') === 'balance' ? 'balance' : 'summary';

$taskDistributionByArea = [];
$balanceTotalTasks = 0;

foreach ($taskDistribution as $distributionArea) {
    $distributionAreaId = (int) ($distributionArea['area_id'] ?? 0);

    if ($distributionAreaId <= 0) {
        continue;
    }

    $taskCount = (int) ($distributionArea['task_count'] ?? 0);
    $taskDistributionByArea[$distributionAreaId] = $distributionArea;
    $balanceTotalTasks += max(0, $taskCount);
}

$balanceAreaRows = [];

foreach ($areas as $area) {
    $areaId = (int) ($area['id'] ?? 0);
    $distributionArea = $taskDistributionByArea[$areaId] ?? [];
    $taskCount = (int) ($distributionArea['task_count'] ?? 0);
    $percentage = $balanceTotalTasks > 0
        ? round(($taskCount / max(1, $balanceTotalTasks)) * 100, 1)
        : 0;
    $areaColor = (string) ($area['color'] ?: '#16C79A');

    $balanceAreaRows[] = [
        'id' => $areaId,
        'name' => (string) ($area['name'] ?? 'Área'),
        'icon' => (string) ($area['icon'] ?? ''),
        'icon_path' => areaIconMaskedPath((string) ($area['icon'] ?? ''), $areaIconByValue),
        'color' => $areaColor,
        'color_soft' => hexToRgba($areaColor, 0.14),
        'color_border' => hexToRgba($areaColor, 0.24),
        'task_count' => max(0, $taskCount),
        'percentage' => $percentage,
        'level' => (int) ($areaProgressionByArea[$areaId]['level'] ?? 1),
    ];
}

$balanceScore = 0;

if (!empty($balanceAreaRows) && $balanceTotalTasks > 0) {
    $idealPercent = 100 / count($balanceAreaRows);
    $deviation = 0;

    foreach ($balanceAreaRows as $balanceAreaRow) {
        $deviation += abs(((float) $balanceAreaRow['percentage']) - $idealPercent);
    }

    $balanceScore = min(100, max(0, (int) round(100 - $deviation)));
}

$balanceStrongestArea = null;
$balanceWeakestArea = null;

if (!empty($balanceAreaRows)) {
    $balanceStrongestArea = $balanceAreaRows[0];
    $balanceWeakestArea = $balanceAreaRows[0];

    foreach ($balanceAreaRows as $balanceAreaRow) {
        if ((float) $balanceAreaRow['percentage'] > (float) $balanceStrongestArea['percentage']) {
            $balanceStrongestArea = $balanceAreaRow;
        }

        if ((float) $balanceAreaRow['percentage'] < (float) $balanceWeakestArea['percentage']) {
            $balanceWeakestArea = $balanceAreaRow;
        }
    }
}

$balanceRecommendationRows = $balanceAreaRows;
usort($balanceRecommendationRows, static function (array $left, array $right): int {
    return [$left['percentage'], $left['task_count'], $left['name']]
        <=> [$right['percentage'], $right['task_count'], $right['name']];
});

$balanceStatusLabel = $balanceScore >= 80
    ? 'Bien equilibrado'
    : ($balanceScore >= 60 ? 'En progreso' : 'Necesita atención');

$balanceStatusText = $balanceScore >= 80
    ? 'Sigue manteniendo este equilibrio.'
    : 'Refuerza las áreas con menos actividad para recuperar balance.';

$balanceDonutDistribution = [];
$balanceDonutLabels = [];
$balanceDonutCurrentPercent = 0.0;

foreach ($balanceAreaRows as $balanceAreaRow) {
    $balanceAreaPercentage = (float) $balanceAreaRow['percentage'];

    if ($balanceAreaPercentage <= 0) {
        continue;
    }

    $balanceDonutDistribution[] = [
        'area_color' => $balanceAreaRow['color'],
        'percentage' => $balanceAreaPercentage,
    ];

    $balanceDonutMidPercent = $balanceDonutCurrentPercent + ($balanceAreaPercentage / 2);
    $balanceDonutAngle = deg2rad(($balanceDonutMidPercent / 100) * 360 - 90);
    $balanceDonutLabelRadius = 45;

    $balanceDonutLabels[] = [
        'name' => $balanceAreaRow['name'],
        'color' => $balanceAreaRow['color'],
        'percentage' => $balanceAreaPercentage,
        'x' => round(50 + cos($balanceDonutAngle) * $balanceDonutLabelRadius, 2),
        'y' => round(50 + sin($balanceDonutAngle) * $balanceDonutLabelRadius, 2),
    ];

    $balanceDonutCurrentPercent += $balanceAreaPercentage;
}

$balanceRadarRows = array_slice($balanceAreaRows, 0, 6);
$balanceRadarCount = count($balanceRadarRows);
$balanceRadarCenter = 120;
$balanceRadarRadius = 78;
$balanceRadarPoints = [];
$balanceRadarIdealPoints = [];
$balanceRadarAxes = [];
$balanceRadarRings = [];
$balanceRadarIdealPercent = $balanceRadarCount > 0 ? 100 / $balanceRadarCount : 0;
$balanceRadarMaxPercent = $balanceRadarIdealPercent;

foreach ($balanceRadarRows as $balanceRadarRow) {
    $balanceRadarMaxPercent = max($balanceRadarMaxPercent, (float) ($balanceRadarRow['percentage'] ?? 0));
}

$balanceRadarScaleMax = $balanceRadarMaxPercent > 0
    ? min(100, max(25, ceil($balanceRadarMaxPercent * 1.15)))
    : 100;

if ($balanceRadarCount >= 3) {
    for ($ring = 1; $ring <= 4; $ring++) {
        $ringRadius = $balanceRadarRadius * ($ring / 4);
        $ringPoints = [];

        for ($index = 0; $index < $balanceRadarCount; $index++) {
            $angle = (-M_PI / 2) + (($index * 2 * M_PI) / $balanceRadarCount);
            $ringPoints[] = round($balanceRadarCenter + cos($angle) * $ringRadius, 2) . ',' . round($balanceRadarCenter + sin($angle) * $ringRadius, 2);
        }

        $balanceRadarRings[] = implode(' ', $ringPoints);
    }

    foreach ($balanceRadarRows as $index => &$balanceRadarRow) {
        $angle = (-M_PI / 2) + (($index * 2 * M_PI) / $balanceRadarCount);
        $areaPercentage = (float) ($balanceRadarRow['percentage'] ?? 0);
        $pointRadius = $balanceRadarRadius * (min($areaPercentage, $balanceRadarScaleMax) / $balanceRadarScaleMax);
        $idealPointRadius = $balanceRadarRadius * (min($balanceRadarIdealPercent, $balanceRadarScaleMax) / $balanceRadarScaleMax);

        $balanceRadarRow['radar_value'] = round($areaPercentage, 1);
        $balanceRadarRow['label_x'] = round($balanceRadarCenter + cos($angle) * ($balanceRadarRadius + 26), 2);
        $balanceRadarRow['label_y'] = round($balanceRadarCenter + sin($angle) * ($balanceRadarRadius + 26), 2);
        $balanceRadarRow['anchor'] = cos($angle) > 0.25 ? 'start' : (cos($angle) < -0.25 ? 'end' : 'middle');

        $balanceRadarAxes[] = [
            'x' => round($balanceRadarCenter + cos($angle) * $balanceRadarRadius, 2),
            'y' => round($balanceRadarCenter + sin($angle) * $balanceRadarRadius, 2),
        ];
        $balanceRadarPoints[] = round($balanceRadarCenter + cos($angle) * $pointRadius, 2) . ',' . round($balanceRadarCenter + sin($angle) * $pointRadius, 2);
        $balanceRadarIdealPoints[] = round($balanceRadarCenter + cos($angle) * $idealPointRadius, 2) . ',' . round($balanceRadarCenter + sin($angle) * $idealPointRadius, 2);
    }
    unset($balanceRadarRow);
}

$balanceRadarPolygon = implode(' ', $balanceRadarPoints);
$balanceRadarIdealPolygon = implode(' ', $balanceRadarIdealPoints);

$stylesCssVersion = (int) (@filemtime(__DIR__ . '/../assets/css/styles.css') ?: time());
$crudCssVersion = (int) (@filemtime(__DIR__ . '/../assets/css/modules/crud.css') ?: time());
$areasCssVersion = (int) (@filemtime(__DIR__ . '/../assets/css/modules/areas.css') ?: time());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Áreas | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?= $stylesCssVersion ?>">
    <link rel="stylesheet" href="../assets/css/modules/crud.css?v=<?= $crudCssVersion ?>">
    <link rel="stylesheet" href="../assets/css/modules/areas.css?v=<?= $areasCssVersion ?>">
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

        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main">
        <?php $topbarSearchPlaceholder = 'Buscar áreas, metas o misiones...'; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <section class="lq-page-shell">
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

            <div class="areas-hub-layout">
                <section class="areas-main-column">
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

                    <div class="areas-tabs" aria-label="Vistas de áreas">
                        <div class="areas-tabs-group" role="tablist">
                            <a href="areas.php?tab=summary" class="<?= $activeAreasTab === 'summary' ? 'is-active' : '' ?>">Resumen</a>
                            <a href="areas.php?tab=balance" class="<?= $activeAreasTab === 'balance' ? 'is-active' : '' ?>">Balance</a>
                        </div>
                        <button type="button" class="btn btn-primary areas-create-btn" data-goal-modal-open>+ Crear</button>
                    </div>

                    <?php if ($activeAreasTab === 'balance'): ?>
                        <section class="areas-summary-grid areas-balance-summary" aria-label="Resumen de balance">
                            <article class="areas-summary-card">
                                <span class="areas-summary-icon area-teal">⚖</span>
                                <div>
                                    <small>Equilibrio general</small>
                                    <strong><?= $balanceScore ?>%</strong>
                                    <small><?= e($balanceStatusLabel) ?></small>
                                </div>
                            </article>
                            <article class="areas-summary-card">
                                <?php if ($balanceStrongestArea && $balanceStrongestArea['icon_path']): ?>
                                    <span class="areas-level-icon areas-level-icon-shell" style="--area-color: <?= e($balanceStrongestArea['color']) ?>; --area-color-soft: <?= e($balanceStrongestArea['color_soft']) ?>; --area-color-border: <?= e($balanceStrongestArea['color_border']) ?>;">
                                        <span class="areas-level-icon-mask" style="-webkit-mask-image: url('<?= e($balanceStrongestArea['icon_path']) ?>'); mask-image: url('<?= e($balanceStrongestArea['icon_path']) ?>');"></span>
                                    </span>
                                <?php else: ?>
                                    <span class="areas-summary-icon area-green">✓</span>
                                <?php endif; ?>
                                <div>
                                    <small>Área más fuerte</small>
                                    <strong><?= e($balanceStrongestArea['name'] ?? 'Sin datos') ?></strong>
                                    <small><?= $balanceStrongestArea ? number_format((float) $balanceStrongestArea['percentage'], 0) . '%' : '0%' ?></small>
                                </div>
                            </article>
                            <article class="areas-summary-card">
                                <?php if ($balanceWeakestArea && $balanceWeakestArea['icon_path']): ?>
                                    <span class="areas-level-icon areas-level-icon-shell" style="--area-color: <?= e($balanceWeakestArea['color']) ?>; --area-color-soft: <?= e($balanceWeakestArea['color_soft']) ?>; --area-color-border: <?= e($balanceWeakestArea['color_border']) ?>;">
                                        <span class="areas-level-icon-mask" style="-webkit-mask-image: url('<?= e($balanceWeakestArea['icon_path']) ?>'); mask-image: url('<?= e($balanceWeakestArea['icon_path']) ?>');"></span>
                                    </span>
                                <?php else: ?>
                                    <span class="areas-summary-icon area-purple">!</span>
                                <?php endif; ?>
                                <div>
                                    <small>Área a reforzar</small>
                                    <strong><?= e($balanceWeakestArea['name'] ?? 'Sin datos') ?></strong>
                                    <small><?= $balanceWeakestArea ? number_format((float) $balanceWeakestArea['percentage'], 0) . '%' : '0%' ?></small>
                                </div>
                            </article>
                            <article class="areas-summary-card">
                                <span class="areas-summary-icon area-purple">✦</span>
                                <div>
                                    <strong><?= number_format($totalAreaXp, 0, ',', '.') ?> XP</strong>
                                    <small>XP de áreas</small>
                                </div>
                            </article>
                        </section>

                        <section class="areas-balance-panel">
                            <div class="areas-balance-panel-main">
                                <header class="areas-balance-panel-header">
                                    <h2>Balance de áreas</h2>
                                    <p><?= e($balanceStatusText) ?></p>
                                </header>

                                <?php if (empty($balanceAreaRows) || $balanceTotalTasks <= 0): ?>
                                    <article class="lq-empty">
                                        <h2>No hay balance todavía</h2>
                                        <p>Asigna áreas a tus misiones para calcular cómo se reparte tu actividad.</p>
                                    </article>
                                <?php else: ?>
                                    <div class="areas-balance-visuals">
                                        <div class="areas-balance-donut-stage">
                                            <div class="areas-balance-donut" style="background: radial-gradient(circle, #fff 52%, transparent 53%), <?= buildDonutGradient($balanceDonutDistribution) ?>;">
                                                <strong><?= $balanceScore ?>%</strong>
                                                <span>Equilibrio<br>general</span>
                                            </div>
                                            <div class="areas-balance-orbit">
                                                <?php foreach ($balanceDonutLabels as $balanceDonutLabel): ?>
                                                    <span style="--area-color: <?= e($balanceDonutLabel['color']) ?>; --label-x: <?= e((string) $balanceDonutLabel['x']) ?>%; --label-y: <?= e((string) $balanceDonutLabel['y']) ?>%;">
                                                        <?= e(shortText($balanceDonutLabel['name'], 12)) ?>
                                                        <b><?= number_format((float) $balanceDonutLabel['percentage'], 0) ?>%</b>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="areas-radar-card" aria-label="Radar de balance por área">
                                            <?php if ($balanceRadarCount >= 3): ?>
                                                <svg class="areas-radar-chart" viewBox="0 0 240 240" role="img" aria-label="Gráfico radar de balance por áreas">
                                                    <?php foreach ($balanceRadarRings as $ringPoints): ?>
                                                        <polygon class="areas-radar-ring" points="<?= e($ringPoints) ?>"></polygon>
                                                    <?php endforeach; ?>

                                                    <?php foreach ($balanceRadarAxes as $axis): ?>
                                                        <line class="areas-radar-axis" x1="<?= $balanceRadarCenter ?>" y1="<?= $balanceRadarCenter ?>" x2="<?= e((string) $axis['x']) ?>" y2="<?= e((string) $axis['y']) ?>"></line>
                                                    <?php endforeach; ?>

                                                    <polygon class="areas-radar-ideal" points="<?= e($balanceRadarIdealPolygon) ?>"></polygon>
                                                    <polygon class="areas-radar-current" points="<?= e($balanceRadarPolygon) ?>"></polygon>

                                                    <?php foreach ($balanceRadarRows as $balanceRadarRow): ?>
                                                        <text class="areas-radar-label" x="<?= e((string) $balanceRadarRow['label_x']) ?>" y="<?= e((string) $balanceRadarRow['label_y']) ?>" text-anchor="<?= e($balanceRadarRow['anchor']) ?>">
                                                            <tspan><?= e(shortText($balanceRadarRow['name'], 10)) ?></tspan>
                                                            <tspan x="<?= e((string) $balanceRadarRow['label_x']) ?>" dy="10" class="areas-radar-label-value"><?= number_format((float) $balanceRadarRow['radar_value'], 0) ?>%</tspan>
                                                        </text>
                                                    <?php endforeach; ?>
                                                </svg>
                                                <div class="areas-radar-legend">
                                                    <span><i></i>Tu balance actual</span>
                                                    <span><i></i>Balance ideal</span>
                                                </div>
                                            <?php else: ?>
                                                <article class="lq-empty">
                                                    <h2>Radar no disponible</h2>
                                                    <p>Crea al menos tres áreas para ver el gráfico radar.</p>
                                                </article>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="areas-balance-note">
                                        <span>✦</span>
                                        <p><strong>¡Buen trabajo, <?= e(shortText($user['name'] ?? 'Alex', 14)) ?>!</strong> <?= e($balanceStatusText) ?><?= $balanceWeakestArea ? ' Refuerza ' . e($balanceWeakestArea['name']) . ' para acercarte a tu mejor balance.' : '' ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <aside class="areas-recommendations-card">
                                <div class="areas-side-title">
                                    <h2>Recomendaciones</h2>
                                </div>
                                <?php foreach (array_slice($balanceRecommendationRows, 0, 3) as $recommendationArea): ?>
                                    <article class="areas-recommendation-item">
                                        <?php if ($recommendationArea['icon_path']): ?>
                                            <span class="areas-level-icon areas-level-icon-shell" style="--area-color: <?= e($recommendationArea['color']) ?>; --area-color-soft: <?= e($recommendationArea['color_soft']) ?>; --area-color-border: <?= e($recommendationArea['color_border']) ?>;">
                                                <span class="areas-level-icon-mask" style="-webkit-mask-image: url('<?= e($recommendationArea['icon_path']) ?>'); mask-image: url('<?= e($recommendationArea['icon_path']) ?>');"></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="areas-level-icon" style="background: <?= e($recommendationArea['color_soft']) ?>; color: <?= e($recommendationArea['color']) ?>; border: 1px solid <?= e($recommendationArea['color_border']) ?>;">•</span>
                                        <?php endif; ?>
                                        <div>
                                            <strong>Refuerza <?= e(shortText($recommendationArea['name'], 18)) ?></strong>
                                            <p><?= number_format((float) $recommendationArea['percentage'], 0) ?>% de actividad actual.</p>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                                <a href="goals.php?section=tasks" class="btn btn-secondary full">Ver misiones</a>
                            </aside>
                        </section>
                    <?php else: ?>
                    <section class="areas-summary-grid" aria-label="Resumen de áreas">
                        <article class="areas-summary-card">
                            <span class="areas-summary-icon area-green">◔</span>
                            <div>
                                <strong><?= count($areaRows) ?></strong>
                                <small>áreas activas</small>
                            </div>
                        </article>
                        <article class="areas-summary-card">
                            <span class="areas-summary-icon area-orange">▥</span>
                            <div>
                                <strong><?= number_format((float) $averageAreaLevel, 1, ',', '.') ?></strong>
                                <small>nivel medio</small>
                            </div>
                        </article>
                        <article class="areas-summary-card">
                            <span class="areas-summary-icon area-purple">✦</span>
                            <div>
                                <strong><?= number_format($totalAreaXp, 0, ',', '.') ?> XP</strong>
                                <small>XP de áreas</small>
                            </div>
                        </article>
                        <article class="areas-summary-card">
                            <span class="areas-summary-icon area-teal">⚖</span>
                            <div>
                                <strong><?= $balanceScore ?>%</strong>
                                <small>equilibrio general</small>
                            </div>
                        </article>
                    </section>

                    <section class="areas-level-panel">
                        <div class="areas-level-heading">
                            <h2>Nivel por áreas</h2>
                            <span>Nivel actual</span>
                            <span>Progreso</span>
                        </div>

                        <div class="areas-level-list">
                            <?php if (empty($areaRows)): ?>
                                <article class="lq-empty">
                                    <h2>No hay áreas todavía</h2>
                                    <p>Empieza creando áreas como Salud, Estudios, Trabajo o Finanzas.</p>
                                </article>
                            <?php endif; ?>

                            <?php foreach ($areaRows as $areaRow): ?>
                                <?php $area = $areaRow['area']; ?>
                                <?php $areaColor = (string) ($area['color'] ?: '#16C79A'); ?>
                                <?php $areaColorSoft = hexToRgba($areaColor, 0.14); ?>
                                <?php $areaColorBorder = hexToRgba($areaColor, 0.24); ?>
                                <article class="areas-level-row">
                                    <div class="areas-level-area">
                                        <?php if ($areaRow['icon_path']): ?>
                                            <span class="areas-level-icon areas-level-icon-shell" style="--area-color: <?= e($areaColor) ?>; --area-color-soft: <?= e($areaColorSoft) ?>; --area-color-border: <?= e($areaColorBorder) ?>;">
                                                <span class="areas-level-icon-mask" style="-webkit-mask-image: url('<?= e($areaRow['icon_path']) ?>'); mask-image: url('<?= e($areaRow['icon_path']) ?>');"></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="areas-level-icon" style="background: <?= e($areaColorSoft) ?>; color: <?= e($areaColor) ?>; border: 1px solid <?= e($areaColorBorder) ?>;">
                                                <?= e($area['icon'] ?: '●') ?>
                                            </span>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= e($area['name']) ?></strong>
                                            <small><?= e($area['description'] ? shortText($area['description'], 64) : 'Sin descripción.') ?></small>
                                        </div>
                                    </div>

                                    <strong class="areas-level-value">Lvl <?= (int) $areaRow['level'] ?></strong>

                                    <div class="areas-level-progress">
                                        <div class="areas-progress-track"><i style="width: <?= (int) $areaRow['level_percent'] ?>%; background: <?= e($areaColor) ?>;"></i></div>
                                        <span><?= number_format((int) $areaRow['level_xp'], 0, ',', '.') ?> / <?= number_format((int) $areaRow['level_target'], 0, ',', '.') ?> XP</span>
                                    </div>

                                    <div class="mission-item-actions areas-row-actions">
                                        <a href="areas.php?edit=<?= (int) $area['id'] ?>" class="btn btn-secondary lq-icon-btn lq-icon-edit" aria-label="Editar área"></a>
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta área?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $area['id'] ?>">
                                            <button type="submit" class="btn lq-btn-danger lq-icon-btn lq-icon-delete" aria-label="Eliminar área"></button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="areas-next-objective">
                        <span class="areas-target-icon">◎</span>
                        <div>
                            <strong>Próximo objetivo</strong>
                            <?php if ($featuredAreaRow): ?>
                                <p>Alcanza Lvl <?= (int) $featuredAreaRow['level'] + 1 ?> en <?= e($featuredAreaRow['area']['name']) ?>.</p>
                            <?php else: ?>
                                <p>Crea tu primera área para empezar a medir tu avance.</p>
                            <?php endif; ?>
                        </div>
                        <div class="areas-progress-track"><i style="width: <?= $featuredAreaRow ? (int) $featuredAreaRow['level_percent'] : 0 ?>%;"></i></div>
                        <a href="goals.php" class="btn btn-secondary">Ver plan</a>
                    </section>
                    <?php endif; ?>
                </section>

                <aside class="areas-side-column">
                    <section class="areas-profile-card">
                        <div class="areas-avatar-wrap">
                            <?php if ($heroAvatarSrc !== null): ?>
                                <img src="<?= e($heroAvatarSrc) ?>" alt="Avatar de <?= e($user['name']) ?>">
                            <?php else: ?>
                                <span><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="areas-profile-copy">
                            <strong>Nivel <?= $level ?></strong>
                            <span>Explorador</span>
                            <div class="areas-progress-track"><i style="width: <?= $xpPercent ?>%;"></i></div>
                            <small><?= number_format($xpCurrentLevel, 0, ',', '.') ?> / <?= number_format($xpPerLevel, 0, ',', '.') ?> XP</small>
                        </div>
                    </section>

                    <?php if ($activeAreasTab === 'summary'): ?>
                        <section class="areas-feature-card">
                            <div class="areas-side-title">
                                <h2>Área destacada</h2>
                                <span>★</span>
                            </div>
                            <?php if ($featuredAreaRow): ?>
                                <?php $featuredArea = $featuredAreaRow['area']; ?>
                                <?php $featuredAreaColor = (string) ($featuredArea['color'] ?: '#16C79A'); ?>
                                <?php $featuredAreaColorSoft = hexToRgba($featuredAreaColor, 0.14); ?>
                                <?php $featuredAreaColorBorder = hexToRgba($featuredAreaColor, 0.24); ?>
                                <div class="areas-feature-main">
                                    <?php if ($featuredAreaRow['icon_path']): ?>
                                        <span class="areas-level-icon areas-level-icon-shell" style="--area-color: <?= e($featuredAreaColor) ?>; --area-color-soft: <?= e($featuredAreaColorSoft) ?>; --area-color-border: <?= e($featuredAreaColorBorder) ?>;">
                                            <span class="areas-level-icon-mask" style="-webkit-mask-image: url('<?= e($featuredAreaRow['icon_path']) ?>'); mask-image: url('<?= e($featuredAreaRow['icon_path']) ?>');"></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="areas-level-icon" style="background: <?= e($featuredAreaColorSoft) ?>; color: <?= e($featuredAreaColor) ?>; border: 1px solid <?= e($featuredAreaColorBorder) ?>;"><?= e($featuredArea['icon'] ?: '●') ?></span>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($featuredArea['name']) ?></strong>
                                        <small>Tu área más fuerte</small>
                                    </div>
                                </div>
                                <p>¡Excelente trabajo cuidando de ti! Sigue manteniendo esos hábitos.</p>
                            <?php else: ?>
                                <p>Crea un área para verla destacada aquí.</p>
                            <?php endif; ?>
                        </section>
                    <?php else: ?>
                        <section class="areas-feature-card areas-current-balance-card">
                            <div class="areas-side-title">
                                <h2>Balance actual</h2>
                                <span>★</span>
                            </div>
                            <div class="areas-feature-main areas-current-balance-main">
                                <span class="areas-summary-icon area-teal">⚖</span>
                                <div>
                                    <strong><?= e($balanceStatusLabel) ?></strong>
                                    <div class="areas-current-balance-progress">
                                        <div class="areas-progress-track"><i style="width: <?= $balanceScore ?>%;"></i></div>
                                        <b><?= $balanceScore ?>%</b>
                                    </div>
                                </div>
                            </div>
                            <p><?= e($balanceStatusText) ?></p>
                        </section>
                    <?php endif; ?>

                    <section class="areas-balance-card">
                        <div class="areas-side-title">
                            <h2>Balance de áreas</h2>
                        </div>
                        <?php if ($balanceTotalTasks <= 0): ?>
                            <div class="mini-empty">
                                <strong>Sin datos todavía.</strong>
                                <p>Completa misiones con área asignada para ver el balance.</p>
                            </div>
                        <?php else: ?>
                            <div class="donut-wrap areas-donut-wrap">
                                <div class="donut" style="background: radial-gradient(circle, #fff 55%, transparent 56%), <?= buildDonutGradient($balanceDonutDistribution) ?>;"></div>
                                <div class="donut-legend">
                                    <?php foreach ($balanceAreaRows as $distributionArea): ?>
                                        <span><i style="background: <?= e($distributionArea['color'] ?? '#8b5cf6') ?>;"></i><?= e($distributionArea['name'] ?? 'Área') ?> <b><?= number_format((float) $distributionArea['percentage'], 0) ?>%</b></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="areas-tip-card">
                        <span>💡</span>
                        <div>
                            <strong>Consejo del día</strong>
                            <p>El equilibrio no es hacerlo todo perfecto, sino avanzar en lo que importa cada día.</p>
                            <small>— LifeQuest</small>
                        </div>
                    </section>
                </aside>
            </div>
        </section>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
