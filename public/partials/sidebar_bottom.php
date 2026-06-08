<?php
$sessionBadgeToasts = $_SESSION['badge_unlock_toasts'] ?? [];
unset($_SESSION['badge_unlock_toasts']);

if (!is_array($sessionBadgeToasts)) {
    $sessionBadgeToasts = [];
}

$queryBadgeToasts = [];
$queryPayload = $_GET['badge_toasts'] ?? null;
if (is_string($queryPayload) && $queryPayload !== '') {
    $decodedJson = base64_decode($queryPayload, true);
    if (is_string($decodedJson) && $decodedJson !== '') {
        $decoded = json_decode($decodedJson, true);
        if (is_array($decoded)) {
            $queryBadgeToasts = $decoded;
        }
    }
}

$badgeToasts = [];
$seen = [];
foreach (array_merge($sessionBadgeToasts, $queryBadgeToasts) as $badge) {
    if (!is_array($badge)) {
        continue;
    }

    $code = trim((string) ($badge['code'] ?? ''));
    $title = trim((string) ($badge['title'] ?? 'Insignia'));
    $icon = trim((string) ($badge['icon'] ?? '🏅'));
    $dedupeKey = $code !== '' ? $code : $title;

    if ($dedupeKey === '' || isset($seen[$dedupeKey])) {
        continue;
    }

    $badgeToasts[] = [
        'code' => $code,
        'title' => $title,
        'icon' => $icon,
    ];
    $seen[$dedupeKey] = true;
}
?>

<div class="lq-sidebar-bottom">
    <a href="profile.php" aria-label="Abrir configuracion del perfil" title="Configuracion">⚙️</a>
    <a href="mailto:soporte@lifequest.app?subject=Ayuda%20LifeQuest" aria-label="Contactar con soporte" title="Ayuda">?</a>
    <a href="logout.php" aria-label="Cerrar sesion" title="Cerrar sesion">↪</a>
</div>

<?php if (!empty($badgeToasts)): ?>
    <div class="badge-toast" data-badge-toast role="status" aria-live="polite">
        <div class="badge-toast-icon" aria-hidden="true"><?= htmlspecialchars((string) ($badgeToasts[0]['icon'] ?? '🏅'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="badge-toast-body">
            <?php if (count($badgeToasts) === 1): ?>
                <strong>Nueva insignia desbloqueada</strong>
                <p><?= htmlspecialchars((string) ($badgeToasts[0]['title'] ?? 'Insignia'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <strong><?= count($badgeToasts) ?> nuevas insignias desbloqueadas</strong>
                <p>
                    <?php foreach ($badgeToasts as $index => $badge): ?>
                        <?= $index > 0 ? ', ' : '' ?><?= htmlspecialchars((string) ($badge['title'] ?? 'Insignia'), ENT_QUOTES, 'UTF-8') ?>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
        <button type="button" class="badge-toast-close" data-close-badge-toast aria-label="Cerrar notificacion">×</button>
    </div>

    <script>
        (function () {
            var toast = document.querySelector('[data-badge-toast]');
            if (!toast) {
                return;
            }

            if (document.body && toast.parentElement !== document.body) {
                document.body.appendChild(toast);
            }

            var closeButton = toast.querySelector('[data-close-badge-toast]');

            var closeToast = function () {
                toast.classList.add('is-hiding');
                window.setTimeout(function () {
                    if (toast && toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 220);
            };

            if (closeButton) {
                closeButton.addEventListener('click', closeToast);
            }

            window.setTimeout(closeToast, 5200);
        })();
    </script>
<?php endif; ?>
