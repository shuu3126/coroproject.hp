<?php
function app_settings_defaults($config) {
    $setting = [
        'office_name'       => 'CORO PROJECT',
        'office_email'      => 'info@coroproject.jp',
        'public_social_x_url' => 'https://x.com/CoroProjectJP',
        'public_social_mail_address' => 'info@coroproject.jp',
        'office_bank_info'  => '',
        'office_invoice_note' => '',
        'fx_default_rate'   => '150',
        'fx_api_key'        => '',
        'pdf_font_path'     => $config['pdf']['font_path'],
        'pdf_stamp_path'    => $config['pdf']['stamp_path'],
        'smtp_host'         => 's221.myssl.jp',
        'smtp_port'         => '465',
        'smtp_secure'       => 'ssl',
        'smtp_user'         => '',
        'smtp_pass'         => '',
        'smtp_from_email'   => 'info@coroproject.jp',
        'smtp_from_name'    => 'CORO PROJECT',
        'mail_pop_host'     => 's221.myssl.jp',
        'mail_pop_port'     => '995',
        'mail_pop_encryption' => 'ssl',
        'mail_pop_user'     => '',
        'mail_pop_pass'     => '',
        'mail_sync_limit'   => '50',
    ];
}

function load_app_settings($pdo, $config) {
    $settings = app_settings_defaults($config);
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
    }
    return $settings;
}

function save_app_settings_map($pdo, $userId, $map) {
    foreach ($map as $key => $value) {
        $stmt = $pdo->prepare('
            INSERT INTO settings (setting_key, setting_value, updated_by, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = NOW()
        ');
        $stmt->execute([$key, $value, $userId ?: null]);
    }
}

function accounting_http_get_json($url) {
    $response = false;
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'CORO-PROJECT-ADMIN/1.0',
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
        }
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new RuntimeException(
                '為替APIへの接続に失敗しました。' .
                ($error !== '' ? ' ' . $error : '') .
                ($httpCode > 0 ? ' HTTP:' . $httpCode : '')
            );
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: CORO-PROJECT-ADMIN/1.0\r\n",
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('為替APIへの接続に失敗しました。');
        }
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        throw new RuntimeException('為替APIの応答を解析できませんでした。');
    }

    return $json;
}

function accounting_fetch_latest_usd_jpy_rate($apiKey) {
    $apiKey = trim((string)$apiKey);
    if ($apiKey === '') {
        throw new RuntimeException('為替APIキーが未設定です。設定画面で入力してください。');
    }

    $url = 'https://v6.exchangerate-api.com/v6/' . rawurlencode($apiKey) . '/latest/USD';
    $json = accounting_http_get_json($url);

    if (($json['result'] ?? '') !== 'success') {
        $errorType = (string)($json['error-type'] ?? 'unknown-error');
        throw new RuntimeException('為替APIエラー: ' . $errorType);
    }

    $rate = $json['conversion_rates']['JPY'] ?? null;
    if (!is_numeric($rate)) {
        throw new RuntimeException('JPYレートを取得できませんでした。');
    }

    return [
        'rate' => (float)$rate,
        'base_code' => (string)($json['base_code'] ?? 'USD'),
        'time_last_update_utc' => (string)($json['time_last_update_utc'] ?? ''),
        'time_next_update_utc' => (string)($json['time_next_update_utc'] ?? ''),
    ];
}

function accounting_share_percent_default() {
    return 40.0;
}

function accounting_threshold_yen() {
    return 5000.0;
}

