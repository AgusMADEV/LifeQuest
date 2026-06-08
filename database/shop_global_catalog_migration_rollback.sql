-- Rollback PR-5: Catalogo global de tienda
-- Revirtiendo la nulabilidad y reasignando filas sin dueño a un usuario base.

ALTER TABLE rewards
    MODIFY user_id INT NOT NULL;

UPDATE rewards
SET user_id = (
    SELECT id
    FROM (
        SELECT id
        FROM users
        ORDER BY id ASC
        LIMIT 1
    ) AS first_user
)
WHERE user_id IS NULL;
