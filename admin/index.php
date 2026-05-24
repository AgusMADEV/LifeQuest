<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session_guard.php';

if (!empty($_SESSION['admin_portal_user_id'])) {
    if (isAdminPortalSessionExpired()) {
        clearAdminPortalSession();
        header('Location: login.php?message=' . urlencode('Sesion expirada por inactividad.') . '&type=error');
        exit;
    }

    $_SESSION['admin_portal_logged_at'] = time();
    header('Location: database.php?section=db');
    exit;
}

header('Location: login.php');
exit;
