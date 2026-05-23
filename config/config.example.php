<?php

declare(strict_types=1);

// Configuración de ejemplo para LifeQuest
// Copia este archivo a config.php y modifica los valores según tu entorno

define('APP_NAME', 'LifeQuest');
define('APP_URL', 'http://localhost/LifeQuest/public');

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'lifequest');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');
define('DB_CHARSET', 'utf8mb4');

// Flags de funcionalidades (enfoque incremental)
define('FEATURE_HP_SYSTEM', true);
define('FEATURE_NEGATIVE_HABITS', false);
define('FEATURE_AREA_PROGRESSION', false);
define('FEATURE_INDULGENCE_SHOP', true);

// Estado base del personaje
define('PLAYER_BASE_HP', 1000);

// Balance de recompensas
define('REWARD_POINTS_PER_XP', 0.5);
define('REWARD_HABIT_BASE_XP', 10);
define('REWARD_TASK_BASE_XP', 12);
define('REWARD_GOAL_BASE_XP_DAILY', 16);
define('REWARD_GOAL_BASE_XP_WEEKLY', 30);
define('REWARD_GOAL_BASE_XP_MONTHLY', 50);
define('REWARD_GOAL_BASE_XP_QUARTERLY', 70);
define('REWARD_GOAL_BASE_XP_YEARLY', 95);
define('REWARD_GOAL_BASE_XP_FUTURE', 110);

// Balance de tienda (indulgencias)
define('INDULGENCE_REPEAT_COST_MULTIPLIER', 1.25);

// Configuración de sesiones
define('SESSION_NAME', 'lifequest_session');

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
