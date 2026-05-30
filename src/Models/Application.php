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
        'status' => 's.sort_order',
        'payment' => 'pm.title',
        'created' => 'a.created_at',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $newStatusId = (new ApplicationStatus($this->pdo))->idByTitle(self::STATUS_NEW);

        $stmt = $this->pdo->prepare(
            'INSERT INTO applications (user_id, course_type_id, start_date, payment_method_id, status_id, comment)
             VALUES (:user_id, :course_type_id, :start_date, :payment_method_id, :status_id, :comment)'
        );

        $stmt->execute([
            'user_id' => $data['user_id'],
            'course_type_id' => $data['course_type_id'],
            'start_date' => $data['start_date'],
            'payment_method_id' => $data['payment_method_id'],
            'status_id' => $newStatusId,
            'comment' => $data['comment'] ?? null,
        ]);

        $applicationId = (int) $this->pdo->lastInsertId();
        $this->writeStatusHistory($applicationId, null, (int) $newStatusId, 'system');

        return $applicationId;
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.title AS course_title, c.duration_hours, pm.title AS payment_method,
                    s.title AS status, s.code AS status_code, r.rating, r.text AS review_text
             FROM applications a
             INNER JOIN course_types c ON c.id = a.course_type_id
             INNER JOIN payment_methods pm ON pm.id = a.payment_method_id
             INNER JOIN application_statuses s ON s.id = a.status_id
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

        $sql = "SELECT a.*, c.title AS course_title, c.duration_hours, pm.title AS payment_method,
                       s.title AS status, s.code AS status_code, u.login, u.full_name, u.phone, u.email
                FROM applications a
                INNER JOIN course_types c ON c.id = a.course_type_id
                INNER JOIN payment_methods pm ON pm.id = a.payment_method_id
                INNER JOIN application_statuses s ON s.id = a.status_id
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
             INNER JOIN payment_methods pm ON pm.id = a.payment_method_id
             INNER JOIN application_statuses s ON s.id = a.status_id
             INNER JOIN users u ON u.id = a.user_id
             {$where}"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function statistics(): array
    {
        $stmt = $this->pdo->query(
            'SELECT s.title AS status, COUNT(a.id) AS total
             FROM application_statuses s
             LEFT JOIN applications a ON a.status_id = s.id
             GROUP BY s.id, s.title, s.sort_order
             ORDER BY s.sort_order'
        );
        $stats = array_fill_keys(self::ALLOWED_STATUSES, 0);
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row['status']] = (int) $row['total'];
        }

        return $stats;
    }

    public function dashboardMetrics(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.title AS status, COUNT(a.id) AS total
             FROM application_statuses s
             LEFT JOIN applications a ON a.status_id = s.id AND a.user_id = :user_id
             GROUP BY s.id, s.title, s.sort_order
             ORDER BY s.sort_order'
        );
        $stmt->execute(['user_id' => $userId]);

        $metrics = array_fill_keys(self::ALLOWED_STATUSES, 0);
        foreach ($stmt->fetchAll() as $row) {
            $metrics[$row['status']] = (int) $row['total'];
        }

        return $metrics;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.title AS course_title, pm.title AS payment_method, s.title AS status,
                    u.full_name, u.id AS user_id
             FROM applications a
             INNER JOIN course_types c ON c.id = a.course_type_id
             INNER JOIN payment_methods pm ON pm.id = a.payment_method_id
             INNER JOIN application_statuses s ON s.id = a.status_id
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $application = $stmt->fetch();

        return $application ?: null;
    }

    public function statusHistory(int $applicationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sh.changed_by, sh.changed_at, old_s.title AS old_status, new_s.title AS new_status
             FROM status_history sh
             LEFT JOIN application_statuses old_s ON old_s.id = sh.old_status_id
             INNER JOIN application_statuses new_s ON new_s.id = sh.new_status_id
             WHERE sh.application_id = :application_id
             ORDER BY sh.changed_at DESC, sh.id DESC'
        );
        $stmt->execute(['application_id' => $applicationId]);

        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, string $changedBy = 'Admin26'): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return false;
        }

        $statusModel = new ApplicationStatus($this->pdo);
        $newStatusId = $statusModel->idByTitle($status);
        if ($newStatusId === null) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT status_id FROM applications WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $oldStatusId = $stmt->fetchColumn();
            if ($oldStatusId === false) {
                $this->pdo->rollBack();
                return false;
            }

            if ((int) $oldStatusId !== $newStatusId) {
                $update = $this->pdo->prepare('UPDATE applications SET status_id = :status_id WHERE id = :id');
                $update->execute(['status_id' => $newStatusId, 'id' => $id]);
                $this->writeStatusHistory($id, (int) $oldStatusId, $newStatusId, $changedBy);
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
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
            $conditions[] = 's.title = :status';
            $params[':status'] = $status;
        }

        $paymentId = (int) ($filters['payment_method_id'] ?? 0);
        if ($paymentId > 0) {
            $conditions[] = 'pm.id = :payment_method_id';
            $params[':payment_method_id'] = $paymentId;
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

    private function writeStatusHistory(int $applicationId, ?int $oldStatusId, int $newStatusId, string $changedBy): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO status_history (application_id, old_status_id, new_status_id, changed_by)
             VALUES (:application_id, :old_status_id, :new_status_id, :changed_by)'
        );
        $stmt->execute([
            'application_id' => $applicationId,
            'old_status_id' => $oldStatusId,
            'new_status_id' => $newStatusId,
            'changed_by' => $changedBy,
        ]);
    }
}
