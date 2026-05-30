<?php
require __DIR__ . '/../src/Support/bootstrap.php';
require __DIR__ . '/../src/Support/validation.php';

use App\Database;
use App\Models\User;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = validate_registration($_POST);
    remember_old($_POST);

    if (!$errors) {
        $users = new User(Database::connection());
        $users->create([
            'login' => trim($_POST['login']),
            'password' => $_POST['password'],
            'full_name' => trim($_POST['full_name']),
            'phone' => trim($_POST['phone']),
            'email' => trim($_POST['email']),
        ]);

        clear_old();
        flash('success', 'Регистрация прошла успешно. Теперь войдите в систему.');
        redirect('/login.php');
    }
}

$title = 'Регистрация — Учусь.РФ';
require __DIR__ . '/partials_header.php';
?>
<section class="auth-layout">
    <div class="hero-card">
        <p class="eyebrow">Новый пользователь</p>
        <h1>Регистрация на портале</h1>
        <p>Создайте аккаунт, чтобы подавать заявки на онлайн-курсы и отслеживать историю обучения.</p>
    </div>

    <form class="card form" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form__group">
            <label for="login">Логин</label>
            <input id="login" name="login" required minlength="6" pattern="[A-Za-z0-9]{6,}" value="<?= e(old('login')) ?>" autocomplete="username">
            <small>Латинские буквы и цифры, минимум 6 символов.</small>
            <?php if (isset($errors['login'])): ?><span class="field-error"><?= e($errors['login']) ?></span><?php endif; ?>
        </div>
        <div class="form__group">
            <label for="password">Пароль</label>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
            <small>8 символов и более.</small>
            <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>
        <div class="form__group">
            <label for="full_name">ФИО</label>
            <input id="full_name" name="full_name" required value="<?= e(old('full_name')) ?>" autocomplete="name">
            <?php if (isset($errors['full_name'])): ?><span class="field-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
        </div>
        <div class="form__group">
            <label for="phone">Контактный номер телефона</label>
            <input id="phone" name="phone" required value="<?= e(old('phone')) ?>" autocomplete="tel">
            <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
        </div>
        <div class="form__group">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required value="<?= e(old('email')) ?>" autocomplete="email">
            <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>
        <button class="button" type="submit">Зарегистрироваться</button>
        <p class="form__link">Уже зарегистрированы? <a href="/login.php">Войти</a></p>
    </form>
</section>
<?php require __DIR__ . '/partials_footer.php'; ?>
