<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'review_invoice') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'pending'));
        $adminNote = mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 3000);
        if (!in_array($status, ['pending', 'approved', 'rejected', 'paid', 'receipt_received'], true)) {
            $status = 'pending';
        }
        try {
            $pdo->prepare('
                UPDATE creative_project_invoices
                SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ')->execute([$status, $adminNote, (int)$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'update', 'creative_project_invoice', $id, 'Creative請求書を確認しました: ' . $status);
            set_flash('success', '請求書の確認状態を更新しました。');
        } catch (Exception $e) {
            set_flash('error', '請求書の更新に失敗しました: ' . $e->getMessage());
        }
    } elseif ($action === 'create_statement') {
        try {
            $creatorId = trim((string)($_POST['creator_id'] ?? ''));
            $projectId = trim((string)($_POST['project_id'] ?? ''));
            $subject = mb_substr(trim((string)($_POST['subject'] ?? '')), 0, 255);
            if ($creatorId === '' || $subject === '') {
                throw new RuntimeException('クリエイターと件名は必須です。');
            }
            $amount = (float)($_POST['amount'] ?? 0);
            $taxAmount = (float)($_POST['tax_amount'] ?? 0);
            $withholdingAmount = (float)($_POST['withholding_amount'] ?? 0);
            $adjustmentAmount = (float)($_POST['adjustment_amount'] ?? 0);
            $netAmount = isset($_POST['net_amount']) && $_POST['net_amount'] !== '' ? (float)$_POST['net_amount'] : ($amount + $taxAmount - $withholdingAmount + $adjustmentAmount);
            $statementUpload = creative_portal_upload_document($_FILES['statement_file'] ?? [], 'statements', $creatorId . '-statement', ['pdf', 'jpg', 'jpeg', 'png', 'webp']);
            $receiptUpload = creative_portal_upload_document($_FILES['receipt_file'] ?? [], 'statement_receipts', $creatorId . '-receipt', ['pdf', 'jpg', 'jpeg', 'png', 'webp']);

            $pdo->prepare('
                INSERT INTO creative_payment_statements
                    (creator_id, project_id, statement_no, statement_month, subject,
                     amount, tax_amount, withholding_amount, adjustment_amount, net_amount,
                     currency, scheduled_at, paid_at, status,
                     statement_file_path, statement_original_name, receipt_file_path, receipt_original_name,
                     portal_note, created_by, updated_by, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "JPY", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ')->execute([
                $creatorId,
                $projectId !== '' ? $projectId : null,
                trim((string)($_POST['statement_no'] ?? '')) ?: null,
                trim((string)($_POST['statement_month'] ?? '')) ?: null,
                $subject,
                $amount,
                $taxAmount,
                $withholdingAmount,
                $adjustmentAmount,
                $netAmount,
                trim((string)($_POST['scheduled_at'] ?? '')) ?: null,
                trim((string)($_POST['paid_at'] ?? '')) ?: null,
                trim((string)($_POST['status'] ?? 'scheduled')) ?: 'scheduled',
                $statementUpload['path'] ?? null,
                $statementUpload['original_name'] ?? null,
                $receiptUpload['path'] ?? null,
                $receiptUpload['original_name'] ?? null,
                trim((string)($_POST['portal_note'] ?? '')) ?: null,
                (int)$user['id'],
                (int)$user['id'],
            ]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'creative_payment_statement', 0, 'Creative支払明細を作成しました');
            set_flash('success', '支払明細を作成しました。');
        } catch (Exception $e) {
            set_flash('error', '支払明細の作成に失敗しました: ' . $e->getMessage());
        }
    } elseif ($action === 'update_statement') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $statementUpload = creative_portal_upload_document($_FILES['statement_file'] ?? [], 'statements', 'statement-' . $id, ['pdf', 'jpg', 'jpeg', 'png', 'webp']);
            $receiptUpload = creative_portal_upload_document($_FILES['receipt_file'] ?? [], 'statement_receipts', 'receipt-' . $id, ['pdf', 'jpg', 'jpeg', 'png', 'webp']);
            $sets = [
                'status = ?',
                'scheduled_at = ?',
                'paid_at = ?',
                'portal_note = ?',
                'updated_by = ?',
                'updated_at = NOW()',
            ];
            $params = [
                trim((string)($_POST['status'] ?? 'scheduled')) ?: 'scheduled',
                trim((string)($_POST['scheduled_at'] ?? '')) ?: null,
                trim((string)($_POST['paid_at'] ?? '')) ?: null,
                trim((string)($_POST['portal_note'] ?? '')) ?: null,
                (int)$user['id'],
            ];
            if ($statementUpload) {
                $sets[] = 'statement_file_path = ?';
                $sets[] = 'statement_original_name = ?';
                $params[] = $statementUpload['path'];
                $params[] = $statementUpload['original_name'];
            }
            if ($receiptUpload) {
                $sets[] = 'receipt_file_path = ?';
                $sets[] = 'receipt_original_name = ?';
                $params[] = $receiptUpload['path'];
                $params[] = $receiptUpload['original_name'];
            }
            $params[] = $id;
            $pdo->prepare('UPDATE creative_payment_statements SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
            write_admin_log($pdo, (int)$user['id'], 'update', 'creative_payment_statement', $id, 'Creative支払明細を更新しました');
            set_flash('success', '支払明細を更新しました。');
        } catch (Exception $e) {
            set_flash('error', '支払明細の更新に失敗しました: ' . $e->getMessage());
        }
    } elseif ($action === 'delete_statement') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM creative_payment_statements WHERE id = ?')->execute([$id]);
            write_admin_log($pdo, (int)$user['id'], 'delete', 'creative_payment_statement', $id, 'Creative支払明細を削除しました');
            set_flash('success', '支払明細を削除しました。');
        } catch (Exception $e) {
            set_flash('error', '支払明細の削除に失敗しました。');
        }
    }
    redirect_to($baseUrl . '/creative/portal_billing.php');
}

