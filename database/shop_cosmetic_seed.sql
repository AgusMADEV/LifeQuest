-- Seed opcional de cosmeticos base para usuarios existentes

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
SELECT u.id,
       'Marco Aurora',
       'Cosmetico para destacar tu perfil con un marco premium.',
       450,
      'marco',
       'cosmetic',
       0,
       99,
       1
FROM users u
WHERE NOT EXISTS (
    SELECT 1
    FROM rewards r
    WHERE r.user_id = u.id
      AND r.name = 'Marco Aurora'
);

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
SELECT u.id,
       'Tema Oceanic',
       'Paleta visual inspirada en tonos oceanicos.',
       600,
      'fondo',
       'cosmetic',
       0,
       99,
       1
FROM users u
WHERE NOT EXISTS (
    SELECT 1
    FROM rewards r
    WHERE r.user_id = u.id
      AND r.name = 'Tema Oceanic'
);

INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
SELECT u.id,
       'Pack Stickers Focus',
       'Stickers exclusivos para tus tableros y cards.',
       280,
      'stickers',
       'cosmetic',
       0,
       99,
       1
FROM users u
WHERE NOT EXISTS (
    SELECT 1
    FROM rewards r
    WHERE r.user_id = u.id
      AND r.name = 'Pack Stickers Focus'
);

    INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
    SELECT u.id,
       'Hoodie Menta',
       'Outfit suave para tu avatar principal.',
       520,
       'outfit',
       'cosmetic',
       0,
       99,
       1
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1
        FROM rewards r
        WHERE r.user_id = u.id
      AND r.name = 'Hoodie Menta'
    );

    INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
    SELECT u.id,
       'Auriculares Aurora',
       'Accesorio visual con acabado pastel.',
       380,
       'accesorio',
       'cosmetic',
       0,
       99,
       1
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1
        FROM rewards r
        WHERE r.user_id = u.id
      AND r.name = 'Auriculares Aurora'
    );

    INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
    SELECT u.id,
       'Dino Buddy',
       'Companero decorativo para tu perfil.',
       700,
       'companero',
       'cosmetic',
       0,
       99,
       1
    FROM users u
    WHERE NOT EXISTS (
        SELECT 1
        FROM rewards r
        WHERE r.user_id = u.id
      AND r.name = 'Dino Buddy'
    );
