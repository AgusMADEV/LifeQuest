<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class DailyObjective
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getConnection();
    }

    /**
     * Verifica si el objetivo diario ya fue completado hoy
     */
    public function isCompletedToday(int $userId): bool
    {
        $sql = "SELECT id 
                FROM daily_objectives 
                WHERE user_id = :user_id 
                  AND objective_date = CURDATE()
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch() !== false;
    }

    /**
     * Obtiene el registro del objetivo diario de hoy (si existe)
     */
    public function getTodayObjective(int $userId): ?array
    {
        $sql = "SELECT * 
                FROM daily_objectives 
                WHERE user_id = :user_id 
                  AND objective_date = CURDATE()
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Registra la completación del objetivo diario y otorga el XP bonus
     */
    public function complete(int $userId, int $tasksCompleted, int $tasksRequired, int $xpBonus): bool
    {
        // Verificar que no exista ya un registro de hoy
        if ($this->isCompletedToday($userId)) {
            return false; // Ya se completó hoy, no se puede volver a completar
        }

        $sql = "INSERT INTO daily_objectives (
                    user_id, objective_date, tasks_completed, tasks_required, 
                    xp_bonus_awarded, completed_at
                )
                VALUES (
                    :user_id, CURDATE(), :tasks_completed, :tasks_required,
                    :xp_bonus, NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'user_id' => $userId,
            'tasks_completed' => $tasksCompleted,
            'tasks_required' => $tasksRequired,
            'xp_bonus' => $xpBonus,
        ]);

        if ($result) {
            // Otorgar el XP bonus al usuario
            $this->awardXpBonus($userId, $xpBonus);
        }

        return $result;
    }

    /**
     * Otorga el XP bonus al usuario
     */
    private function awardXpBonus(int $userId, int $xpBonus): void
    {
        $sql = "UPDATE users 
                SET xp = xp + :xp_bonus,
                    level = GREATEST(1, FLOOR((xp + :xp_bonus) / 1000) + 1)
                WHERE id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'xp_bonus' => $xpBonus,
        ]);
    }

    /**
     * Obtiene el historial de objetivos completados
     */
    public function getHistory(int $userId, int $limit = 30): array
    {
        $sql = "SELECT * 
                FROM daily_objectives 
                WHERE user_id = :user_id 
                ORDER BY objective_date DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtiene la racha de días consecutivos completando objetivos
     */
    public function getStreak(int $userId): int
    {
        $sql = "SELECT objective_date 
                FROM daily_objectives 
                WHERE user_id = :user_id 
                ORDER BY objective_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($dates)) {
            return 0;
        }

        $streak = 0;
        $expectedDate = new DateTimeImmutable('today');

        foreach ($dates as $dateStr) {
            $objectiveDate = new DateTimeImmutable($dateStr);
            
            if ($objectiveDate->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
                $streak++;
                $expectedDate = $expectedDate->modify('-1 day');
            } else {
                break;
            }
        }

        return $streak;
    }
}
