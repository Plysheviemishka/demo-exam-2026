<?php
require __DIR__ . '/../src/Support/bootstrap.php';

use App\Database;
use App\Models\Application;

$user = require_user();
$applications = (new Application(Database::connection()))->forUser((int) $user['id']);
$title = 'Личный кабинет — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="section-heading">
    <div>
        <p class="eyebrow">Личный кабинет</p>
        <h1><?= e($user['full_name']) ?></h1>
        <p>История заявок и отзывы о завершенных услугах.</p>
    </div>
    <a class="button" href="/apply.php">Оформить заявку</a>
</section>

<section class="card">
    <h2>Мои заявки</h2>
    <?php if (!$applications): ?>
        <p class="muted">У вас пока нет заявок. Создайте первую заявку на обучение.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>№</th>
                    <th>Курс</th>
                    <th>Дата старта</th>
                    <th>Оплата</th>
                    <th>Статус</th>
                    <th>Отзыв</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= (int) $application['id'] ?></td>
                        <td><?= e($application['course_title']) ?></td>
                        <td><?= e(date('d.m.Y', strtotime($application['start_date']))) ?></td>
                        <td><?= e($application['payment_method']) ?></td>
                        <td><span class="status status--<?= e(match ($application['status']) { 'Новая' => 'new', 'Идет обучение' => 'progress', default => 'done' }) ?>"><?= e($application['status']) ?></span></td>
                        <td>
                            <?php if ($application['review_text']): ?>
                                <strong><?= (int) $application['rating'] ?>/5</strong><br>
                                <span><?= e($application['review_text']) ?></span>
                            <?php elseif ($application['status'] === Application::STATUS_DONE): ?>
                                <form class="review-form" method="post" action="/review.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                                    <select name="rating" aria-label="Оценка">
                                        <option value="5">5</option>
                                        <option value="4">4</option>
                                        <option value="3">3</option>
                                        <option value="2">2</option>
                                        <option value="1">1</option>
                                    </select>
                                    <input name="text" required maxlength="1000" placeholder="Ваш отзыв">
                                    <button class="button button--small" type="submit">Отправить</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Доступен после завершения обучения</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials_footer.php'; ?>
