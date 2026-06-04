<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET /api/revenues?talent_id=1&year=2026&month=5
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['talent_id'])) {
        $where[] = 'r.talent_id = ?';
        $params[] = (int)$_GET['talent_id'];
    }
    if (!empty($_GET['year'])) {
        $where[] = 'r.year = ?';
        $params[] = (int)$_GET['year'];
    }
    if (!empty($_GET['month'])) {
        $where[] = 'r.month = ?';
        $params[] = (int)$_GET['month'];
    }

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

api_error(405, 'Method not allowed');
