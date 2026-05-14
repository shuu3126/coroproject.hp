<?php
function cp_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cp_flash_set($type, $message) {
    $_SESSION['creative_portal_flash'] = ['type' => $type, 'message' => $message];
}

function cp_flash_get() {
    $flash = isset($_SESSION['creative_portal_flash']) ? $_SESSION['creative_portal_flash'] : null;
    unset($_SESSION['creative_portal_flash']);
    return $flash;
}

function cp_redirect($path) {
    header('Location: ' . $path);
    exit;
}

function cp_csrf_token() {
    if (empty($_SESSION['creative_portal_csrf'])) {
        $_SESSION['creative_portal_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['creative_portal_csrf'];
}

function cp_verify_csrf($token) {
    return isset($_SESSION['creative_portal_csrf']) && hash_equals($_SESSION['creative_portal_csrf'], (string)$token);
}

function cp_format_money($amount, $currency = 'JPY') {
    $currency = strtoupper((string)$currency);
    $prefix = $currency === 'JPY' ? '¥' : $currency . ' ';
    return $prefix . number_format((float)$amount, $currency === 'JPY' ? 0 : 2);
}

function cp_format_date($value) {
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTime($value))->format('Y-m-d');
    } catch (Exception $e) {
        return (string)$value;
    }
}

function cp_format_datetime($value) {
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTime($value))->format('Y-m-d H:i');
    } catch (Exception $e) {
        return (string)$value;
    }
}

