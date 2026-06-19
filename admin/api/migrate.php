<?php
/**
 * 一時マイグレーション — mail_messages テーブルを utf8mb4 に変換
 * 実行後は削除すること
 */
require __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { api_error(405, 'POST only'); }

$results = [];

$sqls = [
    "ALTER TABLE mail_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
];

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['sql' => $sql, 'status' => 'OK'];
    } catch (\Throwable $e) {
        $results[] = ['sql' => $sql, 'status' => 'ERROR', 'message' => $e->getMessage()];
    }
}

api_ok(['migration' => 'mail_messages charset fix', 'results' => $results]);
