<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

if ($method === 'GET') {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['talent_id'])) { $where[] = 'r.talent_id = ?'; $params[] = $_GET['talent_id']; }
    if (!empty($_GET['year']))      { $where[] = 'r.year = ?';      $params[] = (int)$_GET['year']; }
    if (!empty($_GET['month']))     { $where[] = 'r.month = ?';     $params[] = (int)$_GET['month']; }
    $stmt = $pdo->prepare("
        SELECT r.*, t.name AS talent_name,
               (r.amount_streaming + r.amount_goods + r.amount_sponsor) AS amount_total
        FROM accounting_revenues r
        LEFT JOIN talents t ON t.id = r.talent_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.year DESC, r.month DESC, r.talent_id
    ");
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($method === 'POST' && !$id) {
    $body = api_input();
    $required = ['talent_id', 'year', 'month', 'currency'];
    foreach ($required as $f) { if (empty($body[$f])) { api_error(400, "field '{$f}' is required"); } }
    $stmt = $pdo->prepare("
        INSERT INTO accounting_revenues (talent_id, year, month, currency, amount_streaming, amount_goods, amount_sponsor, memo, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())
        ON DUPLICATE KEY UPDATE amount_streaming=VALUES(amount_streaming), amount_goods=VALUES(amount_goods), amount_sponsor=VALUES(amount_sponsor), updated_at=NOW()
    ");
    $stmt->execute([$body['talent_id'], (int)$body['year'], (int)$body['month'], $body['currency'], $body['amount_streaming'] ?? 0, $body['amount_goods'] ?? 0, $body['amount_sponsor'] ?? 0, $body['memo'] ?? null]);
    api_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['amount_streaming', 'amount_goods', 'amount_sponsor', 'currency', 'memo', 'status'];
    $sets = []; $params = [];
    foreach ($allowed as $f) { if (array_key_exists($f, $body)) { $sets[] = "{$f} = ?"; $params[] = $body[$f]; } }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE accounting_revenues SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($params);
    api_ok(['id' => $id]);
}

api_error(405, 'Method not allowed');