function cp_table_has_column($pdo, $table, $column) {
    static $cache = [];
    $key = (string)$table . '.' . (string)$column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ');
        $stmt->execute([(string)$table, (string)$column]);
        $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function cp_table_ready($pdo, $table) {
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ');
        $stmt->execute([(string)$table]);
        $cache[$table] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

function cp_creator_profile_fields() {
    return [
        'real_name',
        'display_name',
        'email',
        'discord_name',
        'postal_code',
        'address',
        'bank_info',
        'invoice_registration_no',
        'withholding_type',
        'availability_status',
        'available_note',
    ];
}

function cp_project_portal_fields() {
    return [
        'portal_visible',
        'portal_summary',
        'portal_reference_url',
        'portal_terms_note',
        'portal_status_note',
    ];
}

function cp_columns_ready($pdo, $table, $fields) {
    foreach ((array)$fields as $field) {
        if (!cp_table_has_column($pdo, $table, $field)) {
            return false;
        }
    }
    return true;
}

function cp_get_creator_info($pdo, $creatorId) {
    $extra = [];
    foreach (cp_creator_profile_fields() as $field) {
        $extra[] = cp_table_has_column($pdo, 'cre_creators', $field)
            ? 'c.`' . str_replace('`', '``', $field) . '`'
            : 'NULL AS `' . str_replace('`', '``', $field) . '`';
    }

    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.skill_tags_json, c.rate_memo, c.contact, c.portfolio_url,
               c.type, c.memo, c.is_active, c.created_at, c.updated_at,
               ' . implode(",\n               ", $extra) . '
        FROM cre_creators c
        WHERE c.id = ?
        LIMIT 1
    ');
    $stmt->execute([(string)$creatorId]);
    return $stmt->fetch();
}

function cp_project_select_sql($pdo) {
    $fields = [];
    foreach (cp_project_portal_fields() as $field) {
        if (cp_table_has_column($pdo, 'cre_projects', $field)) {
            $fields[] = 'p.`' . str_replace('`', '``', $field) . '`';
        } else {
            if ($field === 'portal_visible') {
                $fields[] = '1 AS portal_visible';
            } else {
                $fields[] = 'NULL AS `' . str_replace('`', '``', $field) . '`';
            }
        }
    }

    return 'p.id, p.title, p.category, p.status, p.creator_id, p.deadline, p.deliverable_url,
            p.creator_amount, p.created_at, p.updated_at, ' . implode(', ', $fields);
}

function cp_portal_project_where_sql($pdo) {
    if (cp_table_has_column($pdo, 'cre_projects', 'portal_visible')) {
        return 'p.creator_id = ? AND p.portal_visible = 1';
    }
    return 'p.creator_id = ?';
}

function cp_fetch_projects($pdo, $creatorId, $limit = null, $status = '') {
    $params = [(string)$creatorId];
    $sql = 'SELECT ' . cp_project_select_sql($pdo) . '
            FROM cre_projects p
            WHERE ' . cp_portal_project_where_sql($pdo);

    $status = trim((string)$status);
    if ($status !== '') {
        $sql .= ' AND p.status = ?';
        $params[] = $status;
    }

    $sql .= ' ORDER BY
                CASE WHEN p.deadline IS NULL THEN 1 ELSE 0 END ASC,
                p.deadline ASC,
                p.updated_at DESC';

    if ($limit !== null) {
        $limit = max(1, min(100, (int)$limit));
        $sql .= ' LIMIT ' . $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function cp_fetch_project($pdo, $creatorId, $projectId) {
    $stmt = $pdo->prepare('
        SELECT ' . cp_project_select_sql($pdo) . '
        FROM cre_projects p
        WHERE ' . cp_portal_project_where_sql($pdo) . ' AND p.id = ?
        LIMIT 1
    ');
    $stmt->execute([(string)$creatorId, (string)$projectId]);
    return $stmt->fetch();
}

function cp_fetch_notices($pdo, $limit = 10) {
    if (!cp_table_ready($pdo, 'creative_portal_notices')) {
        return [];
    }
    $limit = max(1, min(50, (int)$limit));
    try {
        $stmt = $pdo->query('
            SELECT id, title, body, published_at, created_at
            FROM creative_portal_notices
            WHERE is_published = 1
            ORDER BY COALESCE(published_at, created_at) DESC, id DESC
            LIMIT ' . $limit
        );
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function cp_fetch_comments($pdo, $creatorId, $projectId, $limit = 100) {
    if (!cp_table_ready($pdo, 'creative_project_comments')) {
        return [];
    }
    $limit = max(1, min(200, (int)$limit));
    $stmt = $pdo->prepare('
        SELECT c.*
        FROM creative_project_comments c
        WHERE c.creator_id = ?
          AND c.project_id = ?
          AND c.is_internal = 0
        ORDER BY c.created_at ASC, c.id ASC
        LIMIT ' . $limit
    );
    $stmt->execute([(string)$creatorId, (string)$projectId]);
    return $stmt->fetchAll();
}

function cp_fetch_submissions($pdo, $creatorId, $projectId = null, $limit = 20) {
    if (!cp_table_ready($pdo, 'creative_project_submissions')) {
        return [];
    }
    $limit = max(1, min(200, (int)$limit));
    $params = [(string)$creatorId];
    $where = 's.creator_id = ?';
    if ($projectId !== null && $projectId !== '') {
        $where .= ' AND s.project_id = ?';
        $params[] = (string)$projectId;
    }
    $stmt = $pdo->prepare('
        SELECT s.*, p.title AS project_title
        FROM creative_project_submissions s
        LEFT JOIN cre_projects p ON p.id = s.project_id
        WHERE ' . $where . '
        ORDER BY s.created_at DESC, s.id DESC
        LIMIT ' . $limit
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function cp_fetch_invoices($pdo, $creatorId, $limit = 50) {
    if (!cp_table_ready($pdo, 'creative_project_invoices')) {
        return [];
    }
    $limit = max(1, min(200, (int)$limit));
    $stmt = $pdo->prepare('
        SELECT i.*, p.title AS project_title
        FROM creative_project_invoices i
        LEFT JOIN cre_projects p ON p.id = i.project_id
        WHERE i.creator_id = ?
        ORDER BY COALESCE(i.invoice_date, i.created_at) DESC, i.id DESC
        LIMIT ' . $limit
    );
    $stmt->execute([(string)$creatorId]);
    return $stmt->fetchAll();
}

function cp_fetch_statements($pdo, $creatorId, $limit = 50) {
    if (!cp_table_ready($pdo, 'creative_payment_statements')) {
        return [];
    }
    $limit = max(1, min(200, (int)$limit));
    $stmt = $pdo->prepare('
        SELECT s.*, p.title AS project_title
        FROM creative_payment_statements s
        LEFT JOIN cre_projects p ON p.id = s.project_id
        WHERE s.creator_id = ?
        ORDER BY COALESCE(s.scheduled_at, s.paid_at, s.created_at) DESC, s.id DESC
        LIMIT ' . $limit
    );
    $stmt->execute([(string)$creatorId]);
    return $stmt->fetchAll();
}

function cp_fetch_activity($pdo, $creatorId, $limit = 80) {
    if (!cp_table_ready($pdo, 'creative_portal_activity_logs')) {
        return [];
    }
    $limit = max(1, min(200, (int)$limit));
    $stmt = $pdo->prepare('
        SELECT action, detail, ip, user_agent, created_at
        FROM creative_portal_activity_logs
        WHERE creator_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT ' . $limit
    );
    $stmt->execute([(string)$creatorId]);
    return $stmt->fetchAll();
}

function cp_write_activity($pdo, $creatorId, $accountId, $action, $detail = '') {
    if (!cp_table_ready($pdo, 'creative_portal_activity_logs')) {
        return;
    }
    try {
        $pdo->prepare('
            INSERT INTO creative_portal_activity_logs
                (creator_id, account_id, action, detail, ip, user_agent, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW())
        ')->execute([
            (string)$creatorId,
            $accountId ? (int)$accountId : null,
            mb_substr((string)$action, 0, 80),
            mb_substr((string)$detail, 0, 2000),
            mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
            mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Exception $e) {
    }
}

function cp_project_status_class($status) {
    switch ((string)$status) {
        case '完了':
        case '納品':
            return 'success';
        case '確認中':
        case '修正依頼':
            return 'warning';
        case '制作中':
        case '企画・ラフ':
            return 'info';
        default:
            return 'muted';
    }
}

function cp_submission_status($status) {
    switch ((string)$status) {
        case 'approved':
            return ['label' => '承認済', 'class' => 'success'];
        case 'revision_requested':
            return ['label' => '修正依頼', 'class' => 'warning'];
        case 'rejected':
            return ['label' => '差し戻し', 'class' => 'danger'];
        case 'submitted':
        default:
            return ['label' => '提出済', 'class' => 'info'];
    }
}

function cp_invoice_status($status) {
    switch ((string)$status) {
        case 'approved':
            return ['label' => '確認済', 'class' => 'success'];
        case 'paid':
            return ['label' => '支払済', 'class' => 'success'];
        case 'receipt_received':
            return ['label' => '領収書受領', 'class' => 'success'];
        case 'rejected':
            return ['label' => '差し戻し', 'class' => 'danger'];
        case 'pending':
        default:
            return ['label' => '確認待ち', 'class' => 'warning'];
    }
}

function cp_statement_status($status) {
    switch ((string)$status) {
        case 'paid':
            return ['label' => '支払済', 'class' => 'success'];
        case 'cancelled':
            return ['label' => '取消', 'class' => 'danger'];
        case 'scheduled':
        default:
            return ['label' => '支払予定', 'class' => 'warning'];
    }
}

function cp_submission_type_label($type) {
    switch ((string)$type) {
        case 'rough': return 'ラフ';
        case 'draft': return '初稿';
        case 'revision': return '修正版';
        case 'final': return '納品';
        case 'other':
        default: return 'その他';
    }
}

function cp_ensure_upload_dir($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        file_put_contents($dir . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
}

function cp_upload_file($file, $creatorId, $bucket, $allowedExts, $maxBytes = 52428800) {
    if (!isset($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'ファイルのアップロードに失敗しました。'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'アップロードされたファイルを確認できませんでした。'];
    }
    if ((int)($file['size'] ?? 0) > $maxBytes) {
        return ['error' => 'ファイルサイズが大きすぎます。'];
    }

    $allowedExts = array_values(array_unique(array_map('strtolower', (array)$allowedExts)));
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExts, true)) {
        return ['error' => '許可されていないファイル形式です。'];
    }

    $ym = date('Ym');
    $bucket = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$bucket));
    $dir = CREATIVE_PORTAL_UPLOAD_DIR . '/' . $bucket . '/' . $ym;
    cp_ensure_upload_dir($dir);

    $safeCreator = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$creatorId));
    $filename = sprintf('%s_%s_%s.%s', $safeCreator ?: 'creator', $bucket, bin2hex(random_bytes(8)), $ext);
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'ファイルを保存できませんでした。'];
    }

    return [
        'path' => 'creative-portal/uploads/' . $bucket . '/' . $ym . '/' . $filename,
        'original_name' => mb_substr((string)($file['name'] ?? ''), 0, 255),
    ];
}

function cp_add_comment($pdo, $creatorId, $accountId, $projectId, $body) {
    if (!cp_table_ready($pdo, 'creative_project_comments')) {
        return ['error' => 'CreativeポータルのDB更新が未実行です。'];
    }
    $body = mb_substr(trim((string)$body), 0, 4000);
    if ($body === '') {
        return ['error' => 'コメントを入力してください。'];
    }
    $project = cp_fetch_project($pdo, $creatorId, $projectId);
    if (!$project) {
        return ['error' => '案件が見つかりません。'];
    }
    try {
        $pdo->prepare('
            INSERT INTO creative_project_comments
                (project_id, creator_id, sender_type, account_id, body, is_internal, created_at)
            VALUES
                (?, ?, "creator", ?, ?, 0, NOW())
        ')->execute([(string)$projectId, (string)$creatorId, $accountId ? (int)$accountId : null, $body]);
        cp_write_activity($pdo, $creatorId, $accountId, 'project_comment', $project['title'] . ' にコメント');
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'コメントの保存に失敗しました。'];
    }
}

function cp_submit_project_file($pdo, $creatorId, $accountId, $projectId, $data, $file) {
    if (!cp_table_ready($pdo, 'creative_project_submissions')) {
        return ['error' => 'CreativeポータルのDB更新が未実行です。'];
    }
    $project = cp_fetch_project($pdo, $creatorId, $projectId);
    if (!$project) {
        return ['error' => '案件が見つかりません。'];
    }

    $type = trim((string)($data['submission_type'] ?? 'draft'));
    if (!in_array($type, ['rough', 'draft', 'revision', 'final', 'other'], true)) {
        $type = 'draft';
    }
    $title = mb_substr(trim((string)($data['title'] ?? '')), 0, 255);
    $comment = mb_substr(trim((string)($data['comment'] ?? '')), 0, 3000);
    $externalUrl = mb_substr(trim((string)($data['external_url'] ?? '')), 0, 500);

    $upload = cp_upload_file($file, $creatorId, 'submissions', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'zip', 'psd', 'clip', 'txt']);
    if (is_array($upload) && isset($upload['error'])) {
        return $upload;
    }
    if (!$upload && $externalUrl === '' && $comment === '') {
        return ['error' => 'ファイル・URL・コメントのいずれかを入力してください。'];
    }

    try {
        $pdo->prepare('
            INSERT INTO creative_project_submissions
                (project_id, creator_id, account_id, submission_type, title, comment,
                 file_path, original_filename, external_url, status, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, "submitted", NOW(), NOW())
        ')->execute([
            (string)$projectId,
            (string)$creatorId,
            $accountId ? (int)$accountId : null,
            $type,
            $title !== '' ? $title : cp_submission_type_label($type),
            $comment,
            $upload ? $upload['path'] : null,
            $upload ? $upload['original_name'] : null,
            $externalUrl !== '' ? $externalUrl : null,
        ]);
        cp_write_activity($pdo, $creatorId, $accountId, 'project_submit', $project['title'] . ' に提出物を送信');
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => '提出物の保存に失敗しました。'];
    }
}

