<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();
$prefillProjectId = trim((string)($_GET['project_id'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cp_verify_csrf($_POST['_csrf'] ?? '')) {
        cp_flash_set('error', '不正なリクエストです。ページを再読み込みしてください。');
        cp_redirect($creativePortalBase . '/billing.php');
    }
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'invoice') {
        $result = cp_submit_invoice($pdo, $creator['creator_id'], (int)$creator['id'], $_POST, $_FILES['invoice_file'] ?? []);
        cp_flash_set(!empty($result['success']) ? 'success' : 'error', !empty($result['success']) ? '請求書を提出しました。' : ($result['error'] ?? '提出に失敗しました。'));
    } elseif ($action === 'receipt') {
        $result = cp_submit_receipt($pdo, $creator['creator_id'], (int)$creator['id'], (int)($_POST['invoice_id'] ?? 0), $_FILES['receipt_file'] ?? []);
        cp_flash_set(!empty($result['success']) ? 'success' : 'error', !empty($result['success']) ? '領収書を提出しました。' : ($result['error'] ?? '提出に失敗しました。'));
    }
    cp_redirect($creativePortalBase . '/billing.php');
}

$projects = cp_fetch_projects($pdo, $creator['creator_id'], 100);
$statements = cp_fetch_statements($pdo, $creator['creator_id'], 100);
$invoices = cp_fetch_invoices($pdo, $creator['creator_id'], 100);

cp_start_page('支払・請求', '支払明細、請求書、領収書をまとめて管理します。');
?>
<div class="cp-grid aside">
  <div class="cp-grid">
    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>支払明細</h2>
          <p>CORO PROJECTから発行された支払予定・支払済の明細です。</p>
        </div>
      </div>
      <div class="cp-table-wrap">
        <table class="cp-table">
          <thead>
            <tr>
              <th>件名</th>
              <th>対象</th>
              <th>支払予定</th>
              <th class="cp-text-right">税込支払額</th>
              <th>状態</th>
              <th>書類</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$statements): ?>
            <tr><td colspan="6" class="cp-empty">支払明細はまだありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($statements as $statement): $st = cp_statement_status($statement['status']); ?>
            <tr>
              <td>
                <strong><?= cp_h($statement['subject']) ?></strong>
                <?php if (!empty($statement['portal_note'])): ?>
                  <div class="cp-muted cp-small"><?= nl2br(cp_h($statement['portal_note'])) ?></div>
                <?php endif; ?>
              </td>
              <td><?= cp_h($statement['project_title'] ?: ($statement['statement_month'] ?: '—')) ?></td>
              <td><?= cp_h(cp_format_date($statement['scheduled_at'] ?: $statement['paid_at'])) ?></td>
              <td class="cp-text-right"><?= cp_h(cp_format_money($statement['net_amount'], $statement['currency'])) ?></td>
              <td><span class="cp-badge <?= cp_h($st['class']) ?>"><?= cp_h($st['label']) ?></span></td>
              <td>
                <div class="cp-actions">
                  <?php if (!empty($statement['statement_file_path'])): ?>
                    <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/download.php?type=statement&id=<?= (int)$statement['id'] ?>">明細</a>
                  <?php endif; ?>
                  <?php if (!empty($statement['receipt_file_path'])): ?>
                    <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/download.php?type=statement_receipt&id=<?= (int)$statement['id'] ?>">領収書</a>
                  <?php endif; ?>
                  <?php if (empty($statement['statement_file_path']) && empty($statement['receipt_file_path'])): ?>
                    <span class="cp-muted">—</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>提出した請求書</h2>
          <p>請求書の確認状況と領収書提出状況です。</p>
        </div>
      </div>
      <div class="cp-table-wrap">
        <table class="cp-table">
          <thead>
            <tr>
              <th>請求書</th>
              <th>案件</th>
              <th>請求日</th>
              <th class="cp-text-right">請求額</th>
              <th>状態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$invoices): ?>
            <tr><td colspan="6" class="cp-empty">提出済みの請求書はありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($invoices as $invoice): $st = cp_invoice_status($invoice['status']); ?>
            <tr>
              <td>
                <strong><?= cp_h($invoice['invoice_no'] ?: '請求書 #' . $invoice['id']) ?></strong>
                <?php if (!empty($invoice['admin_note'])): ?>
                  <div class="cp-alert cp-mt"><strong>確認コメント</strong><?= nl2br(cp_h($invoice['admin_note'])) ?></div>
                <?php endif; ?>
              </td>
              <td><?= cp_h($invoice['project_title'] ?: '—') ?></td>
              <td><?= cp_h(cp_format_date($invoice['invoice_date'] ?: $invoice['created_at'])) ?></td>
              <td class="cp-text-right"><?= cp_h(cp_format_money($invoice['total_amount'], $invoice['currency'])) ?></td>
              <td><span class="cp-badge <?= cp_h($st['class']) ?>"><?= cp_h($st['label']) ?></span></td>
              <td>
                <div class="cp-actions">
                  <?php if (!empty($invoice['invoice_file_path'])): ?>
                    <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/download.php?type=invoice&id=<?= (int)$invoice['id'] ?>">請求書</a>
                  <?php endif; ?>
                  <?php if (!empty($invoice['receipt_file_path'])): ?>
                    <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/download.php?type=invoice_receipt&id=<?= (int)$invoice['id'] ?>">領収書</a>
                  <?php elseif (in_array((string)$invoice['status'], ['approved', 'paid'], true)): ?>
                    <form method="post" enctype="multipart/form-data" class="cp-actions">
                      <input type="hidden" name="action" value="receipt">
                      <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">
                      <input type="file" name="receipt_file" required>
                      <button class="cp-btn-muted" type="submit">領収書提出</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <aside class="cp-card">
    <div class="cp-card-head">
      <div>
        <h2>請求書を提出</h2>
        <p>PDFまたは画像の請求書を提出できます。</p>
      </div>
    </div>
    <form method="post" enctype="multipart/form-data" class="cp-card-pad cp-form">
      <input type="hidden" name="action" value="invoice">
      <label>
        <span class="cp-label">案件</span>
        <select name="project_id">
          <option value="">案件に紐づけない</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= cp_h($project['id']) ?>" <?= (string)$prefillProjectId === (string)$project['id'] ? 'selected' : '' ?>>
              <?= cp_h($project['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">請求書番号</span>
          <input type="text" name="invoice_no" placeholder="任意">
        </label>
        <label>
          <span class="cp-label">請求日</span>
          <input type="date" name="invoice_date" value="<?= cp_h(date('Y-m-d')) ?>">
        </label>
      </div>
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">税抜金額</span>
          <input type="number" name="amount" step="1" min="0" required>
        </label>
        <label>
          <span class="cp-label">消費税</span>
          <input type="number" name="tax_amount" step="1" min="0" value="0">
        </label>
      </div>
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">源泉徴収</span>
          <input type="number" name="withholding_amount" step="1" min="0" value="0">
        </label>
        <label>
          <span class="cp-label">合計支払額</span>
          <input type="number" name="total_amount" step="1" min="0" placeholder="未入力なら自動計算">
        </label>
      </div>
      <label>
        <span class="cp-label">請求書ファイル</span>
        <input type="file" name="invoice_file" required>
        <span class="cp-help">PDF, jpg, png, webp / 20MBまで</span>
      </label>
      <button class="cp-btn" type="submit">請求書を提出</button>
    </form>
  </aside>
</div>
<?php cp_end_page(); ?>
