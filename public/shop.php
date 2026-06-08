<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Reward.php';
require_once __DIR__ . '/../app/Models/AppSettings.php';
require_once __DIR__ . '/../app/Support/AvatarLibrary.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$userModel = new User();
$rewardModel = new Reward();
$settingsModel = new AppSettings();

$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$shopEnabled = defined('FEATURE_INDULGENCE_SHOP') ? (bool) FEATURE_INDULGENCE_SHOP : false;
$message = null;
$messageType = null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $shopEnabled) {
    $action = (string) ($_POST['action'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        header('Location: shop.php?message=' . urlencode('No se pudo validar la acción. Inténtalo de nuevo.') . '&type=error');
        exit;
    }

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
    } elseif ($action === 'equip_cosmetic') {
        $result = $rewardModel->equipCosmetic($userId, (int) ($_POST['reward_id'] ?? 0));
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
    $rewardModel->ensureDefaultCatalog();
}

$indulgences = $shopEnabled ? $rewardModel->getShopItems($userId, 'indulgence') : [];
$cosmetics = $shopEnabled ? $rewardModel->getShopItems($userId, 'cosmetic') : [];
$inventoryCosmetics = $shopEnabled ? $rewardModel->getInventoryCosmetics($userId) : [];
$equippedCosmetics = $shopEnabled ? $rewardModel->getInventoryCosmetics($userId, true) : [];
$shopLimitedOfferName = $shopEnabled
    ? trim((string) ($settingsModel->getMany(['SHOP_LIMITED_OFFER_NAME'])['SHOP_LIMITED_OFFER_NAME'] ?? ''))
    : '';
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

function formatCoins(int $value): string
{
    return number_format($value, 0, ',', '.') . ' LC';
}

function shopCategoryKey(string|null $category, string $fallback = 'cosmetico'): string
{
    $key = strtolower(trim((string) $category));
    $key = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $key);

    return $key !== '' ? $key : $fallback;
}

function shopCategoryLabel(string|null $category, string $shopType): string
{
    if ($shopType === 'indulgence') {
        return 'Indulgencia';
    }

    $labels = [
        'outfit' => 'Outfit',
        'avatar' => 'Avatar',
        'accesorio' => 'Accesorio',
        'accessory' => 'Accesorio',
        'marco' => 'Marco',
        'fondo' => 'Fondo',
        'stickers' => 'Stickers',
        'sticker' => 'Sticker',
        'companero' => 'Compañero',
        'companion' => 'Compañero',
        'tema' => 'Tema',
        'cosmetico' => 'Cosmético',
    ];

    $key = shopCategoryKey($category);

    return $labels[$key] ?? ucfirst($key);
}

function shopVisualClass(array $item, string $shopType): string
{
    if ($shopType === 'indulgence') {
        return 'indulgence';
    }

    $key = shopCategoryKey((string) ($item['category'] ?? 'cosmetico'));
    $allowed = ['outfit', 'avatar', 'accesorio', 'accessory', 'marco', 'fondo', 'stickers', 'sticker', 'companero', 'companion', 'tema'];

    return in_array($key, $allowed, true) ? $key : 'cosmetico';
}

function findShopItemByName(array $items, string $name): ?array
{
    $name = trim($name);

    if ($name === '') {
        return null;
    }

    foreach ($items as $item) {
        if ((string) ($item['name'] ?? '') === $name) {
            return $item;
        }
    }

    return null;
}

function shopImageSrc(string|null $value): ?string
{
    $value = trim((string) $value);

    if ($value === '' || preg_match('/[\x00-\x1F]/', $value) === 1) {
        return null;
    }

    if (preg_match('/^javascript:/i', $value) === 1) {
        return null;
    }

    return $value;
}

$avatarPreviewSrc = AvatarLibrary::getAvatarSrc($user['avatar'] ?? null);
$featuredIndulgence = $indulgences[0] ?? null;
$featuredCosmetic = findShopItemByName($cosmetics, $shopLimitedOfferName) ?? ($cosmetics[0] ?? null);

