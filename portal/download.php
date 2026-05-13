<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

function portal_download_fail($code = 404) {
    http_response_code($code);
    exit($code === 403 ? 'Forbidden' : 'Not Found');
}

function portal_download_resolve_path($path) {
    $path = trim(str_replace('\\', '/', (string)$path));
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || strpos($path, "\0") !== false) {
        portal_download_fail();
    }
    if (preg_match('#(^|/)\.\.(/|$)#', $path)) {
        portal_download_fail(403);
    }

    $projectRoot = realpath(dirname(__DIR__));
    $fullPath = realpath($projectRoot . DIRECTORY_SEPARATOR . ltrim($path, '/'));
    if (!$projectRoot || !$fullPath || !is_file($fullPath)) {
        portal_download_fail();
    }

    $projectRootPrefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($fullPath, $projectRootPrefix) !== 0) {
        portal_download_fail(403);
    }

    return $fullPath;
}

function portal_download_send_file($path, $disposition = 'inline') {
    $fullPath = portal_download_resolve_path($path);
    $filename = basename($fullPath);
    $mime = 'application/octet-stream';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($fullPath);
        if (is_string($detected) && $detected !== '') {
            $mime = $detected;
        }
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('X-Robots-Tag: noindex, nofollow', true);
    header('X-Content-Type-Options: nosniff', true);
    header('Cache-Control: private, no-store, max-age=0', true);
    header('Content-Type: ' . $mime, true);
    header('Content-Length: ' . filesize($fullPath), true);
    header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($filename, '"\\') . '"', true);
    readfile($fullPath);
    exit;
}

$talent = current_portal_talent();
$type = trim((string)($_GET['type'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
if ($type === '' || $id <= 0) {
    portal_download_fail();
}

$path = null;
$detail = '';
switch ($type) {
    case 'evidence':
        $stmt = $pdo->prepare('
            SELECT evidence_path
            FROM accounting_revenues
            WHERE id = ? AND talent_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id, $talent['talent_id']]);
        $path = $stmt->fetchColumn();
        $detail = '証憑ファイルを確認';
        break;

    case 'invoice':
        $stmt = $pdo->prepare('
            SELECT invoice_pdf_path
            FROM accounting_invoices
            WHERE id = ? AND talent_id = ? AND division = "production"
            LIMIT 1
        ');
        $stmt->execute([$id, $talent['talent_id']]);
        $path = $stmt->fetchColumn();
        $detail = '請求書PDFをダウンロード';
        break;

    case 'receipt':
        $stmt = $pdo->prepare('
            SELECT r.receipt_pdf_path
            FROM accounting_receipts r
            JOIN accounting_invoices i ON i.id = r.invoice_id
            WHERE i.id = ? AND i.talent_id = ? AND i.division = "production"
            LIMIT 1
        ');
        $stmt->execute([$id, $talent['talent_id']]);
        $path = $stmt->fetchColumn();
        $detail = '領収書PDFをダウンロード';
        break;

    default:
        portal_download_fail();
}

portal_write_activity($pdo, $talent['talent_id'], (int)$talent['id'], 'file_download', $detail);
portal_download_send_file($path, $type === 'evidence' ? 'inline' : 'attachment');
