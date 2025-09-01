<?php
// File: includes/db.php

$dsn     = 'mysql:host=localhost;dbname=presensi;charset=utf8mb4';
$dbUser  = 'root';
$dbPass  = '';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}