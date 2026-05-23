<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Goal.php';
require_once __DIR__ . '/../Support/RewardCalculator.php';

final class GoalController
{
    private Goal $goalModel;

    public function __construct()
    {
        $this->goalModel = new Goal();
    }

    public function index(int $userId): array
    {
        return $this->goalModel->getAllByUser($userId);
    }

    public function store(int $userId, array $data): array
    {
        $clean = $this->validate($userId, $data);
        if (!$clean['success']) return $clean;
        $ok = $this->goalModel->create($userId, $clean['data']);
        return ['success' => $ok, 'message' => $ok ? 'Meta creada correctamente.' : 'No se pudo crear la meta.'];
    }

    public function update(int $userId, array $data): array
    {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0 || !$this->goalModel->findByIdAndUser($id, $userId)) {
            return ['success' => false, 'message' => 'Meta no válida.'];
        }
        $clean = $this->validate($userId, $data);
        if (!$clean['success']) return $clean;
        $ok = $this->goalModel->update($id, $userId, $clean['data']);
        return ['success' => $ok, 'message' => $ok ? 'Meta actualizada correctamente.' : 'No se pudo actualizar la meta.'];
    }

    public function destroy(int $userId, int $id): array
    {
        if ($id <= 0 || !$this->goalModel->findByIdAndUser($id, $userId)) {
            return ['success' => false, 'message' => 'Meta no válida.'];
        }
        $ok = $this->goalModel->delete($id, $userId);
        return ['success' => $ok, 'message' => $ok ? 'Meta eliminada correctamente.' : 'No se pudo eliminar la meta.'];
    }

    private function validate(int $userId, array $data): array
    {
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            return [
                'success' => false,
                'message' => 'El título de la meta es obligatorio.',
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

        $areaId = ((int)($data['area_id'] ?? 0)) > 0 ? (int)$data['area_id'] : null;
        
        // Validar que el área pertenece al usuario si se especificó
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

        $allowedTypes = ['daily','weekly','monthly','quarterly','yearly','future'];
        $allowedPriorities = ['low','medium','high','critical'];
        $allowedStatuses = ['not_started','in_progress','paused','completed','cancelled'];

        $type = in_array(($data['type'] ?? ''), $allowedTypes, true) ? $data['type'] : 'monthly';
        $priority = in_array(($data['priority'] ?? ''), $allowedPriorities, true) ? $data['priority'] : 'medium';
        $status = in_array(($data['status'] ?? ''), $allowedStatuses, true) ? $data['status'] : 'not_started';
        $reward = RewardCalculator::forGoal($type, $priority);

        return [
            'success' => true,
            'data' => [
                'area_id' => $areaId,
                'title' => $title,
                'description' => trim($data['description'] ?? '') ?: null,
                'type' => $type,
                'priority' => $priority,
                'status' => $status,
                'progress' => max(0, min(100, (int)($data['progress'] ?? 0))),
                'start_date' => trim($data['start_date'] ?? '') ?: null,
                'due_date' => trim($data['due_date'] ?? '') ?: null,
                'xp_reward' => $reward['xp'],
                'points_reward' => $reward['points'],
            ]
        ];
    }
}
