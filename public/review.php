<?php
require __DIR__ . '/../src/Support/bootstrap.php';

use App\Database;
use App\Models\Review;

$user = require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard.php');
}

verify_csrf();

try {
    (new Review(Database::connection()))->create(
        (int) $user['id'],
        (int) ($_POST['application_id'] ?? 0),
        (int) ($_POST['rating'] ?? 5),
        (string) ($_POST['text'] ?? '')
    );
    flash('success', 'Спасибо! Отзыв сохранен.');
} catch (Throwable $exception) {
    flash('danger', $exception->getMessage());
}

redirect('/dashboard.php');
