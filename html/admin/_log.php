<?php
function write_admin_log(PDO $pdo, ?int $userId, string $actionType, string $targetType, ?int $targetId, string $summary, ?array $details = null): void
{
    $stmt = $pdo->prepare('INSERT INTO admin_logs (user_id, action_type, target_type, target_id, summary, details_json) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $actionType,
        $targetType,
        $targetId,
        $summary,
        $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
    ]);
}
