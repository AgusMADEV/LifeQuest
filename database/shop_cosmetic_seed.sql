-- Seed opcional de cosmeticos base para usuarios existentes

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
SELECT NULL,
       'Marco Aurora',
       'Cosmetico para destacar tu perfil con un marco premium.',
       450,
      'marco',
       'cosmetic',
       0,
       99,
       1
WHERE NOT EXISTS (
    SELECT 1
    FROM rewards r
    WHERE r.user_id IS NULL
      AND r.name = 'Marco Aurora'
      AND r.shop_type = 'cosmetic'
);

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
SELECT NULL,
       'Tema Oceanic',
       'Paleta visual inspirada en tonos oceanicos.',
       600,
      'fondo',
       'cosmetic',
       0,
       99,
       1
WHERE NOT EXISTS (
    SELECT 1
    FROM rewards r
    WHERE r.user_id IS NULL
      AND r.name = 'Tema Oceanic'
      AND r.shop_type = 'cosmetic'
);

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
SELECT NULL,
       'Pack Stickers Focus',
       'Stickers exclusivos para tus tableros y cards.',
       280,
      'stickers',
       'cosmetic',
       0,
       99,
       1
WHERE NOT EXISTS (
    SELECT 1
    FROM rewards r
  WHERE r.user_id IS NULL
    AND r.name = 'Pack Stickers Focus'
    AND r.shop_type = 'cosmetic'
);

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
  SELECT NULL,
       'Hoodie Menta',
       'Outfit suave para tu avatar principal.',
       520,
       'outfit',
       'cosmetic',
       0,
       99,
       1
    WHERE NOT EXISTS (
        SELECT 1
        FROM rewards r
        WHERE r.user_id IS NULL
      AND r.name = 'Hoodie Menta'
      AND r.shop_type = 'cosmetic'
    );

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
    SELECT NULL,
       'Auriculares Aurora',
       'Accesorio visual con acabado pastel.',
       380,
       'accesorio',
       'cosmetic',
       0,
       99,
       1
    WHERE NOT EXISTS (
        SELECT 1
        FROM rewards r
        WHERE r.user_id IS NULL
      AND r.name = 'Auriculares Aurora'
      AND r.shop_type = 'cosmetic'
    );

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
    SELECT NULL,
       'Dino Buddy',
       'Companero decorativo para tu perfil.',
       700,
       'companero',
       'cosmetic',
       0,
       99,
       1
    WHERE NOT EXISTS (
        SELECT 1
        FROM rewards r
        WHERE r.user_id IS NULL
      AND r.name = 'Dino Buddy'
      AND r.shop_type = 'cosmetic'
    );
