-- PR-4: Avatar inicial persistido
-- Ejecutar en bases existentes para guardar el primer avatar conocido.

ALTER TABLE users
    ADD COLUMN initial_avatar VARCHAR(255) DEFAULT NULL AFTER avatar;

UPDATE users
SET initial_avatar = avatar
WHERE initial_avatar IS NULL
  AND avatar IS NOT NULL;
