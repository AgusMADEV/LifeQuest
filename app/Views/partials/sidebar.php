<?php
$currentUserName = $user['name'] ?? ($_SESSION['user_name'] ?? 'Usuario');
$currentUserInitial = mb_strtoupper(mb_substr($currentUserName, 0, 1));
$currentUserLevel = (int)($user['level'] ?? 1);
$currentUserStreak = (int)($user['current_streak'] ?? 0);

if (!function_exists('lq_e')) {
    function lq_e(string|null $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lq_short')) {
    function lq_short(string|null $value, int $limit = 42): string
    {
        $value = trim((string)$value);
        return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
    }
}

$activePage = $activePage ?? '';

$navItems = [
    ['key' => 'dashboard', 'href' => 'dashboard.php', 'icon' => '🏠', 'label' => 'Inicio'],
    ['key' => 'goals', 'href' => 'goals.php', 'icon' => '🎯', 'label' => 'Metas'],
    ['key' => 'projects', 'href' => 'projects.php', 'icon' => '🧗', 'label' => 'Retos'],
    ['key' => 'tasks', 'href' => 'tasks.php', 'icon' => '⚡', 'label' => 'Misiones'],
    ['key' => 'battle', 'href' => 'battle.php', 'icon' => '⚔️', 'label' => 'Modo Batalla'],
    ['key' => 'habits', 'href' => 'habits.php', 'icon' => '💚', 'label' => 'Hábitos'],
    ['key' => 'areas', 'href' => 'areas.php', 'icon' => '🧩', 'label' => 'Áreas'],
    ['key' => 'shop', 'href' => '#', 'icon' => '🛍️', 'label' => 'Tienda'],
    ['key' => 'progress', 'href' => '#', 'icon' => '📊', 'label' => 'Progreso'],
];
?>

<aside class="lq-sidebar">
    <a href="dashboard.php" class="lq-logo">
        <span>Life<span>Quest</span><i>✦</i></span>
    </a>

    <nav class="lq-nav">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= lq_e($item['href']) ?>" class="<?= $activePage === $item['key'] ? 'active' : '' ?>">
                <span><?= lq_e($item['icon']) ?></span>
                <?= lq_e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <section class="lq-sidebar-card streak">
        <div class="streak-icon">
            <?= $activePage === 'battle' ? '⚔️' : ($activePage === 'habits' ? '💚' : '🔥') ?>
        </div>
        <p><?= $activePage === 'battle' ? 'Modo enfoque' : ($activePage === 'habits' ? 'Constancia' : 'Racha actual') ?></p>
        <strong><?= $activePage === 'battle' ? 'ON' : $currentUserStreak . ' días' ?></strong>
        <small><?= $activePage === 'battle' ? 'Ejecuta sin ruido' : '¡Sigue así!' ?></small>

        <?php if ($activePage !== 'battle'): ?>
            <div class="week-dots">
                <span class="done">L</span><span class="done">M</span><span class="done">X</span><span class="done">J</span><span class="done">V</span><span>S</span><span>D</span>
            </div>
        <?php endif; ?>
    </section>

    <section class="lq-sidebar-card unlock">
        <div>
            <strong><?= $activePage === 'battle' ? 'Sin distracciones' : ($activePage === 'habits' ? 'Pequeños pasos' : '¡Desbloquea más!') ?></strong>
            <p>
                <?php if ($activePage === 'battle'): ?>
                    Elige una misión, activa el temporizador y ejecuta.
                <?php elseif ($activePage === 'habits'): ?>
                    Completa rutinas simples para mantener tu progreso.
                <?php else: ?>
                    Completa misiones y consigue recompensas exclusivas.
                <?php endif; ?>
            </p>
            <a href="<?= $activePage === 'battle' ? 'tasks.php' : ($activePage === 'habits' ? 'goals.php' : '#') ?>" class="mini-btn">
                <?= $activePage === 'battle' ? 'Ver misiones' : ($activePage === 'habits' ? 'Ver metas' : 'Ver tienda') ?>
            </a>
        </div>
        <span class="bag"><?= $activePage === 'battle' ? '⚔️' : ($activePage === 'habits' ? '💚' : '🎒') ?></span>
    </section>

    <section class="lq-user-mini">
        <div class="mini-avatar"><?= lq_e($currentUserInitial) ?></div>
        <div>
            <strong><?= lq_e(lq_short($currentUserName, 18)) ?></strong>
            <small>Nivel <?= $currentUserLevel ?></small>
        </div>
        <span>⌄</span>
    </section>

    <div class="lq-sidebar-bottom">
        <a href="#">⚙️</a>
        <a href="#">?</a>
        <a href="logout.php">↪</a>
    </div>
</aside>
