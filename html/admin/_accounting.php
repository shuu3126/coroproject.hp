<?php
function app_settings_defaults($config) {
    return [
        'office_name' => 'CORO PROJECT',
        'office_email' => 'info@coroproject.jp',
        'office_bank_info' => '',
        'office_invoice_note' => '',
        'fx_default_rate' => '150',
        'fx_api_key' => '',
        'pdf_font_path' => $config['pdf']['font_path'],
        'pdf_stamp_path' => $config['pdf']['stamp_path'],
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
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_by, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()');
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
    $sql = 'SELECT id, name, kana, talent_group, status, debut, last_active, avatar, bio, long_bio_json, tags_json, sort_order, is_published FROM talents';
    if (!$includeUnpublished) {
        $sql .= ' WHERE is_published = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, debut ASC, name ASC';
    return $pdo->query($sql)->fetchAll();
}

function accounting_find_talent($pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT * FROM talents WHERE id = ? LIMIT 1');
    $stmt->execute([(string)$talentId]);
    return $stmt->fetch();
}

function accounting_get_talent_setting($pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT * FROM accounting_talent_settings WHERE talent_id = ? LIMIT 1');
    $stmt->execute([(string)$talentId]);
    $row = $stmt->fetch();
    $talent = accounting_find_talent($pdo, $talentId);
    return [
        'talent_id' => (string)$talentId,
        'office_share_percent' => $row ? (float)$row['office_share_percent'] : accounting_share_percent_default(),
        'invoice_name' => $row && $row['invoice_name'] !== null && $row['invoice_name'] !== '' ? $row['invoice_name'] : ($talent ? $talent['name'] : ''),
        'email' => $row ? (string)$row['email'] : '',
        'bank_info' => $row ? (string)$row['bank_info'] : '',
        'memo' => $row ? (string)$row['memo'] : '',
        'is_active' => $row ? (int)$row['is_active'] : 1,
    ];
}

function accounting_upsert_talent_setting($pdo, $talentId, $data, $userId) {
    $stmt = $pdo->prepare('INSERT INTO accounting_talent_settings (talent_id, office_share_percent, invoice_name, email, bank_info, memo, is_active, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE office_share_percent = VALUES(office_share_percent), invoice_name = VALUES(invoice_name), email = VALUES(email), bank_info = VALUES(bank_info), memo = VALUES(memo), is_active = VALUES(is_active), updated_by = VALUES(updated_by), updated_at = NOW()');
    $stmt->execute([
        (string)$talentId,
        (float)$data['office_share_percent'],
        (string)$data['invoice_name'],
        (string)$data['email'],
        (string)$data['bank_info'],
        (string)$data['memo'],
        !empty($data['is_active']) ? 1 : 0,
        $userId ?: null,
    ]);
}

function accounting_next_invoice_no($pdo) {
    $row = $pdo->query('SELECT invoice_no FROM accounting_invoices ORDER BY id DESC LIMIT 1')->fetch();
    if (!$row) return 'INV-000001';
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
    $last = $months[count($months)-1];
    if ((int)$first['year'] === (int)$last['year'] && (int)$first['month'] === (int)$last['month']) {
        return sprintf('%d年%02d月', $first['year'], $first['month']);
    }
    return sprintf('%d年%02d月〜%d年%02d月', $first['year'], $first['month'], $last['year'], $last['month']);
}

function accounting_get_uninvoiced_months_upto($pdo, $talentId, $year, $month) {
    $stmt = $pdo->prepare('SELECT r.year, r.month FROM accounting_revenues r LEFT JOIN accounting_invoiced_months im ON im.talent_id = r.talent_id AND im.year = r.year AND im.month = r.month WHERE r.talent_id = ? AND im.id IS NULL AND (r.year < ? OR (r.year = ? AND r.month <= ?)) GROUP BY r.year, r.month ORDER BY r.year, r.month');
    $stmt->execute([(string)$talentId, (int)$year, (int)$year, (int)$month]);
    return $stmt->fetchAll() ?: [];
}

function accounting_calc_office_share_jpy_for_month($pdo, $talentId, $year, $month, $fxRate) {
    $stmt = $pdo->prepare('SELECT currency, SUM(amount_streaming + amount_goods + amount_sponsor) AS total_amount FROM accounting_revenues WHERE talent_id = ? AND year = ? AND month = ? GROUP BY currency');
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
    $sql = 'SELECT r.*, t.name, COALESCE(ts.invoice_name, t.name) AS invoice_name FROM accounting_revenues r JOIN talents t ON t.id = r.talent_id LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id';
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
        $stmt = $pdo->prepare('UPDATE accounting_revenues SET talent_id=?, year=?, month=?, currency=?, amount_streaming=?, amount_goods=?, amount_sponsor=?, evidence_path=?, memo=?, updated_by=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([
            (string)$data['talent_id'], (int)$data['year'], (int)$data['month'], (string)$data['currency'],
            (float)$data['amount_streaming'], (float)$data['amount_goods'], (float)$data['amount_sponsor'],
            $data['evidence_path'], $data['memo'], $userId ?: null, (int)$id,
        ]);
        return (int)$id;
    }
    $stmt = $pdo->prepare('INSERT INTO accounting_revenues (talent_id, year, month, currency, amount_streaming, amount_goods, amount_sponsor, evidence_path, memo, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute([
        (string)$data['talent_id'], (int)$data['year'], (int)$data['month'], (string)$data['currency'],
        (float)$data['amount_streaming'], (float)$data['amount_goods'], (float)$data['amount_sponsor'],
        $data['evidence_path'], $data['memo'], $userId ?: null, $userId ?: null,
    ]);
    return (int)$pdo->lastInsertId();
}

