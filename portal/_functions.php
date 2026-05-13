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
    $extra = [];
    foreach (portal_talent_profile_fields() as $field) {
        $extra[] = _portal_table_has_column($pdo, 'accounting_talent_settings', $field)
            ? 'ts.' . $field
            : 'NULL AS ' . $field;
    }

    $stmt = $pdo->prepare('
        SELECT t.id, t.name, t.avatar,
               COALESCE(ts.invoice_name, t.name) AS invoice_name,
               COALESCE(ts.office_share_percent, 40) AS office_share_percent,
               ts.email, ts.bank_info,
               ' . implode(",\n               ", $extra) . '
        FROM talents t
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
        WHERE t.id = ?
    ');
    $stmt->execute([$talent_id]);
    return $stmt->fetch();
}

function portal_talent_profile_fields() {
    return ['real_name', 'phone', 'postal_code', 'address', 'emergency_contact', 'profile_note'];
}

function portal_lines_from_json($json) {
    $decoded = json_decode((string)$json, true);
    if (!is_array($decoded)) {
        return '';
    }
    $lines = [];
    foreach ($decoded as $line) {
        if (is_array($line)) {
            continue;
        }
        $line = trim((string)$line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    return implode("\n", $lines);
}

function portal_parse_lines($text) {
    $text = trim((string)$text);
    if ($text === '') {
        return [];
    }
    $lines = preg_split('/\R/u', $text);
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function portal_parse_pipe_lines($text, $leftKey, $rightKey) {
    $rows = [];
    foreach (portal_parse_lines($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $rows[] = [$leftKey => mb_substr($parts[0], 0, 100), $rightKey => mb_substr($parts[1], 0, 500)];
        }
    }
    return $rows;
}

function portal_pipe_lines_from_rows($rows, $leftKey, $rightKey) {
    $lines = [];
    foreach ((array)$rows as $row) {
        $left = trim((string)($row[$leftKey] ?? ''));
        $right = trim((string)($row[$rightKey] ?? ''));
        if ($left !== '' || $right !== '') {
            $lines[] = $left . '|' . $right;
        }
    }
    return implode("\n", $lines);
}

function portal_profile_columns_ready($pdo) {
    foreach (portal_talent_profile_fields() as $field) {
        if (!_portal_table_has_column($pdo, 'accounting_talent_settings', $field)) {
            return false;
        }
    }
    return true;
}

function portal_profile_requests_ready($pdo) {
    return _portal_table_has_column($pdo, 'talent_profile_change_requests', 'id');
}

function portal_fetch_public_profile($pdo, $talent_id) {
    $stmt = $pdo->prepare('
        SELECT id, name, kana, talent_group, status, debut, avatar, bio,
               long_bio_json, tags_json
        FROM talents
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->execute([(string)$talent_id]);
    $profile = $stmt->fetch();
    if (!$profile) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT name, url FROM talent_platforms WHERE talent_id = ? ORDER BY id ASC');
    $stmt->execute([(string)$talent_id]);
    $platforms = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT label, url FROM talent_links WHERE talent_id = ? ORDER BY id ASC');
    $stmt->execute([(string)$talent_id]);
    $links = $stmt->fetchAll();

    $tags = json_decode((string)($profile['tags_json'] ?? '[]'), true);
    $profile['long_bio_text'] = portal_lines_from_json($profile['long_bio_json'] ?? '[]');
    $profile['tags_text'] = is_array($tags) ? implode(', ', array_filter(array_map('strval', $tags))) : '';
    $profile['platforms_text'] = portal_pipe_lines_from_rows($platforms, 'name', 'url');
    $profile['links_text'] = portal_pipe_lines_from_rows($links, 'label', 'url');

    return $profile;
}

function portal_fetch_latest_public_profile_request($pdo, $talent_id) {
    if (!portal_profile_requests_ready($pdo)) {
        return null;
    }
    try {
        $stmt = $pdo->prepare('
            SELECT *
            FROM talent_profile_change_requests
            WHERE talent_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ');
        $stmt->execute([(string)$talent_id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function portal_fetch_revenue_history($pdo, $talent_id) {
    $hasStatus     = _portal_table_has_column($pdo, 'accounting_revenues', 'status');
    $hasPortalNote = _portal_table_has_column($pdo, 'accounting_revenues', 'portal_note');
    $statusSel     = $hasStatus ? 'COALESCE(r.status, "confirmed") AS status' : '"confirmed" AS status';
    $noteSel       = $hasPortalNote ? 'r.portal_note' : 'NULL AS portal_note';

    $stmt = $pdo->prepare("
        SELECT r.id, r.year, r.month, r.currency,
               r.amount_streaming, r.amount_goods, r.amount_sponsor,
               r.evidence_path, {$noteSel}, r.updated_at,
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

function portal_update_talent_profile($pdo, $talent_id, $data) {
    if (!portal_profile_columns_ready($pdo)) {
        return ['error' => 'プロフィール用のDB更新が未実行です。運営に連絡してください。'];
    }

    $invoiceName      = mb_substr(trim((string)($data['invoice_name'] ?? '')), 0, 255);
    $email            = mb_substr(trim((string)($data['email'] ?? '')), 0, 255);
    $realName         = mb_substr(trim((string)($data['real_name'] ?? '')), 0, 255);
    $phone            = mb_substr(trim((string)($data['phone'] ?? '')), 0, 50);
    $postalCode       = mb_substr(trim((string)($data['postal_code'] ?? '')), 0, 20);
    $address          = mb_substr(trim((string)($data['address'] ?? '')), 0, 2000);
    $bankInfo         = mb_substr(trim((string)($data['bank_info'] ?? '')), 0, 2000);
    $emergencyContact = mb_substr(trim((string)($data['emergency_contact'] ?? '')), 0, 2000);
    $profileNote      = mb_substr(trim((string)($data['profile_note'] ?? '')), 0, 2000);

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'メールアドレスの形式が正しくありません。'];
    }

    try {
        $stmt = $pdo->prepare('SELECT name FROM talents WHERE id = ? LIMIT 1');
        $stmt->execute([(string)$talent_id]);
        $talentName = (string)$stmt->fetchColumn();
        if ($invoiceName === '') {
            $invoiceName = $realName !== '' ? $realName : $talentName;
        }

        $pdo->prepare('
            INSERT INTO accounting_talent_settings
                (talent_id, invoice_name, email, real_name, phone, postal_code, address, bank_info, emergency_contact, profile_note, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                invoice_name = VALUES(invoice_name),
                email = VALUES(email),
                real_name = VALUES(real_name),
                phone = VALUES(phone),
                postal_code = VALUES(postal_code),
                address = VALUES(address),
                bank_info = VALUES(bank_info),
                emergency_contact = VALUES(emergency_contact),
                profile_note = VALUES(profile_note),
                updated_at = NOW()
        ')->execute([
            (string)$talent_id,
            $invoiceName,
            $email,
            $realName,
            $phone,
            $postalCode,
            $address,
            $bankInfo,
            $emergencyContact,
            $profileNote,
        ]);

        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'プロフィールの保存に失敗しました。時間をおいて再試行してください。'];
    }
}

function portal_submit_public_profile_request($pdo, $talent_id, $data) {
    if (!portal_profile_requests_ready($pdo)) {
        return ['error' => 'HP掲載情報申請用のDB更新が未実行です。運営に連絡してください。'];
    }

    $name = mb_substr(trim((string)($data['name'] ?? '')), 0, 255);
    if ($name === '') {
        return ['error' => '活動名を入力してください。'];
    }

    $payload = [
        'name' => $name,
        'kana' => mb_substr(trim((string)($data['kana'] ?? '')), 0, 255),
        'talent_group' => mb_substr(trim((string)($data['talent_group'] ?? '')), 0, 255),
        'debut' => trim((string)($data['debut'] ?? '')),
        'avatar' => mb_substr(trim((string)($data['avatar'] ?? '')), 0, 500),
        'bio' => mb_substr(trim((string)($data['bio'] ?? '')), 0, 2000),
        'long_bio_text' => mb_substr(trim((string)($data['long_bio_text'] ?? '')), 0, 6000),
        'platforms_text' => mb_substr(trim((string)($data['platforms_text'] ?? '')), 0, 6000),
        'links_text' => mb_substr(trim((string)($data['links_text'] ?? '')), 0, 6000),
        'tags_text' => mb_substr(trim((string)($data['tags_text'] ?? '')), 0, 1000),
    ];

    if ($payload['debut'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload['debut'])) {
        return ['error' => 'デビュー日は正しい日付形式で入力してください。'];
    }

    try {
        $pdo->prepare('
            INSERT INTO talent_profile_change_requests
                (talent_id, payload_json, status, created_at, updated_at)
            VALUES
                (?, ?, "pending", NOW(), NOW())
        ')->execute([
            (string)$talent_id,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'HP掲載情報の申請に失敗しました。時間をおいて再試行してください。'];
    }
}

function portal_upload_public_profile_image($file, $talent_id) {
    if (!isset($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => '画像のアップロードに失敗しました。'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed, true)) {
        return ['error' => 'JPG・PNG・GIF・WebPのみアップロードできます。'];
    }

    if ((int)$file['size'] > 10 * 1024 * 1024) {
        return ['error' => '画像サイズは10MB以下にしてください。'];
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? 'jpg';
    $ym = date('Ym');
    $dir = PORTAL_UPLOAD_DIR . '/profile/' . $ym;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }

    $filename = sprintf(
        '%s_profile_%s.%s',
        preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$talent_id)),
        bin2hex(random_bytes(6)),
        $ext
    );
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => '画像の保存に失敗しました。'];
    }

    return ['path' => 'portal/uploads/profile/' . $ym . '/' . $filename];
}

function portal_change_password($pdo, $account_id, $talent_id, $current_password, $new_password, $new_password_confirm) {
    $current_password = (string)$current_password;
    $new_password = (string)$new_password;
    $new_password_confirm = (string)$new_password_confirm;

    if ($current_password === '' || $new_password === '' || $new_password_confirm === '') {
        return ['error' => '現在のパスワードと新しいパスワードを入力してください。'];
    }
    if ($new_password !== $new_password_confirm) {
        return ['error' => '新しいパスワードが確認用と一致しません。'];
    }
    if (strlen($new_password) < 8) {
        return ['error' => '新しいパスワードは8文字以上で設定してください。'];
    }
    if ($current_password === $new_password) {
        return ['error' => '現在と異なるパスワードを設定してください。'];
    }

    try {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM talent_portal_accounts WHERE id = ? AND talent_id = ? LIMIT 1');
        $stmt->execute([(int)$account_id, (string)$talent_id]);
        $account = $stmt->fetch();
        if (!$account || !password_verify($current_password, $account['password_hash'])) {
            return ['error' => '現在のパスワードが正しくありません。'];
        }

        $sql = 'UPDATE talent_portal_accounts SET password_hash = ?, login_attempts = 0, locked_until = NULL, updated_at = NOW()';
        if (_portal_table_has_column($pdo, 'talent_portal_accounts', 'password_changed_at')) {
            $sql .= ', password_changed_at = NOW()';
        }
        $sql .= ' WHERE id = ? AND talent_id = ?';

        $pdo->prepare($sql)->execute([
            password_hash($new_password, PASSWORD_DEFAULT),
            (int)$account_id,
            (string)$talent_id,
        ]);

        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'パスワード変更に失敗しました。時間をおいて再試行してください。'];
    }
}

function portal_submit_revenue($pdo, $talent_id, $year, $month, $data, $evidence_path = null) {
    if (!_portal_table_has_column($pdo, 'accounting_revenues', 'status')
        || !_portal_table_has_column($pdo, 'accounting_revenues', 'submitted_by')
        || !_portal_table_has_column($pdo, 'accounting_revenues', 'portal_note')) {
        return ['error' => 'タレントポータル用のDB更新が未実行です。運営に連絡してください。'];
    }

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
        file_put_contents($dir . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
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
