<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class AdminDatabaseManager
{
    private PDO $db;
    private array $referenceTableCache = [];
    private array $referenceLabelCache = [];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getConnection();
    }

    public function getOverviewCounts(): array
    {
        return [
            'users' => $this->countTable('users'),
            'goals' => $this->countTable('goals'),
            'tasks' => $this->countTable('tasks'),
            'habits' => $this->countTable('habits'),
            'projects' => $this->countTable('projects'),
            'rewards' => $this->countTable('rewards'),
            'inventory' => $this->countTable('user_reward_inventory'),
        ];
    }

    public function getAdminUsers(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, email, level, xp, points,
                    ' . ($this->hasColumn('users', 'hp') ? 'hp' : '0') . ' AS hp,
                    ' . ($this->hasColumn('users', 'max_hp') ? 'max_hp' : '0') . ' AS max_hp
             FROM users
             ORDER BY id ASC'
        );

        return $stmt->fetchAll();
    }

    public function updatePlayerStats(int $userId, array $payload): bool
    {
        if ($userId < 1) {
            return false;
        }

        $allowed = ['points', 'xp', 'level', 'hp', 'max_hp'];
        $setParts = [];
        $values = [];

        foreach ($allowed as $column) {
            if (!array_key_exists($column, $payload) || !$this->hasColumn('users', $column)) {
                continue;
            }

            $setParts[] = '`' . $column . '` = ?';
            $values[] = max(0, (int) $payload[$column]);
        }

        if (empty($setParts)) {
            return false;
        }

        $values[] = $userId;
        $stmt = $this->db->prepare('UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ? LIMIT 1');
        $stmt->execute($values);

        return $stmt->rowCount() >= 0;
    }

    public function getShopSummary(): array
    {
        return [
            'indulgences' => $this->countWhere('rewards', "shop_type = 'indulgence'"),
            'cosmetics' => $this->countWhere('rewards', "shop_type = 'cosmetic'"),
            'inventory' => $this->countTable('user_reward_inventory'),
            'equipped' => $this->countWhere('user_reward_inventory', 'equipped = 1'),
            'redemptions' => $this->countTable('reward_redemptions'),
        ];
    }

    public function getShopRewards(string $shopType = 'all', int $limit = 80): array
    {
        $limit = max(1, min($limit, 200));
        $where = '';

        if (in_array($shopType, ['indulgence', 'cosmetic'], true)) {
            $where = 'WHERE r.shop_type = :shop_type';
        }

        $sql = 'SELECT r.id, r.user_id, u.name AS user_name, u.email AS user_email,
                       r.name, r.description,
                       ' . ($this->hasColumn('rewards', 'image_path') ? 'r.image_path' : 'NULL') . ' AS image_path,
                       r.cost_points, r.category, r.shop_type,
                       r.effect_hp, r.weekly_limit, r.active, r.created_at
                FROM rewards r
                INNER JOIN users u ON u.id = r.user_id
                ' . $where . '
                ORDER BY r.created_at DESC, r.id DESC
                LIMIT ' . $limit;

        $stmt = $this->db->prepare($sql);

        if ($where !== '') {
            $stmt->execute(['shop_type' => $shopType]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    public function getShopRewardById(int $rewardId): ?array
    {
        if ($rewardId < 1) {
            return null;
        }

        $sql = 'SELECT r.id, r.user_id, u.name AS user_name, u.email AS user_email,
                       r.name, r.description,
                       ' . ($this->hasColumn('rewards', 'image_path') ? 'r.image_path' : 'NULL') . ' AS image_path,
                       r.cost_points, r.category, r.shop_type,
                       r.effect_hp, r.weekly_limit, r.active, r.created_at
                FROM rewards r
                INNER JOIN users u ON u.id = r.user_id
                WHERE r.id = :reward_id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['reward_id' => $rewardId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createShopReward(array $payload): int
    {
        $targetUserId = max(0, (int) ($payload['target_user_id'] ?? 0));
        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $imagePath = $this->normalizeImagePath((string) ($payload['image_path'] ?? ''));
        $shopType = in_array((string) ($payload['shop_type'] ?? ''), ['indulgence', 'cosmetic'], true)
            ? (string) $payload['shop_type']
            : 'indulgence';
        $category = trim((string) ($payload['category'] ?? ($shopType === 'cosmetic' ? 'cosmetico' : 'indulgencia')));
        $costPoints = max(0, (int) ($payload['cost_points'] ?? 0));
        $effectHp = $shopType === 'indulgence' ? max(0, (int) ($payload['effect_hp'] ?? 0)) : 0;
        $weeklyLimit = max(1, (int) ($payload['weekly_limit'] ?? ($shopType === 'cosmetic' ? 99 : 1)));
        $active = !empty($payload['active']) ? 1 : 0;

        if ($name === '' || $costPoints < 1) {
            return 0;
        }

        $users = [];
        if ($targetUserId > 0) {
            $users[] = $targetUserId;
        } else {
            $stmt = $this->db->query('SELECT id FROM users ORDER BY id ASC');
            $users = array_map(static fn(array $row): int => (int) $row['id'], $stmt->fetchAll());
        }

        if (empty($users)) {
            return 0;
        }

        $inserted = 0;
        $supportsImagePath = $this->hasColumn('rewards', 'image_path');
        $insert = $this->db->prepare(
            'INSERT INTO rewards (user_id, name, description' . ($supportsImagePath ? ', image_path' : '') . ', cost_points, category, shop_type, effect_hp, weekly_limit, active)
             SELECT :user_id, :name, :description' . ($supportsImagePath ? ', :image_path' : '') . ', :cost_points, :category, :shop_type, :effect_hp, :weekly_limit, :active
             WHERE NOT EXISTS (
                SELECT 1 FROM rewards WHERE user_id = :exists_user_id AND name = :exists_name LIMIT 1
             )'
        );

        foreach ($users as $userId) {
            $params = [
                'user_id' => $userId,
                'name' => $name,
                'description' => $description,
            ];

            if ($supportsImagePath) {
                $params['image_path'] = $imagePath;
            }

            $params += [
                'cost_points' => $costPoints,
                'category' => $category,
                'shop_type' => $shopType,
                'effect_hp' => $effectHp,
                'weekly_limit' => $weeklyLimit,
                'active' => $active,
                'exists_user_id' => $userId,
                'exists_name' => $name,
            ];

            $insert->execute($params);
            $inserted += $insert->rowCount();
        }

        return $inserted;
    }

    public function updateShopReward(int $rewardId, array $payload): bool
    {
        if ($rewardId < 1) {
            return false;
        }

        $current = $this->getRowByPrimaryKey('rewards', 'id', $rewardId);
        if ($current === null) {
            return false;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $imagePath = $this->normalizeImagePath((string) ($payload['image_path'] ?? ''));
        $shopType = in_array((string) ($payload['shop_type'] ?? ''), ['indulgence', 'cosmetic'], true)
            ? (string) $payload['shop_type']
            : (string) ($current['shop_type'] ?? 'indulgence');
        $category = trim((string) ($payload['category'] ?? (string) ($current['category'] ?? '')));
        $costPoints = max(0, (int) ($payload['cost_points'] ?? 0));
        $effectHp = $shopType === 'indulgence' ? max(0, (int) ($payload['effect_hp'] ?? 0)) : 0;
        $weeklyLimit = max(1, (int) ($payload['weekly_limit'] ?? 1));
        $active = !empty($payload['active']) ? 1 : 0;

        if ($name === '' || $costPoints < 1) {
            return false;
        }

        $ownerId = (int) ($current['user_id'] ?? 0);
        if ($ownerId > 0) {
            $duplicateStmt = $this->db->prepare(
                'SELECT 1
                 FROM rewards
                 WHERE user_id = :user_id
                   AND name = :name
                   AND id <> :reward_id
                 LIMIT 1'
            );
            $duplicateStmt->execute([
                'user_id' => $ownerId,
                'name' => $name,
                'reward_id' => $rewardId,
            ]);

            if ($duplicateStmt->fetchColumn()) {
                return false;
            }
        }

        $supportsImagePath = $this->hasColumn('rewards', 'image_path');
        $columns = [
            'name' => $name,
            'description' => $description,
            'cost_points' => $costPoints,
            'category' => $category,
            'shop_type' => $shopType,
            'effect_hp' => $effectHp,
            'weekly_limit' => $weeklyLimit,
            'active' => $active,
        ];

        if ($supportsImagePath) {
            $columns['image_path'] = $imagePath;
        }

        $setParts = [];
        $params = [];

        foreach ($columns as $column => $value) {
            $setParts[] = '`' . $column . '` = ?';
            $params[] = $value;
        }

        $params[] = $rewardId;
        $stmt = $this->db->prepare('UPDATE rewards SET ' . implode(', ', $setParts) . ' WHERE id = ? LIMIT 1');
        $stmt->execute($params);

        return $stmt->rowCount() >= 0;
    }

    public function setRewardActive(int $rewardId, bool $active): bool
    {
        if ($rewardId < 1) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE rewards SET active = :active WHERE id = :id LIMIT 1');
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'id' => $rewardId,
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function getShopInventory(int $limit = 80): array
    {
        if (!$this->tableExists('user_reward_inventory')) {
            return [];
        }

        $limit = max(1, min($limit, 200));
        $stmt = $this->db->query(
            'SELECT uri.id, uri.user_id, u.name AS user_name, u.email AS user_email,
                    uri.reward_id, r.name AS reward_name, r.category, uri.equipped,
                    uri.acquired_at, uri.equipped_at
             FROM user_reward_inventory uri
             INNER JOIN users u ON u.id = uri.user_id
             INNER JOIN rewards r ON r.id = uri.reward_id
             ORDER BY uri.equipped DESC, uri.acquired_at DESC
             LIMIT ' . $limit
        );

        return $stmt->fetchAll();
    }

    public function grantInventoryItem(int $userId, int $rewardId): bool
    {
        if (!$this->tableExists('user_reward_inventory') || $userId < 1 || $rewardId < 1) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO user_reward_inventory (user_id, reward_id, equipped)
             SELECT :user_id, r.id, 0
             FROM rewards r
             WHERE r.id = :reward_id
               AND r.user_id = :reward_user_id
               AND r.shop_type = \'cosmetic\'
             ON DUPLICATE KEY UPDATE reward_id = reward_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'reward_user_id' => $userId,
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function getTables(): array
    {
        $stmt = $this->db->query('SHOW TABLES');
        $tables = [];

        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
            $tables[] = (string) $row[0];
        }

        sort($tables);

        return $tables;
    }

    public function getTableColumns(string $table): array
    {
        $table = $this->sanitizeTableName($table);
        if ($table === '') {
            return [];
        }

        $stmt = $this->db->prepare('SHOW COLUMNS FROM `' . $table . '`');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPrimaryKey(string $table): ?string
    {
        $table = $this->sanitizeTableName($table);
        if ($table === '') {
            return null;
        }

        $stmt = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return (string) ($row['Column_name'] ?? '');
    }

    public function getPaginatedRows(string $table, int $page, int $limit, string $search = ''): array
    {
        $table = $this->sanitizeTableName($table);
        $limit = max(1, min($limit, (int) (defined('ADMIN_DB_MAX_ROWS') ? ADMIN_DB_MAX_ROWS : 200)));
        $page = max(1, $page);

        if ($table === '') {
            return [
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'pages' => 1,
                'page' => 1,
                'limit' => $limit,
            ];
        }

        $columnsInfo = $this->getTableColumns($table);
        $allColumns = array_values(array_filter(array_map(static fn(array $c): string => (string) ($c['Field'] ?? ''), $columnsInfo)));

        $searchColumns = [];
        foreach ($columnsInfo as $column) {
            $type = strtolower((string) ($column['Type'] ?? ''));
            $name = (string) ($column['Field'] ?? '');

            if ($name === '') {
                continue;
            }

            if (str_contains($type, 'char') || str_contains($type, 'text')) {
                $searchColumns[] = $name;
            }

            if (count($searchColumns) >= 6) {
                break;
            }
        }

        $whereClause = '';
        $params = [];
        $search = trim($search);

        if ($search !== '' && !empty($searchColumns)) {
            $parts = [];
            foreach ($searchColumns as $column) {
                $parts[] = "`{$column}` LIKE ?";
                $params[] = '%' . $search . '%';
            }
            $whereClause = ' WHERE ' . implode(' OR ', $parts);
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `{$table}`{$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $selectColumns = empty($allColumns)
            ? '*'
            : implode(', ', array_map(static fn(string $col): string => "`{$col}`", $allColumns));

        $rowsStmt = $this->db->prepare(
            "SELECT {$selectColumns}
             FROM `{$table}`{$whereClause}
             LIMIT {$limit} OFFSET {$offset}"
        );
        $rowsStmt->execute($params);
        $rows = $rowsStmt->fetchAll();

        return [
            'columns' => $allColumns,
            'rows' => $rows,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function insertRow(string $table, array $payload): int
    {
        $table = $this->sanitizeTableName($table);
        if ($table === '') {
            return 0;
        }

        [$columns, $values] = $this->buildWritePayload($table, $payload, false);

        if (empty($columns)) {
            return 0;
        }

        $columnsSql = implode(', ', array_map(static fn(string $column): string => "`{$column}`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $stmt = $this->db->prepare("INSERT INTO `{$table}` ({$columnsSql}) VALUES ({$placeholders})");
        $stmt->execute($values);

        return (int) $this->db->lastInsertId();
    }

    public function updateRow(string $table, string $primaryKey, mixed $primaryValue, array $payload): int
    {
        $table = $this->sanitizeTableName($table);
        $primaryKey = $this->sanitizeColumnName($primaryKey);

        if ($table === '' || $primaryKey === '') {
            return 0;
        }

        [$columns, $values] = $this->buildWritePayload($table, $payload, true, $primaryKey);

        if (empty($columns)) {
            return 0;
        }

        $setParts = [];
        foreach ($columns as $column) {
            $setParts[] = "`{$column}` = ?";
        }

        $values[] = $primaryValue;
        $stmt = $this->db->prepare(
            "UPDATE `{$table}`
             SET " . implode(', ', $setParts) . "
             WHERE `{$primaryKey}` = ?
             LIMIT 1"
        );
        $stmt->execute($values);

        return $stmt->rowCount();
    }

    public function deleteRow(string $table, string $primaryKey, mixed $primaryValue): int
    {
        $table = $this->sanitizeTableName($table);
        $primaryKey = $this->sanitizeColumnName($primaryKey);

        if ($table === '' || $primaryKey === '') {
            return 0;
        }

        $stmt = $this->db->prepare("DELETE FROM `{$table}` WHERE `{$primaryKey}` = ? LIMIT 1");
        $stmt->execute([$primaryValue]);

        return $stmt->rowCount();
    }

    public function getRowByPrimaryKey(string $table, string $primaryKey, mixed $primaryValue): ?array
    {
        $table = $this->sanitizeTableName($table);
        $primaryKey = $this->sanitizeColumnName($primaryKey);

        if ($table === '' || $primaryKey === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM `{$table}` WHERE `{$primaryKey}` = ? LIMIT 1");
        $stmt->execute([$primaryValue]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function executeQuery(string $sql): array
    {
        $sql = trim($sql);
        if ($sql === '') {
            return [
                'ok' => false,
                'message' => 'La consulta SQL esta vacia.',
            ];
        }

        $keyword = $this->getQueryKeyword($sql);
        $isRead = in_array($keyword, ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN'], true);
        $isWrite = in_array($keyword, ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'], true);
        $isSchema = in_array($keyword, ['CREATE', 'ALTER', 'DROP', 'TRUNCATE', 'RENAME'], true);

        if (!$isRead && !$isWrite && !$isSchema) {
            return [
                'ok' => false,
                'message' => 'Tipo de consulta no permitida para este panel.',
            ];
        }

        if ($isWrite && !(defined('ADMIN_DB_ALLOW_WRITE_QUERIES') && ADMIN_DB_ALLOW_WRITE_QUERIES === true)) {
            return [
                'ok' => false,
                'message' => 'Las consultas de escritura estan deshabilitadas en config.',
            ];
        }

        if ($isSchema && !(defined('ADMIN_DB_ALLOW_SCHEMA_QUERIES') && ADMIN_DB_ALLOW_SCHEMA_QUERIES === true)) {
            return [
                'ok' => false,
                'message' => 'Las consultas de esquema estan deshabilitadas en config.',
            ];
        }

        if ($isRead) {
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll();
            $columns = [];

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
            }

            return [
                'ok' => true,
                'type' => 'read',
                'columns' => $columns,
                'rows' => $rows,
                'affected' => count($rows),
                'message' => 'Consulta ejecutada correctamente.',
            ];
        }

        $affected = $this->db->exec($sql);

        return [
            'ok' => true,
            'type' => 'write',
            'columns' => [],
            'rows' => [],
            'affected' => (int) $affected,
            'message' => 'Consulta ejecutada correctamente.',
        ];
    }

    public function displayValue(string $table, string $column, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '' || !$this->isReferenceColumn($column)) {
            return $raw;
        }

        $label = $this->resolveReferenceLabel($table, $column, $raw);

        return $label !== null && $label !== '' ? $label : $raw;
    }

    private function countTable(string $table): int
    {
        $table = $this->sanitizeTableName($table);
        if ($table === '') {
            return 0;
        }

        try {
            $stmt = $this->db->query('SELECT COUNT(*) AS c FROM `' . $table . '`');
            return (int) $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function countWhere(string $table, string $where): int
    {
        $table = $this->sanitizeTableName($table);
        if ($table === '' || !$this->tableExists($table)) {
            return 0;
        }

        try {
            $stmt = $this->db->query('SELECT COUNT(*) AS c FROM `' . $table . '` WHERE ' . $where);
            return (int) $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function tableExists(string $table): bool
    {
        $table = $this->sanitizeTableName($table);
        if ($table === '') {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             LIMIT 1'
        );
        $stmt->execute(['table' => $table]);

        return (bool) $stmt->fetchColumn();
    }

    private function hasColumn(string $table, string $column): bool
    {
        $table = $this->sanitizeTableName($table);
        $column = $this->sanitizeColumnName($column);

        if ($table === '' || $column === '') {
            return false;
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

        return (bool) $stmt->fetchColumn();
    }

    private function isReferenceColumn(string $column): bool
    {
        return $column !== 'id' && str_ends_with($column, '_id');
    }

    private function resolveReferenceLabel(string $sourceTable, string $column, string $value): ?string
    {
        $target = $this->resolveReferenceTarget($sourceTable, $column);
        if ($target === null) {
            return null;
        }

        $cacheKey = $target['table'] . '.' . $target['column'] . ':' . $value;
        if (array_key_exists($cacheKey, $this->referenceLabelCache)) {
            return $this->referenceLabelCache[$cacheKey];
        }

        $labelColumns = $this->getReferenceLabelColumns($target['table']);
        $selectParts = array_merge([$target['column']], $labelColumns);
        $selectSql = implode(', ', array_map(static fn(string $col): string => '`' . $col . '`', array_unique($selectParts)));

        try {
            $stmt = $this->db->prepare(
                'SELECT ' . $selectSql . '
                 FROM `' . $target['table'] . '`
                 WHERE `' . $target['column'] . '` = ?
                 LIMIT 1'
            );
            $stmt->execute([$value]);
            $row = $stmt->fetch();

            if (!$row) {
                $this->referenceLabelCache[$cacheKey] = null;
                return null;
            }

            $label = $this->buildReferenceLabel($row, $labelColumns);
            $this->referenceLabelCache[$cacheKey] = $label;

            return $label;
        } catch (Throwable) {
            $this->referenceLabelCache[$cacheKey] = null;
            return null;
        }
    }

    private function resolveReferenceTarget(string $sourceTable, string $column): ?array
    {
        $sourceTable = $this->sanitizeTableName($sourceTable);
        $column = $this->sanitizeColumnName($column);

        if ($sourceTable === '' || $column === '' || !$this->isReferenceColumn($column)) {
            return null;
        }

        $cacheKey = $sourceTable . '.' . $column;
        if (array_key_exists($cacheKey, $this->referenceTableCache)) {
            return $this->referenceTableCache[$cacheKey];
        }

        $fkTarget = $this->getForeignKeyTarget($sourceTable, $column);
        if ($fkTarget !== null) {
            $this->referenceTableCache[$cacheKey] = $fkTarget;
            return $fkTarget;
        }

        $baseName = substr($column, 0, -3);
        $manualMap = [
            'user' => 'users',
            'admin_portal_user' => 'admin_portal_users',
            'area' => 'life_areas',
            'life_area' => 'life_areas',
            'goal' => 'goals',
            'project' => 'projects',
            'task' => 'tasks',
            'habit' => 'habits',
            'reward' => 'rewards',
            'badge' => 'badges',
            'daily_objective' => 'daily_objectives',
        ];

        $candidates = [];
        if (isset($manualMap[$baseName])) {
            $candidates[] = $manualMap[$baseName];
        }
        $candidates[] = $baseName . 's';
        $candidates[] = $baseName . 'es';

        foreach (array_unique($candidates) as $candidate) {
            if ($this->tableExists($candidate) && $this->hasColumn($candidate, 'id')) {
                $this->referenceTableCache[$cacheKey] = ['table' => $candidate, 'column' => 'id'];
                return $this->referenceTableCache[$cacheKey];
            }
        }

        $this->referenceTableCache[$cacheKey] = null;
        return null;
    }

    private function getForeignKeyTarget(string $sourceTable, string $column): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT REFERENCED_TABLE_NAME AS referenced_table, REFERENCED_COLUMN_NAME AS referenced_column
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME = :column
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1'
            );
            $stmt->execute([
                'table' => $sourceTable,
                'column' => $column,
            ]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            $table = $this->sanitizeTableName((string) ($row['referenced_table'] ?? ''));
            $referencedColumn = $this->sanitizeColumnName((string) ($row['referenced_column'] ?? ''));

            if ($table === '' || $referencedColumn === '' || !$this->tableExists($table) || !$this->hasColumn($table, $referencedColumn)) {
                return null;
            }

            return ['table' => $table, 'column' => $referencedColumn];
        } catch (Throwable) {
            return null;
        }
    }

    private function getReferenceLabelColumns(string $table): array
    {
        $priority = match ($table) {
            'users' => ['name', 'email'],
            'admin_portal_users' => ['username'],
            'app_settings' => ['setting_key', 'setting_value'],
            default => ['name', 'title', 'label', 'email', 'username', 'slug', 'description'],
        };

        $columns = [];
        foreach ($priority as $column) {
            if ($this->hasColumn($table, $column)) {
                $columns[] = $column;
            }

            if (count($columns) >= 2) {
                break;
            }
        }

        return $columns;
    }

    private function buildReferenceLabel(array $row, array $labelColumns): string
    {
        $parts = [];

        foreach ($labelColumns as $column) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode(' · ', $parts);
    }

    private function normalizeImagePath(string $value): string
    {
        $value = trim($value);

        if ($value === '' || preg_match('/[\x00-\x1F]/', $value) === 1) {
            return '';
        }

        if (preg_match('/^javascript:/i', $value) === 1) {
            return '';
        }

        return mb_substr($value, 0, 255);
    }

    private function sanitizeTableName(string $table): string
    {
        $table = trim($table);

        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return '';
        }

        return $table;
    }

    private function sanitizeColumnName(string $column): string
    {
        $column = trim($column);

        if ($column === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return '';
        }

        return $column;
    }

    private function buildWritePayload(string $table, array $payload, bool $forUpdate, ?string $excludeColumn = null): array
    {
        $columnsInfo = $this->getTableColumns($table);
        $fieldMap = [];

        foreach ($columnsInfo as $column) {
            $name = (string) ($column['Field'] ?? '');
            if ($name === '') {
                continue;
            }

            $fieldMap[$name] = $column;
        }

        $columns = [];
        $values = [];

        foreach ($payload as $key => $value) {
            $column = $this->sanitizeColumnName((string) $key);
            if ($column === '' || !array_key_exists($column, $fieldMap)) {
                continue;
            }

            if ($excludeColumn !== null && $column === $excludeColumn) {
                continue;
            }

            $extra = strtolower((string) ($fieldMap[$column]['Extra'] ?? ''));
            if (!$forUpdate && str_contains($extra, 'auto_increment')) {
                continue;
            }

            $shouldForceUserDefault = $this->shouldForceUserDefaultOnInsert($table, $column, $value, $forUpdate);

            if (!$forUpdate && !$shouldForceUserDefault && $this->shouldUseDatabaseDefaultOnInsert($value, $fieldMap[$column])) {
                continue;
            }

            if (
                $forUpdate
                && $this->isSensitivePasswordColumn($table, $column)
                && is_string($value)
                && trim($value) === ''
            ) {
                continue;
            }

            $columns[] = $column;
            $values[] = $this->normalizeValue($value, $fieldMap[$column], $table, $column, $forUpdate);
        }

        return [$columns, $values];
    }

    private function normalizeValue(mixed $value, array $column, string $table = '', string $columnName = '', bool $forUpdate = false): mixed
    {
        $raw = is_string($value) ? trim($value) : $value;
        $allowsNull = strtoupper((string) ($column['Null'] ?? 'NO')) === 'YES';

        if (!$forUpdate && $table === 'users') {
            $userDefault = $this->getUserDefaultValue($columnName);
            if ($userDefault !== null && ($raw === '' || $raw === null)) {
                return $userDefault;
            }
        }

        if ($this->isSensitivePasswordColumn($table, $columnName)) {
            $rawPassword = is_string($raw) ? trim($raw) : (string) $raw;

            if ($forUpdate && $rawPassword === '') {
                return $rawPassword;
            }

            if ($rawPassword !== '' && empty(password_get_info($rawPassword)['algo'])) {
                return password_hash($rawPassword, PASSWORD_DEFAULT);
            }

            return $rawPassword;
        }

        if ($raw === '' && $allowsNull) {
            return null;
        }

        $type = strtolower((string) ($column['Type'] ?? ''));

        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type) === 1) {
            return ($raw === '' || $raw === null) ? 0 : (int) $raw;
        }

        if (preg_match('/^(decimal|float|double)/', $type) === 1) {
            return ($raw === '' || $raw === null) ? 0.0 : (float) $raw;
        }

        return $raw;
    }

    private function isSensitivePasswordColumn(string $table, string $columnName): bool
    {
        return ($table === 'users' && $columnName === 'password')
            || ($table === 'admin_portal_users' && $columnName === 'password_hash');
    }

    private function shouldUseDatabaseDefaultOnInsert(mixed $value, array $column): bool
    {
        $raw = is_string($value) ? trim($value) : $value;
        $default = $column['Default'] ?? null;

        return $raw === '' && $default !== null;
    }

    private function shouldForceUserDefaultOnInsert(string $table, string $column, mixed $value, bool $forUpdate): bool
    {
        if ($forUpdate || $table !== 'users') {
            return false;
        }

        $userDefault = $this->getUserDefaultValue($column);
        if ($userDefault === null) {
            return false;
        }

        $raw = is_string($value) ? trim($value) : $value;
        return $raw === '' || $raw === null;
    }

    private function getUserDefaultValue(string $columnName): int|null
    {
        return match ($columnName) {
            'level' => 1,
            'xp', 'points', 'current_streak' => 0,
            'hp', 'max_hp' => 1000,
            default => null,
        };
    }

    private function getQueryKeyword(string $sql): string
    {
        $clean = ltrim($sql);
        $parts = preg_split('/\s+/', $clean);

        return strtoupper((string) ($parts[0] ?? ''));
    }
}
