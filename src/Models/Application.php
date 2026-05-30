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

    public const SORT_MAP = [
        'id' => 'a.id',
        'user' => 'u.full_name',
        'course' => 'c.title',
        'date' => 'a.start_date',
        'status' => 'a.status',
        'created' => 'a.created_at',
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
        return $this->allFiltered([], 1, 1000, 'created', 'desc');
    }

    public function allFiltered(array $filters, int $page = 1, int $perPage = 6, string $sort = 'created', string $direction = 'desc'): array
    {
        [$where, $params] = $this->buildFilterSql($filters);
        $sortColumn = self::SORT_MAP[$sort] ?? self::SORT_MAP['created'];
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT a.*, c.title AS course_title, u.login, u.full_name, u.phone, u.email
                FROM applications a
                INNER JOIN course_types c ON c.id = a.course_type_id
                INNER JOIN users u ON u.id = a.user_id
                {$where}
                ORDER BY {$sortColumn} {$direction}, a.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countFiltered(array $filters): int
    {
        [$where, $params] = $this->buildFilterSql($filters);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM applications a
             INNER JOIN course_types c ON c.id = a.course_type_id
             INNER JOIN users u ON u.id = a.user_id
             {$where}"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function statistics(): array
    {
        $stmt = $this->pdo->query('SELECT status, COUNT(*) AS total FROM applications GROUP BY status');
        $stats = array_fill_keys(self::ALLOWED_STATUSES, 0);
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row['status']] = (int) $row['total'];
        }

        return $stats;
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

    private function buildFilterSql(array $filters): array
    {
        $conditions = [];
        $params = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $conditions[] = '(u.full_name LIKE :q OR u.login LIKE :q OR u.phone LIKE :q OR u.email LIKE :q OR c.title LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && in_array($status, self::ALLOWED_STATUSES, true)) {
            $conditions[] = 'a.status = :status';
            $params[':status'] = $status;
        }

        $payment = (string) ($filters['payment'] ?? '');
        if ($payment !== '') {
            $conditions[] = 'a.payment_method = :payment';
            $params[':payment'] = $payment;
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $conditions[] = 'a.start_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $conditions[] = 'a.start_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
