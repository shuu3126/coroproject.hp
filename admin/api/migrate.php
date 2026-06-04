<?php
// 一回限りのDB migration endpoint
// 使用後は削除してください
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { api_error(405, 'POST only'); }

$results = [];

$migrations = [
    // accounting_invoices に due_date / payment_bank_info が欠けている場合に追加
    'invoices_due_date' => "ALTER TABLE accounting_invoices ADD COLUMN due_date DATE NULL AFTER note",
    'invoices_payment_bank_info' => "ALTER TABLE accounting_invoices ADD COLUMN payment_bank_info TEXT NULL AFTER due_date",
];

foreach ($migrations as $name => $sql) {
    try {
        $pdo->exec($sql);
        $results[$name] = 'applied';
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Duplicate column = already exists (MySQL error 1060)
        if (str_contains($msg, '1060') || str_contains($msg, 'Duplicate column')) {
            $results[$name] = 'already_exists';
        } else {
            $results[$name] = 'error: ' . $msg;
        }
    }
}

api_ok(['migrations' => $results]);
