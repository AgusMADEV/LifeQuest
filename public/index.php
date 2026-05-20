<?php

require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?> | Convierte tus metas en resultados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="landing">
        <section class="hero">
            <div class="hero-content">
                <p class="eyebrow">Productividad gamificada</p>
                <h1>QuestBoard</h1>
                <p class="hero-subtitle">Convierte tus metas en resultados.</p>
                <p class="hero-text">
                    Un tablero personal para enfocarte, ejecutar y medir tu progreso mediante metas,
                    hábitos, tareas, estadísticas y Modo Batalla.
                </p>

                <div class="hero-actions">
                    <a href="register.php" class="btn btn-primary">Crear cuenta</a>
                    <a href="login.php" class="btn btn-secondary">Iniciar sesión</a>
                </div>
            </div>

            <div class="hero-card">
                <div class="mock-header">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="mock-dashboard">
                    <div class="mock-card wide">
                        <small>Prioridad del día</small>
                        <strong>Avanzar en tu objetivo principal</strong>
                    </div>

                    <div class="mock-grid">
                        <div class="mock-card">
                            <small>Nivel</small>
                            <strong>01</strong>
                        </div>
                        <div class="mock-card">
                            <small>Racha</small>
                            <strong>0 días</strong>
                        </div>
                    </div>

                    <div class="mock-card battle">
                        <small>Modo Batalla</small>
                        <strong>25:00</strong>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