function cp_submit_invoice($pdo, $creatorId, $accountId, $data, $file) {
    if (!cp_table_ready($pdo, 'creative_project_invoices')) {
        return ['error' => 'CreativeポータルのDB更新が未実行です。'];
    }
    $projectId = trim((string)($data['project_id'] ?? ''));
    if ($projectId !== '' && !cp_fetch_project($pdo, $creatorId, $projectId)) {
        return ['error' => '案件が見つかりません。'];
    }
    $invoiceNo = mb_substr(trim((string)($data['invoice_no'] ?? '')), 0, 100);
    $invoiceDate = trim((string)($data['invoice_date'] ?? ''));
    $amount = max(0, (float)($data['amount'] ?? 0));
    $taxAmount = max(0, (float)($data['tax_amount'] ?? 0));
    $withholdingAmount = max(0, (float)($data['withholding_amount'] ?? 0));
    $totalAmount = max(0, (float)($data['total_amount'] ?? 0));
    if ($totalAmount <= 0) {
        $totalAmount = max(0, $amount + $taxAmount - $withholdingAmount);
    }
    if ($amount <= 0 && $totalAmount <= 0) {
        return ['error' => '請求金額を入力してください。'];
    }
    if ($invoiceDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoiceDate)) {
        return ['error' => '請求日は YYYY-MM-DD 形式で入力してください。'];
    }

    $upload = cp_upload_file($file, $creatorId, 'invoices', ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 20 * 1024 * 1024);
    if (is_array($upload) && isset($upload['error'])) {
        return $upload;
    }
    if (!$upload) {
        return ['error' => '請求書ファイルを添付してください。'];
    }

    try {
        $pdo->prepare('
            INSERT INTO creative_project_invoices
                (creator_id, project_id, account_id, invoice_no, invoice_date,
                 amount, tax_amount, withholding_amount, total_amount, currency,
                 invoice_file_path, invoice_original_name, status, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, "JPY", ?, ?, "pending", NOW(), NOW())
        ')->execute([
            (string)$creatorId,
            $projectId !== '' ? $projectId : null,
            $accountId ? (int)$accountId : null,
            $invoiceNo !== '' ? $invoiceNo : null,
            $invoiceDate !== '' ? $invoiceDate : null,
            $amount,
            $taxAmount,
            $withholdingAmount,
            $totalAmount,
            $upload['path'],
            $upload['original_name'],
        ]);
        cp_write_activity($pdo, $creatorId, $accountId, 'invoice_submit', '請求書を提出');
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => '請求書の保存に失敗しました。'];
    }
}

