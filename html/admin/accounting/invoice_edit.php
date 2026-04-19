<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'revenue';
$talents = accounting_list_talents($pdo, false);
$settings = load_app_settings($pdo, $config);
$defaultFx = (float)$settings['fx_default_rate'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'revenue';
    $talentId = trim(isset($_POST['talent_id']) ? $_POST['talent_id'] : '');
    $year = (int)(isset($_POST['year']) ? $_POST['year'] : date('Y'));
    $month = (int)(isset($_POST['month']) ? $_POST['month'] : date('n'));
    $fxRate = (float)(isset($_POST['fx_rate']) ? $_POST['fx_rate'] : $defaultFx);
    $note = trim(isset($_POST['note']) ? $_POST['note'] : '');
    try {
        if ($talentId === '') {
            throw new RuntimeException('タレントを選択してください。');
        }
        if ($mode === 'manual') {
            $subject = trim(isset($_POST['subject']) ? $_POST['subject'] : '');
            $detailText = trim(isset($_POST['details_text']) ? $_POST['details_text'] : '');
            $details = parse_detail_lines($detailText);
            if ($subject === '') throw new RuntimeException('件名を入力してください。');
            if (!$details) throw new RuntimeException('明細を1行以上入力してください。');
            $invoiceId = accounting_create_manual_invoice($pdo, $config, $user['id'], $talentId, $year, $month, $subject, $details, $note);
        } else {
            $invoiceId = accounting_create_revenue_invoice($pdo, $config, $user['id'], $talentId, $year, $month, $fxRate, $note);
        }
        write_admin_log($pdo, (int)$user['id'], 'create', 'accounting_invoice', $invoiceId, '請求書を作成しました');
        set_flash('success', '請求書を作成しました。');
        redirect_to($baseUrl . '/accounting/invoice_detail.php?id=' . $invoiceId);
    } catch (Exception $e) {
        set_flash('error', '請求書作成に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/invoice_edit.php?mode=' . urlencode($mode));
    }
}
start_page($mode === 'manual' ? '手入力で請求書を作る' : '収益から請求書を作る', '収益ベースまたは手入力で請求書を作成します。');
?>
<main class="page-container narrow"><form method="post" class="card form-card form-stack"><input type="hidden" name="mode" value="<?= h($mode) ?>">
<div class="form-grid two"><label><span>タレント</span><select name="talent_id" required><option value="">選択してください</option><?php foreach($talents as $t): ?><option value="<?= h($t['id']) ?>"><?= h($t['name']) ?></option><?php endforeach; ?></select></label><?php if ($mode === 'manual'): ?><label><span>件名</span><input type="text" name="subject" placeholder="例：イベント出演費"></label><?php else: ?><label><span>為替レート（USD→JPY）</span><input type="number" step="0.0001" name="fx_rate" value="<?= h((string)$defaultFx) ?>"></label><?php endif; ?></div>
<div class="form-grid two"><label><span>年</span><input type="number" name="year" value="<?= h(date('Y')) ?>"></label><label><span>月</span><input type="number" min="1" max="12" name="month" value="<?= h(date('n')) ?>"></label></div>
<?php if ($mode === 'manual'): ?>
<label><span>明細（1行ごとに 内容|金額）</span><textarea name="details_text" rows="8" placeholder="イベント参加費|25000&#10;デザイン費|18000"></textarea></label>
<?php else: ?>
<div class="card"><strong>自動計算ルール</strong><p class="muted">指定した締め年月までの未請求月をまとめて計算し、タレントごとの取り分率で円換算、5,000円以上なら請求書を作成します。</p></div>
<?php endif; ?>
<label><span>備考</span><textarea name="note" rows="4"><?= h($settings['office_invoice_note']) ?></textarea></label>
<div class="actions-inline"><button class="primary-btn" type="submit">請求書を作成する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">一覧へ戻る</a></div>
</form></main>
<?php end_page(); ?>
