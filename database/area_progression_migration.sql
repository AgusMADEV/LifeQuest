-- PR-3: Progresion por area

CREATE TABLE area_progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    area_id INT NOT NULL,
    level INT NOT NULL DEFAULT 1,
    xp INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_area (user_id, area_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES life_areas(id) ON DELETE CASCADE
);

-- Seed inicial opcional desde habitos activos para tener visibilidad inmediata
INSERT INTO area_progression (user_id, area_id, level, xp)
SELECT h.user_id,
       h.area_id,
       GREATEST(1, FLOOR(SUM(CASE WHEN h.current_streak > 0 THEN h.xp_reward ELSE 0 END) / 1000) + 1) AS level,
       GREATEST(0, SUM(CASE WHEN h.current_streak > 0 THEN h.xp_reward ELSE 0 END)) AS xp
FROM habits h
WHERE h.area_id IS NOT NULL
GROUP BY h.user_id, h.area_id
ON DUPLICATE KEY UPDATE
    xp = VALUES(xp),
    level = VALUES(level);
