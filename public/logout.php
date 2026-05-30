<?php
require __DIR__ . '/../src/Support/bootstrap.php';
auth()->logout();
session_start();
flash('success', 'Вы вышли из системы.');
redirect('/login.php');
