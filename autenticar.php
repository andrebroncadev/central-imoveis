<?php
require_once __DIR__ . '/app.php';
require_post();
verify_csrf();

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
$expectedUser = (string)env('ADMIN_USER', 'admin');
$expectedHash = (string)env('ADMIN_PASSWORD_HASH', '');
$expectedPassword = (string)env('ADMIN_PASSWORD', '');

$validPassword = $expectedHash !== ''
    ? password_verify($password, $expectedHash)
    : ($expectedPassword !== '' && hash_equals($expectedPassword, $password));

if ($username === $expectedUser && $validPassword) {
    session_regenerate_id(true);
    $_SESSION['admin_user'] = $username;
    $_SESSION['logged_in'] = true;
    redirect('index.php');
}

flash('error', 'Usuário ou senha inválidos.');
redirect('login.php');