function cp_submit_receipt($pdo, $creatorId, $accountId, $invoiceId, $file) {
    if (!cp_table_ready($pdo, 'creative_project_invoices')) {
        return ['error' => 'CreativeポータルのDB更新が未実行です。'];
    }
    $stmt = $pdo->prepare('SELECT * FROM creative_project_invoices WHERE id = ? AND creator_id = ? LIMIT 1');
    $stmt->execute([(int)$invoiceId, (string)$creatorId]);
    $invoice = $stmt->fetch();
    if (!$invoice) {
        return ['error' => '請求書が見つかりません。'];
    }
    $upload = cp_upload_file($file, $creatorId, 'receipts', ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 20 * 1024 * 1024);
    if (is_array($upload) && isset($upload['error'])) {
        return $upload;
    }
    if (!$upload) {
        return ['error' => '領収書ファイルを添付してください。'];
    }
    try {
        $pdo->prepare('
            UPDATE creative_project_invoices
            SET receipt_file_path = ?, receipt_original_name = ?, status = "receipt_received", updated_at = NOW()
            WHERE id = ? AND creator_id = ?
        ')->execute([$upload['path'], $upload['original_name'], (int)$invoiceId, (string)$creatorId]);
        cp_write_activity($pdo, $creatorId, $accountId, 'receipt_submit', '領収書を提出');
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => '領収書の保存に失敗しました。'];
    }
}

