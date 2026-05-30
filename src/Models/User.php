<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (login, password_hash, full_name, phone, email)
             VALUES (:login, :password_hash, :full_name, :phone, :email)'
        );

        $stmt->execute([
            'login' => $data['login'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE login = :login LIMIT 1');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, login, full_name, phone, email, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function verifyPassword(string $login, string $password): ?array
    {
        $user = $this->findByLogin($login);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }

    public function loginExists(string $login): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE login = :login');
        $stmt->execute(['login' => $login]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
