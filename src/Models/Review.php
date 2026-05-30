<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

final class Review
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, int $applicationId, int $rating, string $text): void
    {
        $applicationModel = new Application($this->pdo);
        $application = $applicationModel->find($applicationId);

        if (!$application || (int) $application['user_id'] !== $userId) {
            throw new RuntimeException('Заявка не найдена.');
        }

        if ($application['status'] !== Application::STATUS_DONE) {
            throw new RuntimeException('Отзыв можно оставить только после завершения обучения.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO reviews (application_id, user_id, rating, text)
             VALUES (:application_id, :user_id, :rating, :text)'
        );

        $stmt->execute([
            'application_id' => $applicationId,
            'user_id' => $userId,
            'rating' => max(1, min(5, $rating)),
            'text' => trim($text),
        ]);
    }
}
