<?php
require_once __DIR__ . '/app.php';
require_post();
verify_csrf();

$email = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

$supabaseUrl = rtrim((string)env('SUPABASE_URL'), '/');
$publishableKey = (string)env('SUPABASE_PUBLISHABLE_KEY');

if ($supabaseUrl !== '' && $publishableKey !== '') {
    $ch = curl_init($supabaseUrl . '/auth/v1/token?grant_type=password');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $publishableKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $data = is_string($raw) ? json_decode($raw, true) : null;

    if ($status >= 200 && $status < 300 && is_array($data) && !empty($data['user']['id'])) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $data['user']['email'] ?? $email;
        $_SESSION['logged_in'] = true;
        session_write_close();
        redirect('index.php');
    }

    flash('error', 'E-mail ou senha inválidos.');
    redirect('login.php');
}

$expectedUser = (string)env('ADMIN_USER', 'admin');
$expectedHash = (string)env('ADMIN_PASSWORD_HASH', '');
$expectedPassword = (string)env('ADMIN_PASSWORD', '');

$validPassword = $expectedHash !== ''
    ? password_verify($password, $expectedHash)
    : ($expectedPassword !== '' && hash_equals($expectedPassword, $password));

if ($email === $expectedUser && $validPassword) {
    session_regenerate_id(true);
    $_SESSION['admin_user'] = $email;
    $_SESSION['logged_in'] = true;
    session_write_close();
    redirect('index.php');
}

flash('error', 'E-mail ou senha inválidos.');
redirect('login.php');
