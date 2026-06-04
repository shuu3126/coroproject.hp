<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();
$uri    = $_SERVER['REQUEST_URI'] ?? '';

if ($method === 'POST' && $id && strpos($uri, '/status') !== false) {
    $body   = api_input();
    $status = $body['status'] ?? '';
    $allowed = ['相談中', '提案済み', '条件交渉中', '実施中', '完了', '不成立'];
    if (!in_array($status, $allowed, true)) { api_error(400, 'invalid status'); }
    $pdo->prepare("UPDATE biz_deals SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $id]);
    api_ok(['id' => $id, 'status' => $status]);
}

if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("SELECT d.*, c.name AS client_name FROM biz_deals d LEFT JOIN clients c ON c.id = d.client_id WHERE d.id = ?");
    $stmt->execute([$id]);
    $deal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$deal) { api_error(404, 'Deal not found'); }
    $cs = $pdo->prepare("SELECT dc.*, t.name AS talent_name FROM biz_deal_candidates dc LEFT JOIN talents t ON t.id = dc.talent_id WHERE dc.deal_id = ?");
    $cs->execute([$id]);
    $deal['candidates'] = $cs->fetchAll(PDO::FETCH_ASSOC);
    api_ok($deal);
}

if ($method === 'POST' && !$id) {
    $body = api_input();
    if (empty($body['title'])) { api_error(400, "field 'title' is required"); }
    $new_id = 'deal-' . uniqid();
    $pdo->prepare("INSERT INTO biz_deals (id, client_id, title, status, description, budget, start_date, end_date, memo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())")->execute([
        $new_id, $body['client_id'] ?? null, $body['title'], $body['status'] ?? '相談中',
        $body['description'] ?? null, $body['budget'] ?? null, $body['start_date'] ?? null,
        $body['end_date'] ?? null, $body['memo'] ?? null
    ]);
    api_ok(['id' => $new_id], 201);
}

if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['title', 'status', 'description', 'budget', 'start_date', 'end_date', 'memo'];
    $sets = []; $params = [];
    foreach ($allowed as $f) { if (array_key_exists($f, $body)) { $sets[] = "{$f} = ?"; $params[] = $body[$f]; } }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE biz_deals SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($params);
    api_ok(['id' => $id]);
}

if ($method === 'DELETE' && $id) {
    $stmt = $pdo->prepare("DELETE FROM biz_deals WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { api_error(404, 'Deal not found'); }
    api_ok(['deleted' => $id]);
}

if ($method === 'GET') {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['status']))    { $where[] = 'd.status = ?';    $params[] = $_GET['status']; }
    if (!empty($_GET['client_id'])) { $where[] = 'd.client_id = ?'; $params[] = $_GET['client_id']; }
    $stmt = $pdo->prepare("SELECT d.id, d.title, d.status, d.budget, d.start_date, d.end_date, c.name AS client_name, d.created_at FROM biz_deals d LEFT JOIN clients c ON c.id = d.client_id WHERE " . implode(' AND ', $where) . " ORDER BY d.created_at DESC");
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

api_error(405, 'Method not allowed');
