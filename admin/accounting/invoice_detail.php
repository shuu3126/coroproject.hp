<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
if ($id <= 0) {
    set_flash('error', '請求IDが不正です。');
    redirect_to($baseUrl . '/accounting/invoices.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    try {
        if ($action === 'mark_paid') {
            accounting_mark_invoice_paid($pdo, $id, $user['id']);
            write_admin_log($pdo, (int)$user['id'], 'mark_paid', 'accounting_invoice', $id, '請求を入金済みにしました');
            set_flash('success', '入金済みに更新しました。');
        } elseif ($action === 'issue_receipt') {
            accounting_generate_receipt_pdf($pdo, $config, $id, $user['id']);
            write_admin_log($pdo, (int)$user['id'], 'issue_receipt', 'accounting_invoice', $id, '領収書を発行しました');
            set_flash('success', '領収書を発行しました。');
        } elseif ($action === 'regenerate_invoice_pdf') {
            accounting_regenerate_invoice_pdf($pdo, $config, $id);
            write_admin_log($pdo, (int)$user['id'], 'regenerate_pdf', 'accounting_invoice', $id, '請求書PDFを再出力しました');
            set_flash('success', '請求書PDFを再出力しました。');
        } elseif ($action === 'delete') {
            $invoice = accounting_delete_invoice($pdo, $id);
            write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_invoice', $id, '請求を削除しました', [
                'invoice_no' => $invoice ? $invoice['invoice_no'] : null,
            ]);
            set_flash('success', '請求を削除しました。');
            redirect_to($baseUrl . '/accounting/invoices.php');
        }
    } catch (Exception $e) {
        set_flash('error', '処理に失敗しました: ' . $e->getMessage());
    }
    redirect_to($baseUrl . '/accounting/invoice_detail.php?id=' . $id);
}

$invoice = accounting_fetch_invoice($pdo, $id);
if (!$invoice) {
    set_flash('error', '請求が見つかりません。');
    redirect_to($baseUrl . '/accounting/invoices.php');
}

$periodLabel = $invoice['months']
    ? accounting_period_label($invoice['months'])
    : sprintf('%d年%02d月', $invoice['close_year'], $invoice['close_month']);

start_page('請求詳細', '書類確認・入金処理・領収書発行を行えます。');
?>
<main class="page-container narrow">

  <section class="page-header-block with-actions">
    <div>
      <h1><?= h($invoice['invoice_no']) ?></h1>
      <p><?= h($invoice['invoice_name'] ?? '—') ?> / <?= h($periodLabel) ?></p>
    </div>
    <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">一覧へ戻る</a>
  </section>

  <!-- 基本情報 -->
  <div class="card form-card">
    <div class="summary-list">
      <div class="summary-row">
        <span>請求書番号</span>
        <strong><?= h($invoice['invoice_no']) ?></strong>
      </div>
      <div class="summary-row">
        <span>宛名</span>
        <strong><?= h($invoice['invoice_name'] ?? '—') ?></strong>
      </div>
      <div class="summary-row">
        <span>対象期間</span>
        <strong><?= h($periodLabel) ?></strong>
      </div>
      <div class="summary-row">
        <span>件名</span>
        <strong><?= h($invoice['subject']) ?></strong>
      </div>
      <div class="summary-row">
        <span>請求額</span>
        <strong>¥<?= h(format_money($invoice['amount_jpy'])) ?></strong>
      </div>
      <div class="summary-row">
        <span>ステータス</span>
        <strong>
          <span class="status-badge <?= status_badge_class((string)$invoice['status']) ?>">
            <?= h(invoice_status_label($invoice['status'])) ?>
          </span>
        </strong>
      </div>
      <?php if (!empty($invoice['paid_at'])): ?>
        <div class="summary-row">
          <span>入金日</span>
          <strong><?= h(format_datetime($invoice['paid_at'])) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 明細 -->
  <?php if ($invoice['items']): ?>
  <div class="card form-card mt-24">
    <div class="section-heading">明細</div>
    <div class="summary-list">
      <?php foreach ($invoice['items'] as $item): ?>
        <div class="summary-row">
          <span><?= h($item['description']) ?></span>
          <strong>¥<?= h(format_money($item['amount_jpy'])) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- 請求書 PDF -->
  <div class="card form-card mt-24">
    <div class="section-heading">請求書</div>
    <div class="actions-inline">
      <?php if (!empty($invoice['invoice_pdf_path'])): ?>
        <a class="ghost-btn" target="_blank"
           href="<?= h($baseUrl) ?>/download.php?kind=invoice&id=<?= (int)$invoice['id'] ?>">
          請求書を開く (PDF)
        </a>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="regenerate_invoice_pdf">
        <button class="primary-btn" type="submit">請求書PDFを再出力する</button>
      </form>
    </div>
  </div>

  <!-- 入金処理 -->
  <div class="card form-card mt-24">
    <div class="section-heading">入金処理</div>
    <?php if ($invoice['status'] === 'issued'): ?>
      <form method="post" data-confirm="この請求を入金済みにしますか？">
        <input type="hidden" name="action" value="mark_paid">
        <div class="actions-inline">
          <button class="warning-btn" type="submit">入金済みにする</button>
          <span style="font-size:11px;color:var(--sub);">入金済みにするとステータスが変更されます</span>
        </div>
      </form>
    <?php else: ?>
      <span class="muted">すでに入金処理済みです。</span>
    <?php endif; ?>
  </div>

  <!-- 領収書 -->
  <div class="card form-card mt-24">
    <div class="section-heading">領収書</div>
    <div class="actions-inline">
      <?php if (!empty($invoice['receipt']) && !empty($invoice['receipt']['receipt_pdf_path'])): ?>
        <a class="ghost-btn" target="_blank"
           href="<?= h($baseUrl) ?>/download.php?kind=receipt&id=<?= (int)$invoice['id'] ?>">
          領収書を開く (PDF)
        </a>
      <?php endif; ?>
      <form method="post" data-confirm="領収書を発行（または再発行）しますか？">
        <input type="hidden" name="action" value="issue_receipt">
        <button class="primary-btn" type="submit">
          <?= empty($invoice['receipt']) ? '領収書を発行する' : '領収書を再発行する' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- 危険操作 -->
  <div class="card form-card mt-24" style="border-color:rgba(220,38,38,.25);">
    <div class="section-heading" style="color:var(--danger);">危険操作</div>
    <form method="post" data-confirm="この請求を完全に削除しますか？ 元に戻せません。">
      <input type="hidden" name="action" value="delete">
      <button class="danger-btn" type="submit">この請求を完全に削除する</button>
    </form>
  </div>

</main>
<?php end_page(); ?>
