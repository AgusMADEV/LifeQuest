<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Habit.php';

final class HabitController
{
    private Habit $habitModel;

    public function __construct()
    {
        $this->habitModel = new Habit();
    }

    public function index(int $userId): array
    {
        return $this->habitModel->getAllByUser($userId);
    }

    public function store(int $userId, array $data): array
    {
        $clean = $this->validate($data);
        if (!$clean['success']) return $clean;

        $created = $this->habitModel->create($userId, $clean['data']);
        return ['success' => $created, 'message' => $created ? 'Hábito creado correctamente.' : 'No se pudo crear el hábito.'];
    }

    public function update(int $userId, array $data): array
    {
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) return ['success' => false, 'message' => 'Hábito no válido.'];
        if (!$this->habitModel->findByIdAndUser($id, $userId)) {
            return ['success' => false, 'message' => 'El hábito no existe o no pertenece a tu usuario.'];
        }

        $clean = $this->validate($data);
        if (!$clean['success']) return $clean;

        $updated = $this->habitModel->update($id, $userId, $clean['data']);
        return ['success' => $updated, 'message' => $updated ? 'Hábito actualizado correctamente.' : 'No se pudo actualizar el hábito.'];
    }

    public function destroy(int $userId, int $id): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Hábito no válido.'];
        if (!$this->habitModel->findByIdAndUser($id, $userId)) {
            return ['success' => false, 'message' => 'El hábito no existe o no pertenece a tu usuario.'];
        }

        $deleted = $this->habitModel->delete($id, $userId);
        return ['success' => $deleted, 'message' => $deleted ? 'Hábito eliminado correctamente.' : 'No se pudo eliminar el hábito.'];
    }

    public function completeToday(int $userId, int $id): array
    {
        return $this->habitModel->completeToday($id, $userId);
    }

    private function validate(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $frequency = $data['frequency'] ?? 'daily';
        $areaId = (int)($data['area_id'] ?? 0);
        $goalId = (int)($data['goal_id'] ?? 0);
        $active = isset($data['active']) ? 1 : 0;
        $xpReward = max(0, (int)($data['xp_reward'] ?? 10));
        $pointsReward = max(0, (int)($data['points_reward'] ?? 5));

        if ($name === '') return ['success' => false, 'message' => 'El nombre del hábito es obligatorio.'];
        if (mb_strlen($name) > 150) return ['success' => false, 'message' => 'El nombre no puede superar los 150 caracteres.'];

        if (!in_array($frequency, ['daily', 'weekly', 'custom'], true)) $frequency = 'daily';

        return [
            'success' => true,
            'data' => [
                'area_id' => $areaId > 0 ? $areaId : null,
                'goal_id' => $goalId > 0 ? $goalId : null,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'frequency' => $frequency,
                'active' => $active,
                'xp_reward' => $xpReward,
                'points_reward' => $pointsReward,
            ]
        ];
    }
}
