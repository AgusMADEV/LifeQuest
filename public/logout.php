<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

AuthController::logout();

header('Location: login.php');
exit;
