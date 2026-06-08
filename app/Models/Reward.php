<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';
require_once __DIR__ . '/AppSettings.php';

final class Reward
{
    private PDO $db;
    private array $columnCache = [];
    private ?array $settingsCache = null;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function ensureDefaultCatalog(int $userId): void
    {
        $catalog = [
            [
                'name' => 'Cerveza fria',
                'description' => 'Disfruta una cerveza de forma consciente.',
                'cost_points' => 200,
                'category' => 'indulgencia',
                'shop_type' => 'indulgence',
                'effect_hp' => 25,
                'weekly_limit' => 2,
            ],
            [
                'name' => 'Postre libre',
                'description' => 'Permiso para un postre sin culpa.',
                'cost_points' => 160,
                'category' => 'indulgencia',
                'shop_type' => 'indulgence',
                'effect_hp' => 20,
                'weekly_limit' => 2,
            ],
            [
                'name' => 'Noche de ocio',
                'description' => 'Una noche para desconectar y recargar.',
                'cost_points' => 320,
                'category' => 'indulgencia',
                'shop_type' => 'indulgence',
                'effect_hp' => 40,
                'weekly_limit' => 1,
            ],
            [
                'name' => 'Marco Aurora',
                'description' => 'Cosmetico para destacar tu perfil con un marco premium.',
                'cost_points' => 450,
                'category' => 'marco',
                'shop_type' => 'cosmetic',
                'effect_hp' => 0,
                'weekly_limit' => 99,
            ],
            [
                'name' => 'Tema Oceanic',
                'description' => 'Paleta visual inspirada en tonos oceanicos.',
                'cost_points' => 600,
                'category' => 'fondo',
                'shop_type' => 'cosmetic',
                'effect_hp' => 0,
                'weekly_limit' => 99,
            ],
            [
                'name' => 'Pack Stickers Focus',
                'description' => 'Stickers exclusivos para tus tableros y cards.',
                'cost_points' => 280,
                'category' => 'stickers',
                'shop_type' => 'cosmetic',
                'effect_hp' => 0,
                'weekly_limit' => 99,
            ],
            [
                'name' => 'Hoodie Menta',
                'description' => 'Outfit suave para tu avatar principal.',
                'cost_points' => 520,
                'category' => 'outfit',
                'shop_type' => 'cosmetic',
                'effect_hp' => 0,
                'weekly_limit' => 99,
            ],
            [
                'name' => 'Auriculares Aurora',
                'description' => 'Accesorio visual con acabado pastel.',
                'cost_points' => 380,
                'category' => 'accesorio',
                'shop_type' => 'cosmetic',
                'effect_hp' => 0,
                'weekly_limit' => 99,
            ],
            [
                'name' => 'Dino Buddy',
                'description' => 'Companero decorativo para tu perfil.',
                'cost_points' => 700,
                'category' => 'companero',
                'shop_type' => 'cosmetic',
                'effect_hp' => 0,
                'weekly_limit' => 99,
            ],
        ];

        $supportsShopType = $this->hasColumn('rewards', 'shop_type');
        $supportsEffectHp = $this->hasColumn('rewards', 'effect_hp');
        $supportsWeeklyLimit = $this->hasColumn('rewards', 'weekly_limit');

        foreach ($catalog as $item) {
            $exists = $this->db->prepare(
                'SELECT id
                 FROM rewards
                 WHERE user_id = :user_id
                   AND name = :name
                 LIMIT 1'
            );
            $exists->execute([
                'user_id' => $userId,
                'name' => $item['name'],
            ]);

            if ($exists->fetch()) {
                continue;
            }

            if ($supportsShopType && $supportsEffectHp && $supportsWeeklyLimit) {
                $insert = $this->db->prepare(
                    'INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
                     VALUES (:user_id, :name, :description, :cost_points, :category, :shop_type, :effect_hp, :weekly_limit, 1)'
                );
                $insert->execute([
                    'user_id' => $userId,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'cost_points' => $item['cost_points'],
                    'category' => $item['category'],
                    'shop_type' => $item['shop_type'],
                    'effect_hp' => $item['effect_hp'],
                    'weekly_limit' => $item['weekly_limit'],
                ]);
                continue;
            }

            $insert = $this->db->prepare(
                'INSERT INTO rewards (user_id, name, description, cost_points, category, active)
                 VALUES (:user_id, :name, :description, :cost_points, :category, 1)'
            );
            $insert->execute([
                'user_id' => $userId,
                'name' => $item['name'],
                'description' => $item['description'],
                'cost_points' => $item['cost_points'],
                'category' => $item['category'],
            ]);
        }
    }

