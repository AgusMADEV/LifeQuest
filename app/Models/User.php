<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

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
        if ($hasApellidos) {
            $sql = "INSERT INTO users (name, apellidos, email, password) VALUES (:name, :apellidos, :email, :password)";
        } else {
            $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
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
