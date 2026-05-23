<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Task.php';
require_once __DIR__ . '/../Support/RewardCalculator.php';

final class TaskController
{
    private Task $taskModel;

    public function __construct()
    {
        $this->taskModel = new Task();
    }

    public function index(int $userId): array
    {
        return $this->taskModel->getAllByUser($userId);
    }

    public function store(int $userId, array $data): array
    {
        $clean = $this->validate($userId, $data);

        if (!$clean['success']) {
            return $clean;
        }

        $ok = $this->taskModel->create($userId, $clean['data']);

        return [
            'success' => $ok,
            'message' => $ok ? 'Misión creada correctamente.' : 'No se pudo crear la misión.'
        ];
    }

    public function update(int $userId, array $data): array
    {
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0 || !$this->taskModel->findByIdAndUser($id, $userId)) {
            return [
                'success' => false,
                'message' => 'Misión no válida.'
            ];
        }

        $clean = $this->validate($userId, $data);

        if (!$clean['success']) {
            return $clean;
        }

        $ok = $this->taskModel->update($id, $userId, $clean['data']);

        return [
            'success' => $ok,
            'message' => $ok ? 'Misión actualizada correctamente.' : 'No se pudo actualizar la misión.'
        ];
    }

    public function destroy(int $userId, int $id): array
    {
        if ($id <= 0 || !$this->taskModel->findByIdAndUser($id, $userId)) {
            return [
                'success' => false,
                'message' => 'Misión no válida.'
            ];
        }

        $ok = $this->taskModel->delete($id, $userId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Misión eliminada correctamente.' : 'No se pudo eliminar la misión.'
        ];
    }

    public function complete(int $userId, int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Misión no válida.'
            ];
        }

        return $this->taskModel->complete($id, $userId);
    }

    private function validate(int $userId, array $data): array
    {
        $title = trim($data['title'] ?? '');

        if ($title === '') {
            return [
                'success' => false,
                'message' => 'El título de la misión es obligatorio.',
                'errors' => ['title' => 'El título es obligatorio.'],
            ];
        }

        if (mb_strlen($title) > 150) {
            return [
                'success' => false,
                'message' => 'El título no puede superar los 150 caracteres.',
                'errors' => ['title' => 'Máximo 150 caracteres.'],
            ];
        }

        $projectId = ((int) ($data['project_id'] ?? 0)) > 0 ? (int) $data['project_id'] : null;
        $goalId = ((int) ($data['goal_id'] ?? 0)) > 0 ? (int) $data['goal_id'] : null;
        $areaId = ((int) ($data['area_id'] ?? 0)) > 0 ? (int) $data['area_id'] : null;

        if ($projectId !== null) {
            require_once __DIR__ . '/../Models/Project.php';
            $projectModel = new Project();

            if (!$projectModel->findByIdAndUser($projectId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'El reto seleccionado no existe o no pertenece a tu usuario.',
                    'errors' => ['project_id' => 'Reto no válido para tu usuario.'],
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
                    'errors' => ['goal_id' => 'Meta no válida para tu usuario.'],
                ];
            }
        }

        if ($areaId !== null) {
            require_once __DIR__ . '/../Models/LifeArea.php';
            $lifeAreaModel = new LifeArea();

            if (!$lifeAreaModel->findByIdAndUser($areaId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'El área seleccionada no existe o no pertenece a tu usuario.',
                    'errors' => ['area_id' => 'Área no válida para tu usuario.'],
                ];
            }
        }

        $allowedPriorities = ['low', 'medium', 'high', 'critical'];
        $allowedStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];

        $priority = in_array(($data['priority'] ?? ''), $allowedPriorities, true) ? $data['priority'] : 'medium';
        $status = in_array(($data['status'] ?? ''), $allowedStatuses, true) ? $data['status'] : 'pending';
        $estimatedMinutes = max(0, (int) ($data['estimated_minutes'] ?? 0));
        $reward = RewardCalculator::forTask($priority, $estimatedMinutes);

        return [
            'success' => true,
            'data' => [
                'project_id' => $projectId,
                'goal_id' => $goalId,
                'area_id' => $areaId,
                'title' => $title,
                'description' => trim($data['description'] ?? '') ?: null,
                'priority' => $priority,
                'status' => $status,
                'estimated_minutes' => $estimatedMinutes,
                'due_date' => trim($data['due_date'] ?? '') ?: null,
                'xp_reward' => $reward['xp'],
                'points_reward' => $reward['points'],
            ]
        ];
    }
}
