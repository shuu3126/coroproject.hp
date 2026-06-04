<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// GET /api/talents または GET /api/talents/{id}
if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("
            SELECT t.*,
                   s.office_share_percent, s.invoice_name, s.email AS accounting_email,
                   s.bank_info, s.is_active AS accounting_active
            FROM talents t
            LEFT JOIN accounting_talent_settings s ON s.talent_id = t.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $talent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$talent) { api_error(404, 'Talent not found'); }

        // プラットフォーム
        $ps = $pdo->prepare("SELECT name, url FROM talent_platforms WHERE talent_id = ? ORDER BY id");
        $ps->execute([$id]);
        $talent['platforms'] = $ps->fetchAll(PDO::FETCH_ASSOC);

        // リンク
        $ls = $pdo->prepare("SELECT label, url FROM talent_links WHERE talent_id = ? ORDER BY id");
        $ls->execute([$id]);
        $talent['links'] = $ls->fetchAll(PDO::FETCH_ASSOC);

        api_ok($talent);
    }

    // 一覧
    $stmt = $pdo->query("
        SELECT t.id, t.name, t.kana, t.talent_group, t.status, t.debut,
               t.avatar, t.is_published, t.sort_order,
               s.office_share_percent, s.email AS accounting_email
        FROM talents t
        LEFT JOIN accounting_talent_settings s ON s.talent_id = t.id
        ORDER BY t.sort_order, t.id
    ");
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// PATCH /api/talents/{id} — 部分更新
if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['debut', 'status', 'kana', 'talent_group', 'is_published', 'sort_order', 'bio'];
    $sets    = [];
    $params  = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $sets[]   = "{$field} = ?";
            $params[] = $body[$field];
        }
    }
    if (empty($sets)) { api_error(400, 'No updatable fields provided'); }
    $params[] = $id;
    $stmt = $pdo->prepare("UPDATE talents SET " . implode(', ', $sets) . " WHERE id = ?");
    $stmt->execute($params);
    if ($stmt->rowCount() === 0) { api_error(404, 'Talent not found'); }
    api_ok(['id' => $id, 'updated' => array_keys(array_intersect_key($body, array_flip($allowed)))]);
}

api_error(405, 'Method not allowed');
