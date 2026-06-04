<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();
$uri    = $_SERVER['REQUEST_URI'] ?? '';

// ステータス更新: POST /api/deals/{id}/status
if ($method === 'POST' && $id && strpos($uri, '/status') !== false) {
    $body   = api_input();
    $status = $body['status'] ?? '';
    $allowed = ['相談中', '提案済み', '条件交渉中', '実施中', '完了', '不成立'];
    if (!in_array($status, $allowed, true)) {
        api_error(400, 'invalid status');
    }
    $stmt = $pdo->prepare("UPDATE biz_deals SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);
    if ($stmt->rowCount() === 0) { api_error(404, 'Deal not found'); }
    api_ok(['id' => $id, 'status' => $status]);
}

// GET /api/deals/{id}
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("
        SELECT d.*, c.name AS client_name
        FROM biz_deals d
        LEFT JOIN clients c ON c.id = d.client_id
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $deal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$deal) { api_error(404, 'Deal not found'); }

    $cs = $pdo->prepare("
        SELECT dc.*, t.name AS talent_name
        FROM biz_deal_candidates dc
        LEFT JOIN talents t ON t.id = dc.talent_id
        WHERE dc.deal_id = ?
    ");
    $cs->execute([$id]);
    $deal['candidates'] = $cs->fetchAll(PDO::FETCH_ASSOC);
    api_ok($deal);
}

// POST /api/deals（新規作成）
if ($method === 'POST' && !$id) {
    $body = api_input();
    $required = ['client_id', 'title'];
    foreach ($required as $f) {
        if (empty($body[$f])) { api_error(400, "field '{$f}' is required"); }
    }
    $stmt = $pdo->prepare("
        INSERT INTO biz_deals (client_id, title, status, description, budget, start_date, end_date, memo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        (int)$body['client_id'],
        $body['title'],
        $body['status'] ?? '相談中',
        $body['description'] ?? null,
        $body['budget'] ?? null,
        $body['start_date'] ?? null,
        $body['end_date'] ?? null,
        $body['memo'] ?? null,
    ]);
    api_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

// GET /api/deals?status=実施中&client_id=1
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];
    if (!empty($_GET['status'])) {
        $where[] = 'd.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['client_id'])) {
        $where[] = 'd.client_id = ?';
        $params[] = (int)$_GET['client_id'];
    }
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.status, d.budget, d.start_date, d.end_date,
               c.name AS client_name, d.created_at
        FROM biz_deals d
        LEFT JOIN clients c ON c.id = d.client_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.id DESC
    ");
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

api_error(405, 'Method not allowed');
