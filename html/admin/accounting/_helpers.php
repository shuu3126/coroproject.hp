<?php
function accounting_settings(PDO $pdo): array
{
    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['setting_key']] = $r['setting_value'];
    return $map + ['fx_default_rate' => '150', 'office_name' => 'CORO PROJECT', 'office_email' => 'info@coroproject.jp', 'office_invoice_note' => ''];
}

function accounting_next_invoice_no(PDO $pdo): string
{
    $row = $pdo->query('SELECT invoice_no FROM accounting_invoices ORDER BY id DESC LIMIT 1')->fetch();
    if (!$row) return 'INV-000001';
    $last = (string)$row['invoice_no'];
    $num = (int)preg_replace('/\D+/', '', $last);
    return sprintf('INV-%06d', $num + 1);
}

function accounting_get_uninvoiced_months(PDO $pdo, int $talentId, int $year, int $month): array
{
    $stmt = $pdo->prepare('SELECT r.year, r.month FROM accounting_revenues r LEFT JOIN accounting_invoiced_months im ON im.talent_id = r.talent_id AND im.year = r.year AND im.month = r.month WHERE r.talent_id = ? AND im.id IS NULL AND (r.year < ? OR (r.year = ? AND r.month <= ?)) GROUP BY r.year, r.month ORDER BY r.year, r.month');
    $stmt->execute([$talentId, $year, $year, $month]);
    return $stmt->fetchAll() ?: [];
}

function accounting_calc_office_share_jpy_for_month(PDO $pdo, int $talentId, int $year, int $month, float $fxRate): float
{
    $stmt = $pdo->prepare('SELECT currency, SUM(amount_streaming + amount_goods + amount_sponsor) total FROM accounting_revenues WHERE talent_id = ? AND year = ? AND month = ? GROUP BY currency');
    $stmt->execute([$talentId, $year, $month]);
    $rows = $stmt->fetchAll();
    $totalJpy = 0.0;
    foreach ($rows as $row) {
        $sum = (float)($row['total'] ?? 0);
        $currency = strtoupper((string)($row['currency'] ?? 'JPY'));
        $totalJpy += $currency === 'USD' ? $sum * $fxRate : $sum;
    }
    return $totalJpy * 0.3;
}

function accounting_period_label(array $months): string
{
    if (!$months) return '';
    usort($months, fn($a, $b) => [$a['year'], $a['month']] <=> [$b['year'], $b['month']]);
    $first = $months[0];
    $last = $months[count($months)-1];
    if ($first['year'] == $last['year'] && $first['month'] == $last['month']) {
        return sprintf('%d年%02d月', $first['year'], $first['month']);
    }
    return sprintf('%d年%02d月〜%d年%02d月', $first['year'], $first['month'], $last['year'], $last['month']);
}

function accounting_insert_journal_for_invoice(PDO $pdo, int $invoiceId, int $talentId, string $invoiceNo, int $year, int $month, float $amount, string $note = ''): void
{
    $description = $note !== '' ? ('請求書 ' . $invoiceNo . '｜' . $note) : sprintf('請求書 %s｜%d年%02d月分 配信収益等', $invoiceNo, $year, $month);
    $stmt = $pdo->prepare('INSERT INTO accounting_journal_entries (`date`, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_at, updated_at) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute(['income', '配信収益', $amount, $description, $talentId, $invoiceId, 'invoice_auto', '']);
}

function accounting_generate_document_html(array $data, string $type, string $absoluteDir, string $relativePrefix): array
{
    ensure_dir_path($absoluteDir);
    $filename = strtolower($data['invoice_no']) . ($type === 'receipt' ? '_receipt' : '') . '.html';
    $path = rtrim($absoluteDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    $title = $type === 'receipt' ? '領収書' : '請求書';
    $amount = format_money($data['amount_jpy']);
    $period = $data['period_label'] ?? sprintf('%d年%02d月', $data['year'], $data['month']);
    $html = '<!doctype html><html lang="ja"><head><meta charset="utf-8"><title>' . h($title) . '</title><style>body{font-family:Meiryo,sans-serif;padding:40px;color:#111} .wrap{max-width:800px;margin:0 auto} h1{text-align:center} .box{border:1px solid #ddd;padding:16px;border-radius:12px;margin:18px 0} table{width:100%;border-collapse:collapse} th,td{border-bottom:1px solid #eee;padding:10px;text-align:left}</style></head><body><div class="wrap"><h1>' . h($title) . '</h1><div class="box"><strong>番号:</strong> ' . h($data['invoice_no']) . '<br><strong>宛名:</strong> ' . h($data['talent_name']) . '<br><strong>対象期間:</strong> ' . h($period) . '<br><strong>金額:</strong> ¥' . h($amount) . '</div>';
    if (!empty($data['details'])) {
        $html .= '<table><thead><tr><th>内容</th><th>金額</th></tr></thead><tbody>';
        foreach ($data['details'] as $detail) {
            $html .= '<tr><td>' . h($detail['desc']) . '</td><td>¥' . h(format_money($detail['amount'])) . '</td></tr>';
        }
        $html .= '</tbody></table>';
    }
    if (!empty($data['note'])) {
        $html .= '<div class="box"><strong>備考</strong><br>' . nl2br(h($data['note'])) . '</div>';
    }
    $html .= '</div></body></html>';
    file_put_contents($path, $html);
    return [
        'absolute_path' => $path,
        'relative_path' => trim($relativePrefix, '/\\') . '/' . $filename,
        'original_name' => $filename,
    ];
}
