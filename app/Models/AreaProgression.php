<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class AreaProgression
{
    private PDO $db;
    private array $tableCache = [];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getConnection();
    }

    public function addXp(int $userId, ?int $areaId, int $xpDelta): void
    {
        if (!$this->isEnabled() || $areaId === null || $areaId <= 0 || $xpDelta === 0) {
            return;
        }

        if (!$this->hasTable('area_progression')) {
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT xp
             FROM area_progression
             WHERE user_id = :user_id AND area_id = :area_id
             LIMIT 1"
        );
        $stmt->execute([
            'user_id' => $userId,
            'area_id' => $areaId,
        ]);

        $existing = $stmt->fetch();

        if (!$existing) {
            if ($xpDelta <= 0) {
                return;
            }

            $xp = max(0, $xpDelta);
            $level = $this->levelFromXp($xp);

            $insert = $this->db->prepare(
                "INSERT INTO area_progression (user_id, area_id, xp, level)
                 VALUES (:user_id, :area_id, :xp, :level)"
            );
            $insert->execute([
                'user_id' => $userId,
                'area_id' => $areaId,
                'xp' => $xp,
                'level' => $level,
            ]);

            return;
        }

        $xp = max(0, ((int) $existing['xp']) + $xpDelta);
        $level = $this->levelFromXp($xp);

        $update = $this->db->prepare(
            "UPDATE area_progression
             SET xp = :xp,
                 level = :level
             WHERE user_id = :user_id AND area_id = :area_id"
        );
        $update->execute([
            'xp' => $xp,
            'level' => $level,
            'user_id' => $userId,
            'area_id' => $areaId,
        ]);
    }

    public function getTopByUser(int $userId, int $limit = 4): array
    {
        if (!$this->isEnabled() || !$this->hasTable('area_progression')) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT ap.area_id,
                    ap.xp,
                    ap.level,
                    la.name,
                    la.icon
             FROM area_progression ap
             INNER JOIN life_areas la ON la.id = ap.area_id
                         WHERE ap.user_id = :ap_user_id
                             AND la.user_id = :la_user_id
             ORDER BY ap.level DESC, ap.xp DESC, la.created_at DESC
             LIMIT :limit"
        );
                $stmt->bindValue('ap_user_id', $userId, PDO::PARAM_INT);
                $stmt->bindValue('la_user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];

        foreach ($stmt->fetchAll() as $row) {
            $xp = max(0, (int) ($row['xp'] ?? 0));
            $rows[] = [
                'area_id' => (int) $row['area_id'],
                'name' => (string) ($row['name'] ?? 'Área'),
                'icon' => (string) ($row['icon'] ?? '⭐'),
                'xp' => $xp,
                'level' => max(1, (int) ($row['level'] ?? $this->levelFromXp($xp))),
                'level_xp' => $xp % 1000,
                'level_xp_target' => 1000,
                'level_percent' => (int) round((($xp % 1000) / 1000) * 100),
            ];
        }

        return $rows;
    }

    private function isEnabled(): bool
    {
        return defined('FEATURE_AREA_PROGRESSION') ? (bool) FEATURE_AREA_PROGRESSION : false;
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             LIMIT 1'
        );
        $stmt->execute(['table' => $table]);

        $exists = (bool) $stmt->fetchColumn();
        $this->tableCache[$table] = $exists;

        return $exists;
    }

    private function levelFromXp(int $xp): int
    {
        return max(1, intdiv(max(0, $xp), 1000) + 1);
    }
}
