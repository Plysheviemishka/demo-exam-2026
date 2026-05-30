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
        <p>История заявок, актуальные статусы обучения и отзывы о завершенных услугах.</p>
    </div>
    <a class="button" href="/apply.php">Оформить заявку</a>
</section>

<section class="slider card animate-in" aria-label="Преимущества портала Учусь.РФ">
    <div class="slider__viewport">
        <?php
        $slides = [
            ['img' => 'slide-1.svg', 'title' => 'Онлайн-курсы без лишних шагов', 'text' => 'Выберите направление, дату старта и отправьте заявку за пару минут.'],
            ['img' => 'slide-2.svg', 'title' => 'Прозрачные статусы', 'text' => 'Следите за этапом рассмотрения и началом обучения в личном кабинете.'],
            ['img' => 'slide-3.svg', 'title' => 'Удобная оплата', 'text' => 'Карта, СБП или счет для организации доступны при оформлении заявки.'],
            ['img' => 'slide-4.svg', 'title' => 'Отзывы после обучения', 'text' => 'После завершения курса можно оставить оценку и комментарий.'],
        ];
        ?>
        <div class="slider__track" data-slider-track>
            <?php foreach ($slides as $slide): ?>
                <article class="slider__slide">
                    <img src="/assets/img/<?= e($slide['img']) ?>" alt="" width="720" height="360">
                    <div class="slider__content">
                        <h2><?= e($slide['title']) ?></h2>
                        <p><?= e($slide['text']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="slider__controls">
        <button class="slider__button" type="button" data-slider-prev aria-label="Предыдущий слайд">‹</button>
        <div class="slider__dots" data-slider-dots></div>
        <button class="slider__button" type="button" data-slider-next aria-label="Следующий слайд">›</button>
    </div>
</section>

<section class="card applications-card">
    <div class="card-head">
        <div>
            <h2>Мои заявки</h2>
        </div>
        <span class="badge"><?= count($applications) ?> заявок</span>
    </div>

    <?php if (!$applications): ?>
        <p class="muted">У вас пока нет заявок. Создайте первую заявку на обучение.</p>
    <?php else: ?>
        <div class="application-list">
            <?php foreach ($applications as $application): ?>
                <article class="application-item animate-in">
                    <div class="application-item__top">
                        <div>
                            <span class="muted">Заявка №<?= (int) $application['id'] ?></span>
                            <h3><?= e($application['course_title']) ?></h3>
                        </div>
                        <span class="status status--<?= e(match ($application['status']) { 'Новая' => 'new', 'Идет обучение' => 'progress', default => 'done' }) ?>"><?= e($application['status']) ?></span>
                    </div>
                    <dl class="meta-grid">
                        <div><dt>Дата старта</dt><dd><?= e(date('d.m.Y', strtotime($application['start_date']))) ?></dd></div>
                        <div><dt>Оплата</dt><dd><?= e($application['payment_method']) ?></dd></div>
                    </dl>
                    <div class="review-box">
                        <?php if ($application['review_text']): ?>
                            <strong>Отзыв: <?= (int) $application['rating'] ?>/5</strong>
                            <p><?= e($application['review_text']) ?></p>
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
                            <span class="muted">Отзыв доступен после завершения обучения.</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials_footer.php'; ?>
