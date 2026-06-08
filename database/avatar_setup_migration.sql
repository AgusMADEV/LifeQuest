-- PR-4: Seleccion inicial de avatar
-- Ejecutar en entorno de prueba y luego en entorno principal.

ALTER TABLE users
    ADD COLUMN avatar_setup_completed TINYINT(1) NOT NULL DEFAULT 1 AFTER avatar;

UPDATE users
SET avatar_setup_completed = 1
WHERE avatar_setup_completed IS NULL;
