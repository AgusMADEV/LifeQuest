<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class Task
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT tasks.*,
                       projects.title AS project_title,
                       goals.title AS goal_title,
                       life_areas.name AS area_name,
                       life_areas.color AS area_color,
                       life_areas.icon AS area_icon
                FROM tasks
                LEFT JOIN projects ON tasks.project_id = projects.id
                LEFT JOIN goals ON tasks.goal_id = goals.id
                LEFT JOIN life_areas ON tasks.area_id = life_areas.id
                WHERE tasks.user_id = :user_id
                ORDER BY
                    CASE tasks.status
                        WHEN 'in_progress' THEN 1
                        WHEN 'pending' THEN 2
                        WHEN 'completed' THEN 3
                        ELSE 4
                    END,
                    tasks.due_date IS NULL,
                    tasks.due_date ASC,
                    tasks.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getTodayByUser(int $userId, int $limit = 5): array
    {
        $sql = "SELECT tasks.*,
                       projects.title AS project_title,
                       goals.title AS goal_title,
                       life_areas.name AS area_name,
                       life_areas.color AS area_color,
                       life_areas.icon AS area_icon
                FROM tasks
                LEFT JOIN projects ON tasks.project_id = projects.id
                LEFT JOIN goals ON tasks.goal_id = goals.id
                LEFT JOIN life_areas ON tasks.area_id = life_areas.id
                WHERE tasks.user_id = :user_id
                  AND tasks.status IN ('pending', 'in_progress', 'completed')
                ORDER BY
                    CASE tasks.status
                        WHEN 'in_progress' THEN 1
                        WHEN 'pending' THEN 2
                        WHEN 'completed' THEN 3
                        ELSE 4
                    END,
                    tasks.due_date IS NULL,
                    tasks.due_date ASC,
                    tasks.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT *
                FROM tasks
                WHERE id = :id AND user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        $task = $stmt->fetch();

        return $task ?: null;
    }

    public function create(int $userId, array $data): bool
    {
        $sql = "INSERT INTO tasks (
                    user_id, project_id, goal_id, area_id, title, description,
                    priority, status, estimated_minutes, due_date, xp_reward, points_reward
                )
                VALUES (
                    :user_id, :project_id, :goal_id, :area_id, :title, :description,
                    :priority, :status, :estimated_minutes, :due_date, :xp_reward, :points_reward
                )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['user_id' => $userId] + $data);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $previous = $this->findByIdAndUser($id, $userId);

        $sql = "UPDATE tasks
                SET project_id = :project_id,
                    goal_id = :goal_id,
                    area_id = :area_id,
                    title = :title,
                    description = :description,
                    priority = :priority,
                    status = :status,
                    estimated_minutes = :estimated_minutes,
                    due_date = :due_date,
                    xp_reward = :xp_reward,
                    points_reward = :points_reward,
                    completed_at = CASE
                        WHEN :status_for_completed = 'completed' AND completed_at IS NULL THEN NOW()
                        WHEN :status_for_cancelled <> 'completed' THEN NULL
                        ELSE completed_at
                    END
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'project_id' => $data['project_id'],
            'goal_id' => $data['goal_id'],
            'area_id' => $data['area_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'status_for_completed' => $data['status'],
            'status_for_cancelled' => $data['status'],
            'estimated_minutes' => $data['estimated_minutes'],
            'due_date' => $data['due_date'],
            'xp_reward' => $data['xp_reward'],
            'points_reward' => $data['points_reward'],
        ]);

        if ($ok) {
            $this->refreshRelatedProgress($previous['project_id'] ?? null, $previous['goal_id'] ?? null);
            $this->refreshRelatedProgress($data['project_id'], $data['goal_id']);
        }

        return $ok;
    }

    public function delete(int $id, int $userId): bool
    {
        $task = $this->findByIdAndUser($id, $userId);

        $sql = "DELETE FROM tasks
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        if ($ok && $task) {
            $this->refreshRelatedProgress($task['project_id'] ?? null, $task['goal_id'] ?? null);
        }

        return $ok;
    }

    public function complete(int $id, int $userId): array
    {
        $task = $this->findByIdAndUser($id, $userId);

        if (!$task) {
            return ['success' => false, 'message' => 'La misión no existe.'];
        }

        if ($task['status'] === 'completed') {
            return ['success' => false, 'message' => 'Esta misión ya estaba completada.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE tasks
                 SET status = 'completed', completed_at = NOW()
                 WHERE id = :id AND user_id = :user_id"
            );

            $stmt->execute([
                'id' => $id,
                'user_id' => $userId,
            ]);

            $userStmt = $this->db->prepare(
                "SELECT xp, points
                 FROM users
                 WHERE id = :user_id
                 LIMIT 1"
            );
            $userStmt->execute(['user_id' => $userId]);
            $user = $userStmt->fetch();

            $newXp = ((int) ($user['xp'] ?? 0)) + (int) $task['xp_reward'];
            $newPoints = ((int) ($user['points'] ?? 0)) + (int) $task['points_reward'];
            $newLevel = max(1, intdiv($newXp, 1000) + 1);

            $rewardStmt = $this->db->prepare(
                "UPDATE users
                 SET xp = :xp,
                     points = :points,
                     level = :level
                 WHERE id = :user_id"
            );

            $rewardStmt->execute([
                'xp' => $newXp,
                'points' => $newPoints,
                'level' => $newLevel,
                'user_id' => $userId,
            ]);

            $this->refreshRelatedProgress($task['project_id'] ?? null, $task['goal_id'] ?? null);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Misión completada. +' . (int) $task['xp_reward'] . ' XP y +' . (int) $task['points_reward'] . ' LifeCoins.'
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();

            return [
                'success' => false,
                'message' => 'No se pudo completar la misión.'
            ];
        }
    }

    private function refreshRelatedProgress(?int $projectId, ?int $goalId): void
    {
        if ($projectId !== null) {
            $this->refreshProjectProgress((int) $projectId);
        }

        if ($goalId !== null) {
            $this->refreshGoalProgress((int) $goalId);
        }
    }

    private function refreshProjectProgress(int $projectId): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
             FROM tasks
             WHERE project_id = :project_id
               AND status <> 'cancelled'"
        );
        $stmt->execute(['project_id' => $projectId]);
        $stats = $stmt->fetch();

        $total = (int) ($stats['total'] ?? 0);
        $completed = (int) ($stats['completed'] ?? 0);
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $update = $this->db->prepare(
            "UPDATE projects
             SET progress = :progress,
                 status = CASE
                    WHEN :progress_completed = 100 THEN 'completed'
                    WHEN status = 'completed' AND :progress_not_completed < 100 THEN 'active'
                    ELSE status
                 END
             WHERE id = :project_id"
        );

        $update->execute([
            'progress' => $progress,
            'progress_completed' => $progress,
            'progress_not_completed' => $progress,
            'project_id' => $projectId,
        ]);
    }

    private function refreshGoalProgress(int $goalId): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
             FROM tasks
             WHERE goal_id = :goal_id
               AND status <> 'cancelled'"
        );
        $stmt->execute(['goal_id' => $goalId]);
        $stats = $stmt->fetch();

        $total = (int) ($stats['total'] ?? 0);
        $completed = (int) ($stats['completed'] ?? 0);
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $update = $this->db->prepare(
            "UPDATE goals
             SET progress = :progress,
                 status = CASE
                    WHEN :progress_completed = 100 THEN 'completed'
                    WHEN status = 'completed' AND :progress_not_completed < 100 THEN 'in_progress'
                    WHEN status = 'not_started' AND :progress_started > 0 THEN 'in_progress'
                    ELSE status
                 END
             WHERE id = :goal_id"
        );

        $update->execute([
            'progress' => $progress,
            'progress_completed' => $progress,
            'progress_not_completed' => $progress,
            'progress_started' => $progress,
            'goal_id' => $goalId,
        ]);
    }
}
