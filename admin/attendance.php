<?php
// File: admin/export_excel.php

// Debugging – hapus di production
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/db.php';

// Ambil filter sama seperti di rekap_presensi.php
$filterNama   = trim($_GET['nama']           ?? '');
$filterStart  = trim($_GET['tanggal_awal']   ?? '');
$filterEnd    = trim($_GET['tanggal_akhir'] ?? '');
$filterRole   = trim($_GET['role']          ?? '');

// Build query persis, dengan ORDER BY tanggal DESC
$sql = "
  SELECT
    u.nama,
    u.role,
    DATE(a.timestamp) AS tanggal,
    MAX(CASE WHEN a.type='masuk'  THEN TIME(a.timestamp) END) AS masuk,
    MAX(CASE WHEN a.type='pulang' THEN TIME(a.timestamp) END) AS pulang,
    MAX(CASE WHEN a.type='masuk'  THEN a.ip_address END)      AS ip_masuk,
    MAX(CASE WHEN a.type='pulang' THEN a.ip_address END)      AS ip_pulang,
    MAX(CASE WHEN a.type='masuk'  THEN a.photo END)           AS photo_masuk
  FROM attendance a
  JOIN users u ON a.user_id = u.id
  WHERE 1=1
";
$params = [];

// Kondisi filter
if ($filterNama)  { $sql .= " AND u.nama LIKE ?";      $params[] = "%{$filterNama}%"; }
if ($filterStart) { $sql .= " AND DATE(a.timestamp)>=?"; $params[] = $filterStart;     }
if ($filterEnd)   { $sql .= " AND DATE(a.timestamp)<=?"; $params[] = $filterEnd;       }
if ($filterRole)  { $sql .= " AND u.role = ?";          $params[] = $filterRole;      }

// **Di sinilah perubahannya: DESC untuk tanggal paling baru di atas**
$sql .= " GROUP BY u.id, DATE(a.timestamp) ORDER BY tanggal DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Header agar browser mengunduh sebagai Excel .xls
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=rekap_presensi.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Siapkan path dan ikon kamera
$uploadBaseDir = realpath(__DIR__ . '/../uploads');
$iconFile      = realpath(__DIR__ . '/../assets/img/camera.png');
$iconUri       = $iconFile
  ? 'file:///' . str_replace('\\','/', $iconFile)
  : '';

// Cetak HTML table
echo "<table border='1'>";
echo "<tr>
        <th>Nama</th>
        <th>Role</th>
        <th>Tanggal</th>
        <th>Masuk</th>
        <th>Pulang</th>
        <th>IP Masuk</th>
        <th>IP Pulang</th>
        <th>Foto</th>
      </tr>";

foreach ($rows as $r) {
    echo '<tr>';
    echo '<td>'.htmlspecialchars($r['nama']).'</td>';
    echo '<td>'.htmlspecialchars($r['role']).'</td>';
    echo '<td>'.htmlspecialchars($r['tanggal']).'</td>';
    echo '<td>'.htmlspecialchars($r['masuk']    ?: '-').'</td>';
    echo '<td>'.htmlspecialchars($r['pulang']   ?: '-').'</td>';
    echo '<td>'.htmlspecialchars($r['ip_masuk'] ?: '-').'</td>';
    echo '<td>'.htmlspecialchars($r['ip_pulang']?: '-').'</td>';

    // Kolom Foto: ikon kamera yang klikable
    echo '<td align="center">';
    if (!empty($r['photo_masuk']) && $iconUri) {
        $photoFile = $uploadBaseDir . '/' . $r['photo_masuk'];
        if (file_exists($photoFile)) {
            $photoUri = 'file:///' . str_replace('\\','/', realpath($photoFile));
            echo '<a href="'.htmlspecialchars($photoUri).'" target="_blank">'
               . '<img src="'.htmlspecialchars($iconUri).'" width="16" height="16" />'
               . '</a>';
        } else {
            echo '-';
        }
    } else {
        echo '-';
    }
    echo '</td>';

    echo '</tr>';
}

echo '</table>';
exit;