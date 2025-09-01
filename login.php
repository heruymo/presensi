<?php
// File: login.php

// Tampilkan error (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session
session_start();

// Jika sudah login, langsung ke index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Koneksi database
require __DIR__ . '/includes/db.php';

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Ambil user berdasarkan username
        $stmt = $pdo->prepare("
            SELECT id, nama, role, password
            FROM users
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi password (kolom password berisi hash)
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID untuk keamanan
            session_regenerate_id(true);

            // Simpan data user di session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect ke halaman utama
            header('Location: index.php');
            exit;
        }

        // Jika gagal
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Presensi</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="max-width:360px; width:100%;">
      <h5 class="card-title text-center mb-3">Login Presensi</h5>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input
            type="text"
            name="username"
            class="form-control"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($username) ?>"
            required
          >
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input
            type="password"
            name="password"
            class="form-control"
            placeholder="Masukkan password"
            required
          >
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk</button>
      </form>
    </div>
  </div>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>