<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/users/index.php'); exit;
}

$id = (int)($_POST['id'] ?? 0);
// 自分自身は削除不可
if ($id > 0 && $id !== (int)currentUser()['id']) {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}

header('Location: /tamiya-home/pages/users/index.php');
exit;
