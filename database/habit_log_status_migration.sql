-- PR-2: Estado diario para habitos en control
-- Ejecutar despues de las migraciones de habitos negativas

ALTER TABLE habit_logs
    ADD COLUMN status ENUM('completed', 'partial') NOT NULL DEFAULT 'completed' AFTER completed_date;

UPDATE habit_logs
SET status = 'completed'
WHERE status IS NULL;