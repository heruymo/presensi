<?php
// File: admin/users.php

// 1) Proteksi IP, koneksi DB & session
require __DIR__ . '/../includes/ip_check.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/session.php';

// 2) Pastikan hanya admin yang boleh akses
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// 3) Ambil parameter search
$search = trim($_GET['search'] ?? '');

// 4) Bangun query dengan filter search
$sql    = "SELECT id, username, nama, role 
           FROM users 
           WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql      .= " AND (username LIKE ? OR nama LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
$sql .= " ORDER BY id";

$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manajemen User</title>
  <!-- Bootstrap CSS lokal -->
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons lokal -->
  <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">
  <style>
    .btn-sm {
      padding: .25rem .5rem;
      font-size: .875rem;
    }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3">
  <a class="navbar-brand h5 mb-0" href="#">Manajemen User</a>
  <button class="navbar-toggler" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarUser"
          aria-controls="navbarUser"
          aria-expanded="false"
          aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse justify-content-end" id="navbarUser">
    <div class="d-flex flex-wrap gap-2">
      <a href="../index.php" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
      </a>
      <a href="add_user.php" class="btn btn-sm btn-success">
        <i class="bi bi-plus-lg"></i> Tambah User
      </a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4" style="max-width:800px; margin:0 auto;">
  <h5 class="mb-3">Daftar User</h5>

  <!-- FORM PENCARIAN -->
  <form method="get" class="row gx-2 gy-2 align-items-center mb-4">
    <div class="col-auto">
      <div class="input-group input-group-sm">
        <input
          type="text"
          name="search"
          class="form-control"
          placeholder="Cari username / nama"
          value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-search"></i> Cari
        </button>
      </div>
    </div>
    <?php if ($search !== ''): ?>
      <div class="col-auto">
        <a href="users.php" class="btn btn-outline-secondary btn-sm">
          Reset
        </a>
      </div>
    <?php endif; ?>
  </form>

  <!-- TABEL USER -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>Username</th>
          <th>Nama</th>
          <th>Role</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted">
              <?php if ($search !== ''): ?>
                Tidak ada user yang sesuai "<?= htmlspecialchars($search) ?>".
              <?php else: ?>
                Belum ada data user.
              <?php endif; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php $no = 1; ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['nama']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td class="text-center">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                  <a href="edit_user.php?id=<?= $u['id'] ?>"
                     class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil-fill"></i> Edit
                  </a>
                  <a href="delete_user.php?id=<?= $u['id'] ?>"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Hapus user ini?')">
                    <i class="bi bi-trash-fill"></i> Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bootstrap JS lokal -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>