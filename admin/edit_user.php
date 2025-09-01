<?php
// File: admin/edit_user.php

// 1) Session & Database
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/db.php';
// require __DIR__ . '/../includes/ip_check.php'; // aktifkan jika perlu

// 2) Proteksi: hanya admin yang boleh akses
if (
    ! isset($_SESSION['user_role'])
    || $_SESSION['user_role'] !== 'admin'
) {
    header('Location: ../login.php');
    exit;
}

// 3) Tangkap dan validasi parameter ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (! $id) {
    header('Location: users.php');
    exit;
}

// 4) Proses UPDATE saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $role     =         $_POST['role']   ?? '';
    $password =         $_POST['password'] ?? '';
    $error    = '';

    // Validasi input
    if ($nama === '') {
        $error = 'Nama lengkap wajib diisi.';
    } elseif ($role === '') {
        $error = 'Role wajib dipilih.';
    } else {
        try {
            if ($password !== '') {
                // Update nama, role & password baru
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql  = "UPDATE users
                         SET nama     = :nama,
                             role     = :role,
                             password = :pass
                         WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':role' => $role,
                    ':pass' => $hash,
                    ':id'   => $id
                ]);
            } else {
                // Update hanya nama & role
                $sql  = "UPDATE users
                         SET nama = :nama,
                             role = :role
                         WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':role' => $role,
                    ':id'   => $id
                ]);
            }

            // Redirect ke daftar user setelah sukses
            header('Location: users.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan perubahan: ' . $e->getMessage();
        }
    }
}

// 5) Ambil data user untuk ditampilkan di form
$stmt = $pdo->prepare("
    SELECT username, nama, role
    FROM users
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $user) {
    // Jika tidak ditemukan, kembali ke daftar
    header('Location: users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit User</title>
  <!-- Bootstrap CSS lokal -->
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container my-5" style="max-width: 500px;">
    <h4 class="mb-4">Edit User: <?= htmlspecialchars($user['username']) ?></h4>

    <?php if (! empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input
          type="text"
          class="form-control"
          value="<?= htmlspecialchars($user['username']) ?>"
          readonly
        >
      </div>

      <div class="mb-3">
        <label class="form-label">Password Baru (opsional)</label>
        <input
          type="password"
          name="password"
          class="form-control"
          placeholder="Kosongkan jika tidak diubah"
        >
      </div>

      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input
          type="text"
          name="nama"
          class="form-control"
          required
          value="<?= htmlspecialchars($user['nama']) ?>"
        >
      </div>

      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
          <option value="">— Pilih Role —</option>
          <option value="admin"
            <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
            Admin
          </option>
          <option value="siswa"
            <?= $user['role'] === 'siswa' ? 'selected' : '' ?>>
            Siswa
          </option>
        </select>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="users.php" class="btn btn-secondary">Kembali ke Daftar User</a>
      </div>
    </form>
  </div>

  <!-- Bootstrap JS lokal -->
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>