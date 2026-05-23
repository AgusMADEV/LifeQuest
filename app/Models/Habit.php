<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';
require_once __DIR__ . '/AreaProgression.php';

final class Habit
{
    private PDO $db;
    private array $columnCache = [];
    private AreaProgression $areaProgression;

    public function __construct()
    {
        $this->db = Connection::getConnection();
        $this->areaProgression = new AreaProgression($this->db);
    }

    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT habits.*, life_areas.name AS area_name, life_areas.icon AS area_icon
                FROM habits
                LEFT JOIN life_areas ON habits.area_id = life_areas.id
                WHERE habits.user_id = :user_id
                ORDER BY habits.active DESC, habits.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function create(int $userId, array $data): bool
    {
        $hasNegativeColumns = $this->hasColumn('habits', 'is_negative') && $this->hasColumn('habits', 'hp_penalty');

        if ($hasNegativeColumns) {
            $sql = "INSERT INTO habits (
                        user_id, area_id, goal_id, name, description, frequency,
                        current_streak, best_streak, active, xp_reward, points_reward, is_negative, hp_penalty
                    )
                    VALUES (
                        :user_id, :area_id, :goal_id, :name, :description, :frequency,
                        0, 0, 1, :xp_reward, :points_reward, :is_negative, :hp_penalty
                    )";
        } else {
            $sql = "INSERT INTO habits (
                        user_id, area_id, goal_id, name, description, frequency,
                        current_streak, best_streak, active, xp_reward, points_reward
                    )
                    VALUES (
                        :user_id, :area_id, :goal_id, :name, :description, :frequency,
                        0, 0, 1, :xp_reward, :points_reward
                    )";
        }

        $stmt = $this->db->prepare($sql);

        $params = [
            'user_id' => $userId,
            'area_id' => $data['area_id'],
            'goal_id' => $data['goal_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'frequency' => $data['frequency'],
            'xp_reward' => $data['xp_reward'],
            'points_reward' => $data['points_reward'],
        ];

        if ($hasNegativeColumns) {
            $params['is_negative'] = (int) ($data['is_negative'] ?? 0);
            $params['hp_penalty'] = (int) ($data['hp_penalty'] ?? 0);
        }

        return $stmt->execute($params);
    }

    public function getWeekLogs(int $userId, string $startDate, string $endDate): array
    {
        return $this->getLogsByRange($userId, $startDate, $endDate);
    }

    public function getLogsByRange(int $userId, string $startDate, string $endDate): array
    {
        $sql = "SELECT habit_logs.habit_id, habit_logs.completed_date
                FROM habit_logs
                INNER JOIN habits ON habits.id = habit_logs.habit_id
                                WHERE habit_logs.user_id = :logs_user_id
                                    AND habits.user_id = :habits_user_id
                  AND habit_logs.completed_date BETWEEN :start_date AND :end_date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
                        'logs_user_id' => $userId,
                        'habits_user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $map = [];

        foreach ($stmt->fetchAll() as $row) {
            $habitId = (int) $row['habit_id'];
            $date = (string) $row['completed_date'];
            $map[$habitId][$date] = true;
        }

        return $map;
    }

    public function getStats(int $userId): array
    {
        $activeStmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, MAX(best_streak) AS best_streak, SUM(xp_reward) AS daily_xp
             FROM habits
             WHERE user_id = :user_id AND active = 1"
        );
        $activeStmt->execute(['user_id' => $userId]);
        $active = $activeStmt->fetch() ?: [];

        $monthKey = date('Y-m');
        $monthStmt = $this->db->prepare(
            "SELECT COUNT(*) AS completed_month
             FROM habit_logs
             WHERE user_id = :user_id
               AND DATE_FORMAT(completed_date, '%Y-%m') = :month_key"
        );
        $monthStmt->execute([
            'user_id' => $userId,
            'month_key' => $monthKey,
        ]);
        $month = $monthStmt->fetch() ?: [];

        return [
            'active_total' => (int) ($active['total'] ?? 0),
            'best_streak' => (int) ($active['best_streak'] ?? 0),
            'completed_month' => (int) ($month['completed_month'] ?? 0),
            'daily_xp' => (int) ($active['daily_xp'] ?? 0),
        ];
    }

    public function toggleToday(int $habitId, int $userId): array
    {
        $habit = $this->findByIdAndUser($habitId, $userId);

        if (!$habit) {
            return ['success' => false, 'message' => 'Hábito no válido.'];
        }

        if (!(bool) $habit['active']) {
            return ['success' => false, 'message' => 'El hábito está inactivo.'];
        }

        $today = date('Y-m-d');

        try {
            $this->db->beginTransaction();

            $existsStmt = $this->db->prepare(
                "SELECT id
                 FROM habit_logs
                 WHERE habit_id = :habit_id
                   AND user_id = :user_id
                   AND completed_date = :completed_date
                 LIMIT 1"
            );
            $existsStmt->execute([
                'habit_id' => $habitId,
                'user_id' => $userId,
                'completed_date' => $today,
            ]);

            $existing = $existsStmt->fetch();
            $xpDelta = (int) $habit['xp_reward'];
            $pointsDelta = (int) $habit['points_reward'];
            $negativeHabitsEnabled = defined('FEATURE_NEGATIVE_HABITS') ? (bool) FEATURE_NEGATIVE_HABITS : false;
            $habitIsNegative = $negativeHabitsEnabled && (bool) ($habit['is_negative'] ?? false);
            $hpPenalty = max(0, (int) ($habit['hp_penalty'] ?? 0));

            if ($existing) {
                $deleteStmt = $this->db->prepare(
                    "DELETE FROM habit_logs
                     WHERE id = :id"
                );
                $deleteStmt->execute(['id' => (int) $existing['id']]);

                if ($habitIsNegative) {
                    $this->applyUserHpDelta($userId, $hpPenalty);
                    $message = $hpPenalty > 0
                        ? 'Hábito de riesgo desmarcado. Recuperaste +' . $hpPenalty . ' HP.'
                        : 'Hábito de riesgo desmarcado para hoy.';
                } else {
                    $this->applyUserRewards($userId, -$xpDelta, -$pointsDelta, isset($habit['area_id']) ? (int) $habit['area_id'] : null);
                    $message = 'Hábito desmarcado para hoy.';
                }
            } else {
                $insertStmt = $this->db->prepare(
                    "INSERT INTO habit_logs (habit_id, user_id, completed_date)
                     VALUES (:habit_id, :user_id, :completed_date)"
                );
                $insertStmt->execute([
                    'habit_id' => $habitId,
                    'user_id' => $userId,
                    'completed_date' => $today,
                ]);

                if ($habitIsNegative) {
                    $this->applyUserHpDelta($userId, -$hpPenalty);
                    $message = $hpPenalty > 0
                        ? 'Hábito de riesgo registrado. -' . $hpPenalty . ' HP.'
                        : 'Hábito de riesgo registrado para hoy.';
                } else {
                    $this->applyUserRewards($userId, $xpDelta, $pointsDelta, isset($habit['area_id']) ? (int) $habit['area_id'] : null);
                    $message = 'Hábito completado para hoy. +' . $xpDelta . ' XP y +' . $pointsDelta . ' LifeCoins.';
                }
            }

            if ($habitIsNegative) {
                $this->resetHabitStreaks($habitId, $userId);
            } else {
                $this->recalculateStreaks($habitId, $userId);
            }
            $this->syncUserCurrentStreak($userId);
            $this->db->commit();

            return ['success' => true, 'message' => $message];
        } catch (Throwable $exception) {
            $this->db->rollBack();

            return ['success' => false, 'message' => 'No se pudo actualizar el hábito.'];
        }
    }

    public function findByIdAndUser(int $habitId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM habits
             WHERE id = :id AND user_id = :user_id
             LIMIT 1"
        );
        $stmt->execute([
            'id' => $habitId,
            'user_id' => $userId,
        ]);

        $habit = $stmt->fetch();

        return $habit ?: null;
    }

    private function applyUserRewards(int $userId, int $xpDelta, int $pointsDelta, ?int $areaId = null): void
    {
        $stmt = $this->db->prepare(
            "SELECT xp, points
             FROM users
             WHERE id = :user_id
             LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch() ?: ['xp' => 0, 'points' => 0];

        $newXp = max(0, ((int) $user['xp']) + $xpDelta);
        $newPoints = max(0, ((int) $user['points']) + $pointsDelta);
        $newLevel = max(1, intdiv($newXp, 1000) + 1);

        $update = $this->db->prepare(
            "UPDATE users
             SET xp = :xp,
                 points = :points,
                 level = :level
             WHERE id = :user_id"
        );

        $update->execute([
            'xp' => $newXp,
            'points' => $newPoints,
            'level' => $newLevel,
            'user_id' => $userId,
        ]);

        $this->areaProgression->addXp($userId, $areaId, $xpDelta);
    }

    private function applyUserHpDelta(int $userId, int $hpDelta): void
    {
        if ($hpDelta === 0 || !$this->hasColumn('users', 'hp')) {
            return;
        }

        $baseHp = defined('PLAYER_BASE_HP') ? (int) PLAYER_BASE_HP : 1000;
        $hasMaxHp = $this->hasColumn('users', 'max_hp');

        $select = $hasMaxHp
            ? "SELECT hp, max_hp FROM users WHERE id = :user_id LIMIT 1"
            : "SELECT hp FROM users WHERE id = :user_id LIMIT 1";

        $stmt = $this->db->prepare($select);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch() ?: ['hp' => $baseHp, 'max_hp' => $baseHp];

        $maxHp = $hasMaxHp ? max(1, (int) ($user['max_hp'] ?? $baseHp)) : $baseHp;
        $currentHp = max(0, min($maxHp, (int) ($user['hp'] ?? $maxHp)));
        $newHp = max(0, min($maxHp, $currentHp + $hpDelta));

        $update = $this->db->prepare(
            "UPDATE users
             SET hp = :hp
             WHERE id = :user_id"
        );

        $update->execute([
            'hp' => $newHp,
            'user_id' => $userId,
        ]);
    }

    private function recalculateStreaks(int $habitId, int $userId): void
    {
        $stmt = $this->db->prepare(
            "SELECT completed_date
             FROM habit_logs
             WHERE habit_id = :habit_id
               AND user_id = :user_id
             ORDER BY completed_date DESC"
        );
        $stmt->execute([
            'habit_id' => $habitId,
            'user_id' => $userId,
        ]);

        $dates = array_map(
            static fn(array $row): string => (string) $row['completed_date'],
            $stmt->fetchAll()
        );

        $currentStreak = 0;
        $bestStreak = 0;

        if (!empty($dates)) {
            $dateSet = array_fill_keys($dates, true);

            // La racha actual se ancla al ultimo check-in realizado,
            // no necesariamente al dia de hoy.
            $cursor = new DateTimeImmutable($dates[0]);
            while (isset($dateSet[$cursor->format('Y-m-d')])) {
                $currentStreak++;
                $cursor = $cursor->modify('-1 day');
            }

            $sorted = $dates;
            sort($sorted);

            $run = 0;
            $prev = null;

            foreach ($sorted as $date) {
                $day = new DateTimeImmutable($date);

                if ($prev === null || $prev->modify('+1 day')->format('Y-m-d') === $day->format('Y-m-d')) {
                    $run++;
                } else {
                    $run = 1;
                }

                if ($run > $bestStreak) {
                    $bestStreak = $run;
                }

                $prev = $day;
            }
        }

        $update = $this->db->prepare(
            "UPDATE habits
             SET current_streak = :current_streak,
                 best_streak = :best_streak
             WHERE id = :habit_id AND user_id = :user_id"
        );

        $update->execute([
            'current_streak' => $currentStreak,
            'best_streak' => $bestStreak,
            'habit_id' => $habitId,
            'user_id' => $userId,
        ]);
    }

    private function syncUserCurrentStreak(int $userId): void
    {
                $excludeNegative = $this->hasColumn('habits', 'is_negative');
                $sql = "SELECT COALESCE(MAX(current_streak), 0) AS current_streak
                                FROM habits
                                WHERE user_id = :user_id
                                    AND active = 1";

                if ($excludeNegative) {
                        $sql .= "
                                    AND (is_negative = 0 OR is_negative IS NULL)";
                }

                $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $globalStreak = (int) (($stmt->fetch()['current_streak'] ?? 0));

        $update = $this->db->prepare(
            "UPDATE users
             SET current_streak = :current_streak
             WHERE id = :user_id"
        );

        $update->execute([
            'current_streak' => $globalStreak,
            'user_id' => $userId,
        ]);
    }

    private function resetHabitStreaks(int $habitId, int $userId): void
    {
        $update = $this->db->prepare(
            "UPDATE habits
             SET current_streak = 0,
                 best_streak = 0
             WHERE id = :habit_id AND user_id = :user_id"
        );

        $update->execute([
            'habit_id' => $habitId,
            'user_id' => $userId,
        ]);
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