function accounting_delete_revenue($pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM accounting_revenues WHERE id = ?');
    $stmt->execute([(int)$id]);
}

function accounting_fetch_invoice($pdo, $id) {
    $stmt = $pdo->prepare('SELECT i.*, t.name AS talent_real_name, COALESCE(ts.invoice_name, t.name) AS invoice_name, ts.office_share_percent FROM accounting_invoices i JOIN talents t ON t.id = i.talent_id LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id WHERE i.id = ? LIMIT 1');
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

function accounting_fetch_invoices($pdo) {
    $sql = 'SELECT i.*, t.name AS talent_name, r.receipt_pdf_path FROM accounting_invoices i JOIN talents t ON t.id = i.talent_id LEFT JOIN accounting_receipts r ON r.invoice_id = i.id ORDER BY i.created_at DESC, i.id DESC';
    return $pdo->query($sql)->fetchAll();
}

function accounting_insert_journal_for_invoice($pdo, $invoiceId, $talentId, $invoiceNo, $subject, $amount, $userId) {
    $stmt = $pdo->prepare('INSERT INTO accounting_journal_entries (`date`, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_by, updated_by, created_at, updated_at) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute(['income', '配信収益', (float)$amount, '請求書 ' . $invoiceNo . '｜' . $subject, (string)$talentId, (int)$invoiceId, 'invoice_auto', '', $userId ?: null, $userId ?: null]);
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
    $periodLabel = $invoice['months'] ? accounting_period_label($invoice['months']) : sprintf('%d年%02d月', $invoice['close_year'], $invoice['close_month']);
    $payload = [
        'invoice_no' => $invoice['invoice_no'],
        'talent_real_name' => $invoice['invoice_name'],
        'talent_display_name' => $invoice['talent_name'],
        'issue_date' => date('Y-m-d'),
        'subject' => $invoice['subject'],
        'amount_jpy' => $invoice['amount_jpy'],
        'items' => array_map(function ($row) { return ['description' => $row['description'], 'amount_jpy' => $row['amount_jpy']]; }, $invoice['items']),
        'note' => $invoice['note'],
        'period_label' => $periodLabel,
    ];
    pdf_make_invoice($config, $settings, $payload, $absolutePath);
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
    $periodLabel = $invoice['months'] ? accounting_period_label($invoice['months']) : sprintf('%d年%02d月', $invoice['close_year'], $invoice['close_month']);
    $description = '配信収益分配金として、' . $periodLabel . ' 分の請求に対する入金。';
    $payload = [
        'invoice_no' => $invoice['invoice_no'],
        'talent_real_name' => $invoice['invoice_name'],
        'talent_display_name' => $invoice['talent_name'],
        'issue_date' => date('Y-m-d'),
        'amount_jpy' => $invoice['amount_jpy'],
        'fx_rate' => $invoice['fx_rate'],
        'description' => $description,
        'note' => $invoice['note'],
    ];
    pdf_make_receipt($config, $settings, $payload, $absolutePath);
    $relativePath = trim($relPrefix, '/\\') . '/' . $filename;
    $stmt = $pdo->prepare('INSERT INTO accounting_receipts (invoice_id, receipt_pdf_path, issued_at, issued_by) VALUES (?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE receipt_pdf_path = VALUES(receipt_pdf_path), issued_at = NOW(), issued_by = VALUES(issued_by)');
    $stmt->execute([(int)$invoiceId, $relativePath, $userId ?: null]);
    $pdo->prepare("UPDATE accounting_invoices SET status='receipt_issued', updated_by=?, updated_at=NOW() WHERE id=?")->execute([$userId ?: null, (int)$invoiceId]);
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
        $stmt = $pdo->prepare('INSERT INTO accounting_invoices (invoice_no, talent_id, close_year, close_month, subject, amount_jpy, fx_rate, status, note, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$invoiceNo, (string)$talentId, (int)$year, (int)$month, $subject, $amount, (float)$fxRate, 'issued', $note, $userId ?: null, $userId ?: null]);
        $invoiceId = (int)$pdo->lastInsertId();
        $itemStmt = $pdo->prepare('INSERT INTO accounting_invoice_items (invoice_id, sort_order, description, amount_jpy, created_at) VALUES (?, ?, ?, ?, NOW())');
        foreach ($items as $idx => $item) {
            $itemStmt->execute([$invoiceId, $idx + 1, $item['description'], $item['amount_jpy']]);
        }
        $markStmt = $pdo->prepare('INSERT INTO accounting_invoiced_months (invoice_id, talent_id, year, month, created_at) VALUES (?, ?, ?, ?, NOW())');
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
    foreach ($details as $detail) $amount += (float)$detail['amount'];
    if ($amount <= 0) {
        throw new RuntimeException('手入力請求の明細を入力してください。');
    }
    $invoiceNo = accounting_next_invoice_no($pdo);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO accounting_invoices (invoice_no, talent_id, close_year, close_month, subject, amount_jpy, fx_rate, status, note, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$invoiceNo, (string)$talentId, (int)$year, (int)$month, $subject, $amount, 'issued', $note, $userId ?: null, $userId ?: null]);
        $invoiceId = (int)$pdo->lastInsertId();
        $itemStmt = $pdo->prepare('INSERT INTO accounting_invoice_items (invoice_id, sort_order, description, amount_jpy, created_at) VALUES (?, ?, ?, ?, NOW())');
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
    $sql = 'SELECT j.*, COALESCE(ts.invoice_name, t.name) AS talent_name FROM accounting_journal_entries j LEFT JOIN talents t ON t.id = j.talent_id LEFT JOIN accounting_talent_settings ts ON ts.talent_id = t.id';
    $conds = [];
    $params = [];
    if (!empty($filters['date_from'])) { $conds[] = 'j.date >= ?'; $params[] = $filters['date_from']; }
    if (!empty($filters['date_to'])) { $conds[] = 'j.date <= ?'; $params[] = $filters['date_to']; }
    if (!empty($filters['kind'])) { $conds[] = 'j.kind = ?'; $params[] = $filters['kind']; }
    if (!empty($filters['category'])) { $conds[] = 'j.category = ?'; $params[] = $filters['category']; }
    if (!empty($filters['talent_id'])) { $conds[] = 'j.talent_id = ?'; $params[] = $filters['talent_id']; }
    if ($conds) $sql .= ' WHERE ' . implode(' AND ', $conds);
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
        $stmt = $pdo->prepare('UPDATE accounting_journal_entries SET date=?, kind=?, category=?, amount=?, description=?, talent_id=?, evidence_path=?, updated_by=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$data['date'], $data['kind'], $data['category'], $data['amount'], $data['description'], $data['talent_id'] ?: null, $data['evidence_path'], $userId ?: null, (int)$id]);
        return (int)$id;
    }
    $stmt = $pdo->prepare('INSERT INTO accounting_journal_entries (date, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute([$data['date'], $data['kind'], $data['category'], $data['amount'], $data['description'], $data['talent_id'] ?: null, 'manual', $data['evidence_path'], $userId ?: null, $userId ?: null]);
    return (int)$pdo->lastInsertId();
}

function accounting_fetch_categories($pdo, $kind = null) {
    $sql = 'SELECT * FROM accounting_journal_categories WHERE is_active = 1';
    $params = [];
    if ($kind) { $sql .= ' AND kind = ?'; $params[] = $kind; }
    $sql .= ' ORDER BY kind, sort_order ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
