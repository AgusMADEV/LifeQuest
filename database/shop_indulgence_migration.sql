-- PR-4: Tienda unica con indulgencias (y base para cosmeticos)

ALTER TABLE rewards
    ADD COLUMN shop_type ENUM('indulgence', 'cosmetic') NOT NULL DEFAULT 'indulgence' AFTER category,
    ADD COLUMN effect_hp INT NOT NULL DEFAULT 0 AFTER shop_type,
    ADD COLUMN weekly_limit INT NOT NULL DEFAULT 2 AFTER effect_hp;

-- Normaliza datos historicos
UPDATE rewards
SET shop_type = 'indulgence',
    effect_hp = CASE WHEN effect_hp < 0 THEN 0 ELSE effect_hp END,
    weekly_limit = CASE WHEN weekly_limit < 1 THEN 1 ELSE weekly_limit END
WHERE shop_type IS NULL OR effect_hp IS NULL OR weekly_limit IS NULL;
