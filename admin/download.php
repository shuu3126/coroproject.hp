<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();

function admin_download_fail($code = 404) {
    http_response_code($code);
    exit($code === 403 ? 'Forbidden' : 'Not Found');
}

function admin_download_resolve_path($path) {
    $path = trim(str_replace('\\', '/', (string)$path));
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || strpos($path, "\0") !== false) {
        admin_download_fail();
    }
    if (preg_match('#(^|/)\.\.(/|$)#', $path)) {
        admin_download_fail(403);
    }

    $projectRoot = realpath(dirname(__DIR__));
    $fullPath = realpath($projectRoot . DIRECTORY_SEPARATOR . ltrim($path, '/'));
    if (!$projectRoot || !$fullPath || !is_file($fullPath)) {
        admin_download_fail();
    }

    $projectRootPrefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($fullPath, $projectRootPrefix) !== 0) {
        admin_download_fail(403);
    }

    return $fullPath;
}

function admin_download_send_file($path, $disposition = 'inline') {
    $fullPath = admin_download_resolve_path($path);
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

$kind = trim((string)($_GET['kind'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
if ($kind === '' || $id <= 0) {
    admin_download_fail();
}

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
            JOIN accounting_invoices i ON i.id = r.invoice_id
            WHERE i.id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'revenue_evidence':
        $stmt = $pdo->prepare('SELECT evidence_path FROM accounting_revenues WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'journal_evidence':
        $stmt = $pdo->prepare('SELECT evidence_path FROM accounting_journal_entries WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'creative_submission':
        $stmt = $pdo->prepare('SELECT file_path FROM creative_project_submissions WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'creative_invoice':
        $stmt = $pdo->prepare('SELECT invoice_file_path FROM creative_project_invoices WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'creative_invoice_receipt':
        $stmt = $pdo->prepare('SELECT receipt_file_path FROM creative_project_invoices WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'creative_statement':
        $stmt = $pdo->prepare('SELECT statement_file_path FROM creative_payment_statements WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    case 'creative_statement_receipt':
        $stmt = $pdo->prepare('SELECT receipt_file_path FROM creative_payment_statements WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        break;

    default:
        admin_download_fail();
}

admin_download_send_file($path, 'inline');
