<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';
require_once __DIR__ . '/../Support/AvatarLibrary.php';

final class User
{
    private PDO $db;
    private array $columnCache = [];

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }


    public function create(string $name, string $apellidos, string $email, string $password): bool
    {
        $hasApellidos = $this->hasColumn('users', 'apellidos');
        $hasInitialAvatar = $this->hasColumn('users', 'initial_avatar');
        $hasAvatarSetup = $this->hasColumn('users', 'avatar_setup_completed');
        if ($hasApellidos) {
            $sql = "INSERT INTO users (name, apellidos, email, password" . ($hasInitialAvatar ? ', initial_avatar' : '') . ($hasAvatarSetup ? ', avatar_setup_completed' : '') . ") VALUES (:name, :apellidos, :email, :password" . ($hasInitialAvatar ? ', :initial_avatar' : '') . ($hasAvatarSetup ? ', :avatar_setup_completed' : '') . ")";
        } else {
            $sql = "INSERT INTO users (name, email, password" . ($hasInitialAvatar ? ', initial_avatar' : '') . ($hasAvatarSetup ? ', avatar_setup_completed' : '') . ") VALUES (:name, :email, :password" . ($hasInitialAvatar ? ', :initial_avatar' : '') . ($hasAvatarSetup ? ', :avatar_setup_completed' : '') . ")";
        }
        $stmt = $this->db->prepare($sql);
        $params = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ];
        if ($hasApellidos) {
            $params['apellidos'] = $apellidos;
        }
        if ($hasInitialAvatar) {
            $params['initial_avatar'] = null;
        }
        if ($hasAvatarSetup) {
            $params['avatar_setup_completed'] = 0;
        }
        return $stmt->execute($params);
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $baseHp = defined('PLAYER_BASE_HP') ? (int) PLAYER_BASE_HP : 1000;

        $hpSelect = $this->hasColumn('users', 'hp')
            ? "COALESCE(users.hp, {$baseHp}) AS hp"
            : "{$baseHp} AS hp";

        $maxHpSelect = $this->hasColumn('users', 'max_hp')
            ? "COALESCE(users.max_hp, {$baseHp}) AS max_hp"
            : "{$baseHp} AS max_hp";

        $motivationalLineSelect = $this->hasColumn('users', 'motivational_line')
            ? "COALESCE(users.motivational_line, '') AS motivational_line"
            : "'' AS motivational_line";

        $profileBioSelect = $this->hasColumn('users', 'profile_bio')
            ? "COALESCE(users.profile_bio, '') AS profile_bio"
            : "'' AS profile_bio";

        $profileNotificationsSelect = $this->hasColumn('users', 'profile_notifications_enabled')
            ? "COALESCE(users.profile_notifications_enabled, 1) AS profile_notifications_enabled"
            : "1 AS profile_notifications_enabled";

        $initialAvatarSelect = $this->hasColumn('users', 'initial_avatar')
            ? "COALESCE(users.initial_avatar, '') AS initial_avatar"
            : "'' AS initial_avatar";

        $avatarSetupSelect = $this->hasColumn('users', 'avatar_setup_completed')
            ? "COALESCE(users.avatar_setup_completed, 1) AS avatar_setup_completed"
            : "1 AS avatar_setup_completed";

        $hasApellidos = $this->hasColumn('users', 'apellidos');
        $apellidosSelect = $hasApellidos ? ', apellidos' : '';

        $sql = "SELECT id,
                       name" . $apellidosSelect . ",
                       email,
                       avatar,
                       level,
                       xp,
                       points,
                       {$hpSelect},
                       {$maxHpSelect},
                       {$motivationalLineSelect},
                       {$profileBioSelect},
                       {$profileNotificationsSelect},
                       {$initialAvatarSelect},
                       {$avatarSetupSelect},
                       COALESCE((
                           SELECT MAX(h.current_streak)
                           FROM habits h
                           WHERE h.user_id = users.id
                             AND h.active = 1
                       ), users.current_streak, 0) AS current_streak,
                       created_at
                FROM users
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateProfilePreferences(
        int $id,
        string $name,
        string $apellidos,
        string $profileBio,
        string $motivationalLine,
        bool $notificationsEnabled
    ): bool {
        $setParts = ['name = :name'];
        $params = [
            'id' => $id,
            'name' => $name,
        ];

        if ($this->hasColumn('users', 'apellidos')) {
            $setParts[] = 'apellidos = :apellidos';
            $params['apellidos'] = $apellidos;
        }

        if ($this->hasColumn('users', 'profile_bio')) {
            $setParts[] = 'profile_bio = :profile_bio';
            $params['profile_bio'] = $profileBio;
        }

        if ($this->hasColumn('users', 'motivational_line')) {
            $setParts[] = 'motivational_line = :motivational_line';
            $params['motivational_line'] = $motivationalLine;
        }

        if ($this->hasColumn('users', 'profile_notifications_enabled')) {
            $setParts[] = 'profile_notifications_enabled = :profile_notifications_enabled';
            $params['profile_notifications_enabled'] = $notificationsEnabled ? 1 : 0;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function updateAvatar(int $id, string $avatar): bool
    {
        if (!$this->hasColumn('users', 'avatar')) {
            return false;
        }

        $avatarFile = AvatarLibrary::normalizeAvatar($avatar);
        if ($avatarFile === null) {
            return false;
        }

        $setParts = ['avatar = :avatar'];
        if ($this->hasColumn('users', 'initial_avatar')) {
            $setParts[] = 'initial_avatar = COALESCE(initial_avatar, :initial_avatar)';
        }
        if ($this->hasColumn('users', 'avatar_setup_completed')) {
            $setParts[] = 'avatar_setup_completed = 1';
        }

        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        try {
            $this->db->beginTransaction();

            $updated = $stmt->execute([
                'avatar' => $avatarFile,
                'initial_avatar' => $avatarFile,
                'id' => $id,
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return false;
            }

            $this->syncEquippedAvatarInventory($id, $avatarFile);
            $this->db->commit();

            return true;
        } catch (Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function isAvatarSetupCompleted(int $id): bool
    {
        if (!$this->hasColumn('users', 'avatar_setup_completed')) {
            return true;
        }

        $stmt = $this->db->prepare(
            'SELECT COALESCE(avatar_setup_completed, 1)
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() === 1;
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

    private function syncEquippedAvatarInventory(int $userId, string $avatarFile): void
    {
        if (!$this->hasTable('user_reward_inventory') || !$this->hasColumn('rewards', 'shop_type') || !$this->hasColumn('rewards', 'category') || !$this->hasColumn('rewards', 'image_path')) {
            return;
        }

        $shopAvatars = $this->db->query(
            "SELECT id, image_path
             FROM rewards
             WHERE active = 1
               AND shop_type = 'cosmetic'
               AND LOWER(category) = 'avatar'"
        )->fetchAll();

        $targetRewardId = null;
        foreach ($shopAvatars as $shopAvatar) {
            $rewardAvatar = AvatarLibrary::normalizeAvatar(basename(str_replace('\\', '/', (string) ($shopAvatar['image_path'] ?? ''))));

            if ($rewardAvatar !== null && $rewardAvatar === $avatarFile) {
                $targetRewardId = (int) $shopAvatar['id'];
                break;
            }
        }

        $unequip = $this->db->prepare(
            "UPDATE user_reward_inventory uri
             INNER JOIN rewards r ON r.id = uri.reward_id
             SET uri.equipped = 0,
                 uri.equipped_at = NULL
             WHERE uri.user_id = :user_id
               AND r.active = 1
               AND r.shop_type = 'cosmetic'
               AND LOWER(r.category) = 'avatar'"
        );
        $unequip->execute(['user_id' => $userId]);

        if ($targetRewardId === null) {
            return;
        }

        $equip = $this->db->prepare(
            'UPDATE user_reward_inventory
             SET equipped = 1,
                 equipped_at = NOW()
             WHERE user_id = :user_id
               AND reward_id = :reward_id'
        );
        $equip->execute([
            'user_id' => $userId,
            'reward_id' => $targetRewardId,
        ]);
    }
}
