-- PR-2: Habitos negativos (solo HP en V1)
-- Ejecutar despues de hp_migration.sql

ALTER TABLE habits
    ADD COLUMN is_negative BOOLEAN NOT NULL DEFAULT FALSE AFTER points_reward,
    ADD COLUMN hp_penalty INT NOT NULL DEFAULT 0 AFTER is_negative;

-- Normaliza registros existentes
UPDATE habits
SET is_negative = 0,
    hp_penalty = 0
WHERE is_negative IS NULL
   OR hp_penalty IS NULL
   OR hp_penalty < 0;
