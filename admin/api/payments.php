<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET /api/payments/schedule — 未払い請求書の支払い予定一覧
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT i.id, i.invoice_no, i.division, i.amount_jpy, i.due_date, i.status,
               i.payment_bank_info,
               t.name AS talent_name, t.id AS talent_id,
               c.name AS client_name
        FROM accounting_invoices i
        LEFT JOIN talents t ON t.id = i.talent_id
        LEFT JOIN clients c ON c.id = i.client_id
        WHERE i.status IN ('issued', 'paid')
        ORDER BY i.due_date ASC, i.id DESC
    ");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 今月・来月の支払い予定をグループ化
    $now   = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
    $today = $now->format('Y-m-d');

    $overdue  = [];
    $upcoming = [];
    $paid     = [];

    foreach ($invoices as $inv) {
        if ($inv['status'] === 'paid') {
            $paid[] = $inv;
        } elseif ($inv['due_date'] && $inv['due_date'] < $today) {
            $overdue[] = $inv;
        } else {
            $upcoming[] = $inv;
        }
    }

    api_ok([
        'today'    => $today,
        'overdue'  => $overdue,
        'upcoming' => $upcoming,
        'paid'     => $paid,
    ]);
}

api_error(405, 'Method not allowed');
