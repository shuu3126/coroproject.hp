<?php
require __DIR__ . '/_bootstrap.php';

// status ENUMに replied/closed を追加（既にある場合は無視）
try {
    $pdo->exec("ALTER TABLE inquiries MODIFY COLUMN status ENUM('unread','read','replied','closed') NOT NULL DEFAULT 'unread'");
} catch (\Throwable $_e) {}

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// GET /inquiries/{id} — 詳細
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { api_error(404, 'Inquiry not found'); }
    api_ok($row);
}

// GET /inquiries — 一覧（?q=検索&status=unread等）
if ($method === 'GET') {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['q'])) {
        $q = '%' . $_GET['q'] . '%';
        $where[] = '(name LIKE ? OR email LIKE ? OR topic LIKE ? OR message LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }
    if (!empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = $_GET['status'];
    }
    $stmt = $pdo->prepare("SELECT id, name, email, topic, message, status, created_at FROM inquiries WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// PATCH /inquiries/{id} — ステータス更新
if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['status'];
    $sets = []; $params = [];
    foreach ($allowed as $f) { if (array_key_exists($f, $body)) { $sets[] = "{$f} = ?"; $params[] = $body[$f]; } }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE inquiries SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($params);
    api_ok(['id' => $id]);
}

api_error(405, 'Method not allowed');
