<?php

declare(strict_types=1);

use App\Database;
use App\Models\User;

function validate_registration(array $data): array
{
    $errors = [];
    $login = trim((string) ($data['login'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $fullName = trim((string) ($data['full_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));

    if (!preg_match('/^[A-Za-z0-9]{6,}$/', $login)) {
        $errors['login'] = 'Логин должен быть уникальным, состоять из латинских букв и цифр, минимум 6 символов.';
    } else {
        $users = new User(Database::connection());
        if ($users->loginExists($login)) {
            $errors['login'] = 'Такой логин уже занят.';
        }
    }

    if (mb_strlen($password) < 8) {
        $errors['password'] = 'Пароль должен содержать 8 символов и более.';
    }

    if ($fullName === '') {
        $errors['full_name'] = 'Укажите ФИО.';
    }

    if ($phone === '') {
        $errors['phone'] = 'Укажите контактный номер телефона.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Укажите корректный e-mail.';
    }

    return $errors;
}

function validate_application(array $data): array
{
    $errors = [];
    $courseTypeId = (int) ($data['course_type_id'] ?? 0);
    $startDate = (string) ($data['start_date'] ?? '');
    $paymentMethod = (string) ($data['payment_method'] ?? '');
    $allowedPayment = ['Банковская карта', 'СБП', 'Счет для организации'];

    if ($courseTypeId <= 0) {
        $errors['course_type_id'] = 'Выберите вид курса.';
    }

    $date = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$date || $date->format('Y-m-d') !== $startDate) {
        $errors['start_date'] = 'Укажите дату начала обучения.';
    }

    if (!in_array($paymentMethod, $allowedPayment, true)) {
        $errors['payment_method'] = 'Выберите способ оплаты.';
    }

    return $errors;
}
