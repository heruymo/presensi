<?php
// admin/add_user.php

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';

$error = '';
// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username']  ?? '');
    $n = trim($_POST['nama']      ?? '');
    $p = $_POST['password']      ?? '';
    $r = $_POST['role']          ?? '';

    if ($u && $n && $p && $r) {
        $hash = password_hash($p, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
          INSERT INTO users (username, password, nama, role)
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$u, $hash, $n, $r]);

        // Redirect ke daftar user
        header('Location: users.php');
        exit;
    }

    $error = 'Seluruh field wajib diisi.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah User – Manajemen User</title>
  <!-- Bootstrap CSS lokal -->
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="users.php">Manajemen User</a>
      <a href="users.php" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
  </nav>

  <div class="container py-5 d-flex justify-content-center">
    <div class="card shadow-sm w-100" style="max-width:500px;">
      <div class="card-body">
        <h4 class="card-title mb-4">Tambah User</h4>

        <?php if ($error): ?>
          <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input
              type="text"
              id="username"
              name="username"
              class="form-control"
              required
              placeholder="Masukkan username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="nama" class="form-label">Nama Lengkap</label>
            <input
              type="text"
              id="nama"
              name="nama"
              class="form-control"
              required
              placeholder="Masukkan nama lengkap"
              value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              required
              placeholder="Masukkan password">
          </div>

          <div class="mb-4">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-select" required>
              <option value="">Pilih role</option>
              <option value="admin"
                <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>
                Admin
              </option>
              <option value="siswa"
                <?= (($_POST['role'] ?? '') === 'siswa') ? 'selected' : '' ?>>
                Siswa
              </option>
            </select>
          </div>

          <div class="d-flex flex-column flex-md-row gap-2">
            <button type="submit" class="btn btn-success flex-fill">
              Simpan
            </button>
            <a href="users.php" class="btn btn-secondary flex-fill">
              Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS lokal -->
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>