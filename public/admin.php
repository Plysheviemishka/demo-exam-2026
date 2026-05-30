<?php
require __DIR__ . '/../src/Support/bootstrap.php';

use App\Database;
use App\Models\Application;
use App\Models\PaymentMethod;

require_admin();
$appModel = new Application(Database::connection());

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => (string) ($_GET['status'] ?? ''),
    'payment_method_id' => (string) ($_GET['payment_method_id'] ?? ''),
    'date_from' => (string) ($_GET['date_from'] ?? ''),
    'date_to' => (string) ($_GET['date_to'] ?? ''),
];
$sort = (string) ($_GET['sort'] ?? 'created');
$direction = (string) ($_GET['direction'] ?? 'desc');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$total = $appModel->countFiltered($filters);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$applications = $appModel->allFiltered($filters, $page, $perPage, $sort, $direction);
$stats = $appModel->statistics();
$payments = (new PaymentMethod(Database::connection()))->active();

function admin_query(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }
    return '?' . http_build_query($params);
}

function sort_link(string $key, string $label, string $currentSort, string $currentDirection): string
{
    $nextDirection = $currentSort === $key && strtolower($currentDirection) === 'asc' ? 'desc' : 'asc';
    $mark = $currentSort === $key ? (strtolower($currentDirection) === 'asc' ? ' ↑' : ' ↓') : '';
    return '<a href="/admin.php' . e(admin_query(['sort' => $key, 'direction' => $nextDirection, 'page' => 1])) . '">' . e($label . $mark) . '</a>';
}

$title = 'Панель администратора — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="section-heading">
    <div>
        <p class="eyebrow">Администратор</p>
        <h1>Панель управления заявками</h1>
        <p>Фильтрация, сортировка, постраничная навигация и быстрые уведомления о действиях.</p>
    </div>
</section>

<section class="stats-grid" aria-label="Статистика заявок">
    <?php foreach ($stats as $statusName => $count): ?>
        <article class="stat-card">
            <span class="status status--<?= e(match ($statusName) { 'Новая' => 'new', 'Идет обучение' => 'progress', default => 'done' }) ?>"><?= e($statusName) ?></span>
            <strong><?= (int) $count ?></strong>
        </article>
    <?php endforeach; ?>
</section>

<section class="card admin-tools animate-in">
    <h2>Инструменты поиска</h2>
    <form class="filters" method="get">
        <div class="form__group">
            <label for="q">Поиск</label>
            <input id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="ФИО, логин, телефон, курс">
        </div>
        <div class="form__group">
            <label for="status">Статус</label>
            <select id="status" name="status">
                <option value="">Все статусы</option>
                <?php foreach (Application::ALLOWED_STATUSES as $statusOption): ?>
                    <option value="<?= e($statusOption) ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form__group">
            <label for="payment_method_id">Оплата</label>
            <select id="payment_method_id" name="payment_method_id">
                <option value="">Любая оплата</option>
                <?php foreach ($payments as $payment): ?>
                    <option value="<?= (int) $payment['id'] ?>" <?= (string) $filters['payment_method_id'] === (string) $payment['id'] ? 'selected' : '' ?>><?= e($payment['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form__group">
            <label for="date_from">Дата от</label>
            <input id="date_from" name="date_from" type="date" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="form__group">
            <label for="date_to">Дата до</label>
            <input id="date_to" name="date_to" type="date" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="filters__actions">
            <button class="button" type="submit">Применить</button>
            <a class="button button--ghost" href="/admin.php">Сбросить</a>
        </div>
    </form>
</section>

<section class="card">
    <div class="card-head">
        <div>
            <h2>Все заявки</h2>
            <p class="muted">Найдено: <?= (int) $total ?>. Страница <?= (int) $page ?> из <?= (int) $totalPages ?>. Данные выбираются через нормализованные справочники статусов и способов оплаты.</p>
        </div>
    </div>
    <?php if (!$applications): ?>
        <p class="muted">Заявки по выбранным параметрам не найдены.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><?= sort_link('id', '№', $sort, $direction) ?></th>
                    <th><?= sort_link('user', 'Пользователь', $sort, $direction) ?></th>
                    <th>Контакты</th>
                    <th><?= sort_link('course', 'Курс', $sort, $direction) ?></th>
                    <th><?= sort_link('date', 'Дата старта', $sort, $direction) ?></th>
                    <th><?= sort_link('payment', 'Оплата', $sort, $direction) ?></th>
                    <th><?= sort_link('status', 'Статус', $sort, $direction) ?></th>
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
                            <form class="inline-form" method="post" action="/admin_update_status.php" data-confirm-status>
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                                <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
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

        <nav class="pagination" aria-label="Постраничная навигация">
            <?php if ($page > 1): ?>
                <a class="pagination__item" href="/admin.php<?= e(admin_query(['page' => $page - 1])) ?>">Назад</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="pagination__item <?= $i === $page ? 'pagination__item--active' : '' ?>" href="/admin.php<?= e(admin_query(['page' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a class="pagination__item" href="/admin.php<?= e(admin_query(['page' => $page + 1])) ?>">Вперед</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<div class="modal" data-modal hidden>
    <div class="modal__overlay" data-modal-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h2 id="modal-title">Подтвердите изменение</h2>
        <p data-modal-text>Сохранить новый статус заявки?</p>
        <div class="modal__actions">
            <button class="button button--ghost" type="button" data-modal-close>Отмена</button>
            <button class="button" type="button" data-modal-submit>Подтвердить</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
