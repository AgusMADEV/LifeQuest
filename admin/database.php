<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/AdminDatabaseManager.php';
require_once __DIR__ . '/../app/Models/AppSettings.php';
require_once __DIR__ . '/../app/Models/AdminPortalUser.php';
require_once __DIR__ . '/session_guard.php';

if (!defined('ADMIN_PORTAL_ENABLED') || ADMIN_PORTAL_ENABLED !== true) {
    http_response_code(404);
    exit('Not Found');
}

if (empty($_SESSION['admin_portal_user_id'])) {
    header('Location: login.php');
    exit;
}

if (isAdminPortalSessionExpired()) {
    clearAdminPortalSession();
    header('Location: login.php?message=' . urlencode('Sesion expirada por inactividad.') . '&type=error');
    exit;
}

$_SESSION['admin_portal_logged_at'] = time();

$section = trim((string) ($_REQUEST['section'] ?? 'db'));
if (!in_array($section, ['db', 'balance'], true)) {
    $section = 'db';
}

$manager = new AdminDatabaseManager();
$settingsModel = new AppSettings();
$adminUserModel = new AdminPortalUser();
$adminUserId = (int) ($_SESSION['admin_portal_user_id'] ?? 0);
$adminUsername = (string) ($_SESSION['admin_portal_username'] ?? 'admin');

$overview = $manager->getOverviewCounts();

$message = null;
$messageType = null;

// ----------------------
// BLOQUE BALANCE
// ----------------------
$balanceKeys = [
    'REWARD_POINTS_PER_XP',
    'REWARD_HABIT_BASE_XP',
    'REWARD_TASK_BASE_XP',
    'REWARD_GOAL_BASE_XP_DAILY',
    'REWARD_GOAL_BASE_XP_WEEKLY',
    'REWARD_GOAL_BASE_XP_MONTHLY',
    'REWARD_GOAL_BASE_XP_QUARTERLY',
    'REWARD_GOAL_BASE_XP_YEARLY',
    'REWARD_GOAL_BASE_XP_FUTURE',
    'INDULGENCE_REPEAT_COST_MULTIPLIER',
    'COSMETIC_PRICE_MULTIPLIER',
];

$balanceDefaults = [
    'REWARD_POINTS_PER_XP' => defined('REWARD_POINTS_PER_XP') ? (string) REWARD_POINTS_PER_XP : '0.5',
    'REWARD_HABIT_BASE_XP' => defined('REWARD_HABIT_BASE_XP') ? (string) REWARD_HABIT_BASE_XP : '10',
    'REWARD_TASK_BASE_XP' => defined('REWARD_TASK_BASE_XP') ? (string) REWARD_TASK_BASE_XP : '12',
    'REWARD_GOAL_BASE_XP_DAILY' => defined('REWARD_GOAL_BASE_XP_DAILY') ? (string) REWARD_GOAL_BASE_XP_DAILY : '16',
    'REWARD_GOAL_BASE_XP_WEEKLY' => defined('REWARD_GOAL_BASE_XP_WEEKLY') ? (string) REWARD_GOAL_BASE_XP_WEEKLY : '30',
    'REWARD_GOAL_BASE_XP_MONTHLY' => defined('REWARD_GOAL_BASE_XP_MONTHLY') ? (string) REWARD_GOAL_BASE_XP_MONTHLY : '50',
    'REWARD_GOAL_BASE_XP_QUARTERLY' => defined('REWARD_GOAL_BASE_XP_QUARTERLY') ? (string) REWARD_GOAL_BASE_XP_QUARTERLY : '70',
    'REWARD_GOAL_BASE_XP_YEARLY' => defined('REWARD_GOAL_BASE_XP_YEARLY') ? (string) REWARD_GOAL_BASE_XP_YEARLY : '95',
    'REWARD_GOAL_BASE_XP_FUTURE' => defined('REWARD_GOAL_BASE_XP_FUTURE') ? (string) REWARD_GOAL_BASE_XP_FUTURE : '110',
    'INDULGENCE_REPEAT_COST_MULTIPLIER' => defined('INDULGENCE_REPEAT_COST_MULTIPLIER') ? (string) INDULGENCE_REPEAT_COST_MULTIPLIER : '1.25',
    'COSMETIC_PRICE_MULTIPLIER' => '1.0',
];

$balanceCurrent = array_merge($balanceDefaults, $settingsModel->getMany($balanceKeys));

