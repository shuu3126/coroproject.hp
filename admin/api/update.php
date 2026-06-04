<?php
// POSTボディでのみ受け付ける（GETパラメータはサーバーログに残るため禁止）
// 使用方法: POST /admin/api/update
// Body: {"table":"talents","id":"talent-2","fields":{"debut":"2025-05-05"}}
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { api_error(405, 'POST only'); }

$body   = api_input();
$table  = $body['table'] ?? '';
$id     = $body['id'] ?? '';
$fields = $body['fields'] ?? [];

$allowed_tables = [
    'talents'   => ['debut', 'status', 'kana', 'talent_group', 'is_published', 'sort_order', 'bio'],
    'clients'   => ['name', 'contact_person', 'email', 'phone', 'category', 'rank', 'memo'],
    'biz_deals' => ['title', 'status', 'description', 'budget', 'start_date', 'end_date', 'memo'],
];

if (!isset($allowed_tables[$table])) { api_error(400, 'invalid table'); }
if (empty($id) || !is_array($fields) || empty($fields)) { api_error(400, 'id and fields are required'); }

$allowed_fields = $allowed_tables[$table];
$sets = []; $params = [];

foreach ($fields as $f => $v) {
    if (!in_array($f, $allowed_fields, true)) { api_error(400, "field '{$f}' is not updatable"); }
    $sets[]   = "{$f} = ?";
    $params[] = $v === '' ? null : $v;
}

if (empty($sets)) { api_error(400, 'no valid fields'); }
$params[] = $id;

$stmt = $pdo->prepare("UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = ?");
$stmt->execute($params);

api_ok(['table' => $table, 'id' => $id, 'rows_affected' => $stmt->rowCount()]);
