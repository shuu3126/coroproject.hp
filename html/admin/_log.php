<?php
function write_admin_log( $pdo, $userId, $actionType, $targetType, $targetId, $summary, $details = null) {
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
