<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/qualifications/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$redirect = '/tamiya-home/pages/craftsmen/index.php';

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT q.id, q.craftsman_id, q.name, c.name AS craftsman_name
        FROM qualifications q
        JOIN craftsmen c ON q.craftsman_id = c.id
        WHERE q.id = ?
    ");
    $stmt->execute([$id]);
    $qualification = $stmt->fetch();

    if ($qualification) {
        $redirect = '/tamiya-home/pages/craftsmen/detail.php?id=' . (int)$qualification['craftsman_id'];

        $stmt = $pdo->prepare('DELETE FROM qualifications WHERE id = ?');
        $stmt->execute([$id]);

        log_action(
            $pdo,
            'delete',
            '資格',
            $qualification['name'],
            $qualification['craftsman_name'] . ' から資格を削除'
        );
    }
}

header('Location: ' . $redirect);
exit;
