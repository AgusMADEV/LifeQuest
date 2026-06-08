-- Rollback PR-4: Seleccion inicial de avatar
-- Ejecutar solo si necesitas revertir PR-4.

ALTER TABLE users
    DROP COLUMN avatar_setup_completed;
