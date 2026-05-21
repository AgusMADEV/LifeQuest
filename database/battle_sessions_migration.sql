CREATE TABLE IF NOT EXISTS battle_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NULL,
    title VARCHAR(150) NOT NULL,
    duration_minutes INT NOT NULL,
    result ENUM('completed', 'partial', 'failed') DEFAULT 'partial',
    notes TEXT,
    xp_earned INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    started_at DATETIME,
    ended_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);
