<?php

function log_action(PDO $pdo, string $action, string $target_type, string $target_name, string $detail = ''): void {
    $user = currentUser();
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, user_name, action, target_type, target_name, detail)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $user['name'] ?: '不明',
        $action,
        $target_type,
        $target_name,
        $detail ?: null,
    ]);
}
