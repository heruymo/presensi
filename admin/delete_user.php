<?php
// admin/delete_user.php
require __DIR__.'/../includes/session.php';
require __DIR__.'/../includes/auth.php'; require_role('admin');
require __DIR__.'/../includes/db.php';

$id = $_GET['id'] ?? '';
if ($id) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
}
header('Location: users.php');
exit;