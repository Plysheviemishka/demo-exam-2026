<?php
/** @var string $title */
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Учусь.РФ') ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/dashboard.php" aria-label="Учусь.РФ">
        <span class="brand__mark">У</span>
        <span>Учусь.РФ</span>
    </a>
    <nav class="topbar__nav" aria-label="Основная навигация">
        <?php if (auth()->user()): ?>
            <a href="/dashboard.php">Личный кабинет</a>
            <a href="/apply.php">Оформить заявку</a>
            <a href="/logout.php">Выйти</a>
        <?php elseif (auth()->isAdmin()): ?>
            <a href="/admin.php">Панель администратора</a>
            <a href="/logout.php">Выйти</a>
        <?php else: ?>
            <a href="/login.php">Вход</a>
            <a class="button button--small" href="/register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page">
    <?php foreach (flash_messages() as $message): ?>
        <div class="alert alert--<?= e($message['type']) ?>" role="alert"><?= e($message['message']) ?></div>
    <?php endforeach; ?>
