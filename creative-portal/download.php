<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

function cp_download_fail($code = 404) {
    http_response_code($code);
    exit($code === 403 ? 'Forbidden' : 'Not Found');
}

function cp_download_resolve_path($path) {
    $path = trim(str_replace('\\', '/', (string)$path));
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || strpos($path, "\0") !== false) {
        cp_download_fail();
    }
    if (preg_match('#(^|/)\.\.(/|$)#', $path)) {
        cp_download_fail(403);
    }

    $projectRoot = realpath(dirname(__DIR__));
    $fullPath = realpath($projectRoot . DIRECTORY_SEPARATOR . ltrim($path, '/'));
    if (!$projectRoot || !$fullPath || !is_file($fullPath)) {
        cp_download_fail();
    }

    $projectRootPrefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($fullPath, $projectRootPrefix) !== 0) {
        cp_download_fail(403);
    }

    return $fullPath;
}

function cp_download_send_file($path, $disposition = 'attachment') {
    $fullPath = cp_download_resolve_path($path);
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

$creator = cp_current_creator();
$type = trim((string)($_GET['type'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
if ($type === '' || $id <= 0) {
    cp_download_fail();
}

$path = null;
$detail = '';
switch ($type) {
    case 'submission':
        $stmt = $pdo->prepare('
            SELECT file_path
            FROM creative_project_submissions
            WHERE id = ? AND creator_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id, $creator['creator_id']]);
        $path = $stmt->fetchColumn();
        $detail = '提出物ファイルをダウンロード';
        break;

    case 'invoice':
        $stmt = $pdo->prepare('
            SELECT invoice_file_path
            FROM creative_project_invoices
            WHERE id = ? AND creator_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id, $creator['creator_id']]);
        $path = $stmt->fetchColumn();
        $detail = '請求書ファイルをダウンロード';
        break;

    case 'invoice_receipt':
        $stmt = $pdo->prepare('
            SELECT receipt_file_path
            FROM creative_project_invoices
            WHERE id = ? AND creator_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id, $creator['creator_id']]);
        $path = $stmt->fetchColumn();
        $detail = '領収書ファイルをダウンロード';
        break;

    case 'statement':
        $stmt = $pdo->prepare('
            SELECT statement_file_path
            FROM creative_payment_statements
            WHERE id = ? AND creator_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id, $creator['creator_id']]);
        $path = $stmt->fetchColumn();
        $detail = '支払明細をダウンロード';
        break;

    case 'statement_receipt':
        $stmt = $pdo->prepare('
            SELECT receipt_file_path
            FROM creative_payment_statements
            WHERE id = ? AND creator_id = ?
            LIMIT 1
        ');
        $stmt->execute([$id, $creator['creator_id']]);
        $path = $stmt->fetchColumn();
        $detail = '支払明細の領収書をダウンロード';
        break;

    default:
        cp_download_fail();
}

cp_write_activity($pdo, $creator['creator_id'], (int)$creator['id'], 'file_download', $detail);
cp_download_send_file($path, 'attachment');
