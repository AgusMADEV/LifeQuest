<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';
require_once __DIR__ . '/AreaProgression.php';
require_once __DIR__ . '/Badge.php';
require_once __DIR__ . '/DailyObjective.php';

final class Task
{
    private PDO $db;
    private AreaProgression $areaProgression;

    public function __construct()
    {
        $this->db = Connection::getConnection();
        $this->areaProgression = new AreaProgression($this->db);
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
                                    AND DATE(COALESCE(tasks.due_date, tasks.created_at)) = CURDATE()
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

    public function getUpcomingByUser(int $userId, int $limit = 5): array
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
                  AND tasks.status IN ('pending', 'in_progress')
                  AND tasks.due_date IS NOT NULL
                  AND DATE(tasks.due_date) > CURDATE()
                ORDER BY tasks.due_date ASC, tasks.priority DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getDistributionByArea(int $userId): array
    {
        $sql = "SELECT 
                    life_areas.id AS area_id,
                    life_areas.name AS area_name,
                    life_areas.color AS area_color,
                    life_areas.icon AS area_icon,
                    COUNT(tasks.id) AS task_count
                FROM tasks
                INNER JOIN life_areas ON tasks.area_id = life_areas.id
                WHERE tasks.user_id = :user_id
                  AND tasks.status IN ('pending', 'in_progress', 'completed')
                GROUP BY life_areas.id, life_areas.name, life_areas.color, life_areas.icon
                HAVING task_count > 0
                ORDER BY task_count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $distribution = $stmt->fetchAll();
        
        // Calcular total y porcentajes
        $total = array_sum(array_column($distribution, 'task_count'));
        
        if ($total === 0) {
            return [];
        }

        foreach ($distribution as &$area) {
            $area['percentage'] = round(((int) $area['task_count'] / $total) * 100, 1);
        }

        return $distribution;
    }

    public function getCompletedDatesByRange(int $userId, string $startDate, string $endDate): array
    {
        $sql = "SELECT DISTINCT DATE(COALESCE(completed_at, created_at)) AS completed_date
                FROM tasks
                WHERE user_id = :user_id
                  AND status = 'completed'
                  AND DATE(COALESCE(completed_at, created_at)) BETWEEN :start_date AND :end_date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $dates = [];

        foreach ($stmt->fetchAll() as $row) {
            $date = (string) ($row['completed_date'] ?? '');

            if ($date !== '') {
                $dates[$date] = true;
            }
        }

        return $dates;
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
        $ok = $stmt->execute(['user_id' => $userId] + $data);

        if ($ok) {
            $this->refreshRelatedProgress($data['project_id'], $data['goal_id']);
        }

        return $ok;
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

            $this->areaProgression->addXp(
                $userId,
                isset($task['area_id']) ? (int) $task['area_id'] : null,
                (int) $task['xp_reward']
            );

            $this->refreshRelatedProgress($task['project_id'] ?? null, $task['goal_id'] ?? null);

            // Verificar y completar objetivo diario (antes del commit, dentro de la transacción)
            $objectiveBonusXp = $this->checkAndAwardDailyObjective($userId);

            $this->db->commit();

            $badgeModel = new Badge($this->db);
            $newlyUnlockedBadges = $badgeModel->syncAndCollectNewlyUnlocked($userId);
            $this->pushBadgeUnlockToast($newlyUnlockedBadges);

            // Mensaje de éxito
            $message = 'Misión completada. +' . (int) $task['xp_reward'] . ' XP y +' . (int) $task['points_reward'] . ' LifeCoins.';
            
            if ($objectiveBonusXp > 0) {
                $message .= ' ¡Objetivo diario completado! +' . $objectiveBonusXp . ' XP de bonus.';
            }

            return [
                'success' => true,
                'message' => $message
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

    private function pushBadgeUnlockToast(array $badges): void
    {
        if (empty($badges)) {
            return;
        }

        $existing = $_SESSION['badge_unlock_toasts'] ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }

        $already = [];
        foreach ($existing as $badge) {
            $code = (string) ($badge['code'] ?? '');
            if ($code !== '') {
                $already[$code] = true;
            }
        }

        foreach ($badges as $badge) {
            $code = (string) ($badge['code'] ?? '');
            if ($code === '' || isset($already[$code])) {
                continue;
            }

            $existing[] = [
                'code' => $code,
                'title' => (string) ($badge['title'] ?? 'Insignia'),
                'icon' => (string) ($badge['icon'] ?? '🏅'),
            ];
            $already[$code] = true;
        }

        $_SESSION['badge_unlock_toasts'] = $existing;
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

        $goalStmt = $this->db->prepare(
            "SELECT goal_id
             FROM projects
             WHERE id = :project_id
             LIMIT 1"
        );
        $goalStmt->execute(['project_id' => $projectId]);
        $project = $goalStmt->fetch();

        if (isset($project['goal_id']) && $project['goal_id'] !== null) {
            $this->refreshGoalProgress((int) $project['goal_id']);
        }
    }

    private function refreshGoalProgress(int $goalId): void
    {
        $projectStatsStmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(AVG(progress), 0) AS avg_progress
             FROM projects
             WHERE goal_id = :goal_id
               AND status <> 'cancelled'"
        );
        $projectStatsStmt->execute(['goal_id' => $goalId]);
        $projectStats = $projectStatsStmt->fetch();

        $projectTotal = (int) ($projectStats['total'] ?? 0);

        if ($projectTotal > 0) {
            $progress = (int) round((float) ($projectStats['avg_progress'] ?? 0));
        } else {
            $taskStatsStmt = $this->db->prepare(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
                 FROM tasks
                 WHERE goal_id = :goal_id
                   AND status <> 'cancelled'"
            );
            $taskStatsStmt->execute(['goal_id' => $goalId]);
            $taskStats = $taskStatsStmt->fetch();

            $taskTotal = (int) ($taskStats['total'] ?? 0);
            $taskCompleted = (int) ($taskStats['completed'] ?? 0);
            $progress = $taskTotal > 0 ? (int) round(($taskCompleted / $taskTotal) * 100) : 0;
        }

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

    /**
     * Verifica si el usuario completó su objetivo diario y otorga el XP bonus
     * Retorna el XP bonus otorgado (0 si no se completó)
     * IMPORTANTE: Se ejecuta dentro de la transacción activa
     */
    private function checkAndAwardDailyObjective(int $userId): int
    {
        // Verificar si ya se completó hoy (usando la misma conexión/transacción)
        $checkStmt = $this->db->prepare(
            "SELECT id FROM daily_objectives 
             WHERE user_id = :user_id AND objective_date = CURDATE()
             LIMIT 1"
        );
        $checkStmt->execute(['user_id' => $userId]);

        if ($checkStmt->fetch()) {
            return 0; // Ya completado hoy
        }

        // Obtener todas las tareas de hoy
        $todayTasks = $this->getTodayByUser($userId, 100);
        
        if (empty($todayTasks)) {
            return 0;
        }

        // Contar tareas completadas
        $completedCount = 0;
        $totalXp = 0;

        foreach ($todayTasks as $task) {
            if (($task['status'] ?? '') === 'completed') {
                $completedCount++;
            }
            $totalXp += (int) ($task['xp_reward'] ?? 0);
        }

        $totalCount = count($todayTasks);
        $requiredCount = max(4, $totalCount);

        // Si completó todas las tareas del día, otorgar bonus
        if ($completedCount >= $requiredCount && $completedCount === $totalCount) {
            // Calcular bonus (25% del XP total, mínimo 100, máximo 500)
            $xpBonus = max(100, (int) round($totalXp * 0.25));
            $xpBonus = min($xpBonus, 500);

            // Registrar completación del objetivo
            $insertStmt = $this->db->prepare(
                "INSERT INTO daily_objectives (
                    user_id, objective_date, tasks_completed, tasks_required, 
                    xp_bonus_awarded, completed_at
                )
                VALUES (
                    :user_id, CURDATE(), :tasks_completed, :tasks_required,
                    :xp_bonus, NOW()
                )"
            );

            $insertStmt->execute([
                'user_id' => $userId,
                'tasks_completed' => $completedCount,
                'tasks_required' => $requiredCount,
                'xp_bonus' => $xpBonus,
            ]);

            // Otorgar el XP bonus al usuario
            $updateStmt = $this->db->prepare(
                "UPDATE users 
                 SET xp = xp + :xp_bonus,
                     level = GREATEST(1, FLOOR((xp + :xp_bonus_calc) / 1000) + 1)
                 WHERE id = :user_id"
            );

            $updateStmt->execute([
                'user_id' => $userId,
                'xp_bonus' => $xpBonus,
                'xp_bonus_calc' => $xpBonus,
            ]);

            return $xpBonus;
        }

        return 0;
    }
}

