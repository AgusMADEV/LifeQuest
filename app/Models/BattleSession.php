<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class BattleSession
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function getPendingTasks(int $userId): array
    {
        $sql = "SELECT
                    tasks.*,
                    projects.title AS project_title,
                    goals.title AS goal_title,
                    life_areas.name AS area_name,
                    life_areas.icon AS area_icon,
                    life_areas.color AS area_color
                FROM tasks
                LEFT JOIN projects ON tasks.project_id = projects.id
                LEFT JOIN goals ON tasks.goal_id = goals.id
                LEFT JOIN life_areas ON tasks.area_id = life_areas.id
                WHERE tasks.user_id = :user_id
                  AND tasks.status IN ('pending', 'in_progress')
                ORDER BY
                    FIELD(tasks.priority, 'critical', 'high', 'medium', 'low'),
                    tasks.due_date IS NULL,
                    tasks.due_date ASC,
                    tasks.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getRecentSessions(int $userId, int $limit = 6): array
    {
        $sql = "SELECT battle_sessions.*, tasks.title AS task_title
                FROM battle_sessions
                LEFT JOIN tasks ON battle_sessions.task_id = tasks.id
                WHERE battle_sessions.user_id = :user_id
                ORDER BY battle_sessions.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findTaskByIdAndUser(int $taskId, int $userId): ?array
    {
        $sql = "SELECT *
                FROM tasks
                WHERE id = :id AND user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $taskId,
            'user_id' => $userId,
        ]);

        $task = $stmt->fetch();

        return $task ?: null;
    }

    public function createSession(int $userId, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $taskId = $data['task_id'];
            $result = $data['result'];
            $xpEarned = $data['xp_earned'];
            $pointsEarned = $data['points_earned'];

            $sql = "INSERT INTO battle_sessions (
                        user_id, task_id, title, duration_minutes, result,
                        notes, xp_earned, points_earned, started_at, ended_at
                    )
                    VALUES (
                        :user_id, :task_id, :title, :duration_minutes, :result,
                        :notes, :xp_earned, :points_earned, :started_at, :ended_at
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'task_id' => $taskId,
                'title' => $data['title'],
                'duration_minutes' => $data['duration_minutes'],
                'result' => $result,
                'notes' => $data['notes'],
                'xp_earned' => $xpEarned,
                'points_earned' => $pointsEarned,
                'started_at' => $data['started_at'],
                'ended_at' => $data['ended_at'],
            ]);

            if ($xpEarned > 0 || $pointsEarned > 0) {
                $this->addRewardsToUser($userId, $xpEarned, $pointsEarned);
            }

            if ($taskId !== null) {
                if ($result === 'completed') {
                    $this->completeTask($taskId, $userId);
                } elseif ($result === 'partial') {
                    $this->markTaskInProgress($taskId, $userId);
                }

                $task = $this->findTaskByIdAndUser($taskId, $userId);

                if ($task) {
                    if (!empty($task['project_id'])) {
                        $this->recalculateProjectProgress((int) $task['project_id'], $userId);
                    }

                    if (!empty($task['goal_id'])) {
                        $this->recalculateGoalProgress((int) $task['goal_id'], $userId);
                    }
                }
            }

            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            return false;
        }
    }

    private function addRewardsToUser(int $userId, int $xp, int $points): void
    {
        $levelIncrementSql = "UPDATE users
                              SET xp = xp + :xp,
                                  points = points + :points,
                                  level = 1 + FLOOR((xp + :xp_for_level) / 500)
                              WHERE id = :user_id";

        $stmt = $this->db->prepare($levelIncrementSql);
        $stmt->execute([
            'xp' => $xp,
            'xp_for_level' => $xp,
            'points' => $points,
            'user_id' => $userId,
        ]);
    }

    private function completeTask(int $taskId, int $userId): void
    {
        $sql = "UPDATE tasks
                SET status = 'completed',
                    completed_at = COALESCE(completed_at, NOW())
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $taskId,
            'user_id' => $userId,
        ]);
    }

    private function markTaskInProgress(int $taskId, int $userId): void
    {
        $sql = "UPDATE tasks
                SET status = 'in_progress'
                WHERE id = :id
                  AND user_id = :user_id
                  AND status = 'pending'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $taskId,
            'user_id' => $userId,
        ]);
    }

    private function recalculateProjectProgress(int $projectId, int $userId): void
    {
        $sql = "SELECT
                    COUNT(*) AS total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
                FROM tasks
                WHERE project_id = :project_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'project_id' => $projectId,
            'user_id' => $userId,
        ]);

        $data = $stmt->fetch();
        $total = (int) ($data['total_tasks'] ?? 0);
        $completed = (int) ($data['completed_tasks'] ?? 0);
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $update = $this->db->prepare(
            "UPDATE projects
             SET progress = :progress,
                 status = CASE WHEN :progress_value >= 100 THEN 'completed' ELSE status END
             WHERE id = :id AND user_id = :user_id"
        );

        $update->execute([
            'progress' => $progress,
            'progress_value' => $progress,
            'id' => $projectId,
            'user_id' => $userId,
        ]);
    }

    private function recalculateGoalProgress(int $goalId, int $userId): void
    {
        $sql = "SELECT
                    COUNT(*) AS total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
                FROM tasks
                WHERE goal_id = :goal_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'goal_id' => $goalId,
            'user_id' => $userId,
        ]);

        $data = $stmt->fetch();
        $total = (int) ($data['total_tasks'] ?? 0);
        $completed = (int) ($data['completed_tasks'] ?? 0);
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $update = $this->db->prepare(
            "UPDATE goals
             SET progress = :progress,
                 status = CASE WHEN :progress_value >= 100 THEN 'completed' ELSE status END
             WHERE id = :id AND user_id = :user_id"
        );

        $update->execute([
            'progress' => $progress,
            'progress_value' => $progress,
            'id' => $goalId,
            'user_id' => $userId,
        ]);
    }
}
