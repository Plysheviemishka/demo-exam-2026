<?php
require __DIR__ . '/../src/Support/bootstrap.php';
require __DIR__ . '/../src/Support/validation.php';

use App\Database;
use App\Models\Application;
use App\Models\CourseType;
use App\Models\PaymentMethod;

$user = require_user();
$pdo = Database::connection();
$courses = (new CourseType($pdo))->all();
$paymentMethods = (new PaymentMethod($pdo))->active();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = validate_application($_POST);
    remember_old($_POST);

    if (!$errors) {
        (new Application($pdo))->create([
            'user_id' => (int) $user['id'],
            'course_type_id' => (int) $_POST['course_type_id'],
            'start_date' => normalize_date_to_sql((string) $_POST['start_date']),
            'payment_method_id' => (int) $_POST['payment_method_id'],
            'comment' => trim((string) ($_POST['comment'] ?? '')) ?: null,
        ]);

        clear_old();
        flash('success', 'Заявка создана и отправлена на согласование администратору.');
        redirect('/dashboard.php');
    }
}

$title = 'Оформление заявки — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="section-heading">
    <div>
        <p class="eyebrow">Новая заявка</p>
        <h1>Оформление заявки на обучение</h1>
        <p>Выберите курс, дату старта в формате ДД.ММ.ГГГГ и удобный способ оплаты. Заявка получит статус «Новая» автоматически.</p>
    </div>
</section>

<form class="card form form--wide animate-in" method="post" novalidate data-smart-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="form__group">
        <label for="course_type_id">Вид курса</label>
        <select id="course_type_id" name="course_type_id" required data-required-message="Выберите курс из списка">
            <option value="">Выберите курс</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= (int) $course['id'] ?>" <?= old('course_type_id') === (string) $course['id'] ? 'selected' : '' ?>>
                    <?= e($course['title']) ?> — <?= (int) $course['duration_hours'] ?> ч.
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['course_type_id'])): ?><span class="field-error"><?= e($errors['course_type_id']) ?></span><?php endif; ?>
    </div>
    <div class="form__group">
        <label for="start_date">Предпочтительная дата начала обучения</label>
        <input id="start_date" name="start_date" type="text" inputmode="numeric" required placeholder="ДД.ММ.ГГГГ" pattern="\d{2}\.\d{2}\.\d{4}" value="<?= e(format_date_for_form(old('start_date'))) ?>" data-date-mask data-required-message="Введите дату в формате ДД.ММ.ГГГГ">
        <small>Например: <?= e(date('d.m.Y', strtotime('+7 days'))) ?></small>
        <?php if (isset($errors['start_date'])): ?><span class="field-error"><?= e($errors['start_date']) ?></span><?php endif; ?>
    </div>
    <div class="form__group">
        <label for="payment_method_id">Способ оплаты</label>
        <select id="payment_method_id" name="payment_method_id" required data-required-message="Выберите способ оплаты">
            <option value="">Выберите способ оплаты</option>
            <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= (int) $method['id'] ?>" <?= old('payment_method_id') === (string) $method['id'] ? 'selected' : '' ?>><?= e($method['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['payment_method_id'])): ?><span class="field-error"><?= e($errors['payment_method_id']) ?></span><?php endif; ?>
    </div>
    <div class="form__group">
        <label for="comment">Комментарий к заявке</label>
        <textarea id="comment" name="comment" maxlength="500" placeholder="Например: удобно начать обучение вечером"><?= e(old('comment')) ?></textarea>
        <small>Необязательное поле, до 500 символов.</small>
    </div>
    <button class="button" type="submit">Отправить заявку</button>
</form>
<?php require __DIR__ . '/partials_footer.php'; ?>
