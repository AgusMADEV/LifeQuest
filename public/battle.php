<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/BattleController.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];

$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$controller = new BattleController();

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->finishSession($userId, $_POST);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: battle.php?message=' . urlencode($message) . '&type=success');
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$tasks = $controller->getPendingTasks($userId);
$recentSessions = $controller->getRecentSessions($userId);

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '…';
}

function resultLabel(string $result): string
{
    return [
        'completed' => 'Completada',
        'partial' => 'Parcial',
        'failed' => 'Fallida',
    ][$result] ?? $result;
}

function resultClass(string $result): string
{
    return [
        'completed' => 'green',
        'partial' => 'orange',
        'failed' => 'red',
    ][$result] ?? 'blue';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modo Batalla | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/battle.css">
</head>
<body class="lifequest-app battle-page">
    <?php
    $activePage = 'battle';
    require __DIR__ . '/../app/Views/partials/sidebar.php';
    ?>

<main class="lq-main">
        <?php
        $searchPlaceholder = 'Buscar misiones para entrar en batalla...';
        require __DIR__ . '/../app/Views/partials/topbar.php';
        ?>

<section class="battle-layout">
            <section class="battle-main">
                <header class="battle-hero">
                    <div>
                        <p class="eyebrow">Enfoque total</p>
                        <h1>Modo Batalla</h1>
                        <p>Elige una misión, activa el temporizador y céntrate en ejecutar. Sin distracciones. Sin excusas.</p>
                    </div>

                    <div class="battle-hero-card">
                        <span>⚔️</span>
                        <strong>Sesión profunda</strong>
                        <small>25 minutos recomendados</small>
                    </div>
                </header>

                <?php if ($message): ?>
                    <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
                <?php endif; ?>

                <article class="battle-console">
                    <form method="POST" id="battleForm">
                        <div class="battle-form-grid">
                            <label>
                                Misión seleccionada
                                <select name="task_id" id="taskSelect">
                                    <option value="">Sesión libre sin misión asociada</option>
                                    <?php foreach ($tasks as $task): ?>
                                        <option
                                            value="<?= (int)$task['id'] ?>"
                                            data-title="<?= e($task['title']) ?>"
                                            data-xp="<?= (int)$task['xp_reward'] ?>"
                                            data-points="<?= (int)$task['points_reward'] ?>"
                                        >
                                            <?= e($task['title']) ?>
                                            <?= !empty($task['project_title']) ? ' · ' . e($task['project_title']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                Objetivo libre
                                <input type="text" name="custom_title" id="customTitle" placeholder="Ej: Redactar memoria del TFG">
                            </label>

                            <label>
                                Duración
                                <select name="duration_minutes" id="durationSelect">
                                    <option value="15">15 min · calentamiento</option>
                                    <option value="25" selected>25 min · enfoque</option>
                                    <option value="45">45 min · profundo</option>
                                    <option value="60">60 min · intensivo</option>
                                    <option value="90">90 min · modo leyenda</option>
                                </select>
                            </label>
                        </div>

                        <section class="timer-card">
                            <div class="timer-ring">
                                <div>
                                    <span id="timerDisplay">25:00</span>
                                    <small id="timerStatus">Listo para empezar</small>
                                </div>
                            </div>

                            <div class="battle-current">
                                <small>Misión actual</small>
                                <strong id="currentMission">Selecciona una misión o escribe una sesión libre</strong>
                                <p>Durante el Modo Batalla se recomienda no cambiar de tarea, no abrir apps extra y registrar el resultado al terminar.</p>

                                <div class="battle-rules">
                                    <span>🚫 Sin distracciones</span>
                                    <span>🎯 Una sola misión</span>
                                    <span>⚡ Ejecutar primero</span>
                                </div>
                            </div>
                        </section>

                        <div class="battle-controls">
                            <button type="button" class="battle-btn start" id="startBtn">Comenzar</button>
                            <button type="button" class="battle-btn pause" id="pauseBtn" disabled>Pausar</button>
                            <button type="button" class="battle-btn reset" id="resetBtn">Reiniciar</button>
                        </div>

                        <section class="battle-finish">
                            <div class="lq-panel-header">
                                <div>
                                    <h2>Registrar resultado</h2>
                                    <p>Al finalizar, guarda cómo ha ido la sesión para sumar XP y LifeCoins.</p>
                                </div>
                            </div>

                            <div class="battle-result-grid">
                                <label class="result-option completed">
                                    <input type="radio" name="result" value="completed" checked>
                                    <span>
                                        <strong>Completada</strong>
                                        <small>He terminado la misión.</small>
                                    </span>
                                </label>

                                <label class="result-option partial">
                                    <input type="radio" name="result" value="partial">
                                    <span>
                                        <strong>Parcial</strong>
                                        <small>He avanzado, pero no terminé.</small>
                                    </span>
                                </label>

                                <label class="result-option failed">
                                    <input type="radio" name="result" value="failed">
                                    <span>
                                        <strong>Fallida</strong>
                                        <small>No conseguí avanzar.</small>
                                    </span>
                                </label>
                            </div>

                            <label class="notes-label">
                                Notas de la sesión
                                <textarea name="notes" rows="3" placeholder="Qué has hecho, qué ha faltado, próximo paso..."></textarea>
                            </label>

                            <button type="submit" class="btn btn-primary full">Guardar sesión</button>
                        </section>
                    </form>
                </article>
            </section>

            <aside class="battle-side">
                <section class="lq-card">
                    <div class="lq-card-header">
                        <h2>Misiones disponibles</h2>
                        <a href="tasks.php">Ver todas</a>
                    </div>

                    <?php if (empty($tasks)): ?>
                        <div class="lq-empty">
                            <h2>No hay misiones pendientes</h2>
                            <p>Crea una misión para poder entrar en Modo Batalla.</p>
                            <a href="tasks.php" class="mini-btn">Crear misión</a>
                        </div>
                    <?php else: ?>
                        <div class="battle-task-list">
                            <?php foreach (array_slice($tasks, 0, 6) as $task): ?>
                                <article>
                                    <span style="background: <?= e($task['area_color'] ?: '#16C79A') ?>;">
                                        <?= e($task['area_icon'] ?: '⚡') ?>
                                    </span>
                                    <div>
                                        <strong><?= e(shortText($task['title'], 28)) ?></strong>
                                        <small><?= !empty($task['project_title']) ? e(shortText($task['project_title'], 28)) : 'Sin reto asociado' ?></small>
                                    </div>
                                    <em>+<?= (int)$task['xp_reward'] ?> XP</em>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="lq-card">
                    <div class="lq-card-header">
                        <h2>Historial</h2>
                    </div>

                    <?php if (empty($recentSessions)): ?>
                        <p class="muted">Todavía no has registrado sesiones de batalla.</p>
                    <?php else: ?>
                        <div class="session-history">
                            <?php foreach ($recentSessions as $session): ?>
                                <article>
                                    <div>
                                        <strong><?= e(shortText($session['title'], 30)) ?></strong>
                                        <small><?= date('d/m/Y H:i', strtotime($session['created_at'])) ?> · <?= (int)$session['duration_minutes'] ?> min</small>
                                    </div>
                                    <span class="lq-badge <?= resultClass($session['result']) ?>">
                                        <?= resultLabel($session['result']) ?>
                                    </span>
                                    <em>+<?= (int)$session['xp_earned'] ?> XP</em>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="battle-tip">
                    <span>💡</span>
                    <strong>Consejo de batalla</strong>
                    <p>Elige una misión pequeña y concreta. Si cabe en una frase, puedes ejecutarla mejor.</p>
                </section>
            </aside>
        </section>
    </main>

    <script src="../assets/js/battle.js"></script>
</body>
</html>
