<?php
$sidebarUserName = (string) ($sidebarUserName ?? ($user['name'] ?? 'Usuario'));
$sidebarUserSubtitle = (string) ($sidebarUserSubtitle ?? 'Ver perfil');
$sidebarUserInitial = mb_strtoupper(mb_substr($sidebarUserName !== '' ? $sidebarUserName : 'U', 0, 1));
$sidebarUserDisplayName = function_exists('shortText') ? shortText($sidebarUserName, 18) : $sidebarUserName;
$sidebarUserEscape = static fn(string $value): string => function_exists('e')
    ? e($value)
    : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$sidebarUserAvatarSrc = AvatarLibrary::getAvatarSrc($user['avatar'] ?? null);
?>
<section class="lq-user-mini">
    <div class="mini-avatar">
        <?php if ($sidebarUserAvatarSrc !== null): ?>
            <img src="<?= $sidebarUserEscape($sidebarUserAvatarSrc) ?>" alt="" class="mini-avatar-image">
        <?php else: ?>
            <?= $sidebarUserInitial ?>
        <?php endif; ?>
    </div>
    <div>
        <strong><?= $sidebarUserEscape($sidebarUserDisplayName) ?></strong>
        <small><?= $sidebarUserEscape($sidebarUserSubtitle) ?></small>
    </div>
    <span>⌄</span>
</section>
