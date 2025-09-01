<?php
// File: dashboard.php

// Tampilkan error (untuk debugging, hilangkan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inisialisasi session dan koneksi database
require __DIR__ . '/includes/session.php'; // session_start() sudah di sini
require __DIR__ . '/includes/db.php';

// Redirect ke login jika belum autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Pengguna';
$userRole = $_SESSION['user_role'] ?? '';
$info     = '';

// Direktori dasar untuk menyimpan foto dan URL dasar untuk ditampilkan
$uploadBaseDir = __DIR__ . '/uploads';
$uploadBaseUrl = 'uploads';

// Proses presensi saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type      = $_POST['type'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $fileName  = null;
    $folder    = null;

    // Validasi tipe presensi
    if (!in_array($type, ['masuk', 'pulang'], true)) {
        $info = 'Tipe presensi tidak valid.';
    }

    // Jika presensi masuk, wajib unggah foto
    if ($info === '' && $type === 'masuk') {
        if (empty($_FILES['photo']['tmp_name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $info = 'Foto selfie wajib diunggah.';
        } else {
            // Validasi MIME dan ekstensi
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            $ext  = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg','jpeg','png','webp'];

            if (strpos($mime, 'image/') !== 0) {
                $info = 'File harus berupa gambar.';
            } elseif (!in_array($ext, $allowedExt, true)) {
                $info = 'Ekstensi gambar tidak didukung.';
            } else {
                // Buat folder sesuai tahun-bulan: uploads/YYYY-MM
                $folder   = date('Y-m');
                $dirPath  = "$uploadBaseDir/$folder";
                if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
                    $info = 'Gagal membuat folder penyimpanan.';
                }

                // Generate nama file unik
                if ($info === '') {
                    $fileName    = $userId . '_' . time() . ".$ext";
                    $destination = "$dirPath/$fileName";
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                        $info = 'Gagal menyimpan foto.';
                    }
                }
            }
        }
    }

    // Jika presensi pulang, cek sudah pernah masuk hari ini
    if ($info === '' && $type === 'pulang') {
        $cekMasuk = $pdo->prepare("
            SELECT COUNT(*) FROM attendance
            WHERE user_id = ? 
              AND type = 'masuk'
              AND DATE(`timestamp`) = CURDATE()
        ");
        $cekMasuk->execute([$userId]);
        if ($cekMasuk->fetchColumn() == 0) {
            $info = 'Anda harus presensi masuk terlebih dahulu.';
        }
    }

    // Simpan presensi jika validasi lolos
    if ($info === '') {
        $cekPresensi = $pdo->prepare("
            SELECT COUNT(*) FROM attendance
            WHERE user_id = ? 
              AND type = ? 
              AND DATE(`timestamp`) = CURDATE()
        ");
        $cekPresensi->execute([$userId, $type]);

        if ($cekPresensi->fetchColumn() == 0) {
            $pathInDb = $fileName ? "$folder/$fileName" : null;
            $insert = $pdo->prepare("
                INSERT INTO attendance (user_id, type, ip_address, photo)
                VALUES (?, ?, ?, ?)
            ");
            $insert->execute([
                $userId,
                $type,
                $ipAddress,
                $pathInDb
            ]);
            $info = "Presensi $type berhasil.";
        } else {
            // Jika sudah pernah presensi tipe yang sama hari ini, hapus file baru jika ada
            if ($fileName && file_exists("$uploadBaseDir/$folder/$fileName")) {
                @unlink("$uploadBaseDir/$folder/$fileName");
            }
            $info = "Anda sudah presensi $type hari ini.";
        }
    }
}

// Ambil riwayat presensi (30 hari terakhir)
$stmt = $pdo->prepare("
    SELECT
      DATE(`timestamp`)                         AS tanggal,
      MAX(CASE WHEN type='masuk'  THEN TIME(`timestamp`) END) AS masuk,
      MAX(CASE WHEN type='pulang' THEN TIME(`timestamp`) END) AS pulang,
      MAX(CASE WHEN type='masuk'  THEN ip_address END)        AS ip_masuk,
      MAX(CASE WHEN type='pulang' THEN ip_address END)        AS ip_pulang,
      MAX(CASE WHEN type='masuk'  THEN photo END)             AS photo_masuk
    FROM attendance
    WHERE user_id = ?
    GROUP BY DATE(`timestamp`)
    ORDER BY tanggal DESC
    LIMIT 30
");
$stmt->execute([$userId]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Presensi</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Presensi</a>
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
            aria-controls="navbarMenu"
            aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMenu">
      <div class="ms-auto d-flex flex-wrap gap-2">
        <?php if ($userRole === 'admin'): ?>
          <a href="admin/users.php" class="btn btn-warning btn-sm">
            <i class="bi bi-people-fill"></i> Manajemen User
          </a>
          <a href="admin/rekap_presensi.php" class="btn btn-info btn-sm">
            <i class="bi bi-calendar-check"></i> Rekap Presensi
          </a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-danger btn-sm">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4" style="max-width:700px;">
  <h4 class="mb-3">Halo, <?= htmlspecialchars($userName) ?></h4>

  <?php if ($info): ?>
    <div class="alert alert-info"><?= htmlspecialchars($info) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="mb-4">
    <div class="mb-3" style="max-width:320px;">
      <label for="photo" class="form-label">Foto Selfie (hanya saat masuk)</label>
      <input type="file"
             name="photo"
             id="photo"
             class="form-control form-control-sm"
             accept="image/*"
             capture="user">
    </div>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button type="submit"
              name="type"
              value="masuk"
              class="btn btn-success btn-sm">
        <i class="bi bi-box-arrow-in-right"></i> Absen Masuk
      </button>
      <button type="submit"
              name="type"
              value="pulang"
              class="btn btn-warning btn-sm">
        <i class="bi bi-box-arrow-in-left"></i> Absen Pulang
      </button>
    </div>
  </form>

  <h5 class="mb-3">Riwayat Presensi</h5>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-light">
        <tr>
          <th>Tanggal</th>
          <th>Masuk</th>
          <th>Pulang</th>
          <th>IP Masuk</th>
          <th>IP Pulang</th>
          <th>Foto Masuk</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($riwayat): ?>
          <?php foreach ($riwayat as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['tanggal']) ?></td>
              <td><?= htmlspecialchars($r['masuk']    ?? '-') ?></td>
              <td><?= htmlspecialchars($r['pulang']   ?? '-') ?></td>
              <td><?= htmlspecialchars($r['ip_masuk'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['ip_pulang']?? '-') ?></td>
              <td>
                <?php
                  $photoPath = $r['photo_masuk']
                    ? "$uploadBaseUrl/{$r['photo_masuk']}"
                    : '';
                  $fullPath = $photoPath
                    ? __DIR__ . "/$photoPath"
                    : '';
                ?>
                <?php if ($photoPath && file_exists($fullPath)): ?>
                  <img src="<?= htmlspecialchars($photoPath) ?>"
                       alt="Foto"
                       style="height:50px; object-fit:cover;"
                       loading="lazy">
                <?php else: ?>
                  &ndash;
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center">Belum ada data presensi.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>