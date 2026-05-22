-- Rollback PR-4: Tienda unica con indulgencias

ALTER TABLE rewards
    DROP COLUMN weekly_limit,
    DROP COLUMN effect_hp,
    DROP COLUMN shop_type;
