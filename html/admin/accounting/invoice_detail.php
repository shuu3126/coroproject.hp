<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_admin_login();
$user = current_admin_user();
$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
if ($id <= 0) redirect_to($baseUrl . '/accounting/invoices.php');

function load_invoice_detail( $pdo, $id) {
    $stmt = $pdo->prepare('SELECT i.*, t.display_name AS talent_display_name, t.name AS talent_real_name FROM accounting_invoices i JOIN accounting_talents t ON t.id = i.talent_id WHERE i.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $fs = $pdo->prepare('SELECT * FROM accounting_invoice_files WHERE invoice_id = ? ORDER BY id ASC');
    $fs->execute([$id]);
    $row['files'] = $fs->fetchAll() ?: [];
    $ms = $pdo->prepare('SELECT year, month FROM accounting_invoiced_months WHERE invoice_id = ? ORDER BY year, month');
    $ms->execute([$id]);
    $row['months'] = $ms->fetchAll() ?: [];
    return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (isset($_POST['action']) ? $_POST['action'] : '');
    $invoice = load_invoice_detail($pdo, $id);
    if (!$invoice) redirect_to($baseUrl . '/accounting/invoices.php');
    try {
        if ($action === 'mark_paid') {
            $pdo->prepare("UPDATE accounting_invoices SET status='paid', paid_at=NOW(), updated_by=?, updated_at=NOW() WHERE id=?")->execute([$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'mark_paid', 'accounting_invoice', $id, '請求を入金済みにしました', ['invoice_no' => $invoice['invoice_no']]);
            set_flash('success', '入金済みに更新しました。');
        } elseif ($action === 'issue_receipt') {
            $periodLabel = $invoice['months'] ? accounting_period_label($invoice['months']) : sprintf('%d年%02d月', $invoice['year'], $invoice['month']);
            $doc = accounting_generate_document_html([
                'invoice_no' => $invoice['invoice_no'],
                'talent_name' => $invoice['talent_real_name'] ?: $invoice['talent_display_name'],
                'year' => $invoice['year'],
                'month' => $invoice['month'],
                'period_label' => $periodLabel,
                'amount_jpy' => $invoice['amount_jpy'],
                'note' => $invoice['note'],
                'details' => [['desc' => '入金に対する領収', 'amount' => $invoice['amount_jpy']]],
            ], 'receipt', $config['uploads']['accounting_root'] . '/receipts', $config['uploads']['accounting_prefix'] . '/receipts');
            $pdo->prepare('INSERT INTO accounting_invoice_files (invoice_id, file_type, file_path, original_file_name, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())')->execute([$id, 'receipt', $doc['relative_path'], $doc['original_name'], $user['id']]);
            $pdo->prepare("UPDATE accounting_invoices SET status='receipt_issued', updated_by=?, updated_at=NOW() WHERE id=?")->execute([$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'issue_receipt', 'accounting_invoice', $id, '領収書を発行しました', ['invoice_no' => $invoice['invoice_no']]);
            set_flash('success', '領収書を発行しました。');
        } elseif ($action === 'delete') {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM accounting_journal_entries WHERE invoice_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM accounting_invoiced_months WHERE invoice_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM accounting_invoice_files WHERE invoice_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM accounting_invoices WHERE id=?')->execute([$id]);
            $pdo->commit();
            write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_invoice', $id, '請求を削除しました', ['invoice_no' => $invoice['invoice_no']]);
            set_flash('success', '請求を削除しました。');
            redirect_to($baseUrl . '/accounting/invoices.php');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', '処理に失敗しました: ' . $e->getMessage());
    }
    redirect_to($baseUrl . '/accounting/invoice_detail.php?id=' . $id);
}
$invoice = load_invoice_detail($pdo, $id);
if (!$invoice) { set_flash('error','請求が見つかりません。'); redirect_to($baseUrl . '/accounting/invoices.php'); }
$filesByType = ['invoice'=>[], 'receipt'=>[]]; foreach($invoice['files'] as $f){ $filesByType[$f['file_type']][]=$f; }
$periodLabel = $invoice['months'] ? accounting_period_label($invoice['months']) : sprintf('%d年%02d月', $invoice['year'], $invoice['month']);
start_page('請求詳細', 'この請求に関する書類確認、入金処理、領収書発行を行えます。');
?>
<main class="page-container narrow">
<div class="card form-card">
  <div class="summary-list">
    <div class="summary-row"><span>請求書番号</span><strong><?= h($invoice['invoice_no']) ?></strong></div>
    <div class="summary-row"><span>タレント</span><strong><?= h($invoice['talent_display_name']) ?></strong></div>
    <div class="summary-row"><span>対象期間</span><strong><?= h($periodLabel) ?></strong></div>
    <div class="summary-row"><span>請求額</span><strong>¥<?= h(format_money($invoice['amount_jpy'])) ?></strong></div>
    <div class="summary-row"><span>ステータス</span><strong><span class="status-badge <?= status_badge_class((string)$invoice['status']) ?>"><?= h($invoice['status']) ?></span></strong></div>
    <div class="summary-row"><span>入金日</span><strong><?= h(format_datetime($invoice['paid_at'])) ?></strong></div>
  </div>
</div>
<div class="card mt-24"><h3>請求書</h3><div class="actions-inline"><?php foreach($filesByType['invoice'] as $file): ?><a class="ghost-btn" target="_blank" href="/<?= h($file['file_path']) ?>">請求書を開く</a><?php endforeach; ?></div></div>
<div class="card mt-24"><h3>領収書</h3><div class="actions-inline"><?php if($filesByType['receipt']): foreach($filesByType['receipt'] as $file): ?><a class="ghost-btn" target="_blank" href="/<?= h($file['file_path']) ?>">領収書を開く</a><?php endforeach; else: ?><form method="post"><input type="hidden" name="action" value="issue_receipt"><button class="primary-btn" type="submit">領収書を発行する</button></form><?php endif; ?></div></div>
<div class="card mt-24"><h3>入金</h3><div class="actions-inline"><?php if($invoice['status'] === 'issued'): ?><form method="post" data-confirm="この請求を入金済みにしますか？"><input type="hidden" name="action" value="mark_paid"><button class="warning-btn" type="submit">入金済みにする</button></form><?php else: ?><span class="muted">すでに入金処理済みです。</span><?php endif; ?></div></div>
<div class="card mt-24"><h3>危険操作</h3><form method="post" data-confirm="この請求を完全に削除しますか？ 元に戻せません。"><input type="hidden" name="action" value="delete"><button class="danger-btn" type="submit">この請求を完全に削除する</button></form></div>
</main>
<?php end_page(); ?>
