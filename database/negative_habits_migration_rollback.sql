-- Rollback PR-2: Habitos negativos

ALTER TABLE habits
    DROP COLUMN hp_penalty,
    DROP COLUMN is_negative;
