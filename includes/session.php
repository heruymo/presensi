<?php
// File: includes/session.php

// 1) Setup cookie params aman
$params = session_get_cookie_params();
$isSecure = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $_SERVER['SERVER_PORT'] == 443
);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => $params['path'],
    'domain'   => $params['domain'],
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// 2) Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3) Timeout 30 menit
if (
    isset($_SESSION['LAST_ACTIVITY']) &&
    time() - $_SESSION['LAST_ACTIVITY'] > 1800
) {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}
$_SESSION['LAST_ACTIVITY'] = time();

// 4) Sinkronisasi 'user_role' → 'role'
if (isset($_SESSION['user_role']) && !isset($_SESSION['role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}

// 5) Proteksi: kecuali saat di login.php
$current = basename($_SERVER['PHP_SELF']);
if ($current !== 'login.php' && !isset($_SESSION['user_id'])) {
    // ubah '/presensi/login.php' jika aplikasi ada di subfolder lain
    header('Location: /presensi/login.php');
    exit;
}