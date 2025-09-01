<?php
// includes/ip_check.php

// Cek apakah IP berada di antara $start dan $end
function ip_in_range(string $ip, string $start, string $end): bool {
    $ipLong  = ip2long($ip);
    $min     = ip2long($start);
    $max     = ip2long($end);
    return ($ipLong !== false && $min !== false && $max !== false
            && $ipLong >= $min && $ipLong <= $max);
}

// Tangkap alamat IP client
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

// Definisikan subnet/subnet-range yang diizinkan
$allowedRanges = [
    // subnet 10.184.36.0/24
    ['10.126.147.0',   '10.126.147.255'],
    ['10.73.201.0',   '10.73.201.255'],
    ['192.168.11.0',   '192.168.11.255'],
    ['192.168.0.0','192.168.0.255'],
];

$accessGranted = false;
foreach ($allowedRanges as $range) {
    if (ip_in_range($clientIp, $range[0], $range[1])) {
        $accessGranted = true;
        break;
    }
}

if (! $accessGranted) {
    // Kalau perlu, bisa pakai header 403
    header('HTTP/1.1 403 Forbidden');
    die('Akses dibatasi hanya untuk jaringan Wi-Fi kantor.');
}