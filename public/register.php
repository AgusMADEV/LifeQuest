<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $result = $auth->register($_POST);

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Refresh: 1.5; URL=login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear cuenta | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <a href="index.php" class="brand">QuestBoard</a>

            <h1>Crear cuenta</h1>
            <p class="muted">Empieza a convertir tus metas en resultados.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <label>
                    Nombre
                    <input type="text" name="name" placeholder="Agustín" required>
                </label>

                <label>
                    Email
                    <input type="email" name="email" placeholder="tu@email.com" required>
                </label>

                <label>
                    Contraseña
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                </label>

                <label>
                    Confirmar contraseña
                    <input type="password" name="password_confirm" placeholder="Repite la contraseña" required>
                </label>

                <button type="submit" class="btn btn-primary full">Crear cuenta</button>
            </form>

            <p class="auth-footer">
                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
            </p>
        </section>
    </main>
</body>
</html>
