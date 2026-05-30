<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class PaymentMethod
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function active(): array
    {
        return $this->pdo
            ->query('SELECT id, title, description FROM payment_methods WHERE is_active = 1 ORDER BY title')
            ->fetchAll();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM payment_methods WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