// ----------------------
// BLOQUE DB
// ----------------------
$tables = $manager->getTables();
$defaultTable = $tables[0] ?? '';
$selectedTable = trim((string) ($_REQUEST['table'] ?? $defaultTable));
if ($selectedTable === '' || !in_array($selectedTable, $tables, true)) {
    $selectedTable = $defaultTable;
}

$previewLimit = (int) ($_REQUEST['limit'] ?? 25);
$previewLimit = max(1, min($previewLimit, (int) (defined('ADMIN_DB_MAX_ROWS') ? ADMIN_DB_MAX_ROWS : 200)));
$search = trim((string) ($_REQUEST['search'] ?? ''));
$page = max(1, (int) ($_REQUEST['page'] ?? 1));
$showFilterPanel = ($search !== '' || $previewLimit !== 25);

$columnsInfo = $selectedTable !== '' ? $manager->getTableColumns($selectedTable) : [];
$primaryKey = $selectedTable !== '' ? $manager->getPrimaryKey($selectedTable) : null;

$editableColumns = [];
foreach ($columnsInfo as $column) {
    $name = (string) ($column['Field'] ?? '');
    if ($name === '') {
        continue;
    }
    $editableColumns[] = $column;
}

$sqlResult = null;
$sqlText = '';
$showSqlConsole = false;

