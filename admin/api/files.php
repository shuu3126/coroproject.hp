<?php
// GET /admin/api/files?kind=invoice&id=15
// GET /admin/api/files?kind=receipt&id=15
// APIキー認証でファイルをダウンロード
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { api_error(405, 'GET only'); }

$kind = trim($_GET['kind'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if (!$kind || $id <= 0) { api_error(400, 'kind and id are required'); }

$path = null;
switch ($kind) {
    case 'invoice':
        $stmt = $pdo->prepare('SELECT invoice_pdf_path FROM accounting_invoices WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'receipt':
        $stmt = $pdo->prepare('
            SELECT r.receipt_pdf_path
            FROM accounting_receipts r
            WHERE r.invoice_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    default:
        api_error(400, 'kind must be invoice or receipt');
}

if (!$path) { api_error(404, 'File path not found for this record'); }

$projectRoot = realpath(dirname(dirname(__DIR__)));
$fullPath    = realpath($projectRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));

if (!$fullPath || !is_file($fullPath)) { api_error(404, 'File not found on disk'); }

$prefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strpos($fullPath, $prefix) !== 0) { api_error(403, 'Forbidden'); }

$filename = basename($fullPath);
$mime     = 'application/pdf';
if (class_exists('finfo')) {
    $detected = (new finfo(FILEINFO_MIME_TYPE))->file($fullPath);
    if ($detected) { $mime = $detected; }
}

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: attachment; filename="' . addcslashes($filename, '"\\') . '"');
header('Cache-Control: private, no-store');
readfile($fullPath);
exit;
