-- PR-2: Preferencias dinamicas del perfil
-- Ejecutar en entorno de prueba y luego en entorno principal.

ALTER TABLE users
    ADD COLUMN motivational_line VARCHAR(160) DEFAULT NULL AFTER avatar,
    ADD COLUMN profile_bio VARCHAR(255) DEFAULT NULL AFTER motivational_line,
    ADD COLUMN profile_theme VARCHAR(20) NOT NULL DEFAULT 'light' AFTER motivational_line,
    ADD COLUMN profile_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER profile_theme;

UPDATE users
SET profile_theme = CASE
        WHEN profile_theme NOT IN ('light', 'forest', 'sunset') THEN 'light'
        ELSE profile_theme
    END,
    profile_notifications_enabled = CASE
        WHEN profile_notifications_enabled IS NULL THEN 1
        ELSE profile_notifications_enabled
    END;