function cp_update_profile($pdo, $creatorId, $data) {
    if (!cp_columns_ready($pdo, 'cre_creators', cp_creator_profile_fields())) {
        return ['error' => 'CreativeポータルのDB更新が未実行です。'];
    }

    $fields = [
        'real_name' => mb_substr(trim((string)($data['real_name'] ?? '')), 0, 255),
        'display_name' => mb_substr(trim((string)($data['display_name'] ?? '')), 0, 255),
        'email' => mb_substr(trim((string)($data['email'] ?? '')), 0, 191),
        'discord_name' => mb_substr(trim((string)($data['discord_name'] ?? '')), 0, 191),
        'postal_code' => mb_substr(trim((string)($data['postal_code'] ?? '')), 0, 20),
        'address' => mb_substr(trim((string)($data['address'] ?? '')), 0, 2000),
        'bank_info' => mb_substr(trim((string)($data['bank_info'] ?? '')), 0, 3000),
        'invoice_registration_no' => mb_substr(trim((string)($data['invoice_registration_no'] ?? '')), 0, 100),
        'withholding_type' => trim((string)($data['withholding_type'] ?? 'individual')),
        'availability_status' => trim((string)($data['availability_status'] ?? 'available')),
        'available_note' => mb_substr(trim((string)($data['available_note'] ?? '')), 0, 2000),
    ];

    if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'メールアドレスの形式が正しくありません。'];
    }
    if (!in_array($fields['withholding_type'], ['individual', 'corporation', 'none'], true)) {
        $fields['withholding_type'] = 'individual';
    }
    if (!in_array($fields['availability_status'], ['available', 'busy', 'paused'], true)) {
        $fields['availability_status'] = 'available';
    }

    try {
        $pdo->prepare('
            UPDATE cre_creators
            SET real_name = ?, display_name = ?, email = ?, discord_name = ?,
                postal_code = ?, address = ?, bank_info = ?, invoice_registration_no = ?,
                withholding_type = ?, availability_status = ?, available_note = ?
            WHERE id = ?
        ')->execute([
            $fields['real_name'],
            $fields['display_name'],
            $fields['email'],
            $fields['discord_name'],
            $fields['postal_code'],
            $fields['address'],
            $fields['bank_info'],
            $fields['invoice_registration_no'],
            $fields['withholding_type'],
            $fields['availability_status'],
            $fields['available_note'],
            (string)$creatorId,
        ]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'プロフィールの保存に失敗しました。'];
    }
}

