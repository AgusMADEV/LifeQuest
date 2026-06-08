-- Rollback PR-3: Bio dinamica del perfil
-- Ejecutar solo si necesitas revertir PR-3.

ALTER TABLE users
    DROP COLUMN profile_bio;
