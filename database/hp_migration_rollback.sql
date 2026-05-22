-- Rollback PR-1: HP base del personaje
-- Ejecutar solo si necesitas revertir PR-1.

ALTER TABLE users
    DROP COLUMN max_hp,
    DROP COLUMN hp;
