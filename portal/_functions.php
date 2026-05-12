<?php
function portal_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function portal_flash_set($type, $message) {
    $_SESSION['portal_flash'] = ['type' => $type, 'message' => $message];
}

function portal_flash_get() {
    $flash = isset($_SESSION['portal_flash']) ? $_SESSION['portal_flash'] : null;
    unset($_SESSION['portal_flash']);
    return $flash;
}

function portal_redirect($path) {
    header('Location: ' . $path);
    exit;
}

function portal_csrf_token() {
    if (empty($_SESSION['portal_csrf'])) {
        $_SESSION['portal_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['portal_csrf'];
}

function portal_verify_csrf($token) {
    return isset($_SESSION['portal_csrf']) && hash_equals($_SESSION['portal_csrf'], (string)$token);
}

function portal_format_money($amount, $decimals = 2) {
    return number_format((float)$amount, $decimals);
}

function portal_revenue_status($status) {
    switch ((string)$status) {
        case 'pending':   return ['label' => '確認待ち', 'class' => 'badge-warning'];
        case 'rejected':  return ['label' => '要修正',   'class' => 'badge-danger'];
        case 'confirmed': return ['label' => '確定済',   'class' => 'badge-success'];
        default:          return ['label' => (string)$status, 'class' => 'badge-muted'];
    }
}

function portal_get_talent_info($pdo, $talent_id) {
    $stmt = $pdo->prepare('
        SELECT t.id, t.name, t.avatar,
               COALESCE(ts.invoice_name, t.name) AS invoice_name,
               COALESCE(ts.office_share_percent, 40) AS office_share_percent,
               ts.email, ts.bank_info
        FROM talents t
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
        WHERE t.id = ?
    ');
    $stmt->execute([$talent_id]);
    return $stmt->fetch();
}

function portal_fetch_revenue_history($pdo, $talent_id) {
    $hasStatus = _portal_table_has_column($pdo, 'accounting_revenues', 'status');
    $statusSel = $hasStatus ? 'COALESCE(r.status, "confirmed") AS status' : '"confirmed" AS status';

    $stmt = $pdo->prepare("
        SELECT r.id, r.year, r.month, r.currency,
               r.amount_streaming, r.amount_goods, r.amount_sponsor,
               r.evidence_path, r.portal_note, r.updated_at,
               {$statusSel},
               CASE WHEN im.id IS NOT NULL THEN 1 ELSE 0 END AS is_invoiced
        FROM accounting_revenues r
        LEFT JOIN accounting_invoiced_months im
            ON im.talent_id = r.talent_id AND im.year = r.year AND im.month = r.month
        WHERE r.talent_id = ?
        ORDER BY r.year DESC, r.month DESC
    ");
    $stmt->execute([$talent_id]);
    return $stmt->fetchAll();
}

function portal_fetch_invoices($pdo, $talent_id) {
    $stmt = $pdo->prepare('
        SELECT i.id, i.invoice_no, i.close_year, i.close_month,
               i.subject, i.amount_jpy, i.status, i.paid_at,
               i.invoice_pdf_path, ar.receipt_pdf_path
        FROM accounting_invoices i
        LEFT JOIN accounting_receipts ar ON ar.invoice_id = i.id
        WHERE i.talent_id = ? AND i.division = "production"
        ORDER BY i.close_year DESC, i.close_month DESC
    ');
    $stmt->execute([$talent_id]);
    return $stmt->fetchAll();
}

function portal_fetch_notices($pdo) {
    try {
        if (!_portal_table_has_column($pdo, 'talent_portal_notices', 'id')) {
            return [];
        }
        $stmt = $pdo->query('
            SELECT id, title, body, published_at, created_at
            FROM talent_portal_notices
            WHERE is_published = 1
            ORDER BY COALESCE(published_at, created_at) DESC
            LIMIT 10
        ');
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function portal_submit_revenue($pdo, $talent_id, $year, $month, $data, $evidence_path = null) {
    $year  = (int)$year;
    $month = (int)$month;

    if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
        return ['error' => '年月の値が不正です。'];
    }

    $allowed_currencies = ['JPY', 'USD'];
    $currency           = in_array($data['currency'] ?? '', $allowed_currencies) ? $data['currency'] : 'JPY';
    $amount_streaming   = max(0, (float)($data['amount_streaming'] ?? 0));
    $amount_goods       = max(0, (float)($data['amount_goods']     ?? 0));
    $amount_sponsor     = max(0, (float)($data['amount_sponsor']   ?? 0));
    $portal_note        = mb_substr(trim((string)($data['portal_note'] ?? '')), 0, 1000);

    if ($amount_streaming + $amount_goods + $amount_sponsor <= 0 && $evidence_path === null) {
        return ['error' => '金額を入力するかエビデンスをアップロードしてください。'];
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM accounting_invoiced_months WHERE talent_id = ? AND year = ? AND month = ?');
        $stmt->execute([$talent_id, $year, $month]);
        if ($stmt->fetch()) {
            return ['error' => 'この月分はすでに請求済みのため変更できません。'];
        }

        $stmt = $pdo->prepare('SELECT id, status FROM accounting_revenues WHERE talent_id = ? AND year = ? AND month = ?');
        $stmt->execute([$talent_id, $year, $month]);
        $existing = $stmt->fetch();

        if ($existing) {
            $existingStatus = $existing['status'] ?? 'confirmed';
            if ($existingStatus === 'confirmed') {
                return ['error' => 'この月分はすでに管理者が確定済みです。変更が必要な場合は運営にご連絡ください。'];
            }
            $sql    = 'UPDATE accounting_revenues SET currency = ?, amount_streaming = ?, amount_goods = ?, amount_sponsor = ?,
                       portal_note = ?, status = "pending", submitted_by = "talent", updated_at = NOW()';
            $params = [$currency, $amount_streaming, $amount_goods, $amount_sponsor, $portal_note];
            if ($evidence_path !== null) {
                $sql    .= ', evidence_path = ?';
                $params[] = $evidence_path;
            }
            $sql    .= ' WHERE id = ?';
            $params[] = $existing['id'];
            $pdo->prepare($sql)->execute($params);
        } else {
            $pdo->prepare('
                INSERT INTO accounting_revenues
                    (talent_id, year, month, currency, amount_streaming, amount_goods, amount_sponsor,
                     evidence_path, portal_note, status, submitted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "talent")
            ')->execute([
                $talent_id, $year, $month, $currency,
                $amount_streaming, $amount_goods, $amount_sponsor,
                $evidence_path, $portal_note,
            ]);
        }

        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'データの保存に失敗しました。時間をおいて再試行してください。'];
    }
}

function portal_upload_evidence($file, $talent_id, $year, $month) {
    if (!isset($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'ファイルのアップロードに失敗しました。'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        return ['error' => 'JPG・PNG・GIF・WebP・PDFのみアップロードできます。'];
    }

    if ((int)$file['size'] > 10 * 1024 * 1024) {
        return ['error' => 'ファイルサイズは10MB以下にしてください。'];
    }

    $extMap = [
        'image/jpeg' => 'jpg', 'image/png' => 'png',
        'image/gif'  => 'gif', 'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $ext = $extMap[$mime] ?? 'jpg';

    $ym  = date('Ym');
    $dir = PORTAL_UPLOAD_DIR . '/' . $ym;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/.htaccess', "Options -Indexes\n<Files ~ \"\\.(php|phtml|php3|php4|php5|phps|phar)$\">\n  Deny from all\n</Files>\n");
    }

    $filename = sprintf('%s_%04d%02d_%s.%s',
        preg_replace('/[^a-z0-9_\-]/', '', strtolower($talent_id)),
        (int)$year, (int)$month,
        bin2hex(random_bytes(6)),
        $ext
    );
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'ファイルの保存に失敗しました。'];
    }

    return ['path' => 'portal/uploads/' . $ym . '/' . $filename];
}

function _portal_table_has_column($pdo, $table, $column) {
    static $cache = [];
    $key = "{$table}.{$column}";
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}