$invoiceReady = admin_table_has_column($pdo, 'creative_project_invoices', 'id');
$statementReady = admin_table_has_column($pdo, 'creative_payment_statements', 'id');
$invoices = [];
$statements = [];
$creators = $pdo->query('SELECT id, name FROM cre_creators ORDER BY name ASC')->fetchAll();
$projects = $pdo->query('
    SELECT p.id, p.title, p.creator_id, c.name AS creator_name
    FROM cre_projects p
    LEFT JOIN cre_creators c ON c.id = p.creator_id
    ORDER BY p.updated_at DESC
    LIMIT 300
')->fetchAll();

if ($invoiceReady) {
    $invoices = $pdo->query('
        SELECT i.*, c.name AS creator_name, p.title AS project_title
        FROM creative_project_invoices i
        LEFT JOIN cre_creators c ON c.id = i.creator_id
        LEFT JOIN cre_projects p ON p.id = i.project_id
        ORDER BY i.created_at DESC, i.id DESC
        LIMIT 300
    ')->fetchAll();
}
if ($statementReady) {
    $statements = $pdo->query('
        SELECT s.*, c.name AS creator_name, p.title AS project_title
        FROM creative_payment_statements s
        LEFT JOIN cre_creators c ON c.id = s.creator_id
        LEFT JOIN cre_projects p ON p.id = s.project_id
        ORDER BY COALESCE(s.scheduled_at, s.paid_at, s.created_at) DESC, s.id DESC
        LIMIT 300
    ')->fetchAll();
}

start_page('Creative支払・請求', '請求書の確認、支払明細の作成、領収書ファイルを管理します。');
?>
<main class="page-container">
  <?php if (!$invoiceReady || !$statementReady): ?>
    <div class="card alert-box alert-error">Creative支払・請求用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block">
    <h1>Creative支払・請求</h1>
    <p>デザイナーが提出した請求書と、CORO PROJECT側で作成する支払明細を管理します。</p>
  </section>

  <section class="card form-card">
    <h2 class="section-heading">支払明細を作成</h2>
    <form method="post" enctype="multipart/form-data" class="form-stack">
      <input type="hidden" name="action" value="create_statement">
      <div class="form-grid two">
        <label>
          <span>クリエイター</span>
          <select name="creator_id" required>
            <option value="">選択してください</option>
            <?php foreach ($creators as $creator): ?>
              <option value="<?= h($creator['id']) ?>"><?= h($creator['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>案件</span>
          <select name="project_id">
            <option value="">紐づけなし</option>
            <?php foreach ($projects as $project): ?>
              <option value="<?= h($project['id']) ?>"><?= h($project['title']) ?> / <?= h($project['creator_name'] ?: '-') ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="form-grid two">
        <label><span>件名</span><input type="text" name="subject" required placeholder="例：2026年5月 イラスト制作報酬"></label>
        <label><span>明細番号</span><input type="text" name="statement_no" placeholder="任意"></label>
      </div>
      <div class="form-grid two">
        <label><span>対象月</span><input type="text" name="statement_month" placeholder="2026-05"></label>
        <label><span>状態</span>
          <select name="status">
            <option value="scheduled">支払予定</option>
            <option value="paid">支払済</option>
            <option value="cancelled">取消</option>
          </select>
        </label>
      </div>
      <div class="form-grid two">
        <label><span>税抜金額</span><input type="number" step="1" name="amount" value="0"></label>
        <label><span>消費税</span><input type="number" step="1" name="tax_amount" value="0"></label>
        <label><span>源泉徴収</span><input type="number" step="1" name="withholding_amount" value="0"></label>
        <label><span>調整額</span><input type="number" step="1" name="adjustment_amount" value="0"></label>
        <label><span>税込支払額</span><input type="number" step="1" name="net_amount" placeholder="未入力なら自動計算"></label>
        <label><span>支払予定日</span><input type="date" name="scheduled_at"></label>
        <label><span>支払日</span><input type="date" name="paid_at"></label>
      </div>
      <div class="form-grid two">
        <label><span>支払明細ファイル</span><input type="file" name="statement_file"></label>
        <label><span>領収書ファイル</span><input type="file" name="receipt_file"></label>
      </div>
      <label><span>ポータル表示メモ</span><textarea name="portal_note" rows="2"></textarea></label>
      <div class="actions-inline">
        <button class="primary-btn" type="submit">明細を作成</button>
      </div>
    </form>
  </section>

  <section class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>件名</th><th>クリエイター</th><th>案件</th><th>支払予定/支払日</th><th>金額</th><th>状態</th><th>書類</th><th style="min-width:300px;">更新</th></tr>
        </thead>
        <tbody>
        <?php if (!$statements): ?>
          <tr><td colspan="8" class="empty-state">支払明細はありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($statements as $statement): ?>
          <tr>
            <td><strong><?= h($statement['subject']) ?></strong><div class="muted" style="font-size:12px;"><?= h($statement['statement_no'] ?? '') ?></div></td>
            <td><?= h($statement['creator_name'] ?: $statement['creator_id']) ?></td>
            <td><?= h($statement['project_title'] ?: '-') ?></td>
            <td><?= h($statement['scheduled_at'] ?: $statement['paid_at'] ?: '-') ?></td>
            <td><?= h(format_money($statement['net_amount'])) ?>円</td>
            <td><span class="status-badge <?= h(creative_billing_badge($statement['status'])) ?>"><?= h(creative_portal_statement_status_label($statement['status'])) ?></span></td>
            <td class="actions-inline">
              <?php if (!empty($statement['statement_file_path'])): ?>
                <a class="ghost-btn" href="<?= h($baseUrl) ?>/download.php?kind=creative_statement&id=<?= (int)$statement['id'] ?>">明細</a>
              <?php endif; ?>
              <?php if (!empty($statement['receipt_file_path'])): ?>
                <a class="ghost-btn" href="<?= h($baseUrl) ?>/download.php?kind=creative_statement_receipt&id=<?= (int)$statement['id'] ?>">領収書</a>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" enctype="multipart/form-data" class="form-stack" style="gap:8px;">
                <input type="hidden" name="action" value="update_statement">
                <input type="hidden" name="id" value="<?= (int)$statement['id'] ?>">
                <div class="form-grid two" style="gap:8px;">
                  <label style="margin:0;"><span>状態</span>
                    <select name="status">
                      <option value="scheduled" <?= selected($statement['status'], 'scheduled') ?>>支払予定</option>
                      <option value="paid" <?= selected($statement['status'], 'paid') ?>>支払済</option>
                      <option value="cancelled" <?= selected($statement['status'], 'cancelled') ?>>取消</option>
                    </select>
                  </label>
                  <label style="margin:0;"><span>支払予定日</span><input type="date" name="scheduled_at" value="<?= h($statement['scheduled_at'] ?? '') ?>"></label>
                  <label style="margin:0;"><span>支払日</span><input type="date" name="paid_at" value="<?= h($statement['paid_at'] ?? '') ?>"></label>
                </div>
                <label style="margin:0;"><span>メモ</span><textarea name="portal_note" rows="2"><?= h($statement['portal_note'] ?? '') ?></textarea></label>
                <div class="form-grid two" style="gap:8px;">
                  <label style="margin:0;"><span>明細差替</span><input type="file" name="statement_file"></label>
                  <label style="margin:0;"><span>領収書差替</span><input type="file" name="receipt_file"></label>
                </div>
                <div class="actions-inline">
                  <button class="primary-btn" type="submit">更新</button>
                </div>
              </form>
              <form method="post" data-confirm="この支払明細を削除しますか？" style="margin-top:8px;">
                <input type="hidden" name="action" value="delete_statement">
                <input type="hidden" name="id" value="<?= (int)$statement['id'] ?>">
                <button class="danger-btn" type="submit">削除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>提出日</th><th>クリエイター</th><th>案件</th><th>請求書</th><th>金額</th><th>状態</th><th>書類</th><th style="min-width:320px;">確認</th></tr>
        </thead>
        <tbody>
        <?php if (!$invoices): ?>
          <tr><td colspan="8" class="empty-state">提出された請求書はありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($invoices as $invoice): ?>
          <tr>
            <td><?= h(format_datetime($invoice['created_at'])) ?></td>
            <td><?= h($invoice['creator_name'] ?: $invoice['creator_id']) ?></td>
            <td><?= h($invoice['project_title'] ?: '-') ?></td>
            <td><strong><?= h($invoice['invoice_no'] ?: '請求書 #' . $invoice['id']) ?></strong><div class="muted" style="font-size:12px;"><?= h($invoice['invoice_date'] ?: '') ?></div></td>
            <td><?= h(format_money($invoice['total_amount'])) ?>円</td>
            <td><span class="status-badge <?= h(creative_billing_badge($invoice['status'])) ?>"><?= h(creative_portal_review_status_label($invoice['status'])) ?></span></td>
            <td class="actions-inline">
              <?php if (!empty($invoice['invoice_file_path'])): ?>
                <a class="ghost-btn" href="<?= h($baseUrl) ?>/download.php?kind=creative_invoice&id=<?= (int)$invoice['id'] ?>">請求書</a>
              <?php endif; ?>
              <?php if (!empty($invoice['receipt_file_path'])): ?>
                <a class="ghost-btn" href="<?= h($baseUrl) ?>/download.php?kind=creative_invoice_receipt&id=<?= (int)$invoice['id'] ?>">領収書</a>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" class="form-stack" style="gap:8px;">
                <input type="hidden" name="action" value="review_invoice">
                <input type="hidden" name="id" value="<?= (int)$invoice['id'] ?>">
                <div class="form-grid two" style="gap:8px;">
                  <label style="margin:0;"><span>状態</span>
                    <select name="status">
                      <option value="pending" <?= selected($invoice['status'], 'pending') ?>>確認待ち</option>
                      <option value="approved" <?= selected($invoice['status'], 'approved') ?>>確認済</option>
                      <option value="rejected" <?= selected($invoice['status'], 'rejected') ?>>差し戻し</option>
                      <option value="paid" <?= selected($invoice['status'], 'paid') ?>>支払済</option>
                      <option value="receipt_received" <?= selected($invoice['status'], 'receipt_received') ?>>領収書受領</option>
                    </select>
                  </label>
                  <div class="actions-inline" style="align-self:end;"><button class="primary-btn" type="submit">更新</button></div>
                </div>
                <label style="margin:0;"><span>確認コメント</span><textarea name="admin_note" rows="2"><?= h($invoice['admin_note'] ?? '') ?></textarea></label>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php
end_page();

function creative_billing_badge($status) {
    switch ((string)$status) {
        case 'paid':
        case 'receipt_received':
        case 'approved':
            return 'success';
        case 'rejected':
        case 'cancelled':
            return 'danger';
        case 'pending':
        case 'scheduled':
        default:
            return 'warning';
    }
}
?>
