<?php
require __DIR__ . '/includes/ip_check.php';
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
