<?php

declare(strict_types=1);

function clearAdminPortalSession(): void
{
    unset($_SESSION['admin_portal_user_id'], $_SESSION['admin_portal_username'], $_SESSION['admin_portal_logged_at']);
}

function isAdminPortalSessionExpired(): bool
{
    $timeoutSeconds = defined('ADMIN_PORTAL_SESSION_TIMEOUT_SECONDS') ? (int) ADMIN_PORTAL_SESSION_TIMEOUT_SECONDS : 900;
    $loggedAt = (int) ($_SESSION['admin_portal_logged_at'] ?? 0);

    return $loggedAt <= 0 || (time() - $loggedAt) > max($timeoutSeconds, 60);
}