    public function ensureDefaultIndulgences(int $userId): void
    {
        $catalog = [
            [
                'name' => 'Cerveza fria',
                'description' => 'Disfruta una cerveza de forma consciente.',
                'cost_points' => 200,
                'category' => 'indulgencia',
                'shop_type' => 'indulgence',
                'effect_hp' => 25,
                'weekly_limit' => 2,
            ],
            [
                'name' => 'Postre libre',
                'description' => 'Permiso para un postre sin culpa.',
                'cost_points' => 160,
                'category' => 'indulgencia',
                'shop_type' => 'indulgence',
                'effect_hp' => 20,
                'weekly_limit' => 2,
            ],
            [
                'name' => 'Noche de ocio',
                'description' => 'Una noche para desconectar y recargar.',
                'cost_points' => 320,
                'category' => 'indulgencia',
                'shop_type' => 'indulgence',
                'effect_hp' => 40,
                'weekly_limit' => 1,
            ],
        ];

        $supportsShopType = $this->hasColumn('rewards', 'shop_type');
        $supportsEffectHp = $this->hasColumn('rewards', 'effect_hp');
        $supportsWeeklyLimit = $this->hasColumn('rewards', 'weekly_limit');

        foreach ($catalog as $item) {
            $exists = $this->db->prepare(
                'SELECT id
                 FROM rewards
                 WHERE user_id = :user_id
                   AND name = :name
                 LIMIT 1'
            );
            $exists->execute([
                'user_id' => $userId,
                'name' => $item['name'],
            ]);

            if ($exists->fetch()) {
                continue;
            }

            if ($supportsShopType && $supportsEffectHp && $supportsWeeklyLimit) {
                $insert = $this->db->prepare(
                    'INSERT INTO rewards (user_id, name, description, cost_points, category, shop_type, effect_hp, weekly_limit, active)
                     VALUES (:user_id, :name, :description, :cost_points, :category, :shop_type, :effect_hp, :weekly_limit, 1)'
                );
                $insert->execute([
                    'user_id' => $userId,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'cost_points' => $item['cost_points'],
                    'category' => $item['category'],
                    'shop_type' => $item['shop_type'],
                    'effect_hp' => $item['effect_hp'],
                    'weekly_limit' => $item['weekly_limit'],
                ]);
                continue;
            }

            $insert = $this->db->prepare(
                'INSERT INTO rewards (user_id, name, description, cost_points, category, active)
                 VALUES (:user_id, :name, :description, :cost_points, :category, 1)'
            );
            $insert->execute([
                'user_id' => $userId,
                'name' => $item['name'],
                'description' => $item['description'],
                'cost_points' => $item['cost_points'],
                'category' => $item['category'],
            ]);
        }
    }

