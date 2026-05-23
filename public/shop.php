<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Reward.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$userModel = new User();
$rewardModel = new Reward();

$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$shopEnabled = defined('FEATURE_INDULGENCE_SHOP') ? (bool) FEATURE_INDULGENCE_SHOP : false;
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $shopEnabled) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'redeem_indulgence') {
        $result = $rewardModel->redeemIndulgence($userId, (int) ($_POST['reward_id'] ?? 0));
        $message = (string) ($result['message'] ?? 'No se pudo procesar la acción.');
        $messageType = !empty($result['success']) ? 'success' : 'error';

        if (!empty($result['success'])) {
            header('Location: shop.php?message=' . urlencode($message) . '&type=' . $messageType);
            exit;
        }
    } elseif ($action === 'redeem_cosmetic') {
        $result = $rewardModel->redeemCosmetic($userId, (int) ($_POST['reward_id'] ?? 0));
        $message = (string) ($result['message'] ?? 'No se pudo procesar la acción.');
        $messageType = !empty($result['success']) ? 'success' : 'error';

        if (!empty($result['success'])) {
            header('Location: shop.php?message=' . urlencode($message) . '&type=' . $messageType);
            exit;
        }
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = (string) $_GET['message'];
    $messageType = (string) $_GET['type'];
}

if ($shopEnabled) {
    $rewardModel->ensureDefaultCatalog($userId);
}

$indulgences = $shopEnabled ? $rewardModel->getShopItems($userId, 'indulgence') : [];
$cosmetics = $shopEnabled ? $rewardModel->getShopItems($userId, 'cosmetic') : [];
$user = $userModel->findById($userId) ?: $user;

$points = (int) ($user['points'] ?? 0);
$baseHp = defined('PLAYER_BASE_HP') ? (int) PLAYER_BASE_HP : 1000;
$maxHp = max(1, (int) ($user['max_hp'] ?? $baseHp));
$hp = max(0, min($maxHp, (int) ($user['hp'] ?? $maxHp)));

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);

    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '...';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/shop.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <?php $activeNav = 'shop'; ?>
        <?php require __DIR__ . '/partials/sidebar_nav.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_user_mini.php'; ?>
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main shop-main">
        <header class="lq-topbar shop-topbar">
            <div>
                <h1>Tienda</h1>
                <p>Indulgencias y cosméticos en un solo lugar, con economía balanceada.</p>
            </div>
            <div class="shop-top-stats">
                <div class="currency-pill coin"><span>🪙</span><strong><?= number_format($points, 0, ',', '.') ?></strong></div>
                <div class="currency-pill hp"><span>❤️</span><strong><?= number_format($hp, 0, ',', '.') ?>/<?= number_format($maxHp, 0, ',', '.') ?></strong></div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if (!$shopEnabled): ?>
            <section class="shop-empty">
                <h2>La tienda de indulgencias está desactivada</h2>
                <p>Activa FEATURE_INDULGENCE_SHOP en config para usar esta sección.</p>
            </section>
        <?php else: ?>
            <section class="shop-section-head">
                <h2>Indulgencias</h2>
                <p>Permisos controlados con costo dinámico por uso semanal.</p>
            </section>

            <section class="shop-grid">
                <?php if (empty($indulgences)): ?>
                    <article class="shop-empty-card">
                        <h2>Sin indulgencias por ahora</h2>
                        <p>Crea o habilita indulgencias para empezar a canjearlas.</p>
                    </article>
                <?php endif; ?>

                <?php foreach ($indulgences as $item): ?>
                    <?php
                    $remaining = max(0, (int) $item['weekly_limit'] - (int) $item['weekly_used']);
                    $canAfford = $points >= (int) $item['cost_points'];
                    $canRedeem = $remaining > 0;
                    ?>
                    <article class="shop-card">
                        <div class="shop-card-head">
                            <h2><?= e(shortText($item['name'], 34)) ?></h2>
                            <span class="shop-type">Indulgencia</span>
                        </div>
                        <p><?= e(shortText($item['description'], 120)) ?></p>
                        <div class="shop-meta">
                            <span>🪙 <?= number_format((int) $item['cost_points'], 0, ',', '.') ?></span>
                            <span>❤️ +<?= (int) $item['effect_hp'] ?> HP</span>
                        </div>
                        <div class="shop-meta subtle">
                            <span>Base: <?= number_format((int) ($item['base_cost_points'] ?? $item['cost_points']), 0, ',', '.') ?> LC</span>
                            <span>Usos esta semana: <?= (int) $item['weekly_used'] ?>/<?= (int) $item['weekly_limit'] ?></span>
                            <span>Quedan: <?= $remaining ?></span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="redeem_indulgence">
                            <input type="hidden" name="reward_id" value="<?= (int) $item['id'] ?>">
                            <button type="submit" <?= (!$canAfford || !$canRedeem) ? 'disabled' : '' ?>>
                                <?= !$canRedeem ? 'Límite semanal alcanzado' : (!$canAfford ? 'Sin LifeCoins suficientes' : 'Canjear') ?>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="shop-section-head">
                <h2>Cosméticos</h2>
                <p>Personalización visual. No altera HP ni rendimiento.</p>
            </section>

            <section class="shop-grid">
                <?php if (empty($cosmetics)): ?>
                    <article class="shop-empty-card">
                        <h2>Sin cosméticos por ahora</h2>
                        <p>Pronto llegarán más estilos y colecciones.</p>
                    </article>
                <?php endif; ?>

                <?php foreach ($cosmetics as $item): ?>
                    <?php $canAfford = $points >= (int) $item['cost_points']; ?>
                    <article class="shop-card cosmetic">
                        <div class="shop-card-head">
                            <h2><?= e(shortText($item['name'], 34)) ?></h2>
                            <span class="shop-type cosmetic">Cosmético</span>
                        </div>
                        <p><?= e(shortText($item['description'], 120)) ?></p>
                        <div class="shop-meta">
                            <span>🪙 <?= number_format((int) $item['cost_points'], 0, ',', '.') ?></span>
                            <span>🎨 Visual</span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="redeem_cosmetic">
                            <input type="hidden" name="reward_id" value="<?= (int) $item['id'] ?>">
                            <button type="submit" <?= !$canAfford ? 'disabled' : '' ?>>
                                <?= !$canAfford ? 'Sin LifeCoins suficientes' : 'Canjear cosmético' ?>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
