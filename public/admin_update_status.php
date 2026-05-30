<?php
require __DIR__ . '/../src/Support/bootstrap.php';

use App\Database;
use App\Models\Application;

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin.php');
}

verify_csrf();

$applicationId = (int) ($_POST['application_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$returnTo = (string) ($_POST['return_to'] ?? '/admin.php');
if (!str_starts_with($returnTo, '/admin.php')) {
    $returnTo = '/admin.php';
}

if ((new Application(Database::connection()))->updateStatus($applicationId, $status)) {
    flash('success', 'Статус заявки обновлен. Уведомление показано администратору.');
} else {
    flash('danger', 'Не удалось обновить статус заявки.');
}

redirect($returnTo);
