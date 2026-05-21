<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

class Mission
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();
    }

    /**
     * Obtener todas las misiones de un usuario por tipo
     */
    public function getAllByUser(int $userId, ?string $type = null): array
    {
        $sql = "SELECT m.*, 
                       CASE 
                           WHEN m.reset_date IS NULL OR m.reset_date <= NOW() THEN 0
                           ELSE 1
                       END as needs_reset
                FROM missions m
                WHERE m.user_id = :user_id";
        
        if ($type) {
            $sql .= " AND m.type = :type";
        }
        
        $sql .= " ORDER BY m.completed ASC, m.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        
        if ($type) {
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Resetear misiones expiradas
        foreach ($missions as &$mission) {
            if ((int)$mission['needs_reset'] === 1) {
                $this->resetMission((int)$mission['id']);
                $mission['completed'] = 0;
                $mission['current_progress'] = 0;
            }
        }
        
        return $missions;
    }

    /**
     * Crear una nueva misión
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO missions (
                    user_id, title, description, type, icon,
                    target_value, target_unit, current_progress,
                    xp_reward, coins_reward, gems_reward
                ) VALUES (
                    :user_id, :title, :description, :type, :icon,
                    :target_value, :target_unit, :current_progress,
                    :xp_reward, :coins_reward, :gems_reward
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'],
            ':icon' => $data['icon'] ?? '🎯',
            ':target_value' => $data['target_value'],
            ':target_unit' => $data['target_unit'] ?? 'veces',
            ':current_progress' => $data['current_progress'] ?? 0,
            ':xp_reward' => $data['xp_reward'] ?? 50,
            ':coins_reward' => $data['coins_reward'] ?? 10,
            ':gems_reward' => $data['gems_reward'] ?? 0
        ]);
        
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Actualizar progreso de una misión
     */
    public function updateProgress(int $missionId, int $userId, int $progress): bool
    {
        // Obtener la misión actual
        $mission = $this->findById($missionId, $userId);
        if (!$mission) {
            return false;
        }
        
        $newProgress = (int)$mission['current_progress'] + $progress;
        $targetValue = (int)$mission['target_value'];
        
        // Si alcanza o supera el objetivo, marcar como completada
        if ($newProgress >= $targetValue) {
            $newProgress = $targetValue;
            $completed = 1;
            
            // Otorgar recompensas
            $this->addRewardsToUser($userId, $mission);
            
            // Registrar en logs
            $this->logMissionCompletion($missionId, $userId);
            
            // Actualizar racha
            $this->updateUserStreak($userId);
            
            // Calcular fecha de reset
            $resetDate = $this->calculateResetDate($mission['type']);
        } else {
            $completed = 0;
            $resetDate = null;
        }
        
        $sql = "UPDATE missions 
                SET current_progress = :progress,
                    completed = :completed,
                    reset_date = :reset_date
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':progress' => $newProgress,
            ':completed' => $completed,
            ':reset_date' => $resetDate,
            ':id' => $missionId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Obtener misión por ID
     */
    public function findById(int $id, int $userId): ?array
    {
        $sql = "SELECT * FROM missions WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        
        $mission = $stmt->fetch(PDO::FETCH_ASSOC);
        return $mission ?: null;
    }

    /**
     * Resetear una misión expirada
     */
    private function resetMission(int $missionId): bool
    {
        $sql = "UPDATE missions 
                SET current_progress = 0,
                    completed = 0,
                    reset_date = NULL
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $missionId]);
    }

    /**
     * Calcular fecha de reset según tipo de misión
     */
    private function calculateResetDate(string $type): string
    {
        $now = new DateTime();
        
        switch ($type) {
            case 'daily':
                $now->modify('+1 day')->setTime(0, 0, 0);
                break;
            case 'weekly':
                $now->modify('next monday')->setTime(0, 0, 0);
                break;
            case 'monthly':
                $now->modify('first day of next month')->setTime(0, 0, 0);
                break;
        }
        
        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Otorgar recompensas al usuario
     */
    private function addRewardsToUser(int $userId, array $mission): void
    {
        $xp = (int)$mission['xp_reward'];
        $coins = (int)$mission['coins_reward'];
        $gems = (int)$mission['gems_reward'];
        
        $sql = "UPDATE users 
                SET xp = xp + :xp,
                    points = points + :coins,
                    gems = gems + :gems
                WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':xp' => $xp,
            ':coins' => $coins,
            ':gems' => $gems,
            ':user_id' => $userId
        ]);
        
        // Verificar subida de nivel
        $this->checkLevelUp($userId);
    }

    /**
     * Verificar y actualizar nivel del usuario
     */
    private function checkLevelUp(int $userId): void
    {
        $sql = "SELECT level, xp FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return;
        
        $currentLevel = (int)$user['level'];
        $currentXp = (int)$user['xp'];
        $xpForNextLevel = $currentLevel * 100;
        
        if ($currentXp >= $xpForNextLevel) {
            $sql = "UPDATE users SET level = level + 1 WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
        }
    }

    /**
     * Registrar completación de misión
     */
    private function logMissionCompletion(int $missionId, int $userId): void
    {
        $sql = "INSERT INTO mission_logs (mission_id, user_id, completed_at) 
                VALUES (:mission_id, :user_id, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':mission_id' => $missionId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Actualizar racha del usuario
     */
    private function updateUserStreak(int $userId): void
    {
        $sql = "SELECT last_activity_date, current_streak FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return;
        
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        $lastActivity = $user['last_activity_date'] 
            ? new DateTime($user['last_activity_date']) 
            : null;
        
        if ($lastActivity) {
            $lastActivity->setTime(0, 0, 0);
            $diff = $today->diff($lastActivity)->days;
            
            if ($diff === 0) {
                // Mismo día, no hacer nada
                return;
            } elseif ($diff === 1) {
                // Día consecutivo, aumentar racha
                $newStreak = (int)$user['current_streak'] + 1;
            } else {
                // Se rompió la racha
                $newStreak = 1;
            }
        } else {
            $newStreak = 1;
        }
        
        $sql = "UPDATE users 
                SET current_streak = :streak,
                    last_activity_date = CURDATE()
                WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':streak' => $newStreak,
            ':user_id' => $userId
        ]);
    }

    /**
     * Obtener estadísticas de misiones del usuario
     */
    public function getStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_missions,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as total_completed,
                    SUM(CASE WHEN type = 'daily' THEN 1 ELSE 0 END) as daily_count,
                    SUM(CASE WHEN type = 'weekly' THEN 1 ELSE 0 END) as weekly_count,
                    SUM(CASE WHEN type = 'monthly' THEN 1 ELSE 0 END) as monthly_count
                FROM missions
                WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Eliminar misión
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM missions WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }

    /**
     * Actualizar misión
     */
    public function update(int $id, int $userId, array $data): bool
    {
        $sql = "UPDATE missions 
                SET title = :title,
                    description = :description,
                    icon = :icon,
                    target_value = :target_value,
                    target_unit = :target_unit
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? '🎯',
            ':target_value' => $data['target_value'],
            ':target_unit' => $data['target_unit'] ?? 'veces',
            ':id' => $id,
            ':user_id' => $userId
        ]);
    }
}