    public function getShopItems(int $userId, string $shopType = 'indulgence'): array
    {
        $supportsShopType = $this->hasColumn('rewards', 'shop_type');
        $supportsEffectHp = $this->hasColumn('rewards', 'effect_hp');
        $supportsWeeklyLimit = $this->hasColumn('rewards', 'weekly_limit');
        $supportsImagePath = $this->hasColumn('rewards', 'image_path');
        $supportsInventory = $this->hasTable('user_reward_inventory');

        $shopTypeSql = $supportsShopType
            ? 'r.shop_type = :shop_type'
            : "(r.category = 'indulgencia' OR r.category = 'indulgence')";

        $inventoryJoin = $supportsInventory
            ? 'LEFT JOIN user_reward_inventory uri
                       ON uri.reward_id = r.id
                      AND uri.user_id = :inventory_user_id'
            : '';

        $sql = 'SELECT r.id,
                       r.name,
                       r.description,
                       ' . ($supportsImagePath ? 'r.image_path' : 'NULL') . ' AS image_path,
                       r.cost_points,
                       r.category,
                       ' . ($supportsEffectHp ? 'r.effect_hp' : '0') . ' AS effect_hp,
                       ' . ($supportsWeeklyLimit ? 'r.weekly_limit' : '2') . ' AS weekly_limit,
                       ' . ($supportsInventory ? 'MAX(CASE WHEN uri.id IS NULL THEN 0 ELSE 1 END)' : '0') . ' AS owned,
                       ' . ($supportsInventory ? 'MAX(CASE WHEN uri.equipped = 1 THEN 1 ELSE 0 END)' : '0') . ' AS equipped,
                       COUNT(rr.id) AS weekly_used
                FROM rewards r
                LEFT JOIN reward_redemptions rr
                       ON rr.reward_id = r.id
                      AND rr.user_id = :rr_user_id
                      AND YEARWEEK(rr.redeemed_at, 1) = YEARWEEK(CURDATE(), 1)
                ' . $inventoryJoin . '
                WHERE r.user_id = :r_user_id
                  AND r.active = 1
                  AND ' . $shopTypeSql . '
                GROUP BY r.id, r.name, r.description, image_path, r.cost_points, r.category, effect_hp, weekly_limit
                ORDER BY r.cost_points ASC, r.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $params = [
            'rr_user_id' => $userId,
            'r_user_id' => $userId,
        ];

        if ($supportsInventory) {
            $params['inventory_user_id'] = $userId;
        }

        if ($supportsShopType) {
            $params['shop_type'] = $shopType;
        }

        $stmt->execute($params);

        $multiplier = $this->getFloatSetting('INDULGENCE_REPEAT_COST_MULTIPLIER', defined('INDULGENCE_REPEAT_COST_MULTIPLIER') ? (float) INDULGENCE_REPEAT_COST_MULTIPLIER : 1.25);
        $cosmeticMultiplier = $this->getFloatSetting('COSMETIC_PRICE_MULTIPLIER', 1.0);

        return array_map(static function (array $row) use ($shopType, $multiplier, $cosmeticMultiplier): array {
            $baseCost = max(0, (int) ($row['cost_points'] ?? 0));
            $weeklyUsed = max(0, (int) ($row['weekly_used'] ?? 0));
            $dynamicCost = $shopType === 'indulgence'
                ? (int) ceil($baseCost * pow(max(1.0, $multiplier), $weeklyUsed))
            : (int) ceil($baseCost * max(0.1, $cosmeticMultiplier));

            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
                'image_path' => (string) ($row['image_path'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'cost_points' => $dynamicCost,
                'base_cost_points' => $baseCost,
                'effect_hp' => max(0, (int) ($row['effect_hp'] ?? 0)),
                'weekly_limit' => max(1, (int) ($row['weekly_limit'] ?? 1)),
                'weekly_used' => $weeklyUsed,
                'owned' => !empty($row['owned']),
                'equipped' => !empty($row['equipped']),
            ];
        }, $stmt->fetchAll());
    }

    public function getShopItemByName(int $userId, string $shopType, string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        foreach ($this->getShopItems($userId, $shopType) as $item) {
            if ((string) ($item['name'] ?? '') === $name) {
                return $item;
            }
        }

        return null;
    }

    public function redeemIndulgence(int $userId, int $rewardId): array
    {
        $supportsShopType = $this->hasColumn('rewards', 'shop_type');
        $supportsEffectHp = $this->hasColumn('rewards', 'effect_hp');
        $supportsWeeklyLimit = $this->hasColumn('rewards', 'weekly_limit');

        $query = 'SELECT id,
                         name,
                         cost_points,
                         ' . ($supportsEffectHp ? 'effect_hp' : '0') . ' AS effect_hp,
                         ' . ($supportsWeeklyLimit ? 'weekly_limit' : '2') . ' AS weekly_limit
                  FROM rewards
                  WHERE id = :reward_id
                    AND user_id = :user_id
                    AND active = 1';

        if ($supportsShopType) {
            $query .= "\n                    AND shop_type = 'indulgence'";
        }

        $rewardStmt = $this->db->prepare($query);
        $rewardStmt->execute([
            'reward_id' => $rewardId,
            'user_id' => $userId,
        ]);

        $reward = $rewardStmt->fetch();

        if (!$reward) {
            return ['success' => false, 'message' => 'La indulgencia no existe o no está disponible.'];
        }

        $weeklyLimit = max(1, (int) ($reward['weekly_limit'] ?? 1));

        $usageStmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM reward_redemptions
             WHERE reward_id = :reward_id
               AND user_id = :user_id
               AND YEARWEEK(redeemed_at, 1) = YEARWEEK(CURDATE(), 1)'
        );
        $usageStmt->execute([
            'reward_id' => $rewardId,
            'user_id' => $userId,
        ]);

        $weeklyUsed = (int) $usageStmt->fetchColumn();

        if ($weeklyUsed >= $weeklyLimit) {
            return ['success' => false, 'message' => 'Límite semanal alcanzado para esta indulgencia.'];
        }

        $userStmt = $this->db->prepare(
            'SELECT points,
                    ' . ($this->hasColumn('users', 'hp') ? 'hp' : '0') . ' AS hp,
                    ' . ($this->hasColumn('users', 'max_hp') ? 'max_hp' : '1000') . ' AS max_hp
             FROM users
             WHERE id = :user_id
             LIMIT 1'
        );
        $userStmt->execute(['user_id' => $userId]);

        $user = $userStmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }

