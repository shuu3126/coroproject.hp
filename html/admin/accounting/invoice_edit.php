<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_admin_login();
$user = current_admin_user();
$mode = $_GET['mode'] ?? 'revenue';
$talents = $pdo->query("SELECT id, display_name, name FROM accounting_talents WHERE status = 'active' ORDER BY display_name ASC")->fetchAll();
$settings = accounting_settings($pdo);
$defaultFx = (float)($settings['fx_default_rate'] ?? 150);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'revenue';
    $talentId = (int)($_POST['talent_id'] ?? 0);
    $year = (int)($_POST['year'] ?? date('Y'));
    $month = (int)($_POST['month'] ?? date('n'));
    $fxRate = (float)($_POST['fx_rate'] ?? $defaultFx);
    $note = trim($_POST['note'] ?? '');
    $details = [];
    $amountJpy = 0.0;
    $months = [];
    if ($talentId <= 0) {
        set_flash('error', 'タレントを選択してください。');
        redirect_to($baseUrl . '/accounting/invoice_edit.php?mode=' . urlencode($mode));
    }
    try {
        if ($mode === 'manual') {
            $detailText = trim($_POST['details_text'] ?? '');
            $details = parse_detail_lines($detailText);
            foreach ($details as $detail) $amountJpy += $detail['amount'];
            if ($amountJpy <= 0) throw new RuntimeException('手入力請求の明細を入力してください。');
        } else {
            $months = accounting_get_uninvoiced_months($pdo, $talentId, $year, $month);
            if (!$months) throw new RuntimeException('指定締め月までの未請求月がありません。');
            foreach ($months as $m) {
                $share = accounting_calc_office_share_jpy_for_month($pdo, $talentId, (int)$m['year'], (int)$m['month'], $fxRate);
                if ($share <= 0) continue;
                $amountJpy += $share;
                $details[] = ['desc' => sprintf('配信収益分配（%d年%02d月）', $m['year'], $m['month']), 'amount' => $share];
            }
            if ($amountJpy < 5000) throw new RuntimeException('請求額が5,000円未満のため、今回は請求書を作成できません。');
        }

        $invoiceNo = accounting_next_invoice_no($pdo);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO accounting_invoices (invoice_no, talent_id, year, month, amount_jpy, fx_rate, status, note, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$invoiceNo, $talentId, $year, $month, $amountJpy, $fxRate, 'issued', $note, $user['id'], $user['id']]);
        $invoiceId = (int)$pdo->lastInsertId();
        if ($months) {
            $stmt = $pdo->prepare('INSERT INTO accounting_invoiced_months (invoice_id, talent_id, year, month, created_at) VALUES (?, ?, ?, ?, NOW())');
            foreach ($months as $m) { $stmt->execute([$invoiceId, $talentId, $m['year'], $m['month']]); }
        }
        accounting_insert_journal_for_invoice($pdo, $invoiceId, $talentId, $invoiceNo, $year, $month, $amountJpy, $note);
        $talent = null; foreach ($talents as $t) if ((int)$t['id'] === $talentId) { $talent = $t; break; }
        $periodLabel = $months ? accounting_period_label($months) : sprintf('%d年%02d月', $year, $month);
        $doc = accounting_generate_document_html([
            'invoice_no' => $invoiceNo,
            'talent_name' => $talent['name'] ?? ($talent['display_name'] ?? ''),
            'year' => $year,
            'month' => $month,
            'period_label' => $periodLabel,
            'amount_jpy' => $amountJpy,
            'note' => $note,
            'details' => $details,
        ], 'invoice', $config['uploads']['accounting_root'] . '/invoices', $config['uploads']['accounting_prefix'] . '/invoices');
        $pdo->prepare('INSERT INTO accounting_invoice_files (invoice_id, file_type, file_path, original_file_name, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())')->execute([$invoiceId, 'invoice', $doc['relative_path'], $doc['original_name'], $user['id']]);
        write_admin_log($pdo, (int)$user['id'], 'create', 'accounting_invoice', $invoiceId, '請求書を作成しました', ['invoice_no' => $invoiceNo]);
        $pdo->commit();
        set_flash('success', '請求書を作成しました。');
        redirect_to($baseUrl . '/accounting/invoice_detail.php?id=' . $invoiceId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', '請求書作成に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/invoice_edit.php?mode=' . urlencode($mode));
    }
}
start_page($mode === 'manual' ? '手入力で請求書を作る' : '収益から請求書を作る', '収益ベースまたは手入力で請求書を作成します。');
?>
<main class="page-container narrow"><form method="post" class="card form-card form-stack"><input type="hidden" name="mode" value="<?= h($mode) ?>">
<div class="form-grid two"><label><span>タレント</span><select name="talent_id" required><option value="">選択してください</option><?php foreach($talents as $t): ?><option value="<?= h((string)$t['id']) ?>"><?= h($t['display_name']) ?></option><?php endforeach; ?></select></label><label><span>為替レート（USD→JPY）</span><input type="number" step="0.0001" name="fx_rate" value="<?= h((string)$defaultFx) ?>"></label></div>
<div class="form-grid two"><label><span>年</span><input type="number" name="year" value="<?= h(date('Y')) ?>"></label><label><span>月</span><input type="number" min="1" max="12" name="month" value="<?= h(date('n')) ?>"></label></div>
<?php if ($mode === 'manual'): ?>
<label><span>明細（1行ごとに 内容|金額）</span><textarea name="details_text" rows="8" placeholder="イベント参加費|25000&#10;デザイン費|18000"></textarea></label>
<?php else: ?>
<div class="card"><strong>自動計算ルール</strong><p class="muted">指定した締め年月までの未請求月をまとめて計算し、30%換算で5,000円以上なら請求書を作成します。</p></div>
<?php endif; ?>
<label><span>備考</span><textarea name="note" rows="4"><?= h($settings['office_invoice_note'] ?? '') ?></textarea></label>
<div class="actions-inline"><button class="primary-btn" type="submit">請求書を作成する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">一覧へ戻る</a></div>
</form></main>
<?php end_page(); ?>
