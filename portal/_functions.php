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

function portal_chart_axis_scale($maxValue, $targetSteps = 4) {
    $maxValue = max(1.0, (float)$maxValue);
    $targetSteps = max(1, (int)$targetSteps);
    $roughStep = $maxValue / $targetSteps;
    $power = pow(10, floor(log10($roughStep)));
    $fraction = $roughStep / $power;

    if ($fraction <= 1.5) {
        $niceFraction = 1;
    } elseif ($fraction <= 3) {
        $niceFraction = 2;
    } elseif ($fraction <= 7) {
        $niceFraction = 5;
    } else {
        $niceFraction = 10;
    }

    $step = $niceFraction * $power;
    $axisMax = $step * ceil($maxValue / $step);
    return ['max' => max($axisMax, $step), 'step' => $step];
}

function portal_chart_stream_date_label($value, $fallback = '') {
    $value = trim((string)$value);
    if ($value === '') {
        return $fallback;
    }
    try {
        return (new DateTime($value))->format('n/j');
    } catch (Exception $e) {
        return $fallback;
    }
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

function portal_fetch_rejected_revenue_alerts($pdo, $talent_id, $limit = 5) {
    if (!_portal_table_has_column($pdo, 'accounting_revenues', 'status')) {
        return [];
    }
    $limit = max(1, min(50, (int)$limit));
    $noteSel = _portal_table_has_column($pdo, 'accounting_revenues', 'portal_note')
        ? 'portal_note'
        : 'NULL AS portal_note';
    try {
        $stmt = $pdo->prepare("
            SELECT id, year, month, {$noteSel}, updated_at
            FROM accounting_revenues
            WHERE talent_id = ? AND status = 'rejected'
            ORDER BY updated_at DESC, id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([(string)$talent_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function portal_notification_count($pdo, $talent_id) {
    $lastReadAt = portal_last_notifications_read_at($pdo, $talent_id);
    $count = 0;

    foreach (portal_fetch_notices($pdo) as $notice) {
        $noticeAt = (string)($notice['published_at'] ?: $notice['created_at'] ?: '');
        if ($lastReadAt === null || ($noticeAt !== '' && strtotime($noticeAt) > strtotime($lastReadAt))) {
            $count++;
        }
    }

    if (!_portal_table_has_column($pdo, 'accounting_revenues', 'status')) {
        return $count;
    }
    try {
        if ($lastReadAt === null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounting_revenues WHERE talent_id = ? AND status = 'rejected'");
            $stmt->execute([(string)$talent_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounting_revenues WHERE talent_id = ? AND status = 'rejected' AND updated_at > ?");
            $stmt->execute([(string)$talent_id, $lastReadAt]);
        }
        $count += (int)$stmt->fetchColumn();
    } catch (Exception $e) {
    }
    return $count;
}

function portal_last_notifications_read_at($pdo, $talent_id) {
    if (!portal_activity_ready($pdo)) {
        return null;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT created_at
            FROM talent_portal_activity_logs
            WHERE talent_id = ? AND action = 'notifications_viewed'
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([(string)$talent_id]);
        $value = $stmt->fetchColumn();
        return $value ? (string)$value : null;
    } catch (Exception $e) {
        return null;
    }
}

function portal_mark_notifications_read($pdo, $talent_id, $account_id = null) {
    portal_write_activity($pdo, $talent_id, $account_id, 'notifications_viewed', '通知を確認しました');
}

function portal_activity_ready($pdo) {
    return _portal_table_has_column($pdo, 'talent_portal_activity_logs', 'id');
}

function portal_write_activity($pdo, $talent_id, $account_id, $action, $detail = '') {
    if (!portal_activity_ready($pdo)) {
        return;
    }
    try {
        $pdo->prepare('
            INSERT INTO talent_portal_activity_logs
                (talent_id, account_id, action, detail, ip, user_agent, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW())
        ')->execute([
            (string)$talent_id,
            $account_id ? (int)$account_id : null,
            mb_substr((string)$action, 0, 80),
            mb_substr((string)$detail, 0, 2000),
            mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
            mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Exception $e) {
    }
}

function portal_fetch_activity_logs($pdo, $talent_id, $limit = 50) {
    if (!portal_activity_ready($pdo)) {
        return [];
    }
    $limit = max(1, min(200, (int)$limit));
    try {
        $stmt = $pdo->prepare("
            SELECT action, detail, ip, user_agent, created_at
            FROM talent_portal_activity_logs
            WHERE talent_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([(string)$talent_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function portal_twitch_required_columns() {
    return [
        'talent_twitch_csv_reports' => [
            'id', 'talent_id', 'report_year', 'report_month', 'original_filename', 'file_path',
            'row_count', 'total_streams', 'total_minutes', 'total_views', 'avg_viewers',
            'peak_viewers', 'followers_gained', 'chat_messages', 'estimated_revenue',
            'summary_json', 'status', 'created_at', 'updated_at',
        ],
        'talent_twitch_csv_rows' => [
            'id', 'report_id', 'stream_date', 'title', 'duration_minutes', 'views',
            'avg_viewers', 'peak_viewers', 'followers_gained', 'chat_messages',
            'estimated_revenue', 'raw_json', 'created_at',
        ],
    ];
}

function portal_twitch_ready($pdo) {
    foreach (portal_twitch_required_columns() as $table => $columns) {
        foreach ($columns as $column) {
            if (!_portal_table_has_column($pdo, $table, $column)) {
                return false;
            }
        }
    }
    return true;
}

function portal_upload_twitch_csv($file, $talent_id, $year, $month) {
    if (!isset($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'CSVファイルのアップロードに失敗しました。'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'アップロードされたCSVファイルを確認できませんでした。'];
    }
    if ((int)$file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'CSVファイルは5MB以下にしてください。'];
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        return ['error' => 'CSVファイル（.csv）のみアップロードできます。'];
    }
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];
        if (is_string($mime) && !in_array($mime, $allowedMimes, true)) {
            return ['error' => 'CSVファイルの中身を確認できませんでした。'];
        }
    }

    $ym = sprintf('%04d%02d', (int)$year, (int)$month);
    $dir = PORTAL_UPLOAD_DIR . '/twitch/' . $ym;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }

    $filename = sprintf(
        '%s_twitch_%s_%s.csv',
        preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$talent_id)),
        $ym,
        bin2hex(random_bytes(6))
    );
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'CSVファイルの保存に失敗しました。'];
    }

    return ['path' => 'portal/uploads/twitch/' . $ym . '/' . $filename, 'absolute_path' => $dest];
}

function portal_twitch_normalize_header($value) {
    $value = mb_strtolower(trim((string)$value));
    $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value);
    $value = str_replace(['　', '_', '-', '（', '）', '(', ')'], [' ', ' ', ' ', ' ', ' ', ' ', ' '], $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim($value);
}

function portal_twitch_pick($row, $headers, $candidates) {
    foreach ($candidates as $candidate) {
        $candidate = portal_twitch_normalize_header($candidate);
        foreach ($headers as $idx => $header) {
            if ($header === $candidate || strpos($header, $candidate) !== false) {
                return trim((string)($row[$idx] ?? ''));
            }
        }
    }
    return '';
}

function portal_twitch_number($value) {
    $value = trim((string)$value);
    if ($value === '') return 0.0;
    $value = str_replace([',', '￥', '¥', '$', '%'], '', $value);
    $value = preg_replace('/[^0-9.\-]/', '', $value);
    return $value === '' || $value === '-' ? 0.0 : (float)$value;
}

function portal_twitch_duration_minutes($value) {
    $value = trim((string)$value);
    if ($value === '') return 0.0;
    if (preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/', $value, $m)) {
        if (isset($m[3]) && $m[3] !== '') {
            return ((int)$m[1] * 60) + (int)$m[2] + ((int)$m[3] / 60);
        }
        return (int)$m[1] + ((int)$m[2] / 60);
    }
    $hours = 0.0;
    $minutes = 0.0;
    if (preg_match('/([0-9.]+)\s*(h|hour|hours|時間)/iu', $value, $m)) {
        $hours = (float)$m[1];
    }
    if (preg_match('/([0-9.]+)\s*(m|min|mins|minute|minutes|分)/iu', $value, $m)) {
        $minutes = (float)$m[1];
    }
    if ($hours > 0 || $minutes > 0) {
        return $hours * 60 + $minutes;
    }
    return portal_twitch_number($value);
}

function portal_twitch_date($value) {
    $value = trim((string)$value);
    if ($value === '') return null;
    try {
        return (new DateTime($value))->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

function portal_twitch_read_csv($absolutePath) {
    $content = file_get_contents($absolutePath);
    if ($content === false) {
        return ['error' => 'CSVファイルを読み込めませんでした。'];
    }
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'SJIS-win, UTF-8, ASCII');
    }

    $fp = fopen('php://temp', 'r+');
    fwrite($fp, $content);
    rewind($fp);

    $headerRow = fgetcsv($fp);
    if (!$headerRow) {
        fclose($fp);
        return ['error' => 'CSVにヘッダー行がありません。'];
    }

    $headers = array_map('portal_twitch_normalize_header', $headerRow);
    $rows = [];
    while (($row = fgetcsv($fp)) !== false) {
        if (!$row || count(array_filter($row, static function ($v) { return trim((string)$v) !== ''; })) === 0) {
            continue;
        }

        $raw = [];
        foreach ($headerRow as $idx => $label) {
            $raw[(string)$label] = (string)($row[$idx] ?? '');
        }

        $streamDate = portal_twitch_date(portal_twitch_pick($row, $headers, ['date', 'stream date', 'start time', 'started at', 'created at', '日付', '配信日', '開始日時']));
        $duration = portal_twitch_duration_minutes(portal_twitch_pick($row, $headers, ['duration', 'stream duration', 'minutes streamed', 'minutes', '配信時間', '時間', '分']));
        $views = (int)round(portal_twitch_number(portal_twitch_pick($row, $headers, ['views', 'live views', 'total views', '視聴回数', '再生数', 'ライブ視聴'])));
        $avgViewers = portal_twitch_number(portal_twitch_pick($row, $headers, ['average viewers', 'avg viewers', '平均視聴者', '平均視聴者数']));
        $peakViewers = (int)round(portal_twitch_number(portal_twitch_pick($row, $headers, ['peak viewers', 'max viewers', '最大視聴者', '最大視聴者数'])));
        $followers = (int)round(portal_twitch_number(portal_twitch_pick($row, $headers, ['followers gained', 'follows', 'new followers', 'フォロワー獲得', 'フォロワー増加'])));
        $chat = (int)round(portal_twitch_number(portal_twitch_pick($row, $headers, ['chat messages', 'messages', 'chat', 'チャット数', 'チャットメッセージ'])));
        $revenue = portal_twitch_number(portal_twitch_pick($row, $headers, ['revenue', 'estimated revenue', '収益', '推定収益']));
        $title = mb_substr(portal_twitch_pick($row, $headers, ['title', 'stream title', '配信タイトル', 'タイトル']), 0, 255);

        $rows[] = [
            'stream_date' => $streamDate,
            'title' => $title,
            'duration_minutes' => $duration,
            'views' => $views,
            'avg_viewers' => $avgViewers,
            'peak_viewers' => $peakViewers,
            'followers_gained' => $followers,
            'chat_messages' => $chat,
            'estimated_revenue' => $revenue,
            'raw_json' => json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }
    fclose($fp);

    if (!$rows) {
        return ['error' => '読み取れるデータ行がありません。'];
    }

    return ['rows' => $rows, 'headers' => $headerRow];
}

function portal_save_twitch_csv_report($pdo, $talent_id, $account_id, $year, $month, $file, $note = '') {
    if (!portal_twitch_ready($pdo)) {
        return ['error' => 'Twitch CSV用のDB更新が未実行です。運営に連絡してください。'];
    }
    $year = (int)$year;
    $month = (int)$month;
    if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
        return ['error' => '対象年月が不正です。'];
    }

    $upload = portal_upload_twitch_csv($file, $talent_id, $year, $month);
    if (isset($upload['error'])) {
        return $upload;
    }

    $parsed = portal_twitch_read_csv($upload['absolute_path']);
    if (isset($parsed['error'])) {
        return $parsed;
    }

    $rows = $parsed['rows'];
    $totalStreams = count($rows);
    $totalMinutes = 0.0;
    $totalViews = 0;
    $weightedAvg = 0.0;
    $weightedBase = 0.0;
    $peakViewers = 0;
    $followers = 0;
    $chat = 0;
    $revenue = 0.0;

    foreach ($rows as $row) {
        $minutes = (float)$row['duration_minutes'];
        $totalMinutes += $minutes;
        $totalViews += (int)$row['views'];
        if ((float)$row['avg_viewers'] > 0 && $minutes > 0) {
            $weightedAvg += (float)$row['avg_viewers'] * $minutes;
            $weightedBase += $minutes;
        }
        $peakViewers = max($peakViewers, (int)$row['peak_viewers']);
        $followers += (int)$row['followers_gained'];
        $chat += (int)$row['chat_messages'];
        $revenue += (float)$row['estimated_revenue'];
    }
    $avgViewers = $weightedBase > 0 ? ($weightedAvg / $weightedBase) : 0.0;

    $summary = [
        'headers' => $parsed['headers'],
        'note' => mb_substr(trim((string)$note), 0, 1000),
    ];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('
            INSERT INTO talent_twitch_csv_reports
                (talent_id, report_year, report_month, original_filename, file_path, row_count,
                 total_streams, total_minutes, total_views, avg_viewers, peak_viewers,
                 followers_gained, chat_messages, estimated_revenue, summary_json, status, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "submitted", NOW(), NOW())
        ');
        $stmt->execute([
            (string)$talent_id,
            $year,
            $month,
            mb_substr((string)($file['name'] ?? ''), 0, 255),
            $upload['path'],
            count($rows),
            $totalStreams,
            $totalMinutes,
            $totalViews,
            $avgViewers,
            $peakViewers,
            $followers,
            $chat,
            $revenue,
            json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $reportId = (int)$pdo->lastInsertId();

        $rowStmt = $pdo->prepare('
            INSERT INTO talent_twitch_csv_rows
                (report_id, stream_date, title, duration_minutes, views, avg_viewers, peak_viewers,
                 followers_gained, chat_messages, estimated_revenue, raw_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        foreach ($rows as $row) {
            $rowStmt->execute([
                $reportId,
                $row['stream_date'],
                $row['title'] !== '' ? $row['title'] : null,
                $row['duration_minutes'],
                $row['views'],
                $row['avg_viewers'],
                $row['peak_viewers'],
                $row['followers_gained'],
                $row['chat_messages'],
                $row['estimated_revenue'],
                $row['raw_json'],
            ]);
        }
        $pdo->commit();
        portal_write_activity($pdo, $talent_id, $account_id, 'twitch_csv_submit', sprintf('%04d年%02d月分のTwitch CSVを提出', $year, $month));
        return ['success' => true, 'id' => $reportId];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        portal_write_activity(
            $pdo,
            $talent_id,
            $account_id,
            'twitch_csv_error',
            'Twitch CSV保存失敗: ' . mb_substr($e->getMessage(), 0, 500)
        );
        return ['error' => 'Twitch CSVの保存に失敗しました。時間をおいて再試行してください。'];
    }
}

function portal_fetch_twitch_reports($pdo, $talent_id, $limit = 20) {
    if (!portal_twitch_ready($pdo)) {
        return [];
    }
    $limit = max(1, min(100, (int)$limit));
    $stmt = $pdo->prepare("
        SELECT *
        FROM talent_twitch_csv_reports
        WHERE talent_id = ?
        ORDER BY report_year DESC, report_month DESC, id DESC
        LIMIT {$limit}
    ");
    $stmt->execute([(string)$talent_id]);
    return $stmt->fetchAll();
}

function portal_fetch_twitch_report_rows($pdo, $reportId, $talent_id) {
    if (!portal_twitch_ready($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare('
        SELECT tr.*
        FROM talent_twitch_csv_rows tr
        JOIN talent_twitch_csv_reports r ON r.id = tr.report_id
        WHERE tr.report_id = ? AND r.talent_id = ?
        ORDER BY tr.stream_date ASC, tr.id ASC
    ');
    $stmt->execute([(int)$reportId, (string)$talent_id]);
    return $stmt->fetchAll();
}

function portal_find_previous_twitch_report($reports, $currentReport) {
    if (!$currentReport) {
        return null;
    }
    $dt = DateTime::createFromFormat('!Y-n-j', sprintf('%04d-%d-1', (int)$currentReport['report_year'], (int)$currentReport['report_month']));
    if (!$dt) {
        return null;
    }
    $dt->modify('-1 month');
    $prevYear = (int)$dt->format('Y');
    $prevMonth = (int)$dt->format('n');
    foreach ($reports as $report) {
        if ((int)$report['report_year'] === $prevYear && (int)$report['report_month'] === $prevMonth) {
            return $report;
        }
    }
    return null;
}

function portal_twitch_trend_badge($current, $previous) {
    if ($previous === null || $previous === '') {
        return '';
    }
    $currentValue = (float)$current;
    $previousValue = (float)$previous;
    $diff = $currentValue - $previousValue;
    if (abs($diff) < 0.000001) {
        return '<span class="portal-trend-badge portal-trend-flat" title="same as previous month" aria-label="same as previous month">&#8594;</span>';
    }
    $class = $diff > 0 ? 'portal-trend-up' : 'portal-trend-down';
    $arrow = $diff > 0 ? '&#8599;' : '&#8600;';
    $label = $diff > 0 ? 'up from previous month' : 'down from previous month';
    return '<span class="portal-trend-badge ' . $class . '" title="' . portal_h($label) . '" aria-label="' . portal_h($label) . '">' . $arrow . '</span>';
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
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'アップロードされた画像を確認できませんでした。'];
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
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'アップロードされたファイルを確認できませんでした。'];
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
