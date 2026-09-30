<?php
require_once __DIR__ . '/../includes/functions.php';
require_post();

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $name    = trim($_POST['name'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    keep_old(['name' => $name, 'email' => $email]);

    $error = null;
    if ($name === '' || $email === '' || $pass === '')      $error = 'Semua field wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))     $error = 'Format email tidak valid.';
    elseif (strlen($pass) < 6)                              $error = 'Password minimal 6 karakter.';
    elseif ($pass !== $confirm)                             $error = 'Konfirmasi password tidak cocok.';
    else {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) $error = 'Email sudah terdaftar.';
    }
    if ($error) { flash('error', $error); redirect('register.php'); }

    $st = db()->prepare('INSERT INTO users (name, username, email, password) VALUES (?,?,?,?)');
    $st->execute([$name, generate_username($email), $email, password_hash($pass, PASSWORD_DEFAULT)]);

    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)db()->lastInsertId();
    $_SESSION['user_name'] = $name;
    clear_old();
    flash('success', 'Registrasi berhasil. Selamat datang di TripMate!');
    redirect('dashboard.php');
}

if ($action === 'login') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    keep_old(['email' => $email]);

    $st = db()->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u || !password_verify($pass, $u['password'])) {
        flash('error', 'Email atau password salah.');
        redirect('login.php');
    }
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$u['id'];
    $_SESSION['user_name'] = $u['name'];
    clear_old();
    redirect('dashboard.php');
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    session_start();
    flash('success', 'Anda telah logout.');
    redirect('login.php');
}

http_response_code(400);
exit('Aksi tidak dikenal.');
