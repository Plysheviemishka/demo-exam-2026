<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class CourseType
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo
            ->query('SELECT id, title, description FROM course_types ORDER BY title')
            ->fetchAll();
    }
}