$baseDbParams = [
    'section' => 'db',
    'table' => $selectedTable,
    'limit' => $previewLimit,
    'search' => $search,
    'page' => $page,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_balance') {
        $section = 'balance';
        $values = [];
        foreach ($balanceKeys as $key) {
            $values[$key] = trim((string) ($_POST[$key] ?? $balanceDefaults[$key]));
        }

        $validationRules = [
            'REWARD_POINTS_PER_XP' => [0.1, 2.0],
            'REWARD_HABIT_BASE_XP' => [1, 100],
            'REWARD_TASK_BASE_XP' => [1, 120],
            'REWARD_GOAL_BASE_XP_DAILY' => [1, 300],
            'REWARD_GOAL_BASE_XP_WEEKLY' => [1, 300],
            'REWARD_GOAL_BASE_XP_MONTHLY' => [1, 400],
            'REWARD_GOAL_BASE_XP_QUARTERLY' => [1, 500],
            'REWARD_GOAL_BASE_XP_YEARLY' => [1, 700],
            'REWARD_GOAL_BASE_XP_FUTURE' => [1, 900],
            'INDULGENCE_REPEAT_COST_MULTIPLIER' => [1.0, 3.0],
            'COSMETIC_PRICE_MULTIPLIER' => [0.1, 3.0],
        ];

        $errors = [];
        foreach ($validationRules as $key => [$min, $max]) {
            if (!is_numeric($values[$key])) {
                $errors[] = $key . ' debe ser numerico.';
                continue;
            }
            $num = (float) $values[$key];
            if ($num < $min || $num > $max) {
                $errors[] = $key . ' debe estar entre ' . $min . ' y ' . $max . '.';
            }
        }

        if (empty($errors)) {
            $settingsModel->upsertMany($values);
            header('Location: database.php?section=balance&message=' . urlencode('Balance actualizado correctamente.') . '&type=success');
            exit;
        }

        $message = implode(' ', $errors);
        $messageType = 'error';
        $balanceCurrent = array_merge($balanceCurrent, $values);
    }

    if ($action === 'reset_balance') {
        $section = 'balance';
        $settingsModel->deleteMany($balanceKeys);
        header('Location: database.php?section=balance&message=' . urlencode('Valores reseteados a defaults de config.') . '&type=success');
        exit;
    }

    if ($action === 'change_password') {
        $section = 'balance';
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');
        $minPasswordLength = defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? (int) ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12;

        $hasUpper = preg_match('/[A-Z]/', $newPassword) === 1;
        $hasLower = preg_match('/[a-z]/', $newPassword) === 1;
        $hasDigit = preg_match('/\d/', $newPassword) === 1;
        $hasSymbol = preg_match('/[^a-zA-Z\d]/', $newPassword) === 1;

        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
            $message = 'Completa todos los campos de contrasena.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < max($minPasswordLength, 10)) {
            $message = 'La nueva contrasena debe tener al menos ' . max($minPasswordLength, 10) . ' caracteres.';
            $messageType = 'error';
        } elseif (!$hasUpper || !$hasLower || !$hasDigit || !$hasSymbol) {
            $message = 'La nueva contrasena debe incluir mayuscula, minuscula, numero y simbolo.';
            $messageType = 'error';
        } elseif (hash_equals($currentPassword, $newPassword)) {
            $message = 'La nueva contrasena debe ser diferente de la actual.';
            $messageType = 'error';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $message = 'La confirmacion de contrasena no coincide.';
            $messageType = 'error';
        } else {
            $admin = $adminUserModel->verifyCredentials($adminUsername, $currentPassword);
            if ($admin === null || (int) $admin['id'] !== $adminUserId) {
                $message = 'La contrasena actual no es correcta.';
                $messageType = 'error';
            } elseif ($adminUserModel->updatePasswordById($adminUserId, $newPassword)) {
                $message = 'Contrasena actualizada correctamente.';
                $messageType = 'success';
            } else {
                $message = 'No se pudo actualizar la contrasena.';
                $messageType = 'error';
            }
        }
    }

    if ($action === 'run_sql') {
        $section = 'db';
        $showSqlConsole = true;
        $sqlText = trim((string) ($_POST['sql'] ?? ''));
        $confirmWrite = ((string) ($_POST['confirm_write'] ?? '')) === 'yes';
        $keyword = strtoupper((string) (preg_split('/\s+/', ltrim($sqlText))[0] ?? ''));
        $isPotentialWrite = in_array($keyword, ['INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'CREATE', 'ALTER', 'DROP', 'TRUNCATE', 'RENAME'], true);

        if ($isPotentialWrite && !$confirmWrite) {
            $message = 'Para consultas de escritura o estructura debes marcar la confirmacion.';
            $messageType = 'error';
        } else {
            try {
                $sqlResult = $manager->executeQuery($sqlText);
                $message = (string) ($sqlResult['message'] ?? 'Consulta procesada.');
                $messageType = !empty($sqlResult['ok']) ? 'success' : 'error';
            } catch (Throwable $exception) {
                $message = 'Error SQL: ' . $exception->getMessage();
                $messageType = 'error';
            }
        }
    }

    if (in_array($action, ['create_row', 'update_row', 'delete_row'], true)) {
        $section = 'db';
        if (!(defined('ADMIN_DB_ALLOW_WRITE_QUERIES') && ADMIN_DB_ALLOW_WRITE_QUERIES === true)) {
            $message = 'CRUD visual deshabilitado. Activa ADMIN_DB_ALLOW_WRITE_QUERIES.';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'create_row') {
                    $payload = [];
                    foreach ($editableColumns as $column) {
                        $name = (string) ($column['Field'] ?? '');
                        if ($name !== '') {
                            $payload[$name] = (string) ($_POST['field_' . $name] ?? '');
                        }
                    }

                    $newId = $manager->insertRow($selectedTable, $payload);
                    $target = 'database.php?' . http_build_query(array_merge($baseDbParams, [
                        'message' => $newId > 0 ? 'Fila creada correctamente.' : 'No se pudo crear la fila.',
                        'type' => $newId > 0 ? 'success' : 'error',
                    ]));
                    header('Location: ' . $target);
                    exit;
                }

                if ($action === 'update_row') {
                    $primaryValue = (string) ($_POST['primary_value'] ?? '');
                    $payload = [];
                    foreach ($editableColumns as $column) {
                        $name = (string) ($column['Field'] ?? '');
                        if ($name === '' || ($primaryKey !== null && $name === $primaryKey)) {
                            continue;
                        }
                        $payload[$name] = (string) ($_POST['field_' . $name] ?? '');
                    }

                    $affected = ($primaryKey !== null)
                        ? $manager->updateRow($selectedTable, $primaryKey, $primaryValue, $payload)
                        : 0;

                    $target = 'database.php?' . http_build_query(array_merge($baseDbParams, [
                        'message' => $affected > 0 ? 'Fila actualizada correctamente.' : 'No hubo cambios en la fila.',
                        'type' => $affected > 0 ? 'success' : 'error',
                    ]));
                    header('Location: ' . $target);
                    exit;
                }

                if ($action === 'delete_row') {
                    $primaryValue = (string) ($_POST['primary_value'] ?? '');
                    $confirmDelete = ((string) ($_POST['confirm_delete'] ?? '')) === 'yes';

                    if (!$confirmDelete) {
                        $message = 'Marca la confirmacion para eliminar la fila.';
                        $messageType = 'error';
                    } else {
                        $affected = ($primaryKey !== null)
                            ? $manager->deleteRow($selectedTable, $primaryKey, $primaryValue)
                            : 0;

                        $target = 'database.php?' . http_build_query(array_merge($baseDbParams, [
                            'message' => $affected > 0 ? 'Fila eliminada correctamente.' : 'No se pudo eliminar la fila.',
                            'type' => $affected > 0 ? 'success' : 'error',
                        ]));
                        header('Location: ' . $target);
                        exit;
                    }
                }
            } catch (Throwable $exception) {
                $message = 'Error CRUD: ' . $exception->getMessage();
                $messageType = 'error';
            }
        }
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = (string) $_GET['message'];
    $messageType = (string) $_GET['type'];
}

