-- =============================================
-- MIGRACIÓN: Sistema de Misiones
-- =============================================

-- Tabla principal de misiones
CREATE TABLE IF NOT EXISTS missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    icon VARCHAR(10) DEFAULT '🎯',
    target_value INT NOT NULL DEFAULT 1,
    target_unit VARCHAR(50) DEFAULT 'veces',
    current_progress INT DEFAULT 0,
    completed TINYINT(1) DEFAULT 0,
    xp_reward INT DEFAULT 50,
    coins_reward INT DEFAULT 10,
    gems_reward INT DEFAULT 0,
    reset_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, type),
    INDEX idx_completed (completed),
    INDEX idx_reset_date (reset_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de misiones completadas
CREATE TABLE IF NOT EXISTS mission_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_id INT NOT NULL,
    user_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de rachas de misiones
CREATE TABLE IF NOT EXISTS mission_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    streak_date DATE NOT NULL,
    missions_completed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, streak_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campos de gemas y racha al usuario si no existen
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS gems INT DEFAULT 0 AFTER points,
ADD COLUMN IF NOT EXISTS current_streak INT DEFAULT 0 AFTER gems,
ADD COLUMN IF NOT EXISTS last_activity_date DATE NULL AFTER current_streak;

-- Insertar misiones de ejemplo para el usuario (solo si existe usuario con id 1)
INSERT INTO missions (user_id, title, description, type, icon, target_value, target_unit, xp_reward, coins_reward)
SELECT 1, 'Ejercicio matutino', 'Hacer 30 minutos de ejercicio', 'daily', '🏃', 1, 'sesión', 50, 10
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = 1)
AND NOT EXISTS (SELECT 1 FROM missions WHERE user_id = 1 AND title = 'Ejercicio matutino');

INSERT INTO missions (user_id, title, description, type, icon, target_value, target_unit, xp_reward, coins_reward)
SELECT 1, 'Leer un libro', 'Leer al menos 30 páginas', 'daily', '📚', 30, 'páginas', 40, 8
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = 1)
AND NOT EXISTS (SELECT 1 FROM missions WHERE user_id = 1 AND title = 'Leer un libro');

INSERT INTO missions (user_id, title, description, type, icon, target_value, target_unit, xp_reward, coins_reward)
SELECT 1, 'Meditar', 'Practicar meditación mindfulness', 'daily', '🧘', 10, 'minutos', 30, 5
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = 1)
AND NOT EXISTS (SELECT 1 FROM missions WHERE user_id = 1 AND title = 'Meditar');

INSERT INTO missions (user_id, title, description, type, icon, target_value, target_unit, xp_reward, coins_reward, gems_reward)
SELECT 1, 'Proyecto semanal', 'Trabajar en proyectos personales', 'weekly', '💻', 5, 'horas', 200, 50, 5
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = 1)
AND NOT EXISTS (SELECT 1 FROM missions WHERE user_id = 1 AND title = 'Proyecto semanal');

-- Verificación
SELECT 'Migración de misiones completada exitosamente' as status;
SELECT COUNT(*) as total_missions FROM missions;
