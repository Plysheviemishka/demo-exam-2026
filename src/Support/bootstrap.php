<?php

declare(strict_types=1);

use App\Database;
use App\Models\Application;
use App\Models\CourseType;
use App\Models\Review;
use App\Models\User;
use App\Services\AuthService;

session_start();

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Некорректный CSRF-токен. Обновите страницу и повторите действие.');
    }
}

function auth(): AuthService
{
    static $service = null;
    return $service ??= new AuthService(new User(Database::connection()));
}

function require_user(): array
{
    $user = auth()->user();
    if (!$user) {
        flash('warning', 'Войдите в систему, чтобы продолжить.');
        redirect('/login.php');
    }

    return $user;
}

function require_admin(): void
{
    if (!auth()->isAdmin()) {
        flash('warning', 'Для доступа к панели администратора выполните вход.');
        redirect('/admin_login.php');
    }
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function remember_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}
