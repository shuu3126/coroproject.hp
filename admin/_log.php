<?php
function write_admin_log($pdo, $userId, $actionType, $targetType, $targetId, $summary, $details = null) {
    $json = $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $stmt = $pdo->prepare('INSERT INTO admin_logs (user_id, action_type, target_type, target_id, summary, details_json, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $userId ?: null,
        (string)$actionType,
        (string)$targetType,
        $targetId !== null ? (string)$targetId : null,
        (string)$summary,
        $json,
    ]);
}
