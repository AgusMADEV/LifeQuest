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
    $result = $auth->login($_POST);

    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    }

    $message = $result['message'];
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <a href="index.php" class="brand">QuestBoard</a>

            <h1>Iniciar sesión</h1>
            <p class="muted">Vuelve a tu tablero y sigue avanzando.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <label>
                    Email
                    <input type="email" name="email" placeholder="tu@email.com" required>
                </label>

                <label>
                    Contraseña
                    <input type="password" name="password" placeholder="Tu contraseña" required>
                </label>

                <button type="submit" class="btn btn-primary full">Entrar</button>
            </form>

            <p class="auth-footer">
                ¿No tienes cuenta? <a href="register.php">Crear cuenta</a>
            </p>
        </section>
    </main>
</body>
</html>
