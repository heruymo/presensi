<?php
// File: admin/rekap_presensi.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/db.php';

// Proteksi: hanya admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Ambil filter GET
$filterNama  = trim($_GET['nama']           ?? '');
$filterStart = $_GET['tanggal_awal']       ?? '';
$filterEnd   = trim($_GET['tanggal_akhir'] ?? '');
$filterRole  = trim($_GET['role']          ?? '');

// Direktori upload
$uploadBaseDir = __DIR__ . '/../uploads';
$uploadBaseUrl = '../uploads';

// Bangun query
$sql = "
  SELECT
    a.id,
    u.nama,
    u.role,
    DATE(a.timestamp) AS tanggal,
    MAX(CASE WHEN a.type='masuk'  THEN TIME(a.timestamp) END) AS masuk,
    MAX(CASE WHEN a.type='pulang' THEN TIME(a.timestamp) END) AS pulang,
    MAX(CASE WHEN a.type='masuk'  THEN a.ip_address END)        AS ip_masuk,
    MAX(CASE WHEN a.type='pulang' THEN a.ip_address END)        AS ip_pulang,
    MAX(CASE WHEN a.type='masuk'  THEN a.photo END)             AS photo_masuk
  FROM attendance a
  JOIN users u ON a.user_id = u.id
  WHERE 1=1
";
$params = [];

// Filter nama
if ($filterNama) {
    $sql      .= " AND u.nama LIKE ?";
    $params[] = "%{$filterNama}%";
}

// Filter tanggal
if ($filterStart) {
    $sql      .= " AND DATE(a.timestamp) >= ?";
    $params[] = $filterStart;
}
if ($filterEnd) {
    $sql      .= " AND DATE(a.timestamp) <= ?";
    $params[] = $filterEnd;
}

// Filter role
if ($filterRole) {
    $sql      .= " AND u.role = ?";
    $params[] = $filterRole;
}

$sql .= "
  GROUP BY u.id, DATE(a.timestamp)
  ORDER BY tanggal DESC
  LIMIT 100
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rekap Presensi</title>
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">
  <style>
    form.row > .col-auto { display: flex; flex-direction: column; }
    .btn-sm { padding: .25rem .5rem; font-size: .875rem; }
    td, th { vertical-align: middle; }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm px-3">
  <span class="navbar-brand h5 mb-0">Rekap Presensi</span>
  <div class="ms-auto">
    <a href="../index.php" class="btn btn-sm btn-secondary">← Dashboard</a>
  </div>
</nav>

<div class="container py-4" style="max-width:1000px;">
  <form method="get" class="row gx-3 gy-2 align-items-end mb-4">
    <div class="col-auto">
      <label class="form-label small mb-1">Nama</label>
      <input type="text" name="nama"
             class="form-control form-control-sm"
             placeholder="Cari nama"
             value="<?= htmlspecialchars($filterNama) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Tanggal Awal</label>
      <div class="input-group input-group-sm">
        <input type="date" name="tanggal_awal"
               class="form-control"
               value="<?= htmlspecialchars($filterStart) ?>">
        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
      </div>
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Tanggal Akhir</label>
      <div class="input-group input-group-sm">
        <input type="date" name="tanggal_akhir"
               class="form-control"
               value="<?= htmlspecialchars($filterEnd) ?>">
        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
      </div>
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Role</label>
      <select name="role" class="form-select form-select-sm">
        <option value=""        <?= $filterRole === ''        ? 'selected' : '' ?>>Semua Role</option>
        <option value="admin"   <?= $filterRole === 'admin'   ? 'selected' : '' ?>>Admin</option>
        <option value="siswa"   <?= $filterRole === 'siswa'   ? 'selected' : '' ?>>Siswa</option>
      </select>
    </div>
    <div class="col-auto">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="rekap_presensi.php" class="btn btn-secondary btn-sm">Reset</a>
        <!-- ... di dalam form / setelah tombol Reset -->
<a
  href="attendance.php?<?= http_build_query([
       'nama'          => $filterNama,
       'tanggal_awal'  => $filterStart,
       'tanggal_akhir' => $filterEnd,
       'role'          => $filterRole,
  ])?>"
  class="btn btn-success btn-sm"
  target="_blank"
>
  <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
</a>
      </div>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Nama</th>
          <th>Role</th>
          <th>Tanggal</th>
          <th>Masuk</th>
          <th>Pulang</th>
          <th>IP Masuk</th>
          <th>IP Pulang</th>
          <th>Foto</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted">Data tidak ditemukan.</td>
          </tr>
        <?php else: foreach ($data as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['role']) ?></td>
            <td><?= htmlspecialchars($r['tanggal']) ?></td>
            <td><?= htmlspecialchars($r['masuk']    ?? '-') ?></td>
            <td><?= htmlspecialchars($r['pulang']   ?? '-') ?></td>
            <td><?= htmlspecialchars($r['ip_masuk'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['ip_pulang']?? '-') ?></td>
            <td class="text-center">
              <?php
                if (!empty($r['photo_masuk'])) {
                  $file = "{$uploadBaseDir}/{$r['photo_masuk']}";
                  $url  = "{$uploadBaseUrl}/" . implode('/', array_map('rawurlencode', explode('/', $r['photo_masuk'])));
                  echo file_exists($file)
                    ? "<img src=\"{$url}\" style=\"height:40px;object-fit:cover;\">"
                    : '&ndash;';
                } else {
                  echo '&ndash;';
                }
              ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>