<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// GET /mail?accounts=1 — 登録アカウント一覧
if ($method === 'GET' && !empty($_GET['accounts'])) {
    try {
        $rows = $pdo->query(
            "SELECT id, label, email, is_default, is_active, last_sync_at FROM mail_accounts ORDER BY is_default DESC, id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        api_ok($rows);
    } catch (\Throwable $e) {
        api_ok([]);
    }
}

// GET /mail/{id} — 詳細（自動既読）
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare(
        "SELECT id, account_email, mailbox, from_name, from_email, to_text, subject, body_text, status, has_attachments, received_at
         FROM mail_messages WHERE id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { api_error(404, 'Message not found'); }
    $pdo->prepare("UPDATE mail_messages SET status = 'read', updated_at = NOW() WHERE id = ? AND status = 'unread'")->execute([$id]);
    api_ok($row);
}

// GET /mail — 受信トレイ一覧
if ($method === 'GET') {
    $where = ["mailbox = 'inbox'"]; $params = [];
    if (!empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['account'])) {
        $where[] = 'account_email = ?';
        $params[] = $_GET['account'];
    }
    $limit = min((int)($_GET['limit'] ?? 30), 100);
    $stmt = $pdo->prepare(
        "SELECT id, account_email, from_name, from_email, subject, status, has_attachments, received_at
         FROM mail_messages WHERE " . implode(' AND ', $where) . " ORDER BY received_at DESC LIMIT {$limit}"
    );
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// PATCH /mail/{id} — ステータス更新（read / unread）
if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['status'];
    $sets = []; $params = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $body)) { $sets[] = "{$f} = ?"; $params[] = $body[$f]; }
    }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE mail_messages SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($params);
    api_ok(['id' => $id]);
}

api_error(405, 'Method not allowed');
