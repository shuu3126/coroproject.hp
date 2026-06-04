<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// ステータス更新: POST /api/invoices/{id}/status
$uri = $_SERVER['REQUEST_URI'] ?? '';
if ($method === 'POST' && $id && strpos($uri, '/status') !== false) {
    $body   = api_input();
    $status = $body['status'] ?? '';
    $allowed = ['issued', 'paid', 'receipt_issued'];
    if (!in_array($status, $allowed, true)) {
        api_error(400, 'status must be one of: ' . implode(', ', $allowed));
    }
    $extra = '';
    $params = [$status];
    if ($status === 'paid') {
        $extra = ', paid_at = NOW()';
    }
    $stmt = $pdo->prepare("UPDATE accounting_invoices SET status = ? {$extra} WHERE id = ?");
    $params[] = $id;
    $stmt->execute($params);
    if ($stmt->rowCount() === 0) { api_error(404, 'Invoice not found'); }
    api_ok(['id' => $id, 'status' => $status]);
}

// GET /api/invoices/{id}
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("
        SELECT i.*,
               t.name AS talent_name,
               c.name AS client_name
        FROM accounting_invoices i
        LEFT JOIN talents t ON t.id = i.talent_id
        LEFT JOIN clients c ON c.id = i.client_id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inv) { api_error(404, 'Invoice not found'); }

    $items = $pdo->prepare("SELECT description, amount_jpy FROM accounting_invoice_items WHERE invoice_id = ? ORDER BY sort_order");
    $items->execute([$id]);
    $inv['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
    api_ok($inv);
}

// GET /api/invoices?status=issued&talent_id=1
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[] = 'i.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['talent_id'])) {
        $where[] = 'i.talent_id = ?';
        $params[] = (int)$_GET['talent_id'];
    }
    if (!empty($_GET['division'])) {
        $where[] = 'i.division = ?';
        $params[] = $_GET['division'];
    }

    $sql = "
        SELECT i.id, i.invoice_no, i.status, i.division,
               i.close_year, i.close_month, i.amount_jpy, i.due_date, i.paid_at,
               t.name AS talent_name, c.name AS client_name
        FROM accounting_invoices i
        LEFT JOIN talents t ON t.id = i.talent_id
        LEFT JOIN clients c ON c.id = i.client_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY i.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

api_error(405, 'Method not allowed');
