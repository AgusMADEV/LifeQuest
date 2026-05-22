<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class Reward
{
    private PDO $db;
    private array $columnCache = [];

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function ensureDefaultIndulgences(int $userId): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM rewards
             WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $defaults = [
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

        foreach ($defaults as $item) {
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

        $shopTypeSql = $supportsShopType
            ? 'r.shop_type = :shop_type'
            : "(r.category = 'indulgencia' OR r.category = 'indulgence')";

        $sql = 'SELECT r.id,
                       r.name,
                       r.description,
                       r.cost_points,
                       ' . ($supportsEffectHp ? 'r.effect_hp' : '0') . ' AS effect_hp,
                       ' . ($supportsWeeklyLimit ? 'r.weekly_limit' : '2') . ' AS weekly_limit,
                       COUNT(rr.id) AS weekly_used
                FROM rewards r
                LEFT JOIN reward_redemptions rr
                       ON rr.reward_id = r.id
                      AND rr.user_id = :rr_user_id
                      AND YEARWEEK(rr.redeemed_at, 1) = YEARWEEK(CURDATE(), 1)
                WHERE r.user_id = :r_user_id
                  AND r.active = 1
                  AND ' . $shopTypeSql . '
                GROUP BY r.id, r.name, r.description, r.cost_points, effect_hp, weekly_limit
                ORDER BY r.cost_points ASC, r.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $params = [
            'rr_user_id' => $userId,
            'r_user_id' => $userId,
        ];

        if ($supportsShopType) {
            $params['shop_type'] = $shopType;
        }

        $stmt->execute($params);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
                'cost_points' => max(0, (int) ($row['cost_points'] ?? 0)),
                'effect_hp' => max(0, (int) ($row['effect_hp'] ?? 0)),
                'weekly_limit' => max(1, (int) ($row['weekly_limit'] ?? 1)),
                'weekly_used' => max(0, (int) ($row['weekly_used'] ?? 0)),
            ];
        }, $stmt->fetchAll());
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

        $cost = max(0, (int) ($reward['cost_points'] ?? 0));
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
}
