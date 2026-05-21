<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/BattleSession.php';

final class BattleController
{
    private BattleSession $battleSessionModel;

    public function __construct()
    {
        $this->battleSessionModel = new BattleSession();
    }

    public function getPendingTasks(int $userId): array
    {
        return $this->battleSessionModel->getPendingTasks($userId);
    }

    public function getRecentSessions(int $userId): array
    {
        return $this->battleSessionModel->getRecentSessions($userId);
    }

    public function finishSession(int $userId, array $data): array
    {
        $taskId = (int) ($data['task_id'] ?? 0);
        $durationMinutes = (int) ($data['duration_minutes'] ?? 25);
        $result = $data['result'] ?? 'partial';
        $notes = trim($data['notes'] ?? '');

        $allowedResults = ['completed', 'partial', 'failed'];

        if (!in_array($result, $allowedResults, true)) {
            $result = 'partial';
        }

        $durationMinutes = max(1, min(240, $durationMinutes));

        $task = null;

        if ($taskId > 0) {
            $task = $this->battleSessionModel->findTaskByIdAndUser($taskId, $userId);

            if (!$task) {
                return [
                    'success' => false,
                    'message' => 'La misión seleccionada no existe o no pertenece a tu usuario.'
                ];
            }
        }

        $title = $task ? $task['title'] : trim($data['custom_title'] ?? '');

        if ($title === '') {
            return [
                'success' => false,
                'message' => 'Selecciona una misión o escribe un objetivo para la sesión.'
            ];
        }

        $baseXp = $task ? (int) $task['xp_reward'] : 20;
        $basePoints = $task ? (int) $task['points_reward'] : 10;

        $durationBonus = max(0, (int) floor($durationMinutes / 25) * 5);

        if ($result === 'completed') {
            $xpEarned = max(20, $baseXp + $durationBonus);
            $pointsEarned = max(10, $basePoints + (int) floor($durationBonus / 2));
        } elseif ($result === 'partial') {
            $xpEarned = max(10, (int) floor(($baseXp + $durationBonus) * 0.55));
            $pointsEarned = max(5, (int) floor(($basePoints + (int) floor($durationBonus / 2)) * 0.55));
        } else {
            $xpEarned = 0;
            $pointsEarned = 0;
        }

        $endedAt = new DateTimeImmutable();
        $startedAt = $endedAt->sub(new DateInterval('PT' . $durationMinutes . 'M'));

        $created = $this->battleSessionModel->createSession($userId, [
            'task_id' => $task ? (int) $task['id'] : null,
            'title' => $title,
            'duration_minutes' => $durationMinutes,
            'result' => $result,
            'notes' => $notes !== '' ? $notes : null,
            'xp_earned' => $xpEarned,
            'points_earned' => $pointsEarned,
            'started_at' => $startedAt->format('Y-m-d H:i:s'),
            'ended_at' => $endedAt->format('Y-m-d H:i:s'),
        ]);

        return [
            'success' => $created,
            'message' => $created
                ? 'Sesión de Modo Batalla registrada. Has ganado +' . $xpEarned . ' XP y +' . $pointsEarned . ' LifeCoins.'
                : 'No se pudo registrar la sesión.'
        ];
    }
}
