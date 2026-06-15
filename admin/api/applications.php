<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// GET /applications/{id} — 詳細
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM talent_applications WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { api_error(404, 'Application not found'); }
    api_ok($row);
}

// GET /applications — 一覧（?status=でフィルタ、?limit=で件数制限）
if ($method === 'GET') {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['status'])) {
        $allowed = ['new','reviewing','passed','rejected','hold'];
        if (!in_array($_GET['status'], $allowed, true)) { api_error(400, 'Invalid status'); }
        $where[] = 'status = ?';
        $params[] = $_GET['status'];
    }
    $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
    $stmt = $pdo->prepare(
        "SELECT id, vtuber_name, gender, age, prefecture, email,"
        . " main_platform, youtube_followers, twitch_followers, twitter_followers, twitcasting_followers,"
        . " affiliation_type, work_style, status, created_at"
        . " FROM talent_applications"
        . " WHERE " . implode(' AND ', $where)
        . " ORDER BY created_at DESC"
        . " LIMIT " . (int)$limit
    );
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// PATCH /applications/{id} — status・admin_note 更新
if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['status', 'admin_note'];
    $sets = []; $params = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $body)) {
            if ($f === 'status') {
                $validStatuses = ['new','reviewing','passed','rejected','hold'];
                if (!in_array($body[$f], $validStatuses, true)) { api_error(400, 'Invalid status value'); }
            }
            $sets[]   = "{$f} = ?";
            $params[] = $body[$f];
        }
    }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE talent_applications SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")
        ->execute($params);
    api_ok(['id' => $id]);
}

api_error(405, 'Method not allowed');
