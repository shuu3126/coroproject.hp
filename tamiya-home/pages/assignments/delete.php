<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/assignments/index.php'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare('DELETE FROM assignments WHERE id = ?')->execute([$id]);
}

$redirect = ($_POST['redirect'] ?? '') === 'index'
    ? '/tamiya-home/pages/assignments/index.php'
    : '/tamiya-home/pages/assignments/index.php';

header('Location: ' . $redirect);
exit;
