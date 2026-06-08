-- PR-3: Bio dinamica del perfil
-- Ejecutar en entorno de prueba y luego en entorno principal.

ALTER TABLE users
    ADD COLUMN profile_bio VARCHAR(255) DEFAULT NULL AFTER motivational_line;
