<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

// POST /api/journal（収支記録追加）
if ($method === 'POST') {
    $body = api_input();
    $required = ['date', 'kind', 'category', 'amount', 'description'];
    foreach ($required as $f) {
        if (empty($body[$f])) { api_error(400, "field '{$f}' is required"); }
    }
    if (!in_array($body['kind'], ['income', 'expense'], true)) {
        api_error(400, "kind must be 'income' or 'expense'");
    }
    $stmt = $pdo->prepare("
        INSERT INTO accounting_journal_entries
            (date, kind, category, amount, description, talent_id, source, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'manual', NOW())
    ");
    $stmt->execute([
        $body['date'],
        $body['kind'],
        $body['category'],
        (int)$body['amount'],
        $body['description'],
        !empty($body['talent_id']) ? (int)$body['talent_id'] : null,
    ]);
    api_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

// GET /api/journal?kind=income&year=2026&month=6
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];
    if (!empty($_GET['kind'])) {
        $where[] = 'j.kind = ?';
        $params[] = $_GET['kind'];
    }
    if (!empty($_GET['year'])) {
        $where[] = 'YEAR(j.date) = ?';
        $params[] = (int)$_GET['year'];
    }
    if (!empty($_GET['month'])) {
        $where[] = 'MONTH(j.date) = ?';
        $params[] = (int)$_GET['month'];
    }
    if (!empty($_GET['talent_id'])) {
        $where[] = 'j.talent_id = ?';
        $params[] = (int)$_GET['talent_id'];
    }
    $stmt = $pdo->prepare("
        SELECT j.*, t.name AS talent_name
        FROM accounting_journal_entries j
        LEFT JOIN talents t ON t.id = j.talent_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY j.date DESC, j.id DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = ['income' => 0, 'expense' => 0];
    foreach ($rows as $r) {
        $summary[$r['kind']] += (int)$r['amount'];
    }
    $summary['balance'] = $summary['income'] - $summary['expense'];

    api_ok(['summary' => $summary, 'entries' => $rows]);
}

api_error(405, 'Method not allowed');
