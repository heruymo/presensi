<?php
// admin/delete_presensi.php

// 1) Mulai session dan cek login + role
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// 2) Ambil ID record yang diklik
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: rekap_presensi.php');
    exit;
}

// 3) Ambil user_id & timestamp dari satu record
$stmt = $pdo->prepare("
    SELECT user_id, `timestamp`
    FROM attendance
    WHERE id = ?
");
$stmt->execute([$id]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rec) {
    header('Location: rekap_presensi.php');
    exit;
}

// 4) Tentukan tanggal dan user_id
$userId  = $rec['user_id'];
$tanggal = date('Y-m-d', strtotime($rec['timestamp']));

// 5) Ambil semua nama file foto untuk user & tanggal itu
$photoStmt = $pdo->prepare("
    SELECT photo
    FROM attendance
    WHERE user_id = ?
      AND DATE(`timestamp`) = ?
      AND photo IS NOT NULL
");
$photoStmt->execute([$userId, $tanggal]);
$photos = $photoStmt->fetchAll(PDO::FETCH_COLUMN);

// 6) Hapus file–file foto yang ada
foreach ($photos as $fileName) {
    $path = __DIR__ . '/../uploads/' . $fileName;
    if ($fileName && is_file($path)) {
        @unlink($path);
    }
}

// 7) Hapus semua record attendance untuk user & tanggal itu
$del = $pdo->prepare("
    DELETE FROM attendance
    WHERE user_id = ?
      AND DATE(`timestamp`) = ?
");
$del->execute([$userId, $tanggal]);

// 8) Redirect kembali ke rekap
header('Location: rekap_presensi.php');
exit;