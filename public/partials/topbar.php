<?php
$topbarEscape = static fn(string $value): string => function_exists('e')
    ? e($value)
    : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$topbarUserName = (string) ($user['name'] ?? 'Usuario');
$topbarUserInitial = mb_strtoupper(mb_substr($topbarUserName !== '' ? $topbarUserName : 'U', 0, 1));
$topbarDisplayName = function_exists('shortText')
    ? shortText($topbarUserName, 12)
    : mb_substr($topbarUserName, 0, 12);

$topbarSearchPlaceholder = isset($topbarSearchPlaceholder)
    ? (string) $topbarSearchPlaceholder
    : 'Buscar en LifeQuest...';

$topbarPoints = isset($points)
    ? (int) $points
    : (int) ($user['points'] ?? 0);

$topbarXpCurrent = isset($xpCurrent)
    ? (int) $xpCurrent
    : (int) ($user['xp'] ?? 0);

$topbarLevel = isset($level)
    ? max(1, (int) $level)
    : max(1, (int) ($user['level'] ?? 1));

$xpPerLevel = 1000;
$xpCurrentLevel = $topbarXpCurrent % $xpPerLevel;
$topbarXpPercent = isset($xpPercent)
    ? max(0, min(100, (int) $xpPercent))
    : max(0, min(100, (int) round(($xpCurrentLevel / max(1, $xpPerLevel)) * 100)));

$topbarGems = isset($gems)
    ? max(0, (int) $gems)
    : max(0, intdiv($topbarPoints, 20));

$topbarShowHp = isset($topbarShowHp)
    ? (bool) $topbarShowHp
    : (
        (defined('FEATURE_HP_SYSTEM') ? (bool) FEATURE_HP_SYSTEM : false)
        && (
            (isset($maxHp) && (int) $maxHp > 0)
            || (isset($user['max_hp']) && (int) ($user['max_hp'] ?? 0) > 0)
        )
    );

$topbarHp = isset($hp)
    ? max(0, (int) $hp)
    : max(0, (int) ($user['hp'] ?? 0));

$topbarMaxHp = isset($maxHp)
    ? max(0, (int) $maxHp)
    : max(0, (int) ($user['max_hp'] ?? 0));

$topbarHeartSlots = 10;
$topbarFilledHearts = $topbarShowHp && $topbarMaxHp > 0
    ? (int) floor(($topbarHp / $topbarMaxHp) * $topbarHeartSlots)
    : 0;