$grid = $selectedTable !== ''
    ? $manager->getPaginatedRows($selectedTable, $page, $previewLimit, $search)
    : ['columns' => [], 'rows' => [], 'total' => 0, 'pages' => 1, 'page' => 1, 'limit' => $previewLimit];

$editValue = trim((string) ($_GET['edit'] ?? ''));
$editRow = null;
if ($selectedTable !== '' && $primaryKey !== null && $editValue !== '') {
    $editRow = $manager->getRowByPrimaryKey($selectedTable, $primaryKey, $editValue);
}

$modal = trim((string) ($_GET['modal'] ?? ''));
if (!in_array($modal, ['create', 'edit'], true)) {
    $modal = '';
}

if ($modal === 'edit' && ($editRow === null || $primaryKey === null)) {
    $modal = '';
}

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function inputTypeFromColumn(array $column): string
{
    $type = strtolower((string) ($column['Type'] ?? ''));

    if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type) === 1) {
        return 'number';
    }

    if (preg_match('/^(decimal|float|double)/', $type) === 1) {
        return 'number';
    }

    if (str_starts_with($type, 'date')) {
        return 'date';
    }

    return 'text';
}

function isTextAreaColumn(array $column): bool
{
    $type = strtolower((string) ($column['Type'] ?? ''));
    return str_contains($type, 'text');
}

function enumOptions(array $column): array
{
    $type = (string) ($column['Type'] ?? '');
    if (!str_starts_with(strtolower($type), 'enum(')) {
        return [];
    }

    $inside = substr($type, 5, -1);
    if ($inside === false) {
        return [];
    }

    $parts = str_getcsv($inside, ',', "'", '\\');
    return array_values(array_filter(array_map('trim', $parts), static fn(string $v): bool => $v !== ''));
}