$spentThisWeek = array_reduce($indulgences, static function (int $carry, array $item): int {
    return $carry + ((int) ($item['weekly_used'] ?? 0) * (int) ($item['base_cost_points'] ?? $item['cost_points'] ?? 0));
}, 0);

$availableIndulgences = array_filter($indulgences, static function (array $item) use ($points): bool {
    $remaining = max(0, (int) ($item['weekly_limit'] ?? 0) - (int) ($item['weekly_used'] ?? 0));

    return $remaining > 0 && $points >= (int) ($item['cost_points'] ?? 0);
});

$ownedCosmeticsCount = count($inventoryCosmetics);

$cosmeticTabs = [
    'destacados' => 'Destacados',
    'outfit' => 'Outfits',
    'accesorio' => 'Accesorios',
    'marco' => 'Marcos',
    'fondo' => 'Fondos',
    'stickers' => 'Stickers',
    'companero' => 'Compañeros',
];
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
        <?php require __DIR__ . '/partials/sidebar_bottom.php'; ?>
    </aside>

    <main class="lq-main shop-main">
        <?php $topbarSearchPlaceholder = 'Buscar indulgencias o cosméticos...'; ?>
        <?php $topbarShowHp = true; ?>
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <?php if ($message): ?>
            <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <div class="shop-content">
            <?php if (!$shopEnabled): ?>
                <section class="shop-empty">
                    <h2>La tienda de indulgencias está desactivada</h2>
                    <p>Activa FEATURE_INDULGENCE_SHOP en config para usar esta sección.</p>
                </section>
            <?php else: ?>
                <section class="shop-page-head">
                    <div>
                        <h1>Tienda</h1>
                        <p>Prioriza permisos conscientes; los cosméticos quedan como colección visual.</p>
                    </div>
                    <div class="shop-head-stats" aria-label="Resumen de tienda">
                        <span><strong><?= count($indulgences) ?></strong> indulgencias</span>
                        <span><strong><?= $ownedCosmeticsCount ?>/<?= count($cosmetics) ?></strong> cosméticos</span>
                    </div>
                </section>

                <section class="shop-layout">
                    <div class="shop-primary-column">
                        <nav class="shop-tabs" aria-label="Categorías de tienda">
                            <a class="active" href="#indulgencias">Indulgencias</a>
                            <?php foreach ($cosmeticTabs as $tabKey => $tabLabel): ?>
                                <a href="#cosmeticos" class="tab-<?= e($tabKey) ?>"><?= e($tabLabel) ?></a>
                            <?php endforeach; ?>
                        </nav>

                        <section class="shop-hero" aria-labelledby="shop-hero-title">
                            <div class="shop-hero-copy">
                                <span>Colección semanal</span>
                                <h2 id="shop-hero-title">Indulgencias conscientes</h2>
                                <p>Permisos de descanso con límite semanal, coste dinámico y recuperación de HP.</p>
                                <a class="shop-hero-button" href="#indulgencias">Ver indulgencias</a>
                            </div>
                            <?php /* Bloque temporalmente desactivado.
                            <div class="shop-hero-feature" aria-label="Destacado de indulgencias">
                                <div class="shop-hero-card">
                                    <span class="shop-hero-badge">Prioridad</span>
                                    <strong><?= e($featuredIndulgence ? shortText($featuredIndulgence['name'], 24) : 'Permiso semanal') ?></strong>
                                    <small><?= $featuredIndulgence ? e(formatCoins((int) $featuredIndulgence['cost_points'])) : 'Sin coste activo' ?></small>
                                </div>
                                <div class="shop-hero-discount">
                                    <strong><?= count($availableIndulgences) ?></strong>
                                    <span>listas</span>
                                </div>
                            </div>
                            */ ?>
                        </section>

                        <section class="shop-section" id="indulgencias">
                            <div class="shop-section-head">
                                <div>
                                    <h2>Indulgencias destacadas</h2>
                                    <p>Las recompensas principales de LifeQuest.</p>
                                </div>
                                <span><?= e(formatCoins($spentThisWeek)) ?> gastadas esta semana</span>
                            </div>

                            <div class="shop-grid indulgence-grid">
                                <?php if (empty($indulgences)): ?>
                                    <article class="shop-empty-card">
                                        <h2>Sin indulgencias por ahora</h2>
                                        <p>Crea o habilita indulgencias para empezar a canjearlas.</p>
                                    </article>
                                <?php endif; ?>

                                <?php foreach ($indulgences as $index => $item): ?>
                                    <?php
                                    $remaining = max(0, (int) $item['weekly_limit'] - (int) $item['weekly_used']);
                                    $canAfford = $points >= (int) $item['cost_points'];
                                    $canRedeem = $remaining > 0;
                                    $usagePercent = max(0, min(100, (int) round(((int) $item['weekly_used'] / max(1, (int) $item['weekly_limit'])) * 100)));
                                    $imageSrc = shopImageSrc($item['image_path'] ?? null);
                                    ?>
                                    <article class="shop-card indulgence-card<?= $index === 0 ? ' featured' : '' ?>">
                                        <div class="shop-card-visual indulgence<?= $imageSrc !== null ? ' has-image' : '' ?>" aria-hidden="true">
                                            <?php if ($imageSrc !== null): ?>
                                                <img class="shop-card-image" src="<?= e($imageSrc) ?>" alt="">
                                            <?php else: ?>
                                                <span><?= $index + 1 ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="shop-card-body">
                                            <div class="shop-card-head">
                                                <div>
                                                    <span class="shop-type">Indulgencia</span>
                                                    <h2><?= e(shortText($item['name'], 34)) ?></h2>
                                                </div>
                                                <strong class="shop-price"><?= e(formatCoins((int) $item['cost_points'])) ?></strong>
                                            </div>
                                            <p><?= e(shortText($item['description'], 120)) ?></p>
                                            <div class="shop-metrics">
                                                <span>+<?= (int) $item['effect_hp'] ?> HP</span>
                                                <span><?= $remaining ?> restantes</span>
                                                <span>Base <?= e(formatCoins((int) ($item['base_cost_points'] ?? $item['cost_points']))) ?></span>
                                            </div>
                                            <div class="shop-usage" aria-label="Usos semanales">
                                                <div><i style="width: <?= $usagePercent ?>%"></i></div>
                                                <small><?= (int) $item['weekly_used'] ?>/<?= (int) $item['weekly_limit'] ?> esta semana</small>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="redeem_indulgence">
                                                <input type="hidden" name="reward_id" value="<?= (int) $item['id'] ?>">
                                                <button type="submit" <?= (!$canAfford || !$canRedeem) ? 'disabled' : '' ?>>
                                                    <?= !$canRedeem ? 'Límite alcanzado' : (!$canAfford ? 'LifeCoins insuficientes' : 'Canjear') ?>
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="shop-section" id="cosmeticos">
                            <div class="shop-section-head">
                                <div>
                                    <h2>Catálogo visual</h2>
                                    <p>Outfits, accesorios, fondos, stickers y marcos para personalización.</p>
                                </div>
                                <a href="#cosmeticos">Ver todo</a>
                            </div>

                            <div class="shop-grid cosmetic-grid">
                                <?php if (empty($cosmetics)): ?>
                                    <article class="shop-empty-card">
                                        <h2>Sin cosméticos por ahora</h2>
                                        <p>Ejecuta el seed opcional para poblar la colección visual.</p>
                                    </article>
                                <?php endif; ?>

                                <?php foreach ($cosmetics as $item): ?>
                                    <?php
                                    $canAfford = $points >= (int) $item['cost_points'];
                                    $visualClass = shopVisualClass($item, 'cosmetic');
                                    $isOwned = !empty($item['owned']);
                                    $isEquipped = !empty($item['equipped']);
                                    $initialAvatarFile = AvatarLibrary::normalizeAvatar($user['initial_avatar'] ?? null);
                                    $itemAvatarFile = AvatarLibrary::normalizeAvatar(basename(str_replace('\\', '/', (string) ($item['image_path'] ?? ''))));
                                    $isInitialAvatar = strtolower((string) ($item['category'] ?? '')) === 'avatar'
                                        && $initialAvatarFile !== null
                                        && $itemAvatarFile !== null
                                        && $initialAvatarFile === $itemAvatarFile;
                                    $imageSrc = shopImageSrc($item['image_path'] ?? null);
                                    ?>
                                    <article class="shop-card cosmetic-card<?= $isOwned ? ' owned' : '' ?><?= $isEquipped ? ' equipped' : '' ?>">
                                        <div class="shop-card-visual <?= e($visualClass) ?><?= $imageSrc !== null ? ' has-image' : '' ?>" aria-hidden="true">
                                            <?php if ($imageSrc !== null): ?>
                                                <img class="shop-card-image" src="<?= e($imageSrc) ?>" alt="">
                                            <?php else: ?>
                                                <span></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="shop-card-body">
                                            <div class="shop-card-head compact">
                                                <div>
                                                    <span class="shop-type cosmetic"><?= e(shopCategoryLabel($item['category'] ?? '', 'cosmetic')) ?></span>
                                                    <h2><?= e(shortText($item['name'], 34)) ?></h2>
                                                </div>
                                                <?php if ($isInitialAvatar): ?>
                                                    <span class="shop-state equipped">Avatar inicial</span>
                                                <?php elseif ($isEquipped): ?>
                                                    <span class="shop-state equipped">Equipado</span>
                                                <?php elseif ($isOwned): ?>
                                                    <span class="shop-state owned">Inventario</span>
                                                <?php endif; ?>
                                            </div>
                                            <p><?= e(shortText($item['description'], 96)) ?></p>
                                            <div class="shop-meta-line">
                                                <strong><?= e(formatCoins((int) $item['cost_points'])) ?></strong>
                                                <span><?= $isInitialAvatar ? 'Incluido al inicio' : ($isOwned ? 'Desbloqueado' : 'Visual') ?></span>
                                            </div>
                                            <?php if (!$isInitialAvatar): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="<?= $isOwned ? 'equip_cosmetic' : 'redeem_cosmetic' ?>">
                                                    <input type="hidden" name="reward_id" value="<?= (int) $item['id'] ?>">
                                                    <button type="submit" <?= ($isEquipped || (!$isOwned && !$canAfford)) ? 'disabled' : '' ?>>
                                                        <?= $isEquipped ? 'Equipado' : ($isOwned ? 'Equipar' : (!$canAfford ? 'LifeCoins insuficientes' : 'Desbloquear')) ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="shop-current-avatar-note">Este es tu avatar inicial y no se puede comprar.</div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                    <aside class="shop-aside" aria-label="Resumen de tienda">
                        <section class="shop-panel avatar-preview">
                            <div class="shop-panel-head">
                                <h2>Vista previa de avatar</h2>
                                <a href="profile.php">Personalizar</a>
                            </div>
                            <div class="avatar-stage">
                                <div class="avatar-glow"></div>
                                <?php if ($avatarPreviewSrc !== null): ?>
                                    <img src="<?= e($avatarPreviewSrc) ?>" alt="" class="shop-avatar-image">
                                <?php else: ?>
                                    <span><?= e(mb_strtoupper(mb_substr((string) ($user['name'] ?? 'U'), 0, 1))) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="avatar-dots" aria-hidden="true"><i class="active"></i><i></i><i></i><i></i><i></i></div>
                        </section>

                        <section class="shop-panel wallet-panel">
                            <div class="shop-panel-head">
                                <h2>Tu billetera</h2>
                                <a href="progress.php">Ver historial</a>
                            </div>
                            <div class="wallet-grid">
                                <div><span>LifeCoins</span><strong><?= number_format($points, 0, ',', '.') ?></strong></div>
                                <div><span>Gemas</span><strong><?= number_format(max(0, intdiv($points, 20)), 0, ',', '.') ?></strong></div>
                            </div>
                            <div class="wallet-bonus"><strong>Bonus activo</strong><span>+10% XP en compras</span></div>
                        </section>

                        <section class="shop-panel equipped-panel">
                            <div class="shop-panel-head">
                                <h2>Equipado actualmente</h2>
                                <a href="#inventario">Inventario</a>
                            </div>
                            <div class="equipped-row">
                                <?php foreach (array_slice($equippedCosmetics, 0, 5) as $item): ?>
                                    <?php $miniImageSrc = shopImageSrc($item['image_path'] ?? null); ?>
                                    <div>
                                        <span class="mini-cosmetic <?= e(shopVisualClass($item, 'cosmetic')) ?><?= $miniImageSrc !== null ? ' has-image' : '' ?>">
                                            <?php if ($miniImageSrc !== null): ?><img src="<?= e($miniImageSrc) ?>" alt=""><?php endif; ?>
                                        </span>
                                        <small><?= e(shopCategoryLabel($item['category'] ?? '', 'cosmetic')) ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($equippedCosmetics)): ?>
                                    <p>Sin piezas equipadas todavía.</p>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="shop-panel inventory-panel" id="inventario">
                            <div class="shop-panel-head">
                                <h2>Inventario</h2>
                                <span><?= $ownedCosmeticsCount ?> piezas</span>
                            </div>
                            <div class="inventory-list">
                                <?php foreach (array_slice($inventoryCosmetics, 0, 6) as $item): ?>
                                    <?php $miniImageSrc = shopImageSrc($item['image_path'] ?? null); ?>
                                    <article>
                                        <span class="mini-cosmetic <?= e(shopVisualClass($item, 'cosmetic')) ?><?= $miniImageSrc !== null ? ' has-image' : '' ?>">
                                            <?php if ($miniImageSrc !== null): ?><img src="<?= e($miniImageSrc) ?>" alt=""><?php endif; ?>
                                        </span>
                                        <div>
                                            <strong><?= e(shortText($item['name'], 24)) ?></strong>
                                            <small><?= e(shopCategoryLabel($item['category'] ?? '', 'cosmetic')) ?></small>
                                        </div>
                                        <?php if (!empty($item['equipped'])): ?>
                                            <span class="inventory-status">Activo</span>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="equip_cosmetic">
                                                <input type="hidden" name="reward_id" value="<?= (int) $item['id'] ?>">
                                                <button type="submit">Equipar</button>
                                            </form>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                                <?php if (empty($inventoryCosmetics)): ?>
                                    <p>Desbloquea cosméticos para llenar tu inventario.</p>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="shop-panel limited-panel">
                            <div class="shop-panel-head">
                                <h2>Oferta limitada</h2>
                                <span>Semanal</span>
                            </div>
                            <div class="limited-offer">
                                <?php $featuredImageSrc = $featuredCosmetic ? shopImageSrc($featuredCosmetic['image_path'] ?? null) : null; ?>
                                <div class="shop-card-visual <?= e($featuredCosmetic ? shopVisualClass($featuredCosmetic, 'cosmetic') : 'outfit') ?><?= $featuredImageSrc !== null ? ' has-image' : '' ?>" aria-hidden="true">
                                    <?php if ($featuredImageSrc !== null): ?>
                                        <img class="shop-card-image" src="<?= e($featuredImageSrc) ?>" alt="">
                                    <?php else: ?>
                                        <span></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?= e($featuredCosmetic ? shortText($featuredCosmetic['name'], 28) : 'Kit visual inicial') ?></strong>
                                    <p><?= e($featuredCosmetic ? shortText($featuredCosmetic['description'], 70) : 'Pack cosmético para empezar la colección.') ?></p>
                                    <span><?= e($featuredCosmetic ? formatCoins((int) $featuredCosmetic['cost_points']) : 'Seed opcional') ?></span>
                                </div>
                            </div>
                        </section>
                    </aside>
                </section>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
