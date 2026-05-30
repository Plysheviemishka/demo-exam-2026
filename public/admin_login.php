<?php
require __DIR__ . '/../src/Support/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (auth()->attemptAdmin($login, $password)) {
        flash('success', 'Вы вошли как администратор.');
        redirect('/admin.php');
    }

    flash('danger', 'Неверный логин или пароль администратора.');
}

$title = 'Вход администратора — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="auth-layout auth-layout--compact">
    <div class="hero-card">
        <p class="eyebrow">Администратор</p>
        <h1>Панель управления заявками</h1>
        <p>Доступ защищен учетными данными администратора.</p>
    </div>

    <form class="card form" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form__group">
            <label for="login">Логин администратора</label>
            <input id="login" name="login" required autocomplete="username">
        </div>
        <div class="form__group">
            <label for="password">Пароль</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="button" type="submit">Войти в панель</button>
        <p class="form__link"><a href="/login.php">Вернуться ко входу пользователя</a></p>
    </form>
</section>
<?php require __DIR__ . '/partials_footer.php'; ?>