        $baseCost = max(0, (int) ($reward['cost_points'] ?? 0));
        $multiplier = $this->getFloatSetting('INDULGENCE_REPEAT_COST_MULTIPLIER', defined('INDULGENCE_REPEAT_COST_MULTIPLIER') ? (float) INDULGENCE_REPEAT_COST_MULTIPLIER : 1.25);
        $cost = (int) ceil($baseCost * pow(max(1.0, $multiplier), $weeklyUsed));
        $points = max(0, (int) ($user['points'] ?? 0));

        if ($points < $cost) {
            return ['success' => false, 'message' => 'No tienes LifeCoins suficientes para esta indulgencia.'];
        }

        $effectHp = max(0, (int) ($reward['effect_hp'] ?? 0));
        $maxHp = max(1, (int) ($user['max_hp'] ?? 1000));
        $currentHp = max(0, min($maxHp, (int) ($user['hp'] ?? $maxHp)));
        $newHp = min($maxHp, $currentHp + $effectHp);

        try {
            $this->db->beginTransaction();

            if ($this->hasColumn('users', 'hp')) {
                $updateUser = $this->db->prepare(
                    'UPDATE users
                     SET points = :points,
                         hp = :hp
                     WHERE id = :user_id'
                );
                $updateUser->execute([
                    'points' => $points - $cost,
                    'hp' => $newHp,
                    'user_id' => $userId,
                ]);
            } else {
                $updateUser = $this->db->prepare(
                    'UPDATE users
                     SET points = :points
                     WHERE id = :user_id'
                );
                $updateUser->execute([
                    'points' => $points - $cost,
                    'user_id' => $userId,
                ]);
            }

            $insert = $this->db->prepare(
                'INSERT INTO reward_redemptions (reward_id, user_id)
                 VALUES (:reward_id, :user_id)'
            );
            $insert->execute([
                'reward_id' => $rewardId,
                'user_id' => $userId,
            ]);

            $this->db->commit();

            $message = 'Indulgencia canjeada.';

            if ($effectHp > 0 && $this->hasColumn('users', 'hp')) {
                $message .= ' +' . $effectHp . ' HP.';
            }

            return ['success' => true, 'message' => $message];
        } catch (Throwable $exception) {
            $this->db->rollBack();

            return ['success' => false, 'message' => 'No se pudo canjear la indulgencia.'];
        }
    }

    public function redeemCosmetic(int $userId, int $rewardId): array
    {
        $supportsShopType = $this->hasColumn('rewards', 'shop_type');
                $supportsInventory = $this->hasTable('user_reward_inventory');

                $query = 'SELECT id, name, cost_points, category
                  FROM rewards
                  WHERE id = :reward_id
                    AND user_id = :user_id
                    AND active = 1';

        if ($supportsShopType) {
            $query .= "\n                    AND shop_type = 'cosmetic'";
        }

        $rewardStmt = $this->db->prepare($query);
        $rewardStmt->execute([
            'reward_id' => $rewardId,
            'user_id' => $userId,
        ]);

        $reward = $rewardStmt->fetch();

        if (!$reward) {
            return ['success' => false, 'message' => 'El cosmético no existe o no está disponible.'];
        }

        if ($supportsInventory && $this->ownsCosmetic($userId, $rewardId)) {
            return ['success' => false, 'message' => 'Este cosmético ya está en tu inventario.'];
        }

        $userStmt = $this->db->prepare(
            'SELECT points
             FROM users
             WHERE id = :user_id
             LIMIT 1'
        );
        $userStmt->execute(['user_id' => $userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }

        $cosmeticMultiplier = $this->getFloatSetting('COSMETIC_PRICE_MULTIPLIER', 1.0);
        $cost = (int) ceil(max(0, (int) ($reward['cost_points'] ?? 0)) * max(0.1, $cosmeticMultiplier));
        $points = max(0, (int) ($user['points'] ?? 0));

        if ($points < $cost) {
            return ['success' => false, 'message' => 'No tienes LifeCoins suficientes para este cosmético.'];
        }

        try {
            $this->db->beginTransaction();

            $updateUser = $this->db->prepare(
                'UPDATE users
                 SET points = :points
                 WHERE id = :user_id'
            );
            $updateUser->execute([
                'points' => $points - $cost,
                'user_id' => $userId,
            ]);

            $insert = $this->db->prepare(
                'INSERT INTO reward_redemptions (reward_id, user_id)
                 VALUES (:reward_id, :user_id)'
            );
            $insert->execute([
                'reward_id' => $rewardId,
                'user_id' => $userId,
            ]);

            if ($supportsInventory) {
                $inventoryInsert = $this->db->prepare(
                    'INSERT INTO user_reward_inventory (user_id, reward_id, equipped)
                     VALUES (:user_id, :reward_id, 0)
                     ON DUPLICATE KEY UPDATE reward_id = reward_id'
                );
                $inventoryInsert->execute([
                    'user_id' => $userId,
                    'reward_id' => $rewardId,
                ]);
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Cosmético añadido a tu inventario.'];
        } catch (Throwable $exception) {
            $this->db->rollBack();

            return ['success' => false, 'message' => 'No se pudo canjear el cosmético.'];
        }
    }

    public function equipCosmetic(int $userId, int $rewardId): array
    {
        if (!$this->hasTable('user_reward_inventory')) {
            return ['success' => false, 'message' => 'El inventario todavía no está migrado.'];
        }

        $supportsShopType = $this->hasColumn('rewards', 'shop_type');

        $query = 'SELECT r.id, r.name, r.category
                  FROM user_reward_inventory uri
                  INNER JOIN rewards r ON r.id = uri.reward_id
                                    WHERE uri.user_id = :inventory_user_id
                    AND r.id = :reward_id
                                        AND r.user_id = :reward_user_id
                    AND r.active = 1';

        if ($supportsShopType) {
            $query .= "\n                    AND r.shop_type = 'cosmetic'";
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'inventory_user_id' => $userId,
            'reward_user_id' => $userId,
            'reward_id' => $rewardId,
        ]);

        $reward = $stmt->fetch();

        if (!$reward) {
            return ['success' => false, 'message' => 'Ese cosmético no está en tu inventario.'];
        }

        $category = $this->normalizeCosmeticCategory((string) ($reward['category'] ?? 'cosmetico'));

        try {
            $this->db->beginTransaction();

            $unequip = $this->db->prepare(
                'UPDATE user_reward_inventory uri
                 INNER JOIN rewards r ON r.id = uri.reward_id
                 SET uri.equipped = 0,
                     uri.equipped_at = NULL
                 WHERE uri.user_id = :inventory_user_id
                   AND r.user_id = :reward_user_id
                   AND r.active = 1
                   AND LOWER(r.category) = :category'
            );
            $unequip->execute([
                'inventory_user_id' => $userId,
                'reward_user_id' => $userId,
                'category' => $category,
            ]);

            $equip = $this->db->prepare(
                'UPDATE user_reward_inventory
                 SET equipped = 1,
                     equipped_at = NOW()
                 WHERE user_id = :user_id
                   AND reward_id = :reward_id'
            );
            $equip->execute([
                'user_id' => $userId,
                'reward_id' => $rewardId,
            ]);

            $this->db->commit();

            return ['success' => true, 'message' => 'Cosmético equipado: ' . (string) $reward['name'] . '.'];
        } catch (Throwable) {
            $this->db->rollBack();

            return ['success' => false, 'message' => 'No se pudo equipar el cosmético.'];
        }
    }

    public function getInventoryCosmetics(int $userId, bool $equippedOnly = false): array
    {
        if (!$this->hasTable('user_reward_inventory')) {
            return [];
        }

        $supportsShopType = $this->hasColumn('rewards', 'shop_type');
        $supportsImagePath = $this->hasColumn('rewards', 'image_path');

        $query = 'SELECT r.id,
                         r.name,
                         r.description,
                 ' . ($supportsImagePath ? 'r.image_path' : 'NULL') . ' AS image_path,
                         r.cost_points,
                         r.category,
                         uri.equipped,
                         uri.acquired_at,
                         uri.equipped_at
                  FROM user_reward_inventory uri
                  INNER JOIN rewards r ON r.id = uri.reward_id
                                    WHERE uri.user_id = :inventory_user_id
                                        AND r.user_id = :reward_user_id
                    AND r.active = 1';

        if ($supportsShopType) {
            $query .= "\n                    AND r.shop_type = 'cosmetic'";
        }

        if ($equippedOnly) {
            $query .= "\n                    AND uri.equipped = 1";
        }

        $query .= "\n                  ORDER BY uri.equipped DESC, COALESCE(uri.equipped_at, uri.acquired_at) DESC, r.name ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'inventory_user_id' => $userId,
            'reward_user_id' => $userId,
        ]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
                'image_path' => (string) ($row['image_path'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'cost_points' => max(0, (int) ($row['cost_points'] ?? 0)),
                'equipped' => !empty($row['equipped']),
                'acquired_at' => (string) ($row['acquired_at'] ?? ''),
                'equipped_at' => (string) ($row['equipped_at'] ?? ''),
            ];
        }, $stmt->fetchAll());
    }

    private function ownsCosmetic(int $userId, int $rewardId): bool
    {
        if (!$this->hasTable('user_reward_inventory')) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM user_reward_inventory
             WHERE user_id = :user_id
               AND reward_id = :reward_id
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'reward_id' => $rewardId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function hasTable(string $table): bool
    {
        $cacheKey = 'table.' . $table;

        if (array_key_exists($cacheKey, $this->columnCache)) {
            return $this->columnCache[$cacheKey];
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             LIMIT 1'
        );
        $stmt->execute(['table' => $table]);

        $exists = (bool) $stmt->fetchColumn();
        $this->columnCache[$cacheKey] = $exists;

        return $exists;
    }

    private function normalizeCosmeticCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $category = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $category);

        return $category !== '' ? $category : 'cosmetico';
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;

        if (array_key_exists($cacheKey, $this->columnCache)) {
            return $this->columnCache[$cacheKey];
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column
             LIMIT 1'
        );
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        $exists = (bool) $stmt->fetchColumn();
        $this->columnCache[$cacheKey] = $exists;

        return $exists;
    }

    private function getFloatSetting(string $key, float $fallback): float
    {
        $settings = $this->settings();

        if (isset($settings[$key]) && is_numeric($settings[$key])) {
            return (float) $settings[$key];
        }

        return $fallback;
    }

    private function settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $model = new AppSettings($this->db);
        $this->settingsCache = $model->getMany([
            'INDULGENCE_REPEAT_COST_MULTIPLIER',
            'COSMETIC_PRICE_MULTIPLIER',
        ]);

        return $this->settingsCache;
    }
}
