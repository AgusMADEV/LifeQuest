<?php
/**
 * Topbar común de LifeQuest.
 *
 * Uso recomendado:
 * $searchPlaceholder = 'Buscar...';
 * require __DIR__ . '/../app/Views/partials/topbar.php';
 */

$currentUserName = $user['name'] ?? ($_SESSION['user_name'] ?? 'Usuario');
$currentUserInitial = mb_strtoupper(mb_substr($currentUserName, 0, 1));
$currentUserLevel = (int)($user['level'] ?? 1);
$currentUserXp = (int)($user['xp'] ?? 0);
$currentUserPoints = (int)($user['points'] ?? 0);
$currentUserGems = max(0, intdiv($currentUserPoints, 20));
$searchPlaceholder = $searchPlaceholder ?? 'Buscar metas, retos, misiones o recompensas...';

if (!function_exists('lq_topbar_e')) {
    function lq_topbar_e(string|null $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lq_topbar_short')) {
    function lq_topbar_short(string|null $value, int $limit = 42): string
    {
        $value = trim((string)$value);

        return mb_strlen($value) <= $limit
            ? $value
            : mb_substr($value, 0, $limit - 1) . '…';
    }
}
?>

<header class="lq-topbar">
    <button class="icon-btn">☰</button>

    <div class="search-box">
        <span>🔎</span>
        <input type="search" placeholder="<?= lq_topbar_e($searchPlaceholder) ?>" disabled>
        <kbd>⌘ K</kbd>
    </div>

    <div class="top-stats">
        <div class="xp-pill">
            <span>✦</span>
            <strong><?= number_format($currentUserXp, 0, ',', '.') ?> XP</strong>
            <div class="mini-progress"><i style="width: 35%"></i></div>
            <small>Nivel <?= $currentUserLevel ?></small>
        </div>

        <div class="currency-pill coin">
            <span>🪙</span>
            <strong><?= number_format($currentUserPoints, 0, ',', '.') ?></strong>
        </div>

        <div class="currency-pill gem">
            <span>💎</span>
            <strong><?= $currentUserGems ?></strong>
        </div>

        <button class="icon-btn">🔔</button>

        <div class="profile-pill">
            <div class="mini-avatar image-like"><?= lq_topbar_e($currentUserInitial) ?></div>
            <strong>¡Hola, <?= lq_topbar_e(lq_topbar_short($currentUserName, 12)) ?>! 👋</strong>
        </div>
    </div>
</header>