function accounting_list_talents($pdo, $includeUnpublished = true) {
    $sql = 'SELECT t.id, t.name, t.kana, t.talent_group, t.status, t.debut, t.avatar, t.bio, t.long_bio_json, t.tags_json, t.sort_order, t.is_published,
                   COALESCE(ts.invoice_name, \'\') AS invoice_name
            FROM talents t
            LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id';
    if (!$includeUnpublished) {
        $sql .= ' WHERE t.is_published = 1';
    }
    $sql .= ' ORDER BY t.sort_order ASC, t.debut ASC, t.name ASC';
    return $pdo->query($sql)->fetchAll();
}

function accounting_find_talent($pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT * FROM talents WHERE id = ? LIMIT 1');
    $stmt->execute([(string)$talentId]);
    return $stmt->fetch();
}

function accounting_talent_profile_fields() {
    return ['real_name', 'phone', 'postal_code', 'address', 'emergency_contact', 'profile_note'];
}

function accounting_get_talent_setting($pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT * FROM accounting_talent_settings WHERE talent_id = ? LIMIT 1');
    $stmt->execute([(string)$talentId]);
    $row = $stmt->fetch();
    $talent = accounting_find_talent($pdo, $talentId);

    return [
        'talent_id' => (string)$talentId,
        'office_share_percent' => $row ? (float)$row['office_share_percent'] : accounting_share_percent_default(),
        'invoice_name' => $row && $row['invoice_name'] !== null && $row['invoice_name'] !== ''
            ? $row['invoice_name']
            : ($talent ? $talent['name'] : ''),
        'email' => $row ? (string)$row['email'] : '',
        'bank_info' => $row ? (string)$row['bank_info'] : '',
        'memo' => $row ? (string)$row['memo'] : '',
        'is_active' => $row ? (int)$row['is_active'] : 1,
    ];

    foreach (accounting_talent_profile_fields() as $field) {
        $setting[$field] = ($row && array_key_exists($field, $row)) ? (string)$row[$field] : '';
    }

    return $setting;
}

function accounting_upsert_talent_setting($pdo, $talentId, $data, $userId) {
    $columns = ['talent_id', 'office_share_percent', 'invoice_name', 'email', 'bank_info', 'memo', 'is_active', 'updated_by', 'updated_at'];
    $values  = ['?', '?', '?', '?', '?', '?', '?', '?', 'NOW()'];
    $updates = [
        'office_share_percent = VALUES(office_share_percent)',
        'invoice_name = VALUES(invoice_name)',
        'email = VALUES(email)',
        'bank_info = VALUES(bank_info)',
        'memo = VALUES(memo)',
        'is_active = VALUES(is_active)',
        'updated_by = VALUES(updated_by)',
        'updated_at = NOW()',
    ];
    $params = [
        (string)$talentId,
        (float)$data['office_share_percent'],
        (string)$data['invoice_name'],
        (string)$data['email'],
        (string)$data['bank_info'],
        (string)$data['memo'],
        !empty($data['is_active']) ? 1 : 0,
        $userId ?: null,
    ];

    foreach (accounting_talent_profile_fields() as $field) {
        if (!admin_table_has_column($pdo, 'accounting_talent_settings', $field)) {
            continue;
        }
        $columns[] = $field;
        $values[] = '?';
        $updates[] = "{$field} = VALUES({$field})";
        $params[] = (string)($data[$field] ?? '');
    }

    $sql = '
        INSERT INTO accounting_talent_settings
            (' . implode(', ', $columns) . ')
        VALUES
            (' . implode(', ', $values) . ')
        ON DUPLICATE KEY UPDATE
            ' . implode(",\n            ", $updates);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function accounting_next_invoice_no($pdo) {
    $row = $pdo->query('SELECT invoice_no FROM accounting_invoices ORDER BY id DESC LIMIT 1')->fetch();
    if (!$row) {
        return 'INV-000001';
    }
    $num = (int)preg_replace('/\D+/', '', (string)$row['invoice_no']);
    return sprintf('INV-%06d', $num + 1);
}

function accounting_period_label($months) {
    if (!$months) return '';

    usort($months, function ($a, $b) {
        if ((int)$a['year'] === (int)$b['year']) {
            return (int)$a['month'] <=> (int)$b['month'];
        }
        return (int)$a['year'] <=> (int)$b['year'];
    });

    $first = $months[0];
    $last = $months[count($months) - 1];

    if ((int)$first['year'] === (int)$last['year'] && (int)$first['month'] === (int)$last['month']) {
        return sprintf('%d年%02d月', $first['year'], $first['month']);
    }

    return sprintf(
        '%d年%02d月〜%d年%02d月',
        $first['year'],
        $first['month'],
        $last['year'],
        $last['month']
    );
}

function accounting_revenue_has_portal_status($pdo) {
    return admin_table_has_column($pdo, 'accounting_revenues', 'status');
}

function accounting_revenue_confirmed_where($pdo, $alias = 'r') {
    if (!accounting_revenue_has_portal_status($pdo)) {
        return '';
    }
    $prefix = $alias !== '' ? $alias . '.' : '';
    return " AND COALESCE({$prefix}status, 'confirmed') = 'confirmed'";
}

function accounting_get_uninvoiced_months_upto($pdo, $talentId, $year, $month) {
    $statusWhere = accounting_revenue_confirmed_where($pdo, 'r');
    $stmt = $pdo->prepare("
        SELECT r.year, r.month
        FROM accounting_revenues r
        LEFT JOIN accounting_invoiced_months im
          ON im.talent_id = r.talent_id
         AND im.year = r.year
         AND im.month = r.month
        WHERE r.talent_id = ?
          AND im.id IS NULL
          AND (r.year < ? OR (r.year = ? AND r.month <= ?))
          {$statusWhere}
        GROUP BY r.year, r.month
        ORDER BY r.year, r.month
    ");
    $stmt->execute([(string)$talentId, (int)$year, (int)$year, (int)$month]);
    return $stmt->fetchAll() ?: [];
}

function accounting_calc_office_share_jpy_for_month($pdo, $talentId, $year, $month, $fxRate) {
    $statusWhere = accounting_revenue_confirmed_where($pdo, '');
    $stmt = $pdo->prepare("
        SELECT currency, SUM(amount_streaming + amount_goods + amount_sponsor) AS total_amount
        FROM accounting_revenues
        WHERE talent_id = ? AND year = ? AND month = ?
        {$statusWhere}
        GROUP BY currency
    ");
    $stmt->execute([(string)$talentId, (int)$year, (int)$month]);
    $rows = $stmt->fetchAll();

    $totalJpy = 0.0;
    foreach ($rows as $row) {
        $currency = strtoupper((string)$row['currency']);
        $sum = (float)$row['total_amount'];
        $totalJpy += ($currency === 'USD') ? ($sum * (float)$fxRate) : $sum;
    }

    $setting = accounting_get_talent_setting($pdo, $talentId);
    return $totalJpy * ((float)$setting['office_share_percent'] / 100);
}

function accounting_fetch_all_revenues($pdo, $q = '') {
    $sql = '
        SELECT r.*, t.name, COALESCE(ts.invoice_name, t.name) AS invoice_name
        FROM accounting_revenues r
        JOIN talents t ON t.id = r.talent_id
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
    ';
    $params = [];

    if ($q !== '') {
        $sql .= ' WHERE t.name LIKE ? OR t.id LIKE ? OR r.memo LIKE ?';
        $params = ["%{$q}%", "%{$q}%", "%{$q}%"];
    }

    $sql .= ' ORDER BY r.year DESC, r.month DESC, t.name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function accounting_fetch_revenue($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM accounting_revenues WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function accounting_save_revenue($pdo, $userId, $id, $data) {
    if ($id) {
        $stmt = $pdo->prepare('
            UPDATE accounting_revenues
            SET talent_id=?, year=?, month=?, currency=?, amount_streaming=?, amount_goods=?, amount_sponsor=?, evidence_path=?, memo=?, updated_by=?, updated_at=NOW()
            WHERE id=?
        ');
        $stmt->execute([
            (string)$data['talent_id'],
            (int)$data['year'],
            (int)$data['month'],
            (string)$data['currency'],
            (float)$data['amount_streaming'],
            (float)$data['amount_goods'],
            (float)$data['amount_sponsor'],
            $data['evidence_path'],
            $data['memo'],
            $userId ?: null,
            (int)$id,
        ]);
        accounting_mark_revenue_admin_confirmed($pdo, (int)$id, $userId);
        return (int)$id;
    }

    $stmt = $pdo->prepare('
        INSERT INTO accounting_revenues
            (talent_id, year, month, currency, amount_streaming, amount_goods, amount_sponsor, evidence_path, memo, created_by, updated_by, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    $stmt->execute([
        (string)$data['talent_id'],
        (int)$data['year'],
        (int)$data['month'],
        (string)$data['currency'],
        (float)$data['amount_streaming'],
        (float)$data['amount_goods'],
        (float)$data['amount_sponsor'],
        $data['evidence_path'],
        $data['memo'],
        $userId ?: null,
        $userId ?: null,
    ]);
    $savedId = (int)$pdo->lastInsertId();
    accounting_mark_revenue_admin_confirmed($pdo, $savedId, $userId);
    return $savedId;
}

function accounting_delete_revenue($pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM accounting_revenues WHERE id = ?');
    $stmt->execute([(int)$id]);
}

function accounting_mark_revenue_admin_confirmed($pdo, $id, $userId = null) {
    if (!accounting_revenue_has_portal_status($pdo) || $id <= 0) {
        return;
    }

    $sets = ["status = 'confirmed'"];
    if (admin_table_has_column($pdo, 'accounting_revenues', 'submitted_by')) {
        $sets[] = "submitted_by = 'admin'";
    }
    $sets[] = 'updated_by = ?';
    $sets[] = 'updated_at = NOW()';

    $sql = 'UPDATE accounting_revenues SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute([$userId ?: null, (int)$id]);
}

function accounting_fetch_invoice($pdo, $id) {
    $stmt = $pdo->prepare('
        SELECT
            i.*,
            t.name AS talent_name,
            t.name AS talent_real_name,
            COALESCE(ts.invoice_name, t.name, c.name) AS invoice_name,
            ts.office_share_percent,
            c.name AS client_name
        FROM accounting_invoices i
        LEFT JOIN talents t ON t.id = i.talent_id
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = i.talent_id
        LEFT JOIN clients c ON c.id = i.client_id
        WHERE i.id = ?
        LIMIT 1
    ');
    $stmt->execute([(int)$id]);
    $invoice = $stmt->fetch();
    if (!$invoice) return null;

    $stmt = $pdo->prepare('SELECT * FROM accounting_invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([(int)$id]);
    $invoice['items'] = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT year, month FROM accounting_invoiced_months WHERE invoice_id = ? ORDER BY year ASC, month ASC');
    $stmt->execute([(int)$id]);
    $invoice['months'] = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM accounting_receipts WHERE invoice_id = ? LIMIT 1');
    $stmt->execute([(int)$id]);
    $invoice['receipt'] = $stmt->fetch();

    return $invoice;
}

function accounting_fetch_invoices($pdo, $division = '') {
    $sql = '
        SELECT i.*,
               t.name AS talent_name,
               COALESCE(c.name, t.name) AS party_name,
               r.receipt_pdf_path
        FROM accounting_invoices i
        LEFT JOIN talents t ON t.id = i.talent_id
        LEFT JOIN clients c ON c.id = i.client_id
        LEFT JOIN accounting_receipts r ON r.invoice_id = i.id
    ';
    $where = [];
    $params = [];
    if ($division !== '') {
        $where[] = 'i.division = ?';
        $params[] = $division;
    }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY i.created_at DESC, i.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function accounting_division_label($division) {
    switch ((string)$division) {
        case 'business':  return 'Business';
        case 'creative':  return 'Creative';
        default:          return 'Production';
    }
}

function accounting_create_client_invoice($pdo, $config, $userId, $clientId, $year, $month, $subject, $details, $note, $division, $dealId = null, $projectId = null, $talentId = null) {
    $amount = 0.0;
    $talentId = trim((string)$talentId);
    $talentId = $talentId !== '' ? $talentId : null;

    foreach ($details as $detail) {
        $amount += (float)$detail['amount'];
    }
    if ($amount <= 0) {
        throw new RuntimeException('明細の金額を入力してください。');
    }

    $invoiceNo = accounting_next_invoice_no($pdo);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO accounting_invoices
                (invoice_no, talent_id, client_id, close_year, close_month, subject, amount_jpy, fx_rate, status, note, division, deal_id, project_id, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $invoiceNo,
            $talentId,
            $clientId ?: null,
            (int)$year,
            (int)$month,
            $subject,
            $amount,
            'issued',
            $note,
            $division,
            $dealId ?: null,
            $projectId ?: null,
            $userId ?: null,
            $userId ?: null,
        ]);
        $invoiceId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO accounting_invoice_items (invoice_id, sort_order, description, amount_jpy, created_at) VALUES (?, ?, ?, ?, NOW())');
        foreach ($details as $idx => $item) {
            $itemStmt->execute([$invoiceId, $idx + 1, $item['desc'], $item['amount']]);
        }

        $stmt = $pdo->prepare('INSERT INTO accounting_journal_entries (`date`, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_by, updated_by, created_at, updated_at) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $categoryLabel = $division === 'business' ? '企業案件収入' : 'その他収入';
        $stmt->execute(['income', $categoryLabel, $amount, '請求書 ' . $invoiceNo . '｜' . $subject, $talentId, $invoiceId, 'invoice_auto', '', $userId ?: null, $userId ?: null]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    accounting_regenerate_invoice_pdf($pdo, $config, $invoiceId);
    return $invoiceId;
}

function accounting_insert_journal_for_invoice($pdo, $invoiceId, $talentId, $invoiceNo, $subject, $amount, $userId) {
    $stmt = $pdo->prepare('
        INSERT INTO accounting_journal_entries
            (`date`, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_by, updated_by, created_at, updated_at)
        VALUES
            (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    $stmt->execute([
        'income',
        '配信収益',
        (float)$amount,
        '請求書 ' . $invoiceNo . '｜' . $subject,
        (string)$talentId,
        (int)$invoiceId,
        'invoice_auto',
        '',
        $userId ?: null,
        $userId ?: null,
    ]);
}

function accounting_regenerate_invoice_pdf($pdo, $config, $invoiceId) {
    $invoice = accounting_fetch_invoice($pdo, $invoiceId);
    if (!$invoice) {
        throw new RuntimeException('請求書が見つかりません。');
    }

    $settings = load_app_settings($pdo, $config);
    $absDir = $config['uploads']['accounting_root'] . '/invoices';
    $relPrefix = $config['uploads']['accounting_prefix'] . '/invoices';
    ensure_dir_path($absDir);

    $filename = $invoice['invoice_no'] . '.pdf';
    $absolutePath = rtrim($absDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    $periodLabel = $invoice['months']
        ? accounting_period_label($invoice['months'])
        : sprintf('%d年%02d月', $invoice['close_year'], $invoice['close_month']);

    $payload = [
        'invoice_no' => $invoice['invoice_no'],
        'talent_real_name' => $invoice['invoice_name'],
        'talent_display_name' => !empty($invoice['talent_name']) ? $invoice['talent_name'] : $invoice['invoice_name'],
        'issue_date' => date('Y-m-d'),
        'subject' => $invoice['subject'],
        'amount_jpy' => $invoice['amount_jpy'],
        'items' => array_map(function ($row) {
            return [
                'description' => $row['description'],
                'amount_jpy' => $row['amount_jpy'],
            ];
        }, $invoice['items']),
        'note' => $invoice['note'],
        'period_label' => $periodLabel,
    ];

    $actualPath = pdf_make_invoice($config, $settings, $payload, $absolutePath);
    if ($actualPath && $actualPath !== $absolutePath) {
        $filename = basename($actualPath);
    }

    $relativePath = trim($relPrefix, '/\\') . '/' . $filename;
    $stmt = $pdo->prepare('UPDATE accounting_invoices SET invoice_pdf_path = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$relativePath, (int)$invoiceId]);

    return $relativePath;
}

function accounting_generate_receipt_pdf($pdo, $config, $invoiceId, $userId) {
    $invoice = accounting_fetch_invoice($pdo, $invoiceId);
    if (!$invoice) {
        throw new RuntimeException('請求書が見つかりません。');
    }

    $settings = load_app_settings($pdo, $config);
    $absDir = $config['uploads']['accounting_root'] . '/receipts';
    $relPrefix = $config['uploads']['accounting_prefix'] . '/receipts';
    ensure_dir_path($absDir);

    $filename = $invoice['invoice_no'] . '_receipt.pdf';
    $absolutePath = rtrim($absDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    $periodLabel = $invoice['months']
        ? accounting_period_label($invoice['months'])
        : sprintf('%d年%02d月', $invoice['close_year'], $invoice['close_month']);

    $description = '配信収益分配金として、' . $periodLabel . ' 分の請求に対する入金。';

    $payload = [
        'invoice_no' => $invoice['invoice_no'],
        'talent_real_name' => $invoice['invoice_name'],
        'talent_display_name' => !empty($invoice['talent_name']) ? $invoice['talent_name'] : $invoice['invoice_name'],
        'issue_date' => date('Y-m-d'),
        'amount_jpy' => $invoice['amount_jpy'],
        'fx_rate' => $invoice['fx_rate'],
        'description' => $description,
        'note' => $invoice['note'],
    ];

    $actualPath = pdf_make_receipt($config, $settings, $payload, $absolutePath);
    if ($actualPath && $actualPath !== $absolutePath) {
        $filename = basename($actualPath);
    }

    $relativePath = trim($relPrefix, '/\\') . '/' . $filename;
    $stmt = $pdo->prepare('
        INSERT INTO accounting_receipts (invoice_id, receipt_pdf_path, issued_at, issued_by)
        VALUES (?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE
            receipt_pdf_path = VALUES(receipt_pdf_path),
            issued_at = NOW(),
            issued_by = VALUES(issued_by)
    ');
    $stmt->execute([(int)$invoiceId, $relativePath, $userId ?: null]);

    $pdo->prepare("UPDATE accounting_invoices SET status='receipt_issued', updated_by=?, updated_at=NOW() WHERE id=?")
        ->execute([$userId ?: null, (int)$invoiceId]);

    return $relativePath;
}

function accounting_mark_invoice_paid($pdo, $invoiceId, $userId) {
    $stmt = $pdo->prepare("UPDATE accounting_invoices SET status='paid', paid_at=NOW(), updated_by=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$userId ?: null, (int)$invoiceId]);
}

function accounting_delete_invoice($pdo, $invoiceId) {
    $invoice = accounting_fetch_invoice($pdo, $invoiceId);
    if (!$invoice) return null;

    $pdo->prepare('DELETE FROM accounting_journal_entries WHERE invoice_id = ?')->execute([(int)$invoiceId]);
    $pdo->prepare('DELETE FROM accounting_receipts WHERE invoice_id = ?')->execute([(int)$invoiceId]);
    $pdo->prepare('DELETE FROM accounting_invoice_items WHERE invoice_id = ?')->execute([(int)$invoiceId]);
    $pdo->prepare('DELETE FROM accounting_invoiced_months WHERE invoice_id = ?')->execute([(int)$invoiceId]);
    $pdo->prepare('DELETE FROM accounting_invoices WHERE id = ?')->execute([(int)$invoiceId]);

    return $invoice;
}

function accounting_create_revenue_invoice($pdo, $config, $userId, $talentId, $year, $month, $fxRate, $note) {
    $months = accounting_get_uninvoiced_months_upto($pdo, $talentId, $year, $month);
    if (!$months) {
        throw new RuntimeException('指定した締め月までの未請求月がありません。');
    }

    $items = [];
    $amount = 0.0;
    foreach ($months as $m) {
        $share = accounting_calc_office_share_jpy_for_month($pdo, $talentId, $m['year'], $m['month'], $fxRate);
        if ($share <= 0) continue;

        $items[] = [
            'description' => sprintf('配信収益分配（%d年%02d月）', $m['year'], $m['month']),
            'amount_jpy' => $share,
        ];
        $amount += $share;
    }

    if ($amount < accounting_threshold_yen()) {
        throw new RuntimeException('請求額が5,000円未満のため、今回は請求書を発行できません。');
    }

    $invoiceNo = accounting_next_invoice_no($pdo);
    $periodLabel = accounting_period_label($months);
    $subject = '配信収益分配（' . $periodLabel . '）';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO accounting_invoices
                (invoice_no, talent_id, close_year, close_month, subject, amount_jpy, fx_rate, status, note, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $invoiceNo,
            (string)$talentId,
            (int)$year,
            (int)$month,
            $subject,
            $amount,
            (float)$fxRate,
            'issued',
            $note,
            $userId ?: null,
            $userId ?: null,
        ]);

        $invoiceId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('
            INSERT INTO accounting_invoice_items (invoice_id, sort_order, description, amount_jpy, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        foreach ($items as $idx => $item) {
            $itemStmt->execute([$invoiceId, $idx + 1, $item['description'], $item['amount_jpy']]);
        }

        $markStmt = $pdo->prepare('
            INSERT INTO accounting_invoiced_months (invoice_id, talent_id, year, month, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        foreach ($months as $m) {
            $markStmt->execute([$invoiceId, (string)$talentId, (int)$m['year'], (int)$m['month']]);
        }

        accounting_insert_journal_for_invoice($pdo, $invoiceId, $talentId, $invoiceNo, $subject, $amount, $userId);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    accounting_regenerate_invoice_pdf($pdo, $config, $invoiceId);
    return $invoiceId;
}

function accounting_create_manual_invoice($pdo, $config, $userId, $talentId, $year, $month, $subject, $details, $note) {
    $amount = 0.0;
    foreach ($details as $detail) {
        $amount += (float)$detail['amount'];
    }

    if ($amount <= 0) {
        throw new RuntimeException('手入力請求の明細を入力してください。');
    }

    $invoiceNo = accounting_next_invoice_no($pdo);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO accounting_invoices
                (invoice_no, talent_id, close_year, close_month, subject, amount_jpy, fx_rate, status, note, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $invoiceNo,
            (string)$talentId,
            (int)$year,
            (int)$month,
            $subject,
            $amount,
            'issued',
            $note,
            $userId ?: null,
            $userId ?: null,
        ]);

        $invoiceId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('
            INSERT INTO accounting_invoice_items (invoice_id, sort_order, description, amount_jpy, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        foreach ($details as $idx => $item) {
            $itemStmt->execute([$invoiceId, $idx + 1, $item['desc'], $item['amount']]);
        }

        accounting_insert_journal_for_invoice($pdo, $invoiceId, $talentId, $invoiceNo, $subject, $amount, $userId);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    accounting_regenerate_invoice_pdf($pdo, $config, $invoiceId);
    return $invoiceId;
}

function accounting_fetch_journal_rows($pdo, $filters = []) {
    $sql = '
        SELECT j.*, COALESCE(ts.invoice_name, t.name) AS talent_name
        FROM accounting_journal_entries j
        LEFT JOIN talents t ON t.id = j.talent_id
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
    ';
    $conds = [];
    $params = [];

    if (!empty($filters['date_from'])) {
        $conds[] = 'j.date >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $conds[] = 'j.date <= ?';
        $params[] = $filters['date_to'];
    }
    if (!empty($filters['kind'])) {
        $conds[] = 'j.kind = ?';
        $params[] = $filters['kind'];
    }
    if (!empty($filters['category'])) {
        $conds[] = 'j.category = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['talent_id'])) {
        $conds[] = 'j.talent_id = ?';
        $params[] = $filters['talent_id'];
    }

    if ($conds) {
        $sql .= ' WHERE ' . implode(' AND ', $conds);
    }

    $sql .= ' ORDER BY j.date DESC, j.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function accounting_fetch_journal($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM accounting_journal_entries WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function accounting_save_journal($pdo, $userId, $id, $data) {
    if ($id) {
        $stmt = $pdo->prepare('
            UPDATE accounting_journal_entries
            SET date=?, kind=?, category=?, amount=?, description=?, talent_id=?, evidence_path=?, updated_by=?, updated_at=NOW()
            WHERE id=?
        ');
        $stmt->execute([
            $data['date'],
            $data['kind'],
            $data['category'],
            $data['amount'],
            $data['description'],
            $data['talent_id'] ?: null,
            $data['evidence_path'],
            $userId ?: null,
            (int)$id
        ]);
        return (int)$id;
    }

    $stmt = $pdo->prepare('
        INSERT INTO accounting_journal_entries
            (date, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_by, updated_by, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW(), NOW())
    ');
    $stmt->execute([
        $data['date'],
        $data['kind'],
        $data['category'],
        $data['amount'],
        $data['description'],
        $data['talent_id'] ?: null,
        'manual',
        $data['evidence_path'],
        $userId ?: null,
        $userId ?: null
    ]);
    return (int)$pdo->lastInsertId();
}

function accounting_fetch_all_revenues_with_status($pdo, $q = '') {
    $sql = '
        SELECT r.*,
               t.name,
               COALESCE(ts.invoice_name, t.name) AS invoice_name,
               CASE WHEN im.id IS NOT NULL THEN 1 ELSE 0 END AS is_invoiced
        FROM accounting_revenues r
        JOIN talents t ON t.id = r.talent_id
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
        LEFT JOIN accounting_invoiced_months im
            ON im.talent_id = r.talent_id
            AND im.year = r.year
            AND im.month = r.month
    ';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE (t.name LIKE ? OR t.id LIKE ? OR r.memo LIKE ?)';
        $params = ["%{$q}%", "%{$q}%", "%{$q}%"];
    }
    $sql .= ' ORDER BY r.year DESC, r.month DESC, t.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function accounting_get_pending_summaries($pdo, $fxRate) {
    $statusWhere = accounting_revenue_confirmed_where($pdo, 'r');
    $stmt = $pdo->query("
        SELECT r.talent_id, r.year, r.month, r.currency,
               (r.amount_streaming + r.amount_goods + r.amount_sponsor) AS month_total,
               COALESCE(ts.invoice_name, t.name) AS invoice_name,
               COALESCE(ts.office_share_percent, 40) AS share_percent
        FROM accounting_revenues r
        JOIN talents t ON t.id = r.talent_id
        LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
        LEFT JOIN accounting_invoiced_months im
            ON im.talent_id = r.talent_id
            AND im.year = r.year
            AND im.month = r.month
        WHERE im.id IS NULL
          {$statusWhere}
        ORDER BY r.talent_id, r.year, r.month
    ");
    $rows = $stmt->fetchAll();

    $byTalent = [];
    foreach ($rows as $row) {
        $tid = (string)$row['talent_id'];
        if (!isset($byTalent[$tid])) {
            $byTalent[$tid] = [
                'talent_id'     => $tid,
                'invoice_name'  => (string)$row['invoice_name'],
                'share_percent' => (float)$row['share_percent'],
                'months'        => [],
                'estimated_jpy' => 0.0,
            ];
        }
        $monthLabel = sprintf('%04d-%02d', $row['year'], $row['month']);
        if (!in_array($monthLabel, $byTalent[$tid]['months'], true)) {
            $byTalent[$tid]['months'][] = $monthLabel;
        }
        $amountJpy = strtoupper((string)$row['currency']) === 'USD'
            ? (float)$row['month_total'] * (float)$fxRate
            : (float)$row['month_total'];
        $byTalent[$tid]['estimated_jpy'] += $amountJpy * ($byTalent[$tid]['share_percent'] / 100.0);
    }

    return array_values($byTalent);
}

// ── Portal revenue functions ──────────────────────────────────

function accounting_portal_pending_count($pdo) {
    if (!admin_table_has_column($pdo, 'accounting_revenues', 'status')) return 0;
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM accounting_revenues WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) { return 0; }
}

function accounting_portal_confirm_revenue($pdo, $id, $userId) {
    if (!admin_table_has_column($pdo, 'accounting_revenues', 'status')) return false;
    try {
        $pdo->prepare("UPDATE accounting_revenues SET status = 'confirmed', updated_by = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$userId, (int)$id]);
        return true;
    } catch (Exception $e) { return false; }
}

function accounting_portal_reject_revenue($pdo, $id, $userId, $note = '') {
    if (!admin_table_has_column($pdo, 'accounting_revenues', 'status')) return false;
    try {
        $stmt = $pdo->prepare('
            SELECT r.id, r.talent_id, r.year, r.month, COALESCE(ts.invoice_name, t.name) AS invoice_name
            FROM accounting_revenues r
            JOIN talents t ON t.id = r.talent_id
            LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
            WHERE r.id = ?
            LIMIT 1
        ');
        $stmt->execute([(int)$id]);
        $revenue = $stmt->fetch();
        if (!$revenue) {
            return false;
        }

        $cleanNote = mb_substr(trim((string)$note), 0, 1000);
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }
        $pdo->prepare("UPDATE accounting_revenues SET status = 'rejected', portal_note = ?, updated_by = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$cleanNote, $userId, (int)$id]);

        if (admin_table_has_column($pdo, 'talent_portal_activity_logs', 'id')) {
            $accountId = null;
            if (admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) {
                $accountStmt = $pdo->prepare('SELECT id FROM talent_portal_accounts WHERE talent_id = ? LIMIT 1');
                $accountStmt->execute([(string)$revenue['talent_id']]);
                $accountId = $accountStmt->fetchColumn() ?: null;
            }
            $detail = sprintf(
                '%04d年%02d月分の収益報告が却下されました。修正して再送信してください。',
                (int)$revenue['year'],
                (int)$revenue['month']
            );
            if ($cleanNote !== '') {
                $detail .= ' 理由: ' . $cleanNote;
            }
            $ip = mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
            $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            if (admin_table_has_column($pdo, 'talent_portal_activity_logs', 'account_id')) {
                $pdo->prepare('
                    INSERT INTO talent_portal_activity_logs
                        (talent_id, account_id, action, detail, ip, user_agent, created_at)
                    VALUES
                        (?, ?, "revenue_rejected", ?, ?, ?, NOW())
                ')->execute([(string)$revenue['talent_id'], $accountId ? (int)$accountId : null, mb_substr($detail, 0, 2000), $ip, $ua]);
            } else {
                $pdo->prepare('
                    INSERT INTO talent_portal_activity_logs
                        (talent_id, action, detail, ip, user_agent, created_at)
                    VALUES
                        (?, "revenue_rejected", ?, ?, ?, NOW())
                ')->execute([(string)$revenue['talent_id'], mb_substr($detail, 0, 2000), $ip, $ua]);
            }
        }

        if ($startedTransaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if (isset($startedTransaction) && $startedTransaction && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}

function accounting_portal_fetch_pending_list($pdo) {
    if (!admin_table_has_column($pdo, 'accounting_revenues', 'status')) return [];
    try {
        $stmt = $pdo->query("
            SELECT r.id, r.talent_id, r.year, r.month, r.currency,
                   r.amount_streaming, r.amount_goods, r.amount_sponsor,
                   r.evidence_path, r.portal_note, r.status, r.submitted_by, r.updated_at,
                   COALESCE(ts.invoice_name, t.name) AS invoice_name
            FROM accounting_revenues r
            JOIN talents t ON t.id = r.talent_id
            LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id
            WHERE r.status = 'pending'
            ORDER BY r.updated_at ASC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) { return []; }
}

// ── Portal account functions ──────────────────────────────────

function accounting_portal_accounts_list($pdo) {
    if (!admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) return [];
    try {
        $stmt = $pdo->query("
            SELECT pa.*, t.name AS talent_name
            FROM talent_portal_accounts pa
            JOIN talents t ON t.id = pa.talent_id
            ORDER BY pa.created_at DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) { return []; }
}

function accounting_portal_account_for_talent($pdo, $talentId) {
    if (!admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) return null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM talent_portal_accounts WHERE talent_id = ? LIMIT 1");
        $stmt->execute([(string)$talentId]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function accounting_portal_account_create($pdo, $talentId, $loginId, $password, $userId, $isActive = 1) {
    if (!admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) return ['error' => 'テーブルが存在しません。'];
    $loginId = trim((string)$loginId);
    if ($loginId === '' || mb_strlen($loginId) > 100) {
        return ['error' => 'ログインIDは1〜100文字で入力してください。'];
    }
    if (strlen((string)$password) < 8) {
        return ['error' => 'パスワードは8文字以上で入力してください。'];
    }
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $hasPasswordChangedAt = admin_table_has_column($pdo, 'talent_portal_accounts', 'password_changed_at');
        if (admin_table_has_column($pdo, 'talent_portal_accounts', 'created_by')) {
            $passwordColumn = $hasPasswordChangedAt ? ', password_changed_at' : '';
            $passwordValue  = $hasPasswordChangedAt ? ', NOW()' : '';
            $pdo->prepare("
                INSERT INTO talent_portal_accounts (talent_id, login_id, password_hash, is_active, created_by{$passwordColumn})
                VALUES (?, ?, ?, ?, ?{$passwordValue})
            ")->execute([$talentId, $loginId, $hash, (int)$isActive, $userId]);
        } else {
            $passwordColumn = $hasPasswordChangedAt ? ', password_changed_at' : '';
            $passwordValue  = $hasPasswordChangedAt ? ', NOW()' : '';
            $pdo->prepare("
                INSERT INTO talent_portal_accounts (talent_id, login_id, password_hash, is_active{$passwordColumn})
                VALUES (?, ?, ?, ?{$passwordValue})
            ")->execute([$talentId, $loginId, $hash, (int)$isActive]);
        }
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'アカウント作成に失敗しました。'];
    }
}

function accounting_portal_account_update($pdo, $id, $data, $userId) {
    if (!admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) return ['error' => 'テーブルが存在しません。'];
    try {
        $sets = [];
        $params = [];
        if (isset($data['login_id'])) {
            $loginId = trim((string)$data['login_id']);
            if ($loginId === '' || mb_strlen($loginId) > 100) {
                return ['error' => 'ログインIDは1〜100文字で入力してください。'];
            }
            $sets[] = 'login_id = ?';
            $params[] = $loginId;
        }
        if (isset($data['password']) && $data['password'] !== '') {
            if (strlen((string)$data['password']) < 8) {
                return ['error' => 'パスワードは8文字以上で入力してください。'];
            }
            $sets[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            if (admin_table_has_column($pdo, 'talent_portal_accounts', 'password_changed_at')) {
                $sets[] = 'password_changed_at = NOW()';
            }
        }
        if (isset($data['is_active'])) {
            $sets[] = 'is_active = ?';
            $params[] = (int)$data['is_active'];
        }
        if (admin_table_has_column($pdo, 'talent_portal_accounts', 'updated_by')) {
            $sets[] = 'updated_by = ?';
            $params[] = $userId;
        }
        if (!$sets) {
            return ['success' => true];
        }
        $params[] = (int)$id;
        $pdo->prepare("UPDATE talent_portal_accounts SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")
            ->execute($params);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'アカウント更新に失敗しました。'];
    }
}

function accounting_portal_account_delete($pdo, $id) {
    if (!admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) return ['error' => 'テーブルが存在しません。'];
    try {
        $pdo->prepare("DELETE FROM talent_portal_accounts WHERE id = ?")->execute([(int)$id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'アカウント削除に失敗しました。'];
    }
}

function accounting_portal_account_save_for_talent($pdo, $talentId, $data, $userId) {
    $loginId  = trim((string)($data['login_id'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $isActive = !empty($data['is_active']) ? 1 : 0;

    if (!admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) {
        if ($loginId !== '' || $password !== '') {
            return ['error' => 'タレントポータルのテーブルが存在しません。admin/portal_migrate.sql を実行してください。'];
        }
        return ['skipped' => true];
    }

    $existing = accounting_portal_account_for_talent($pdo, $talentId);
    if ($existing) {
        if ($loginId === '') {
            return ['error' => '既存のポータルアカウントはログインIDを空にできません。'];
        }
        $payload = [
            'login_id'  => $loginId,
            'is_active' => $isActive,
        ];
        if ($password !== '') {
            $payload['password'] = $password;
        }
        return accounting_portal_account_update($pdo, (int)$existing['id'], $payload, $userId);
    }

    if ($loginId === '' && $password === '') {
        return ['skipped' => true];
    }
    if ($loginId === '' || $password === '') {
        return ['error' => '新規ポータルアカウントを作成する場合は、ログインIDと初期パスワードの両方を入力してください。'];
    }

    return accounting_portal_account_create($pdo, $talentId, $loginId, $password, $userId, $isActive);
}

// ── Portal notices functions ──────────────────────────────────

function accounting_portal_notices_list($pdo) {
    if (!admin_table_has_column($pdo, 'talent_portal_notices', 'id')) return [];
    try {
        $stmt = $pdo->query("
            SELECT * FROM talent_portal_notices
            ORDER BY COALESCE(published_at, created_at) DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) { return []; }
}

function accounting_portal_notice_create($pdo, $title, $body, $isPublished, $userId) {
    if (!admin_table_has_column($pdo, 'talent_portal_notices', 'id')) return ['error' => 'テーブルが存在しません。'];
    try {
        $publishedAt = $isPublished ? 'NOW()' : 'NULL';
        $pdo->prepare("
            INSERT INTO talent_portal_notices (title, body, is_published, published_at, created_by)
            VALUES (?, ?, ?, {$publishedAt}, ?)
        ")->execute([trim($title), trim($body), (int)$isPublished, $userId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'お知らせ作成に失敗しました。'];
    }
}

function accounting_portal_notice_update($pdo, $id, $data, $userId) {
    if (!admin_table_has_column($pdo, 'talent_portal_notices', 'id')) return ['error' => 'テーブルが存在しません。'];
    try {
        $sets = [];
        $params = [];
        if (isset($data['title'])) {
            $sets[] = 'title = ?';
            $params[] = trim($data['title']);
        }
        if (isset($data['body'])) {
            $sets[] = 'body = ?';
            $params[] = trim($data['body']);
        }
        if (isset($data['is_published'])) {
            $sets[] = 'is_published = ?';
            $params[] = (int)$data['is_published'];
            if ($data['is_published']) {
                $sets[] = 'published_at = NOW()';
            }
        }
        $sets[] = 'updated_by = ?';
        $params[] = $userId;
        $params[] = (int)$id;
        $pdo->prepare("UPDATE talent_portal_notices SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")
            ->execute($params);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'お知らせ更新に失敗しました。'];
    }
}

function accounting_portal_notice_delete($pdo, $id) {
    if (!admin_table_has_column($pdo, 'talent_portal_notices', 'id')) return ['error' => 'テーブルが存在しません。'];
    try {
        $pdo->prepare("DELETE FROM talent_portal_notices WHERE id = ?")->execute([(int)$id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'お知らせ削除に失敗しました。'];
    }
}

// ── Fix invoice names ───────────────────────────────────────

function accounting_fix_empty_invoice_names($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT ts.talent_id, t.name
            FROM accounting_talent_settings ts
            JOIN talents t ON t.id = ts.talent_id
            WHERE ts.invoice_name IS NULL OR ts.invoice_name = ''
        ");
        $rows = $stmt->fetchAll();
        if (!$rows) return ['message' => '修正が必要なデータはありませんでした。'];

        $updateStmt = $pdo->prepare("UPDATE accounting_talent_settings SET invoice_name = ? WHERE talent_id = ?");
        $count = 0;
        foreach ($rows as $row) {
            $updateStmt->execute([$row['name'], $row['talent_id']]);
            $count++;
        }
        return ['message' => "{$count}件の請求書宛名を修正しました。"];
    } catch (Exception $e) {
        return ['error' => '修正に失敗しました: ' . $e->getMessage()];
    }
}

function accounting_fetch_categories($pdo, $kind = null) {
    $sql = 'SELECT * FROM accounting_journal_categories WHERE is_active = 1';
    $params = [];

    if ($kind) {
        $sql .= ' AND kind = ?';
        $params[] = $kind;
    }

    $sql .= ' ORDER BY kind, sort_order ASC, id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
