-- PR-1: HP base del personaje
-- Ejecutar en entorno de prueba y luego en entorno principal.

ALTER TABLE users
    ADD COLUMN hp INT NOT NULL DEFAULT 1000 AFTER points,
    ADD COLUMN max_hp INT NOT NULL DEFAULT 1000 AFTER hp;

-- Normaliza usuarios ya existentes
UPDATE users
SET hp = CASE WHEN hp IS NULL OR hp < 0 THEN 1000 ELSE hp END,
    max_hp = CASE WHEN max_hp IS NULL OR max_hp < 1 THEN 1000 ELSE max_hp END;
