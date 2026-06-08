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
if (!in_array($section, ['db', 'balance', 'shop', 'players'], true)) {
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
$shopTypes = [
    'all' => 'Todos',
    'indulgence' => 'Indulgencias',
    'cosmetic' => 'Cosmeticos',
];
$cosmeticCategories = [
    'outfit' => 'Outfit',
    'avatar' => 'Avatar',
    'accesorio' => 'Accesorio',
    'marco' => 'Marco',
    'fondo' => 'Fondo',
    'stickers' => 'Stickers',
    'companero' => 'Companero',
    'tema' => 'Tema',
    'cosmetico' => 'Cosmetico',
];
$shopFilter = trim((string) ($_REQUEST['shop_filter'] ?? 'all'));
if (!array_key_exists($shopFilter, $shopTypes)) {
    $shopFilter = 'all';
}

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

    if ($action === 'create_shop_reward') {
        $section = 'shop';
        try {
            $uploadedImagePath = adminStoreRewardImageFile($_FILES['image_file'] ?? null);
            $fallbackImagePath = adminNormalizeRewardImageReference((string) ($_POST['image_path'] ?? ''));
            $created = $manager->createShopReward([
                'target_user_id' => (int) ($_POST['target_user_id'] ?? 0),
                'name' => (string) ($_POST['name'] ?? ''),
                'description' => (string) ($_POST['description'] ?? ''),
                'image_path' => $uploadedImagePath !== '' ? $uploadedImagePath : $fallbackImagePath,
                'cost_points' => (int) ($_POST['cost_points'] ?? 0),
                'category' => (string) ($_POST['category'] ?? ''),
                'shop_type' => (string) ($_POST['shop_type'] ?? 'indulgence'),
                'effect_hp' => (int) ($_POST['effect_hp'] ?? 0),
                'weekly_limit' => (int) ($_POST['weekly_limit'] ?? 1),
                'active' => (string) ($_POST['active'] ?? '0') === '1',
            ]);

            header('Location: database.php?section=shop&message=' . urlencode($created > 0 ? 'Recompensas creadas: ' . $created . '.' : 'No se creo ninguna recompensa. Revisa nombre, coste o duplicados.') . '&type=' . ($created > 0 ? 'success' : 'error'));
            exit;
        } catch (Throwable $exception) {
            $message = 'Error creando recompensa: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update_shop_reward') {
        $section = 'shop';
        $rewardId = (int) ($_POST['reward_id'] ?? 0);
        try {
            $uploadedImagePath = adminStoreRewardImageFile($_FILES['image_file'] ?? null);
            $fallbackImagePath = adminNormalizeRewardImageReference((string) ($_POST['image_path'] ?? ''));

            $ok = $manager->updateShopReward($rewardId, [
                'name' => (string) ($_POST['name'] ?? ''),
                'description' => (string) ($_POST['description'] ?? ''),
                'image_path' => $uploadedImagePath !== '' ? $uploadedImagePath : $fallbackImagePath,
                'cost_points' => (int) ($_POST['cost_points'] ?? 0),
                'category' => (string) ($_POST['category'] ?? ''),
                'shop_type' => (string) ($_POST['shop_type'] ?? 'indulgence'),
                'effect_hp' => (int) ($_POST['effect_hp'] ?? 0),
                'weekly_limit' => (int) ($_POST['weekly_limit'] ?? 1),
                'active' => (string) ($_POST['active'] ?? '0') === '1',
            ]);

            $targetMessage = $ok ? 'Recompensa actualizada correctamente.' : 'No se pudo actualizar la recompensa.';
            header('Location: database.php?section=shop&shop_filter=' . urlencode($shopFilter) . '&message=' . urlencode($targetMessage) . '&type=' . ($ok ? 'success' : 'error'));
            exit;
        } catch (Throwable $exception) {
            $message = 'Error editando recompensa: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'set_reward_active') {
        $section = 'shop';
        $rewardId = (int) ($_POST['reward_id'] ?? 0);
        $active = (string) ($_POST['active'] ?? '0') === '1';

        try {
            $ok = $manager->setRewardActive($rewardId, $active);
            header('Location: database.php?section=shop&shop_filter=' . urlencode($shopFilter) . '&message=' . urlencode($ok ? 'Estado de recompensa actualizado.' : 'No se pudo actualizar la recompensa.') . '&type=' . ($ok ? 'success' : 'error'));
            exit;
        } catch (Throwable $exception) {
            $message = 'Error actualizando recompensa: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'delete_shop_reward') {
        $section = 'shop';
        $rewardId = (int) ($_POST['reward_id'] ?? 0);

        try {
            $ok = $manager->deleteShopReward($rewardId);
            header('Location: database.php?section=shop&shop_filter=' . urlencode($shopFilter) . '&message=' . urlencode($ok ? 'Recompensa eliminada del catalogo global.' : 'No se pudo eliminar la recompensa.') . '&type=' . ($ok ? 'success' : 'error'));
            exit;
        } catch (Throwable $exception) {
            $message = 'Error eliminando recompensa: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'grant_inventory_item') {
        $section = 'shop';
        try {
            $ok = $manager->grantInventoryItem((int) ($_POST['user_id'] ?? 0), (int) ($_POST['reward_id'] ?? 0));
            header('Location: database.php?section=shop&message=' . urlencode($ok ? 'Cosmetico concedido al inventario.' : 'No se pudo conceder el cosmetico. Debe pertenecer al usuario seleccionado.') . '&type=' . ($ok ? 'success' : 'error'));
            exit;
        } catch (Throwable $exception) {
            $message = 'Error concediendo inventario: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'update_player_stats') {
        $section = 'players';
        try {
            $ok = $manager->updatePlayerStats((int) ($_POST['user_id'] ?? 0), [
                'points' => (int) ($_POST['points'] ?? 0),
                'xp' => (int) ($_POST['xp'] ?? 0),
                'level' => (int) ($_POST['level'] ?? 1),
                'hp' => (int) ($_POST['hp'] ?? 0),
                'max_hp' => (int) ($_POST['max_hp'] ?? 0),
            ]);
            header('Location: database.php?section=players&message=' . urlencode($ok ? 'Jugador actualizado correctamente.' : 'No se pudo actualizar el jugador.') . '&type=' . ($ok ? 'success' : 'error'));
            exit;
        } catch (Throwable $exception) {
            $message = 'Error actualizando jugador: ' . $exception->getMessage();
            $messageType = 'error';
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

$adminUsers = $manager->getAdminUsers();
$shopSummary = $section === 'shop' ? $manager->getShopSummary() : [];
$shopRewards = $section === 'shop' ? $manager->getShopRewards($shopFilter) : [];
$shopInventory = $section === 'shop' ? $manager->getShopInventory() : [];
$shopCosmeticRewards = $section === 'shop' ? $manager->getShopRewards('cosmetic', 200) : [];
$playerRows = $section === 'players' ? $adminUsers : [];
$shopEditRewardId = (int) ($_GET['shop_edit_id'] ?? 0);
$shopEditReward = ($section === 'shop' && $shopEditRewardId > 0)
    ? $manager->getShopRewardById($shopEditRewardId)
    : null;

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

function adminNormalizeRewardImageReference(string $value): string
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

function adminStoreRewardImageFile(?array $file): string
{
    if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('La imagen subida no es valida.');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false || empty($imageInfo['mime'])) {
        throw new RuntimeException('El archivo debe ser una imagen valida.');
    }

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $mime = (string) $imageInfo['mime'];
    if (!array_key_exists($mime, $mimeToExtension)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG, GIF o WEBP.');
    }

    $uploadDir = __DIR__ . '/../assets/uploads/shop_rewards';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('No se pudo preparar la carpeta de subida.');
    }

    $filename = 'reward_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $mimeToExtension[$mime];
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('No se pudo guardar la imagen subida.');
    }

    return '../assets/uploads/shop_rewards/' . $filename;
}

function adminSectionTitle(string $section): string
{
    return match ($section) {
        'shop' => 'Shop Manager',
        'players' => 'Player Manager',
        'balance' => 'Balance Manager',
        default => 'Database Views',
    };
}

function adminSectionSubtitle(string $section): string
{
    return match ($section) {
        'shop' => 'Gestiona catalogo, inventario y equipamiento visual desde una vista dedicada.',
        'players' => 'Ajusta economia, XP, nivel y vida de los usuarios sin tocar tablas crudas.',
        'balance' => 'Ajusta economia y recompensas desde el mismo panel admin.',
        default => 'Vista administrativa de tablas, estructura y datos en vivo.',
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
                <a class="<?= $section === 'shop' ? 'active' : '' ?>" href="database.php?section=shop">
                    <span class="dot"></span>
                    Tienda
                </a>
                <a class="<?= $section === 'players' ? 'active' : '' ?>" href="database.php?section=players">
                    <span class="dot"></span>
                    Jugadores
                </a>
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
                    <h1><?= e(adminSectionTitle($section)) ?></h1>
                    <p><?= e(adminSectionSubtitle($section)) ?></p>
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
                    <article class="admin-kpi"><span>Inventario</span><strong><?= (string) ((int) ($overview['inventory'] ?? 0)) ?></strong></article>
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
                                                        <td><?= e($manager->displayValue($selectedTable, (string) $col, $row[$col] ?? null)) ?></td>
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
                <?php elseif ($section === 'shop'): ?>
                    <section class="admin-kpi-grid admin-kpi-grid-compact">
                        <article class="admin-kpi"><span>Indulgencias</span><strong><?= (string) ((int) ($shopSummary['indulgences'] ?? 0)) ?></strong></article>
                        <article class="admin-kpi"><span>Cosmeticos</span><strong><?= (string) ((int) ($shopSummary['cosmetics'] ?? 0)) ?></strong></article>
                        <article class="admin-kpi"><span>Inventario</span><strong><?= (string) ((int) ($shopSummary['inventory'] ?? 0)) ?></strong></article>
                        <article class="admin-kpi"><span>Equipados</span><strong><?= (string) ((int) ($shopSummary['equipped'] ?? 0)) ?></strong></article>
                        <article class="admin-kpi"><span>Canjes</span><strong><?= (string) ((int) ($shopSummary['redemptions'] ?? 0)) ?></strong></article>
                    </section>

                    <section class="admin-panel-grid shop-admin-layout">
                        <article class="admin-card">
                                <div class="admin-card-head">
                                    <h2>Nueva recompensa</h2>
                                    <span class="admin-muted">Catalogo</span>
                                </div>
                                <div class="admin-card-body">
                                    <form method="POST" enctype="multipart/form-data" class="admin-form admin-stack">
                                        <input type="hidden" name="action" value="create_shop_reward">
                                        <input type="hidden" name="section" value="shop">

                                        <div class="admin-note">El catalogo es global: cualquier cambio se aplica a todos los usuarios.</div>

                                    <div class="admin-row-2">
                                        <label>Tipo
                                            <select name="shop_type">
                                                <option value="indulgence">Indulgencia</option>
                                                <option value="cosmetic">Cosmetico</option>
                                            </select>
                                        </label>
                                        <label>Categoria
                                            <select name="category">
                                                <option value="indulgencia">Indulgencia</option>
                                                <?php foreach ($cosmeticCategories as $value => $label): ?>
                                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>

                                    <label>Nombre
                                        <input type="text" name="name" maxlength="150" required>
                                    </label>

                                    <label>Descripcion
                                        <textarea name="description" rows="3"></textarea>
                                    </label>

                                    <label>Imagen de card
                                        <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                    </label>

                                    <label>Ruta o URL alternativa
                                        <input type="text" name="image_path" maxlength="255" placeholder="Opcional si no subes archivo">
                                    </label>

                                    <div class="admin-row-2">
                                        <label>Coste LifeCoins
                                            <input type="number" min="1" name="cost_points" value="100" required>
                                        </label>
                                        <label>HP efecto
                                            <input type="number" min="0" name="effect_hp" value="0">
                                        </label>
                                    </div>

                                    <div class="admin-row-2">
                                        <label>Limite semanal
                                            <input type="number" min="1" name="weekly_limit" value="1">
                                        </label>
                                        <label>Estado
                                            <select name="active">
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </label>
                                    </div>

                                        <button type="submit" class="btn btn-primary full">Crear recompensa</button>
                                    </form>
                                </div>
                            </article>

                        <article class="admin-card">
                            <div class="admin-card-head">
                                <h2>Catalogo tienda</h2>
                                <form method="GET" class="admin-inline-form">
                                    <input type="hidden" name="section" value="shop">
                                    <select name="shop_filter" onchange="this.form.submit()">
                                        <?php foreach ($shopTypes as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= $shopFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <div class="admin-card-body">
                                <div class="admin-table-wrap">
                                    <table class="admin-table admin-table-compact">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Imagen</th>
                                                <th>Usuario</th>
                                                <th>Tipo</th>
                                                <th>Categoria</th>
                                                <th>Coste</th>
                                                <th>HP</th>
                                                <th>Limite</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($shopRewards as $reward): ?>
                                                <?php $editRewardParams = ['section' => 'shop', 'shop_filter' => $shopFilter, 'shop_edit_id' => (int) $reward['id']]; ?>
                                                <tr>
                                                    <td><strong><?= e((string) $reward['name']) ?></strong><br><small><?= e((string) $reward['description']) ?></small></td>
                                                    <td>
                                                        <?php if (!empty($reward['image_path'])): ?>
                                                            <img class="admin-shop-thumb" src="<?= e((string) $reward['image_path']) ?>" alt="">
                                                        <?php else: ?>
                                                            <span class="admin-muted">Sin imagen</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= !empty($reward['user_id']) ? ('#' . (int) $reward['user_id'] . '<br><small>' . e((string) $reward['user_email']) . '</small>') : 'Global' ?></td>
                                                    <td><?= e((string) $reward['shop_type']) ?></td>
                                                    <td><?= e((string) $reward['category']) ?></td>
                                                    <td><?= number_format((int) $reward['cost_points'], 0, ',', '.') ?></td>
                                                    <td><?= (int) $reward['effect_hp'] ?></td>
                                                    <td><?= (int) $reward['weekly_limit'] ?></td>
                                                    <td>
                                                        <form method="POST" class="admin-row-actions">
                                                            <a class="admin-inline-btn" href="database.php?<?= e(http_build_query($editRewardParams)) ?>">Editar</a>
                                                            <input type="hidden" name="action" value="set_reward_active">
                                                            <input type="hidden" name="section" value="shop">
                                                            <input type="hidden" name="shop_filter" value="<?= e($shopFilter) ?>">
                                                            <input type="hidden" name="reward_id" value="<?= (int) $reward['id'] ?>">
                                                            <input type="hidden" name="active" value="<?= !empty($reward['active']) ? '0' : '1' ?>">
                                                            <button type="submit" class="admin-inline-btn <?= !empty($reward['active']) ? '' : 'primary' ?>"><?= !empty($reward['active']) ? 'Desactivar' : 'Activar' ?></button>
                                                        </form>
                                                        <form method="POST" class="admin-row-actions" onsubmit="return confirm('¿Eliminar definitivamente esta recompensa global? Se borrará también de inventarios y canjes asociados.');">
                                                            <input type="hidden" name="action" value="delete_shop_reward">
                                                            <input type="hidden" name="section" value="shop">
                                                            <input type="hidden" name="shop_filter" value="<?= e($shopFilter) ?>">
                                                            <input type="hidden" name="reward_id" value="<?= (int) $reward['id'] ?>">
                                                            <button type="submit" class="admin-inline-btn danger">Eliminar</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($shopRewards)): ?>
                                                <tr><td colspan="9">Sin recompensas para este filtro.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </article>
                    </section>

                    <?php if ($shopEditReward !== null): ?>
                        <div class="admin-modal-overlay">
                            <div class="admin-modal admin-modal-wide">
                                <div class="admin-card-head">
                                    <h2>Editar recompensa</h2>
                                    <a class="admin-inline-btn" href="database.php?section=shop&shop_filter=<?= e($shopFilter) ?>">Cerrar</a>
                                </div>
                                <div class="admin-card-body">
                                    <form method="POST" enctype="multipart/form-data" class="admin-form admin-stack">
                                        <input type="hidden" name="action" value="update_shop_reward">
                                        <input type="hidden" name="section" value="shop">
                                        <input type="hidden" name="reward_id" value="<?= (int) $shopEditReward['id'] ?>">

                                        <div class="admin-row-2">
                                            <label>Origen
                                                <input type="text" value="<?= !empty($shopEditReward['user_id']) ? ('#' . (int) $shopEditReward['user_id'] . ' · ' . (string) $shopEditReward['user_name']) : 'Catalogo global' ?>" disabled>
                                            </label>
                                            <label>Estado
                                                <select name="active">
                                                    <option value="1" <?= !empty($shopEditReward['active']) ? 'selected' : '' ?>>Activo</option>
                                                    <option value="0" <?= empty($shopEditReward['active']) ? 'selected' : '' ?>>Inactivo</option>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="admin-row-2">
                                            <label>Tipo
                                                <select name="shop_type">
                                                    <option value="indulgence" <?= (($shopEditReward['shop_type'] ?? '') === 'indulgence') ? 'selected' : '' ?>>Indulgencia</option>
                                                    <option value="cosmetic" <?= (($shopEditReward['shop_type'] ?? '') === 'cosmetic') ? 'selected' : '' ?>>Cosmetico</option>
                                                </select>
                                            </label>
                                            <label>Categoria
                                                <input type="text" name="category" value="<?= e((string) ($shopEditReward['category'] ?? '')) ?>" maxlength="100">
                                            </label>
                                        </div>

                                        <label>Nombre
                                            <input type="text" name="name" value="<?= e((string) ($shopEditReward['name'] ?? '')) ?>" maxlength="150" required>
                                        </label>

                                        <label>Descripcion
                                            <textarea name="description" rows="3"><?= e((string) ($shopEditReward['description'] ?? '')) ?></textarea>
                                        </label>

                                        <div class="admin-row-2">
                                            <label>Imagen actual / ruta
                                                <input type="text" name="image_path" value="<?= e((string) ($shopEditReward['image_path'] ?? '')) ?>" maxlength="255" placeholder="Se puede reemplazar o vaciar">
                                            </label>
                                            <label>Nueva imagen
                                                <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                            </label>
                                        </div>

                                        <div class="admin-row-2">
                                            <label>Coste LifeCoins
                                                <input type="number" min="1" name="cost_points" value="<?= (int) ($shopEditReward['cost_points'] ?? 0) ?>" required>
                                            </label>
                                            <label>HP efecto
                                                <input type="number" min="0" name="effect_hp" value="<?= (int) ($shopEditReward['effect_hp'] ?? 0) ?>">
                                            </label>
                                        </div>

                                        <div class="admin-row-2">
                                            <label>Limite semanal
                                                <input type="number" min="1" name="weekly_limit" value="<?= (int) ($shopEditReward['weekly_limit'] ?? 1) ?>">
                                            </label>
                                            <label>Vista previa
                                                <?php if (!empty($shopEditReward['image_path'])): ?>
                                                    <img class="admin-shop-thumb" src="<?= e((string) $shopEditReward['image_path']) ?>" alt="">
                                                <?php else: ?>
                                                    <span class="admin-muted">Sin imagen</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>

                                        <button type="submit" class="btn btn-primary full">Guardar cambios</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <section class="admin-panel-grid shop-admin-layout">
                        <article class="admin-card">
                            <div class="admin-card-head">
                                <h2>Conceder cosmetico</h2>
                                <span class="admin-muted">Inventario</span>
                            </div>
                            <div class="admin-card-body">
                                <form method="POST" class="admin-form admin-stack">
                                    <input type="hidden" name="action" value="grant_inventory_item">
                                    <input type="hidden" name="section" value="shop">
                                    <label>Usuario
                                        <select name="user_id" required>
                                            <?php foreach ($adminUsers as $adminUser): ?>
                                                <option value="<?= (int) $adminUser['id'] ?>">#<?= (int) $adminUser['id'] ?> · <?= e((string) $adminUser['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>Cosmetico global
                                        <select name="reward_id" required>
                                            <?php foreach ($shopCosmeticRewards as $reward): ?>
                                                <option value="<?= (int) $reward['id'] ?>">#<?= (int) $reward['id'] ?> · <?= e((string) $reward['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <button type="submit" class="btn btn-secondary full">Conceder al inventario</button>
                                    <p class="admin-muted">El cosmetico se toma del catalogo global y solo el inventario queda asociado al usuario.</p>
                                </form>
                            </div>
                        </article>

                        <article class="admin-card">
                            <div class="admin-card-head">
                                <h2>Inventario reciente</h2>
                                <span class="admin-muted">Ultimas 80 piezas</span>
                            </div>
                            <div class="admin-card-body">
                                <div class="admin-table-wrap">
                                    <table class="admin-table admin-table-compact">
                                        <thead>
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Cosmetico</th>
                                                <th>Categoria</th>
                                                <th>Equipado</th>
                                                <th>Adquirido</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($shopInventory as $item): ?>
                                                <tr>
                                                    <td>#<?= (int) $item['user_id'] ?><br><small><?= e((string) $item['user_email']) ?></small></td>
                                                    <td><?= e((string) $item['reward_name']) ?></td>
                                                    <td><?= e((string) $item['category']) ?></td>
                                                    <td><?= !empty($item['equipped']) ? 'Si' : 'No' ?></td>
                                                    <td><?= e((string) $item['acquired_at']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($shopInventory)): ?>
                                                <tr><td colspan="5">Inventario vacio por ahora.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </article>
                    </section>

                <?php elseif ($section === 'players'): ?>
                    <section class="admin-card">
                        <div class="admin-card-head">
                            <h2>Jugadores</h2>
                            <span class="admin-muted">Economia y progresion</span>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-table-wrap">
                                <table class="admin-table player-admin-table">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>LifeCoins</th>
                                            <th>XP</th>
                                            <th>Nivel</th>
                                            <th>HP</th>
                                            <th>Max HP</th>
                                            <th>Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($playerRows as $player): ?>
                                            <?php $playerFormId = 'player-form-' . (int) $player['id']; ?>
                                            <tr>
                                                <td>
                                                    <form id="<?= e($playerFormId) ?>" method="POST"></form>
                                                    <input form="<?= e($playerFormId) ?>" type="hidden" name="action" value="update_player_stats">
                                                    <input form="<?= e($playerFormId) ?>" type="hidden" name="section" value="players">
                                                    <input form="<?= e($playerFormId) ?>" type="hidden" name="user_id" value="<?= (int) $player['id'] ?>">
                                                    <strong>#<?= (int) $player['id'] ?> · <?= e((string) $player['name']) ?></strong><br><small><?= e((string) $player['email']) ?></small>
                                                </td>
                                                <td><input form="<?= e($playerFormId) ?>" type="number" min="0" name="points" value="<?= (int) $player['points'] ?>"></td>
                                                <td><input form="<?= e($playerFormId) ?>" type="number" min="0" name="xp" value="<?= (int) $player['xp'] ?>"></td>
                                                <td><input form="<?= e($playerFormId) ?>" type="number" min="1" name="level" value="<?= max(1, (int) $player['level']) ?>"></td>
                                                <td><input form="<?= e($playerFormId) ?>" type="number" min="0" name="hp" value="<?= (int) $player['hp'] ?>"></td>
                                                <td><input form="<?= e($playerFormId) ?>" type="number" min="1" name="max_hp" value="<?= max(1, (int) $player['max_hp']) ?>"></td>
                                                <td><button form="<?= e($playerFormId) ?>" type="submit" class="admin-inline-btn primary">Guardar</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($playerRows)): ?>
                                            <tr><td colspan="7">No hay usuarios.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

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
