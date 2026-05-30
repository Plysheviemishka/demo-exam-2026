<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class AuthService
{
    public function __construct(private readonly User $users)
    {
    }

    public function attempt(string $login, string $password): bool
    {
        $user = $this->users->verifyPassword($login, $password);

        if (!$user) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = 'user';
        return true;
    }

    public function attemptAdmin(string $login, string $password): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';

        if ($login !== $config['admin']['login'] || $password !== $config['admin']['password']) {
            return false;
        }

        $_SESSION['role'] = 'admin';
        unset($_SESSION['user_id']);
        return true;
    }

    public function user(): ?array
    {
        if (($_SESSION['role'] ?? null) !== 'user' || empty($_SESSION['user_id'])) {
            return null;
        }

        return $this->users->findById((int) $_SESSION['user_id']);
    }

    public function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? null) === 'admin';
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
