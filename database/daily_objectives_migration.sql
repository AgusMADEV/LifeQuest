-- Migration: Daily Objectives Tracking
-- Description: Registra cuándo un usuario completa su objetivo diario para evitar farmeo de XP
-- Date: 2026-06-02

CREATE TABLE IF NOT EXISTS daily_objectives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    objective_date DATE NOT NULL,
    tasks_completed INT NOT NULL DEFAULT 0,
    tasks_required INT NOT NULL DEFAULT 4,
    xp_bonus_awarded INT NOT NULL DEFAULT 0,
    completed_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, objective_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, objective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
