<?php
require __DIR__ . '/../src/Support/bootstrap.php';

use App\Database;
use App\Models\Application;

require_admin();
$appModel = new Application(Database::connection());
$applications = $appModel->all();
$title = 'Панель администратора — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="section-heading">
    <div>
        <p class="eyebrow">Администратор</p>
        <h1>Панель управления заявками</h1>
        <p>Просмотр всех заявок и изменение их статуса.</p>
    </div>
</section>

<section class="card">
    <h2>Все заявки</h2>
    <?php if (!$applications): ?>
        <p class="muted">Заявки пока не поступали.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>№</th>
                    <th>Пользователь</th>
                    <th>Контакты</th>
                    <th>Курс</th>
                    <th>Дата старта</th>
                    <th>Оплата</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= (int) $application['id'] ?></td>
                        <td>
                            <strong><?= e($application['full_name']) ?></strong><br>
                            <span class="muted">@<?= e($application['login']) ?></span>
                        </td>
                        <td><?= e($application['phone']) ?><br><?= e($application['email']) ?></td>
                        <td><?= e($application['course_title']) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($application['start_date']))) ?></td>
                        <td><?= e($application['payment_method']) ?></td>
                        <td><span class="status status--<?= e(match ($application['status']) { 'Новая' => 'new', 'Идет обучение' => 'progress', default => 'done' }) ?>"><?= e($application['status']) ?></span></td>
                        <td>
                            <form class="inline-form" method="post" action="/admin_update_status.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                                <select name="status" aria-label="Статус заявки">
                                    <?php foreach (Application::ALLOWED_STATUSES as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $application['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="button button--small" type="submit">Сохранить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials_footer.php'; ?>
