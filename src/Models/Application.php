<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Application
{
    public const STATUS_NEW = 'Новая';
    public const STATUS_IN_PROGRESS = 'Идет обучение';
    public const STATUS_DONE = 'Обучение завершено';

    public const ALLOWED_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO applications (user_id, course_type_id, start_date, payment_method, status)
             VALUES (:user_id, :course_type_id, :start_date, :payment_method, :status)'
        );

        $stmt->execute([
            'user_id' => $data['user_id'],
            'course_type_id' => $data['course_type_id'],
            'start_date' => $data['start_date'],
            'payment_method' => $data['payment_method'],
            'status' => self::STATUS_NEW,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.title AS course_title, r.rating, r.text AS review_text
             FROM applications a
             INNER JOIN course_types c ON c.id = a.course_type_id
             LEFT JOIN reviews r ON r.application_id = a.id
             WHERE a.user_id = :user_id
             ORDER BY a.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT a.*, c.title AS course_title, u.login, u.full_name, u.phone, u.email
             FROM applications a
             INNER JOIN course_types c ON c.id = a.course_type_id
             INNER JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.title AS course_title, u.full_name
             FROM applications a
             INNER JOIN course_types c ON c.id = a.course_type_id
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $application = $stmt->fetch();

        return $application ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE applications SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
