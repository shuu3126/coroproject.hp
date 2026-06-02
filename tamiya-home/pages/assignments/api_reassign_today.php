<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$craftsman_id = (int)($input['craftsman_id'] ?? 0);
$site_id      = (isset($input['site_id']) && $input['site_id'] !== null) ? (int)$input['site_id'] : null;
$date         = $input['date'] ?? '';

if (!$craftsman_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$yesterday = date('Y-m-d', strtotime('-1 day', strtotime($date)));
$tomorrow  = date('Y-m-d', strtotime('+1 day', strtotime($date)));

$stmt = $pdo->prepare("
    SELECT id, site_id, start_date, end_date
    FROM assignments
    WHERE craftsman_id = ?
      AND start_date <= ?
      AND (end_date IS NULL OR end_date >= ?)
");
$stmt->execute([$craftsman_id, $date, $date]);
$existing = $stmt->fetchAll();

foreach ($existing as $a) {
    $pdo->prepare("DELETE FROM assignments WHERE id = ?")->execute([$a['id']]);

    // 今日より前の期間を残す
    if ($a['start_date'] < $date) {
        $pdo->prepare("INSERT INTO assignments (craftsman_id, site_id, start_date, end_date) VALUES (?,?,?,?)")
            ->execute([$craftsman_id, $a['site_id'], $a['start_date'], $yesterday]);
    }

    // 今日より後の期間を残す
    if ($a['end_date'] === null || $a['end_date'] > $date) {
        $pdo->prepare("INSERT INTO assignments (craftsman_id, site_id, start_date, end_date) VALUES (?,?,?,?)")
            ->execute([$craftsman_id, $a['site_id'], $tomorrow, $a['end_date']]);
    }
}

// 今日の新しいアサインを登録
if ($site_id) {
    $pdo->prepare("INSERT INTO assignments (craftsman_id, site_id, start_date, end_date) VALUES (?,?,?,?)")
        ->execute([$craftsman_id, $site_id, $date, $date]);
}

echo json_encode(['success' => true]);
