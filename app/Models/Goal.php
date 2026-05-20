<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class Goal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT goals.*, life_areas.name AS area_name, life_areas.color AS area_color, life_areas.icon AS area_icon
                FROM goals
                LEFT JOIN life_areas ON goals.area_id = life_areas.id
                WHERE goals.user_id = :user_id
                ORDER BY goals.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getMainByUser(int $userId, int $limit = 3): array
    {
        $sql = "SELECT goals.*, life_areas.name AS area_name, life_areas.color AS area_color, life_areas.icon AS area_icon
                FROM goals
                LEFT JOIN life_areas ON goals.area_id = life_areas.id
                WHERE goals.user_id = :user_id
                ORDER BY FIELD(goals.priority, 'critical', 'high', 'medium', 'low'), goals.created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM goals WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $goal = $stmt->fetch();
        return $goal ?: null;
    }

    public function create(int $userId, array $data): bool
    {
        $sql = "INSERT INTO goals (user_id, area_id, title, description, type, priority, status, progress, start_date, due_date, xp_reward, points_reward)
                VALUES (:user_id, :area_id, :title, :description, :type, :priority, :status, :progress, :start_date, :due_date, :xp_reward, :points_reward)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['user_id' => $userId] + $data);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $sql = "UPDATE goals SET area_id=:area_id, title=:title, description=:description, type=:type, priority=:priority, status=:status, progress=:progress, start_date=:start_date, due_date=:due_date, xp_reward=:xp_reward, points_reward=:points_reward WHERE id=:id AND user_id=:user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id, 'user_id' => $userId] + $data);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM goals WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
