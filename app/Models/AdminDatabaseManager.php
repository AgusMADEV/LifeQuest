<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class AdminDatabaseManager
{
    private PDO $db;

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
        ];
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
