<?php
// シンプルなGETベース更新エンドポイント（サーバーがPOST/PATCHをブロックする場合の代替）
// 使用例: /admin/api/update?table=talents&id=talent-2&debut=2025-05-05
require __DIR__ . '/_bootstrap.php';

$table  = $_GET['table'] ?? '';
$id     = $_GET['id'] ?? '';

$allowed_tables = [
    'talents'  => ['debut', 'status', 'kana', 'talent_group', 'is_published', 'sort_order', 'bio'],
    'clients'  => ['name', 'contact_person', 'email', 'phone', 'category', 'rank', 'memo'],
    'biz_deals'=> ['title', 'status', 'description', 'budget', 'start_date', 'end_date', 'memo'],
];

if (!isset($allowed_tables[$table])) { api_error(400, 'invalid table'); }
if (empty($id)) { api_error(400, 'id is required'); }

$allowed_fields = $allowed_tables[$table];
$sets = []; $params = [];

foreach ($allowed_fields as $field) {
    if (isset($_GET[$field])) {
        $sets[]   = "{$field} = ?";
        $params[] = $_GET[$field] === '' ? null : $_GET[$field];
    }
}

if (empty($sets)) { api_error(400, 'no fields to update'); }

$params[] = $id;
$stmt = $pdo->prepare("UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = ?");
$stmt->execute($params);

api_ok(['table' => $table, 'id' => $id, 'updated_fields' => array_keys(array_intersect_key($_GET, array_flip($allowed_fields))), 'rows_affected' => $stmt->rowCount()]);
