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

    public function stats(int $userId): array
    {
        return $this->habitModel->getStats($userId);
    }

    public function weekLogs(int $userId, string $startDate, string $endDate): array
    {
        return $this->logsByRange($userId, $startDate, $endDate);
    }

    public function logsByRange(int $userId, string $startDate, string $endDate): array
    {
        return $this->habitModel->getLogsByRange($userId, $startDate, $endDate);
    }

    public function store(int $userId, array $data): array
    {
        $clean = $this->validate($userId, $data);

        if (!$clean['success']) {
            return $clean;
        }

        $ok = $this->habitModel->create($userId, $clean['data']);

        return [
            'success' => $ok,
            'message' => $ok ? 'Hábito creado correctamente.' : 'No se pudo crear el hábito.',
        ];
    }

    public function toggleToday(int $userId, int $habitId): array
    {
        if ($habitId <= 0) {
            return [
                'success' => false,
                'message' => 'Hábito no válido.',
            ];
        }

        return $this->habitModel->toggleToday($habitId, $userId);
    }

    private function validate(int $userId, array $data): array
    {
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'El nombre del hábito es obligatorio.',
            ];
        }

        if (mb_strlen($name) > 150) {
            return [
                'success' => false,
                'message' => 'El nombre no puede superar los 150 caracteres.',
            ];
        }

        $areaId = ((int) ($data['area_id'] ?? 0)) > 0 ? (int) $data['area_id'] : null;
        $goalId = ((int) ($data['goal_id'] ?? 0)) > 0 ? (int) $data['goal_id'] : null;

        if ($areaId !== null) {
            require_once __DIR__ . '/../Models/LifeArea.php';
            $lifeAreaModel = new LifeArea();

            if (!$lifeAreaModel->findByIdAndUser($areaId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'El área seleccionada no existe o no pertenece a tu usuario.',
                ];
            }
        }

        if ($goalId !== null) {
            require_once __DIR__ . '/../Models/Goal.php';
            $goalModel = new Goal();

            if (!$goalModel->findByIdAndUser($goalId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'La meta seleccionada no existe o no pertenece a tu usuario.',
                ];
            }
        }

        $allowedFrequencies = ['daily', 'weekly', 'custom'];
        $frequency = in_array(($data['frequency'] ?? ''), $allowedFrequencies, true) ? $data['frequency'] : 'daily';
        $negativeHabitsEnabled = defined('FEATURE_NEGATIVE_HABITS') ? (bool) FEATURE_NEGATIVE_HABITS : false;
        $isNegative = $negativeHabitsEnabled && isset($data['is_negative'])
            && in_array((string) $data['is_negative'], ['1', 'on', 'true'], true);
        $hpPenalty = $isNegative ? max(1, (int) ($data['hp_penalty'] ?? 15)) : 0;

        return [
            'success' => true,
            'data' => [
                'name' => $name,
                'description' => trim($data['description'] ?? '') ?: null,
                'area_id' => $areaId,
                'goal_id' => $goalId,
                'frequency' => $frequency,
                'xp_reward' => max(0, (int) ($data['xp_reward'] ?? 10)),
                'points_reward' => max(0, (int) ($data['points_reward'] ?? 5)),
                'is_negative' => $isNegative ? 1 : 0,
                'hp_penalty' => $hpPenalty,
            ],
        ];
    }
}
