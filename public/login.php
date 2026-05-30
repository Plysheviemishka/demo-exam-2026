<?php
require __DIR__ . '/../src/Support/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (auth()->attempt($login, $password)) {
        flash('success', 'Добро пожаловать в личный кабинет.');
        redirect('/dashboard.php');
    }

    flash('danger', 'Неверный логин или пароль. Проверьте данные и повторите попытку.');
}

$title = 'Вход — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="auth-layout">
    <div class="hero-card">
        <p class="eyebrow">Онлайн-обучение</p>
        <h1>Войдите на портал «Учусь.РФ»</h1>
        <p>Подайте заявку на курс, выберите удобную дату старта и отслеживайте статус рассмотрения.</p>
    </div>

    <form class="card form" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form__group">
            <label for="login">Логин</label>
            <input id="login" name="login" required autocomplete="username">
        </div>
        <div class="form__group">
            <label for="password">Пароль</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="button" type="submit">Войти</button>
        <p class="form__link">Еще не зарегистрированы? <a href="/register.php">Регистрация</a></p>
        <p class="form__link"><a href="/admin_login.php">Вход администратора</a></p>
    </form>
</section>
<?php require __DIR__ . '/partials_footer.php'; ?>
