<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Mission.php';
require_once __DIR__ . '/../Models/User.php';

class MissionController
{
    private Mission $model;
    private User $userModel;

    public function __construct()
    {
        $this->model = new Mission();
        $this->userModel = new User();
    }

    /**
     * Obtener misiones de un usuario
     */
    public function index(int $userId, ?string $type = null): array
    {
        return $this->model->getAllByUser($userId, $type);
    }

    /**
     * Crear nueva misión
     */
    public function store(int $userId, array $data): array
    {
        try {
            $missionId = $this->model->create(array_merge($data, ['user_id' => $userId]));
            
            return [
                'success' => true,
                'message' => 'Misión creada exitosamente',
                'mission_id' => $missionId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear la misión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar misión
     */
    public function update(int $missionId, int $userId, array $data): array
    {
        try {
            $success = $this->model->update($missionId, $userId, $data);
            
            return [
                'success' => $success,
                'message' => $success ? 'Misión actualizada' : 'Error al actualizar misión'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar misión
     */
    public function destroy(int $missionId, int $userId): array
    {
        try {
            $success = $this->model->delete($missionId, $userId);
            
            return [
                'success' => $success,
                'message' => $success ? 'Misión eliminada' : 'Error al eliminar misión'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar progreso de misión
     */
    public function updateProgress(int $missionId, int $userId, int $progress = 1): array
    {
        try {
            $success = $this->model->updateProgress($missionId, $userId, $progress);
            
            return [
                'success' => $success,
                'message' => $success ? 'Progreso actualizado' : 'Error al actualizar progreso'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Completar misión
     */
    public function complete(int $missionId, int $userId): array
    {
        try {
            $mission = $this->model->findById($missionId, $userId);
            
            if (!$mission) {
                return [
                    'success' => false,
                    'message' => 'Misión no encontrada'
                ];
            }
            
            $remaining = (int)$mission['target_value'] - (int)$mission['current_progress'];
            $success = $this->model->updateProgress($missionId, $userId, $remaining);
            
            return [
                'success' => $success,
                'message' => $success ? '¡Misión completada!' : 'Error al completar misión'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas
     */
    public function getStats(int $userId): array
    {
        return $this->model->getStats($userId);
    }

    /**
     * Validar datos de misión
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = 'El título es requerido';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'El título es demasiado largo';
        }

        if (empty($data['type']) || !in_array($data['type'], ['daily', 'weekly', 'monthly'])) {
            $errors['type'] = 'Tipo de misión inválido';
        }

        if (!isset($data['target_value']) || (int)$data['target_value'] <= 0) {
            $errors['target_value'] = 'El valor objetivo debe ser mayor a 0';
        }

        return $errors;
    }

    /**
     * Obtener resumen diario para dashboard
     */
    public function getDailySummary(int $userId): array
    {
        $dailyMissions = $this->model->getAllByUser($userId, 'daily');
        $completed = array_filter($dailyMissions, fn($m) => (int)$m['completed'] === 1);
        
        return [
            'total' => count($dailyMissions),
            'completed' => count($completed),
            'pending' => count($dailyMissions) - count($completed),
            'percentage' => count($dailyMissions) > 0 
                ? (int)((count($completed) / count($dailyMissions)) * 100) 
                : 0
        ];
    }
}
