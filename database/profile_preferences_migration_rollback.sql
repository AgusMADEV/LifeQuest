-- Rollback PR-2: Preferencias dinamicas del perfil
-- Ejecutar solo si necesitas revertir PR-2.

ALTER TABLE users
    DROP COLUMN profile_notifications_enabled,
    DROP COLUMN profile_theme,
    DROP COLUMN profile_bio,
    DROP COLUMN motivational_line;