function cp_change_password($pdo, $accountId, $creatorId, $currentPassword, $newPassword, $confirmPassword) {
    $currentPassword = (string)$currentPassword;
    $newPassword = (string)$newPassword;
    $confirmPassword = (string)$confirmPassword;
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        return ['error' => '現在のパスワードと新しいパスワードを入力してください。'];
    }
    if ($newPassword !== $confirmPassword) {
        return ['error' => '新しいパスワードが確認用と一致しません。'];
    }
    if (strlen($newPassword) < 8) {
        return ['error' => '新しいパスワードは8文字以上にしてください。'];
    }

    try {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM creative_portal_accounts WHERE id = ? AND creator_id = ? LIMIT 1');
        $stmt->execute([(int)$accountId, (string)$creatorId]);
        $account = $stmt->fetch();
        if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
            return ['error' => '現在のパスワードが正しくありません。'];
        }
        $pdo->prepare('
            UPDATE creative_portal_accounts
            SET password_hash = ?, login_attempts = 0, locked_until = NULL,
                password_changed_at = NOW(), updated_at = NOW()
            WHERE id = ? AND creator_id = ?
        ')->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$accountId, (string)$creatorId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'パスワード変更に失敗しました。'];
    }
}

function cp_notification_count($pdo, $creatorId) {
    $count = count(cp_fetch_notices($pdo, 20));
    if (cp_table_ready($pdo, 'creative_project_submissions')) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM creative_project_submissions WHERE creator_id = ? AND status IN ('revision_requested', 'rejected')");
            $stmt->execute([(string)$creatorId]);
            $count += (int)$stmt->fetchColumn();
        } catch (Exception $e) {
        }
    }
    if (cp_table_ready($pdo, 'creative_project_invoices')) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM creative_project_invoices WHERE creator_id = ? AND status = 'rejected'");
            $stmt->execute([(string)$creatorId]);
            $count += (int)$stmt->fetchColumn();
        } catch (Exception $e) {
        }
    }
    return $count;
}

function cp_start_page($title, $description = '') {
    global $page_title, $page_description, $creativePortalBase, $pdo;
    $page_title = $title;
    $page_description = $description;
    require __DIR__ . '/_header.php';
}

function cp_end_page() {
    require __DIR__ . '/_footer.php';
}
