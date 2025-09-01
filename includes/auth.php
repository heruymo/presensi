<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        header('Location: forbidden.php');
        exit;
    }
}