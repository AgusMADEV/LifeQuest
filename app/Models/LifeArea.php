<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class LifeArea
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT *
                FROM life_areas
                WHERE user_id = :user_id
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT *
                FROM life_areas
                WHERE id = :id AND user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        $area = $stmt->fetch();

        return $area ?: null;
    }

    public function create(int $userId, string $name, ?string $description, ?string $icon, ?string $color): bool
    {
        $sql = "INSERT INTO life_areas (user_id, name, description, icon, color)
                VALUES (:user_id, :name, :description, :icon, :color)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
        ]);
    }

    public function update(int $id, int $userId, string $name, ?string $description, ?string $icon, ?string $color): bool
    {
        $sql = "UPDATE life_areas
                SET name = :name,
                    description = :description,
                    icon = :icon,
                    color = :color
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM life_areas
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }
}
