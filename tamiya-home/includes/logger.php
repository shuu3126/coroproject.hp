<?php

function log_action(PDO $pdo, string $action, string $target_type, string $target_name, string $detail = ''): void {
    try {
        $user = function_exists('currentUser') ? currentUser() : ['id' => null, 'name' => ''];
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, user_name, action, target_type, target_name, detail)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id']   ?? null,
            $user['name'] ?? '',
            $action,
            $target_type,
            $target_name,
            $detail ?: null,
        ]);
    } catch (Throwable $e) {
        error_log('log_action failed: ' . $e->getMessage());
    }
}
