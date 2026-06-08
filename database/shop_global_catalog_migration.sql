-- PR-5: Catalogo global de tienda
-- Convierte las recompensas a catalogo compartido y consolida duplicados exactos.

ALTER TABLE rewards
    MODIFY user_id INT NULL DEFAULT NULL;

CREATE TEMPORARY TABLE reward_global_map (
    old_id INT NOT NULL PRIMARY KEY,
    new_id INT NOT NULL
) ENGINE=MEMORY;

INSERT INTO reward_global_map (old_id, new_id)
SELECT r.id AS old_id,
       grouped.canonical_id AS new_id
FROM rewards r
INNER JOIN (
    SELECT MIN(id) AS canonical_id,
           name,
           COALESCE(description, '') AS description_key,
           COALESCE(image_path, '') AS image_path_key,
           cost_points,
           COALESCE(category, '') AS category_key,
           COALESCE(shop_type, 'indulgence') AS shop_type_key,
           effect_hp,
           weekly_limit,
           active
    FROM rewards
    GROUP BY name,
             COALESCE(description, ''),
             COALESCE(image_path, ''),
             cost_points,
             COALESCE(category, ''),
             COALESCE(shop_type, 'indulgence'),
             effect_hp,
             weekly_limit,
             active
) grouped
    ON grouped.name = r.name
   AND grouped.description_key = COALESCE(r.description, '')
   AND grouped.image_path_key = COALESCE(r.image_path, '')
   AND grouped.cost_points = r.cost_points
   AND grouped.category_key = COALESCE(r.category, '')
   AND grouped.shop_type_key = COALESCE(r.shop_type, 'indulgence')
   AND grouped.effect_hp = r.effect_hp
   AND grouped.weekly_limit = r.weekly_limit
   AND grouped.active = r.active;

UPDATE reward_redemptions rr
INNER JOIN reward_global_map map ON map.old_id = rr.reward_id
SET rr.reward_id = map.new_id;

UPDATE user_reward_inventory uri
INNER JOIN reward_global_map map ON map.old_id = uri.reward_id
SET uri.reward_id = map.new_id;

DELETE r
FROM rewards r
INNER JOIN reward_global_map map ON map.old_id = r.id
WHERE map.old_id <> map.new_id;

UPDATE rewards
SET user_id = NULL;

DROP TEMPORARY TABLE reward_global_map;
