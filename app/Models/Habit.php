<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class Habit
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT habits.*, life_areas.name AS area_name, life_areas.icon AS area_icon,
                       life_areas.color AS area_color, goals.title AS goal_title,
                       CASE WHEN habit_logs.id IS NULL THEN 0 ELSE 1 END AS completed_today
                FROM habits
                LEFT JOIN life_areas ON habits.area_id = life_areas.id
                LEFT JOIN goals ON habits.goal_id = goals.id
                LEFT JOIN habit_logs ON habit_logs.habit_id = habits.id
                    AND habit_logs.completed_date = CURDATE()
                WHERE habits.user_id = :user_id
                ORDER BY habits.active DESC, habits.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM habits WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $habit = $stmt->fetch();
        return $habit ?: null;
    }

    public function create(int $userId, array $data): bool
    {
        $sql = "INSERT INTO habits (user_id, area_id, goal_id, name, description, frequency, active, xp_reward, points_reward)
                VALUES (:user_id, :area_id, :goal_id, :name, :description, :frequency, :active, :xp_reward, :points_reward)";
        return $this->db->prepare($sql)->execute([
            'user_id' => $userId,
            'area_id' => $data['area_id'],
            'goal_id' => $data['goal_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'frequency' => $data['frequency'],
            'active' => $data['active'],
            'xp_reward' => $data['xp_reward'],
            'points_reward' => $data['points_reward'],
        ]);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $sql = "UPDATE habits
                SET area_id = :area_id, goal_id = :goal_id, name = :name, description = :description,
                    frequency = :frequency, active = :active, xp_reward = :xp_reward, points_reward = :points_reward
                WHERE id = :id AND user_id = :user_id";
        return $this->db->prepare($sql)->execute([
            'id' => $id,
            'user_id' => $userId,
            'area_id' => $data['area_id'],
            'goal_id' => $data['goal_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'frequency' => $data['frequency'],
            'active' => $data['active'],
            'xp_reward' => $data['xp_reward'],
            'points_reward' => $data['points_reward'],
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->db->prepare("DELETE FROM habits WHERE id = :id AND user_id = :user_id")
            ->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function completeToday(int $habitId, int $userId): array
    {
        $habit = $this->findByIdAndUser($habitId, $userId);

        if (!$habit) {
            return ['success' => false, 'message' => 'El hábito no existe o no pertenece a tu usuario.'];
        }

        if ((int)$habit['active'] !== 1) {
            return ['success' => false, 'message' => 'Este hábito está desactivado.'];
        }

        if ($this->isCompletedToday($habitId, $userId)) {
            return ['success' => false, 'message' => 'Este hábito ya está completado hoy.'];
        }

        $this->db->beginTransaction();

        try {
            $this->db->prepare("INSERT INTO habit_logs (habit_id, user_id, completed_date) VALUES (:habit_id, :user_id, CURDATE())")
                ->execute(['habit_id' => $habitId, 'user_id' => $userId]);

            $this->updateHabitStreak($habitId, $userId);
            $this->addRewardsToUser($userId, (int)$habit['xp_reward'], (int)$habit['points_reward']);
            $this->updateUserCurrentStreak($userId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Hábito completado. Has ganado +' . (int)$habit['xp_reward'] . ' XP y +' . (int)$habit['points_reward'] . ' LifeCoins.'
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'No se pudo completar el hábito.'];
        }
    }

    private function isCompletedToday(int $habitId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM habit_logs WHERE habit_id = :habit_id AND user_id = :user_id AND completed_date = CURDATE() LIMIT 1");
        $stmt->execute(['habit_id' => $habitId, 'user_id' => $userId]);
        return (bool)$stmt->fetch();
    }

    private function updateHabitStreak(int $habitId, int $userId): void
    {
        $stmt = $this->db->prepare("SELECT completed_date FROM habit_logs WHERE habit_id = :habit_id AND user_id = :user_id ORDER BY completed_date DESC");
        $stmt->execute(['habit_id' => $habitId, 'user_id' => $userId]);

        $dates = array_column($stmt->fetchAll(), 'completed_date');
        $currentStreak = 0;
        $expected = new DateTimeImmutable('today');

        foreach ($dates as $date) {
            $completedDate = new DateTimeImmutable($date);
            if ($completedDate->format('Y-m-d') === $expected->format('Y-m-d')) {
                $currentStreak++;
                $expected = $expected->sub(new DateInterval('P1D'));
            } else {
                break;
            }
        }

        $this->db->prepare("UPDATE habits SET current_streak = :current_streak, best_streak = GREATEST(best_streak, :best_streak) WHERE id = :id AND user_id = :user_id")
            ->execute(['current_streak' => $currentStreak, 'best_streak' => $currentStreak, 'id' => $habitId, 'user_id' => $userId]);
    }

    private function updateUserCurrentStreak(int $userId): void
    {
        $stmt = $this->db->prepare("SELECT completed_date FROM habit_logs WHERE user_id = :user_id GROUP BY completed_date ORDER BY completed_date DESC");
        $stmt->execute(['user_id' => $userId]);

        $dates = array_column($stmt->fetchAll(), 'completed_date');
        $currentStreak = 0;
        $expected = new DateTimeImmutable('today');

        foreach ($dates as $date) {
            $completedDate = new DateTimeImmutable($date);
            if ($completedDate->format('Y-m-d') === $expected->format('Y-m-d')) {
                $currentStreak++;
                $expected = $expected->sub(new DateInterval('P1D'));
            } else {
                break;
            }
        }

        $this->db->prepare("UPDATE users SET current_streak = :current_streak WHERE id = :user_id")
            ->execute(['current_streak' => $currentStreak, 'user_id' => $userId]);
    }

    private function addRewardsToUser(int $userId, int $xp, int $points): void
    {
        $sql = "UPDATE users
                SET xp = xp + :xp,
                    points = points + :points,
                    level = 1 + FLOOR((xp + :xp_for_level) / 500)
                WHERE id = :user_id";
        $this->db->prepare($sql)->execute([
            'xp' => $xp,
            'xp_for_level' => $xp,
            'points' => $points,
            'user_id' => $userId,
        ]);
    }
}
