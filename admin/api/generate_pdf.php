<?php
// POST /admin/api/generate_pdf
// Body: {"kind":"invoice","id":2}  または {"kind":"receipt","id":2}
// 既存の会計関数を使って請求書・領収書PDFを再生成し、DBのpathを更新する
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { api_error(405, 'POST only'); }

$body = api_input();
$kind = $body['kind'] ?? '';
$id   = (int)($body['id'] ?? 0);

if (!in_array($kind, ['invoice', 'receipt'], true)) { api_error(400, 'kind must be invoice or receipt'); }
if ($id <= 0) { api_error(400, 'id is required'); }

// 依存ファイルの読み込み
$adminDir = dirname(__DIR__);
require_once $adminDir . '/_pdf.php';
require_once $adminDir . '/_upload.php';
require_once $adminDir . '/_functions.php';
require_once $adminDir . '/_accounting.php';

try {
    $config = require $adminDir . '/config.php';

    if ($kind === 'invoice') {
        $path = accounting_regenerate_invoice_pdf($pdo, $config, $id);
        api_ok(['id' => $id, 'kind' => 'invoice', 'path' => $path]);
    } else {
        $path = accounting_generate_receipt_pdf($pdo, $config, $id, null);
        api_ok(['id' => $id, 'kind' => 'receipt', 'path' => $path]);
    }
} catch (RuntimeException $e) {
    api_error(500, $e->getMessage());
}
