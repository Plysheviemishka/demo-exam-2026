<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class ApplicationStatus
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo
            ->query('SELECT id, title, code, sort_order FROM application_statuses ORDER BY sort_order')
            ->fetchAll();
    }

    public function idByTitle(string $title): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM application_statuses WHERE title = :title LIMIT 1');
        $stmt->execute(['title' => $title]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function titleById(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT title FROM application_statuses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $title = $stmt->fetchColumn();

        return $title === false ? null : (string) $title;
    }
}