$topbarFilledHearts = max(0, min($topbarHeartSlots, $topbarFilledHearts));
if ($topbarHp > 0 && $topbarFilledHearts === 0) {
    $topbarFilledHearts = 1;
}
?>
<header class="lq-topbar">
    <div class="topbar-left">
        <button class="icon-btn" type="button" aria-label="Abrir navegación">
            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H16M4 12H20M4 18H12" stroke="#101935" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <div class="search-box">
            <span>
                <svg id="Search" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11.2481" cy="10.7887" r="8.03854" stroke="#7b86a3" stroke-width="1.5" stroke-linecap="square"></circle>
                <path d="M16.7369 16.7083L21.2904 21.2499" stroke="#7b86a3" stroke-width="1.5" stroke-linecap="square"></path>
                </svg>
            </span>
            <input type="search" placeholder="<?= $topbarEscape($topbarSearchPlaceholder) ?>" disabled>
            <kbd>⌘ K</kbd>
        </div>
    </div>
    <div class="top-stats">
        <div class="xp-pill">
                        <span>
                                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="xpHexOuter" x1="4" y1="4" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#3cffb0"/>
                                            <stop offset="1" stop-color="#0bb86c"/>
                                        </linearGradient>
                                        <linearGradient id="xpHexInner" x1="8" y1="8" x2="24" y2="24" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#2be98a"/>
                                            <stop offset="1" stop-color="#0e9e4a"/>
                                        </linearGradient>
                                        <radialGradient id="xpGlow" cx="16" cy="16" r="16" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#baffc9" stop-opacity=".7"/>
                                            <stop offset="1" stop-color="#00ffb0" stop-opacity="0"/>
                                        </radialGradient>
                                    </defs>
                                    <polygon points="16,3 29,11 29,25 16,31 3,25 3,11" fill="url(#xpHexOuter)" stroke="#0bb86c" stroke-width="1.5"/>
                                    <polygon points="16,6.5 26,13 26,23 16,28 6,23 6,13" fill="url(#xpHexInner)"/>
                                    <circle cx="16" cy="16" r="10" fill="url(#xpGlow)"/>
                                    <g>
                                        <path d="M16 10V21" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M16 10L12.5 14M16 10L19.5 14" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                                    </g>
                                    <g opacity=".7">
                                        <circle cx="11" cy="13" r="1.1" fill="#fff"/>
                                        <circle cx="21" cy="12" r="0.7" fill="#fff"/>
                                        <circle cx="19" cy="19" r="0.5" fill="#fff"/>
                                    </g>
                                </svg>
                        </span>
            <div class="xp-content">
                <div class="xp-header">
                    <strong><?= number_format($topbarXpCurrent, 0, ',', '.') ?> XP</strong>
                    <small>Nivel <?= $topbarLevel ?></small>
                </div>
                <div class="mini-progress"><i style="width: <?= $topbarXpPercent ?>%"></i></div>
            </div>
        </div>

        <div class="currency-group">
            <div class="currency-pill coin">
                <span>
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="coinOuter<?= $topbarPoints ?>" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFE27A"/>
                                <stop offset="0.45" stop-color="#FFC93A"/>
                                <stop offset="1" stop-color="#F59F00"/>
                            </linearGradient>
                            <linearGradient id="coinInner<?= $topbarPoints ?>" x1="7" y1="6" x2="17" y2="18" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFD85C"/>
                                <stop offset="1" stop-color="#F08C00"/>
                            </linearGradient>
                            <filter id="coinShadow<?= $topbarPoints ?>" x="0" y="0" width="24" height="24" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feDropShadow dx="0" dy="1" stdDeviation="0.8" flood-color="#C76B00" flood-opacity="0.35"/>
                            </filter>
                        </defs>
                        <g filter="url(#coinShadow<?= $topbarPoints ?>)">
                            <circle cx="12" cy="12" r="10" fill="url(#coinOuter<?= $topbarPoints ?>)"/>
                            <circle cx="12" cy="12" r="8.1" fill="url(#coinInner<?= $topbarPoints ?>)" stroke="#FFB11A" stroke-width="0.9"/>
                            <path d="M5.8 7.5C7.1 5.4 9.34 4 11.9 4" stroke="#FFF4BF" stroke-width="1.4" stroke-linecap="round" opacity="0.9"/>
                            <path d="M12.1 7.1C10.8 7.1 9.9 7.75 9.9 8.72C9.9 9.73 10.87 10.2 12.22 10.58C13.64 10.98 14.4 11.47 14.4 12.58C14.4 13.73 13.42 14.55 12 14.69V15.6C12 15.93 11.73 16.2 11.4 16.2C11.07 16.2 10.8 15.93 10.8 15.6V14.63C9.89 14.48 9.04 13.99 8.49 13.25C8.29 12.98 8.34 12.61 8.61 12.42C8.87 12.22 9.25 12.27 9.44 12.54C9.91 13.18 10.69 13.56 11.47 13.56H12C13 13.56 13.2 12.98 13.2 12.62C13.2 12.05 12.87 11.72 11.9 11.44C10.43 11.02 8.7 10.4 8.7 8.77C8.7 7.43 9.69 6.47 10.8 6.22V5.4C10.8 5.07 11.07 4.8 11.4 4.8C11.73 4.8 12 5.07 12 5.4V6.15C12.78 6.21 13.47 6.48 14.07 6.95C14.33 7.15 14.37 7.53 14.17 7.79C13.97 8.05 13.59 8.09 13.33 7.89C12.96 7.6 12.52 7.43 12.1 7.1Z" fill="#FFF9EA"/>
                        </g>
                    </svg>
                </span>
                <strong><?= number_format($topbarPoints, 0, ',', '.') ?></strong>
            </div>
            <div class="currency-pill gem">
                <span>
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="gemTopLeft<?= $topbarGems ?>" x1="4.5" y1="5" x2="11" y2="13" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#D8B8FF"/>
                                <stop offset="1" stop-color="#A45CFF"/>
                            </linearGradient>
                            <linearGradient id="gemTopCenter<?= $topbarGems ?>" x1="12" y1="4" x2="12" y2="13" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C99CFF"/>
                                <stop offset="1" stop-color="#A66BFF"/>
                            </linearGradient>
                            <linearGradient id="gemTopRight<?= $topbarGems ?>" x1="18.5" y1="5" x2="13" y2="13" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#9B4DFF"/>
                                <stop offset="1" stop-color="#7B2CF3"/>
                            </linearGradient>
                            <linearGradient id="gemBottomLeft<?= $topbarGems ?>" x1="4.5" y1="12" x2="12" y2="22" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#8B3FFF"/>
                                <stop offset="1" stop-color="#6622D7"/>
                            </linearGradient>
                            <linearGradient id="gemBottomCenter<?= $topbarGems ?>" x1="12" y1="12" x2="12" y2="23" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#BC84FF"/>
                                <stop offset="1" stop-color="#7A35EA"/>
                            </linearGradient>
                            <linearGradient id="gemBottomRight<?= $topbarGems ?>" x1="19.5" y1="12" x2="12" y2="22" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#6C1DE5"/>
                                <stop offset="1" stop-color="#4F0FC0"/>
                            </linearGradient>
                            <filter id="gemShadow<?= $topbarGems ?>" x="1" y="2" width="22" height="21" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feDropShadow dx="0" dy="1" stdDeviation="0.8" flood-color="#4E17A8" flood-opacity="0.28"/>
                            </filter>
                        </defs>
                        <g filter="url(#gemShadow<?= $topbarGems ?>)">
                            <path d="M6.2 5H17.8L21 9.1L12 20L3 9.1L6.2 5Z" fill="#9D5CFF"/>
                            <path d="M6.2 5L3 9.1H8.1L12 5H6.2Z" fill="url(#gemTopLeft<?= $topbarGems ?>)"/>
                            <path d="M12 5L8.1 9.1H15.9L12 5Z" fill="url(#gemTopCenter<?= $topbarGems ?>)"/>
                            <path d="M17.8 5L12 5L15.9 9.1H21L17.8 5Z" fill="url(#gemTopRight<?= $topbarGems ?>)"/>
                            <path d="M3 9.1H8.1L12 20L3 9.1Z" fill="url(#gemBottomLeft<?= $topbarGems ?>)"/>
                            <path d="M8.1 9.1H15.9L12 20L8.1 9.1Z" fill="url(#gemBottomCenter<?= $topbarGems ?>)"/>
                            <path d="M15.9 9.1H21L12 20L15.9 9.1Z" fill="url(#gemBottomRight<?= $topbarGems ?>)"/>
                            <path d="M6.2 5H17.8" stroke="#C794FF" stroke-width="0.7" stroke-linecap="round" opacity="0.9"/>
                            <path d="M8.1 9.1H15.9" stroke="#C48AFF" stroke-width="0.7" stroke-linecap="round" opacity="0.9"/>
                            <path d="M6.7 5.8L8.1 9.1L12 5.2" stroke="#F6ECFF" stroke-width="0.9" stroke-linecap="round" stroke-linejoin="round" opacity="0.9"/>
                        </g>
                    </svg>
                </span>
                <strong><?= $topbarGems ?></strong>
            </div>
            <button class="currency-add" type="button" aria-label="Añadir monedas">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="var(--text)" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <button class="notify-pill" type="button" aria-label="Notificaciones">
            <svg id="Notification 2" width="24" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.4997 4.08637V2.63843H12.9997V4.08637C16.5455 4.46086 19.3083 7.4606 19.3083 11.1056V13.8988C19.3083 15.633 19.8355 17.3263 20.8199 18.754L21.6306 19.9298H2.86875L3.67942 18.754C4.66385 17.3263 5.19102 15.633 5.19102 13.8988V11.1057C5.19102 7.4606 7.95389 4.46086 11.4997 4.08637ZM12.2497 5.547C9.17971 5.547 6.69102 8.03569 6.69102 11.1057V13.8988C6.69102 15.4791 6.31861 17.0304 5.61209 18.4298H18.8872C18.1807 17.0304 17.8083 15.4791 17.8083 13.8988V11.1056C17.8083 8.03569 15.3196 5.547 12.2497 5.547Z" fill="#101935"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.27168 19.4102V19.1797H9.77168V19.4102C9.77168 20.779 10.8813 21.8887 12.2502 21.8887C13.6191 21.8887 14.7287 20.779 14.7287 19.4102V19.1797H16.2287V19.4102C16.2287 21.6074 14.4475 23.3887 12.2502 23.3887C10.0529 23.3887 8.27168 21.6074 8.27168 19.4102Z" fill="#101935"></path>
            </svg>
        </button>

        <div class="profile-pill">
            <div class="mini-avatar image-like"><?= $topbarUserInitial ?></div>
            <div class="profile-copy">
                <div class="profile-greeting">
                    <strong>¡Hola, <?= $topbarEscape($topbarDisplayName) ?>!</strong>
                    <span class="profile-wave" aria-hidden="true">👋</span>
                </div>
                <?php if ($topbarShowHp): ?>
                    <div class="profile-hp">
                        <div class="profile-hp-hearts" aria-label="Vida actual">
                            <span class="profile-hp-label">Vida:</span>
                            <span class="profile-hp-icons" aria-hidden="true">
                                <?php for ($heartIndex = 0; $heartIndex < $topbarHeartSlots; $heartIndex++): ?>
                                    <span class="profile-heart<?= $heartIndex < $topbarFilledHearts ? ' is-filled' : '' ?>">♥</span>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <span class="profile-hp-value"><?= number_format($topbarHp, 0, ',', '.') ?>/<?= number_format($topbarMaxHp, 0, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <span class="profile-chevron" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 10L12 14L16 10" stroke="#43506d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
    </div>
</header>