function createPrefillValue(string $table, string $field): string
{
    if ($table !== 'users') {
        return '';
    }

    return match ($field) {
        'level' => '1',
        'xp', 'points', 'current_streak' => '0',
        'hp', 'max_hp' => '1000',
        default => '',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin | <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/admin_panel.css">
</head>
<body class="admin-shell">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <strong>LifeQuest Admin</strong>
                <small>Data Control Panel</small>
            </div>

            <nav class="admin-nav">
                <a class="<?= $section === 'db' ? 'active' : '' ?>" href="database.php?section=db">
                    <span class="dot"></span>
                    Base de datos
                </a>
                <?php if ($section === 'db' && !empty($tables)): ?>
                    <div class="admin-subnav" aria-label="Tablas de base de datos">
                        <?php foreach ($tables as $table): ?>
                            <?php $tableParams = ['section' => 'db', 'table' => $table, 'limit' => $previewLimit, 'search' => $search, 'page' => 1]; ?>
                            <a class="<?= $selectedTable === $table ? 'active' : '' ?>" href="database.php?<?= e(http_build_query($tableParams)) ?>"><?= e($table) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a class="<?= $section === 'balance' ? 'active' : '' ?>" href="database.php?section=balance">
                    <span class="dot"></span>
                    Balance
                </a>
                <a href="logout.php">
                    <span class="dot"></span>
                    Cerrar sesion
                </a>
            </nav>

            <div class="admin-side-note">
                Escritura SQL: <?= (defined('ADMIN_DB_ALLOW_WRITE_QUERIES') && ADMIN_DB_ALLOW_WRITE_QUERIES === true) ? 'ACTIVA' : 'BLOQUEADA' ?><br>
                Esquema SQL: <?= (defined('ADMIN_DB_ALLOW_SCHEMA_QUERIES') && ADMIN_DB_ALLOW_SCHEMA_QUERIES === true) ? 'ACTIVO' : 'BLOQUEADO' ?>
            </div>
        </aside>

        <section class="admin-main">
            <header class="admin-topbar">
                <div>
                    <h1><?= $section === 'db' ? 'Database Views' : 'Balance Manager' ?></h1>
                    <p><?= $section === 'db' ? 'Vista administrativa de tablas, estructura y datos en vivo.' : 'Ajusta economia y recompensas desde el mismo panel admin.' ?></p>
                </div>
                <div class="admin-top-actions">
                    <span class="admin-user-chip">Usuario: <?= e($adminUsername) ?></span>
                </div>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
                <?php endif; ?>

                <section class="admin-kpi-grid">
                    <article class="admin-kpi"><span>Usuarios</span><strong><?= (string) ((int) ($overview['users'] ?? 0)) ?></strong></article>
                    <article class="admin-kpi"><span>Metas</span><strong><?= (string) ((int) ($overview['goals'] ?? 0)) ?></strong></article>
                    <article class="admin-kpi"><span>Proyectos</span><strong><?= (string) ((int) ($overview['projects'] ?? 0)) ?></strong></article>
                    <article class="admin-kpi"><span>Tareas</span><strong><?= (string) ((int) ($overview['tasks'] ?? 0)) ?></strong></article>
                    <article class="admin-kpi"><span>Habitos</span><strong><?= (string) ((int) ($overview['habits'] ?? 0)) ?></strong></article>
                    <article class="admin-kpi"><span>Recompensas</span><strong><?= (string) ((int) ($overview['rewards'] ?? 0)) ?></strong></article>
                </section>

                <?php if ($section === 'db'): ?>
                    <section class="admin-panel-grid db-layout">
                        <article class="admin-card admin-card-secondary">
                            <div class="admin-card-head">
                                <h2>Controles</h2>
                                <span class="admin-muted">Secundario</span>
                            </div>
                            <div class="admin-card-body admin-stack">
                                <details class="admin-sql-details" <?= $showFilterPanel ? 'open' : '' ?>>
                                    <summary>Filtros y paginacion</summary>
                                    <form method="GET" class="admin-form admin-stack" style="margin-top:10px;">
                                        <input type="hidden" name="section" value="db">
                                        <input type="hidden" name="table" value="<?= e($selectedTable) ?>">
                                        <div class="admin-row-2">
                                            <label>Filas por pagina
                                                <input type="number" min="1" max="<?= (int) (defined('ADMIN_DB_MAX_ROWS') ? ADMIN_DB_MAX_ROWS : 200) ?>" name="limit" value="<?= (string) $previewLimit ?>">
                                            </label>
                                            <label>Buscar
                                                <input type="text" name="search" value="<?= e($search) ?>" placeholder="email, nombre...">
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-primary full">Aplicar filtros</button>
                                        <a class="btn btn-secondary full" href="database.php?section=db&table=<?= e($selectedTable) ?>">Limpiar</a>
                                    </form>
                                </details>

                                <details class="admin-sql-details" <?= $showSqlConsole ? 'open' : '' ?>>
                                    <summary>Consola SQL</summary>
                                    <form method="POST" class="admin-form admin-stack" style="margin-top:10px;">
                                        <input type="hidden" name="action" value="run_sql">
                                        <input type="hidden" name="section" value="db">
                                        <label>Consulta SQL
                                            <textarea name="sql" rows="6" placeholder="SELECT * FROM users LIMIT 20;"><?= e($sqlText) ?></textarea>
                                        </label>
                                        <label>
                                            <input type="checkbox" name="confirm_write" value="yes"> Confirmo ejecucion con cambios
                                        </label>
                                        <button type="submit" class="btn btn-secondary full">Ejecutar SQL</button>
                                    </form>
                                </details>
                            </div>
                        </article>

                        <article class="admin-card admin-card-primary">
                            <div class="admin-card-head">
                                <h2>Data View: <?= e($selectedTable) ?></h2>
                                <div class="admin-head-actions">
                                    <?php $createParams = array_merge($baseDbParams, ['modal' => 'create']); ?>
                                    <a class="admin-inline-btn primary" href="database.php?<?= e(http_build_query($createParams)) ?>">+ Nuevo</a>
                                    <span class="admin-muted">Filas: <?= (string) ((int) ($grid['total'] ?? 0)) ?></span>
                                    <span class="admin-muted">Pag: <?= (string) ((int) ($grid['page'] ?? 1)) ?>/<?= (string) ((int) ($grid['pages'] ?? 1)) ?></span>
                                    <?php if ($primaryKey !== null && $primaryKey !== ''): ?>
                                        <span class="admin-muted">PK: <?= e($primaryKey) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="admin-card-body admin-stack">
                                <div class="admin-table-wrap">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <?php if ($primaryKey !== null): ?><th>Action</th><?php endif; ?>
                                                <?php foreach (($grid['columns'] ?? []) as $col): ?>
                                                    <th><?= e((string) $col) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($grid['rows'] ?? []) as $row): ?>
                                                <tr>
                                                    <?php if ($primaryKey !== null): ?>
                                                        <td>
                                                            <?php $editParams = array_merge($baseDbParams, ['edit' => (string) ($row[$primaryKey] ?? '')]); ?>
                                                            <?php $editModalParams = array_merge($editParams, ['modal' => 'edit']); ?>
                                                            <div class="admin-row-actions">
                                                                <a class="admin-inline-btn" href="database.php?<?= e(http_build_query($editModalParams)) ?>">Editar</a>
                                                                <form method="POST" onsubmit="return confirm('¿Eliminar este registro?');">
                                                                    <input type="hidden" name="action" value="delete_row">
                                                                    <input type="hidden" name="section" value="db">
                                                                    <input type="hidden" name="table" value="<?= e($selectedTable) ?>">
                                                                    <input type="hidden" name="limit" value="<?= (string) $previewLimit ?>">
                                                                    <input type="hidden" name="search" value="<?= e($search) ?>">
                                                                    <input type="hidden" name="page" value="<?= (string) ((int) ($grid['page'] ?? 1)) ?>">
                                                                    <input type="hidden" name="primary_value" value="<?= e((string) ($row[$primaryKey] ?? '')) ?>">
                                                                    <input type="hidden" name="confirm_delete" value="yes">
                                                                    <button type="submit" class="admin-inline-btn danger">Eliminar</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php foreach (($grid['columns'] ?? []) as $col): ?>
                                                        <td><?= e((string) ($row[$col] ?? '')) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ((int) ($grid['pages'] ?? 1) > 1): ?>
                                    <div class="admin-pager">
                                        <?php
                                        $currentPage = (int) ($grid['page'] ?? 1);
                                        $totalPages = (int) ($grid['pages'] ?? 1);
                                        $prev = max(1, $currentPage - 1);
                                        $next = min($totalPages, $currentPage + 1);
                                        $prevParams = array_merge($baseDbParams, ['page' => $prev]);
                                        $nextParams = array_merge($baseDbParams, ['page' => $next]);
                                        ?>
                                        <a class="btn btn-secondary" href="database.php?<?= e(http_build_query($prevParams)) ?>">Anterior</a>
                                        <span class="admin-muted">Pagina <?= (string) $currentPage ?> / <?= (string) $totalPages ?></span>
                                        <a class="btn btn-secondary" href="database.php?<?= e(http_build_query($nextParams)) ?>">Siguiente</a>
                                    </div>
                                <?php endif; ?>

                                <details class="admin-sql-details">
                                    <summary>Estructura de columnas</summary>
                                    <div class="admin-table-wrap" style="margin-top:10px;">
                                        <table class="admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Type</th>
                                                    <th>Null</th>
                                                    <th>Key</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($columnsInfo as $column): ?>
                                                    <tr>
                                                        <td><?= e((string) ($column['Field'] ?? '')) ?></td>
                                                        <td><?= e((string) ($column['Type'] ?? '')) ?></td>
                                                        <td><?= e((string) ($column['Null'] ?? '')) ?></td>
                                                        <td><?= e((string) ($column['Key'] ?? '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </details>

                                <?php if ($sqlResult !== null): ?>
                                    <div class="admin-card" style="border-radius:10px;">
                                        <div class="admin-card-head">
                                            <h2>SQL Result</h2>
                                            <span class="admin-muted">Rows: <?= (string) ((int) ($sqlResult['affected'] ?? 0)) ?></span>
                                        </div>
                                        <div class="admin-card-body">
                                            <?php if (!empty($sqlResult['columns'])): ?>
                                                <div class="admin-table-wrap">
                                                    <table class="admin-table">
                                                        <thead>
                                                            <tr>
                                                                <?php foreach ($sqlResult['columns'] as $col): ?>
                                                                    <th><?= e((string) $col) ?></th>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach (($sqlResult['rows'] ?? []) as $row): ?>
                                                                <tr>
                                                                    <?php foreach ($sqlResult['columns'] as $col): ?>
                                                                        <td><?= e((string) ($row[$col] ?? '')) ?></td>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    </section>

                    <?php if ($modal !== ''): ?>
                        <div class="admin-modal-overlay">
                            <div class="admin-modal">
                                <div class="admin-card-head">
                                    <h2><?= $modal === 'create' ? 'Nuevo registro' : 'Editar registro' ?> en <?= e($selectedTable) ?></h2>
                                    <a class="admin-inline-btn" href="database.php?<?= e(http_build_query($baseDbParams)) ?>">Cerrar</a>
                                </div>
                                <div class="admin-card-body">
                                    <form method="POST" class="admin-form admin-stack">
                                        <input type="hidden" name="action" value="<?= $modal === 'create' ? 'create_row' : 'update_row' ?>">
                                        <input type="hidden" name="section" value="db">
                                        <input type="hidden" name="table" value="<?= e($selectedTable) ?>">
                                        <input type="hidden" name="limit" value="<?= (string) $previewLimit ?>">
                                        <input type="hidden" name="search" value="<?= e($search) ?>">
                                        <input type="hidden" name="page" value="<?= (string) ((int) ($grid['page'] ?? 1)) ?>">

                                        <?php if ($modal === 'edit' && $editRow !== null && $primaryKey !== null): ?>
                                            <input type="hidden" name="primary_value" value="<?= e((string) ($editRow[$primaryKey] ?? '')) ?>">
                                        <?php endif; ?>

                                        <?php foreach ($editableColumns as $column): ?>
                                            <?php
                                            $field = (string) ($column['Field'] ?? '');
                                            $extra = strtolower((string) ($column['Extra'] ?? ''));
                                            if ($field === '') {
                                                continue;
                                            }
                                            if ($modal === 'create' && str_contains($extra, 'auto_increment')) {
                                                continue;
                                            }
                                            if ($modal === 'edit' && $primaryKey !== null && $field === $primaryKey) {
                                                continue;
                                            }
                                            $currentValue = ($modal === 'edit' && $editRow !== null)
                                                ? (string) ($editRow[$field] ?? '')
                                                : createPrefillValue($selectedTable, $field);
                                            $isNotNull = strtoupper((string) ($column['Null'] ?? 'NO')) === 'NO';
                                            $hasDbDefault = array_key_exists('Default', $column) && $column['Default'] !== null;
                                            $required = ($isNotNull && !$hasDbDefault) ? 'required' : '';
                                            $enum = enumOptions($column);
                                            ?>
                                            <label><?= e($field) ?>
                                                <?php if (!empty($enum)): ?>
                                                    <select name="field_<?= e($field) ?>" <?= $required ?>>
                                                        <?php if ($required === ''): ?>
                                                            <option value="">(null)</option>
                                                        <?php endif; ?>
                                                        <?php foreach ($enum as $option): ?>
                                                            <option value="<?= e($option) ?>" <?= $currentValue === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif (isTextAreaColumn($column)): ?>
                                                    <textarea name="field_<?= e($field) ?>" rows="3" <?= $required ?>><?= e($currentValue) ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?= e(inputTypeFromColumn($column)) ?>" name="field_<?= e($field) ?>" value="<?= e($currentValue) ?>" <?= $required ?>>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>

                                        <button type="submit" class="btn btn-primary full"><?= $modal === 'create' ? 'Crear registro' : 'Guardar cambios' ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <section class="admin-panel-grid" style="grid-template-columns: minmax(340px, 500px) minmax(0, 1fr);">
                        <article class="admin-card">
                            <div class="admin-card-head">
                                <h2>Balance Settings</h2>
                            </div>
                            <div class="admin-card-body admin-stack">
                                <form method="POST" class="admin-form admin-stack">
                                    <input type="hidden" name="action" value="save_balance">
                                    <input type="hidden" name="section" value="balance">

                                    <div class="admin-row-2">
                                        <label>Points por XP
                                            <input type="number" step="0.01" min="0.1" max="2" name="REWARD_POINTS_PER_XP" value="<?= e((string) $balanceCurrent['REWARD_POINTS_PER_XP']) ?>">
                                        </label>
                                        <label>Base XP habito
                                            <input type="number" min="1" max="100" name="REWARD_HABIT_BASE_XP" value="<?= e((string) $balanceCurrent['REWARD_HABIT_BASE_XP']) ?>">
                                        </label>
                                    </div>

                                    <div class="admin-row-2">
                                        <label>Base XP mision
                                            <input type="number" min="1" max="120" name="REWARD_TASK_BASE_XP" value="<?= e((string) $balanceCurrent['REWARD_TASK_BASE_XP']) ?>">
                                        </label>
                                        <label>Goal diario
                                            <input type="number" min="1" max="300" name="REWARD_GOAL_BASE_XP_DAILY" value="<?= e((string) $balanceCurrent['REWARD_GOAL_BASE_XP_DAILY']) ?>">
                                        </label>
                                    </div>

                                    <div class="admin-row-2">
                                        <label>Goal semanal
                                            <input type="number" min="1" max="300" name="REWARD_GOAL_BASE_XP_WEEKLY" value="<?= e((string) $balanceCurrent['REWARD_GOAL_BASE_XP_WEEKLY']) ?>">
                                        </label>
                                        <label>Goal mensual
                                            <input type="number" min="1" max="400" name="REWARD_GOAL_BASE_XP_MONTHLY" value="<?= e((string) $balanceCurrent['REWARD_GOAL_BASE_XP_MONTHLY']) ?>">
                                        </label>
                                    </div>

                                    <div class="admin-row-2">
                                        <label>Goal trimestral
                                            <input type="number" min="1" max="500" name="REWARD_GOAL_BASE_XP_QUARTERLY" value="<?= e((string) $balanceCurrent['REWARD_GOAL_BASE_XP_QUARTERLY']) ?>">
                                        </label>
                                        <label>Goal anual
                                            <input type="number" min="1" max="700" name="REWARD_GOAL_BASE_XP_YEARLY" value="<?= e((string) $balanceCurrent['REWARD_GOAL_BASE_XP_YEARLY']) ?>">
                                        </label>
                                    </div>

                                    <div class="admin-row-2">
                                        <label>Goal futuro
                                            <input type="number" min="1" max="900" name="REWARD_GOAL_BASE_XP_FUTURE" value="<?= e((string) $balanceCurrent['REWARD_GOAL_BASE_XP_FUTURE']) ?>">
                                        </label>
                                        <label>Indulgencia repetida
                                            <input type="number" step="0.01" min="1" max="3" name="INDULGENCE_REPEAT_COST_MULTIPLIER" value="<?= e((string) $balanceCurrent['INDULGENCE_REPEAT_COST_MULTIPLIER']) ?>">
                                        </label>
                                    </div>

                                    <label>Multiplicador cosmetico
                                        <input type="number" step="0.01" min="0.1" max="3" name="COSMETIC_PRICE_MULTIPLIER" value="<?= e((string) $balanceCurrent['COSMETIC_PRICE_MULTIPLIER']) ?>">
                                    </label>

                                    <button type="submit" class="btn btn-primary full">Guardar balance</button>
                                </form>

                                <form method="POST" class="admin-form">
                                    <input type="hidden" name="action" value="reset_balance">
                                    <input type="hidden" name="section" value="balance">
                                    <button type="submit" class="btn btn-secondary full">Resetear a defaults</button>
                                </form>
                            </div>
                        </article>

                        <article class="admin-card">
                            <div class="admin-card-head">
                                <h2>Seguridad de acceso</h2>
                            </div>
                            <div class="admin-card-body admin-stack">
                                <form method="POST" class="admin-form admin-stack" autocomplete="off">
                                    <input type="hidden" name="action" value="change_password">
                                    <input type="hidden" name="section" value="balance">

                                    <label>Contrasena actual
                                        <input type="password" name="current_password" required autocomplete="current-password">
                                    </label>
                                    <label>Nueva contrasena
                                        <input type="password" name="new_password" minlength="<?= (int) (defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12) ?>" required autocomplete="new-password">
                                    </label>
                                    <label>Confirmar contrasena nueva
                                        <input type="password" name="new_password_confirm" minlength="<?= (int) (defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12) ?>" required autocomplete="new-password">
                                    </label>
                                    <p class="admin-muted">Minimo <?= (int) (defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12) ?> caracteres, con mayuscula, minuscula, numero y simbolo.</p>
                                    <button type="submit" class="btn btn-primary full">Actualizar contrasena</button>
                                </form>
                            </div>
                        </article>
                    </section>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
