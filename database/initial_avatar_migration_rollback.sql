-- Rollback PR-4: Avatar inicial persistido
-- Ejecutar solo si necesitas revertir PR-4.

ALTER TABLE users
    DROP COLUMN initial_avatar;
