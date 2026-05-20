<?php

declare(strict_types=1);

// Configuración de ejemplo para QuestBoard
// Copia este archivo a config.php y modifica los valores según tu entorno

define('APP_NAME', 'QuestBoard');
define('APP_URL', 'http://localhost/questboard/public');

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'questboard');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');
define('DB_CHARSET', 'utf8mb4');

// Configuración de sesiones
define('SESSION_NAME', 'questboard_session');

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
