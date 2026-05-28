<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/User.php';

final class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function register(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $apellidos = trim($data['apellidos'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if ($name === '' || $apellidos === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El email no tiene un formato válido.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if ($this->userModel->findByEmail($email)) {
            return ['success' => false, 'message' => 'Ya existe una cuenta con ese email.'];
        }

        $created = $this->userModel->create($name, $apellidos, $email, $password);

        if (!$created) {
            return ['success' => false, 'message' => 'No se pudo crear la cuenta.'];
        }

        return ['success' => true, 'message' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.'];
    }

    public function login(array $data): array
    {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Email y contraseña son obligatorios.'];
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];

        return ['success' => true, 'message' => 'Sesión iniciada correctamente.'];
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
