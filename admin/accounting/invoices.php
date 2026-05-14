<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$division = trim($_GET['division'] ?? '');
$status   = trim($_GET['status'] ?? '');

$sql = '
    SELECT i.*, COALESCE(c.name, t.name, \'—\') AS party_name, r.receipt_pdf_path
    FROM accounting_invoices i
    LEFT JOIN talents t ON t.id = i.talent_id
    LEFT JOIN clients c ON c.id = i.client_id
    LEFT JOIN accounting_receipts r ON r.invoice_id = i.id
    WHERE 1=1
';
$params = [];
if ($division !== '') { $sql .= ' AND i.division = ?'; $params[] = $division; }
if ($status !== '')   { $sql .= ' AND i.status = ?';   $params[] = $status; }
$sql .= ' ORDER BY i.created_at DESC, i.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$divisions = ['production' => 'Production', 'business' => 'Business', 'creative' => 'Creative'];
$statuses  = ['issued' => '発行済', 'paid' => '入金済', 'receipt_issued' => '領収書発行済'];

start_page('請求管理', '全事業部の請求書・入金・領収書を管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div><h1>請求管理</h1><p>事業部を絞り込んで請求書を管理します。</p></div>
    <div class="actions-inline">
      <a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=revenue&division=production">Production 収益請求</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=manual&division=business">Business 請求書</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=manual&division=creative">Creative 請求書</a>
    </div>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label><span>事業部</span>
      <select name="division">
        <option value="">すべて</option>
        <?php foreach ($divisions as $val => $label): ?>
          <option value="<?= h($val) ?>" <?= selected($division, $val) ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="form-grid two" style="gap:10px;">
      <label><span>ステータス</span>
        <select name="status">
          <option value="">すべて</option>
          <?php foreach ($statuses as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= selected($status, $val) ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="actions-inline" style="align-self:end;">
        <button class="ghost-btn" type="submit">検索</button>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">リセット</a>
      </div>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead><tr><th>請求書番号</th><th>事業部</th><th>宛先</th><th>締め年月</th><th>件名</th><th>請求額</th><th>状態</th><th>領収書</th><th>入金日</th><th>操作</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="10" class="empty-state">まだ請求データがありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= h($row['invoice_no']) ?></td>
            <td><span class="status-badge muted"><?= h(accounting_division_label($row['division'] ?? 'production')) ?></span></td>
            <td><?= h($row['party_name']) ?></td>
            <td><?= h(sprintf('%04d-%02d', $row['close_year'], $row['close_month'])) ?></td>
            <td><?= h($row['subject']) ?></td>
            <td class="text-right">¥<?= h(format_money($row['amount_jpy'])) ?></td>
            <td><span class="status-badge <?= status_badge_class($row['status']) ?>"><?= h(invoice_status_label($row['status'])) ?></span></td>
            <td><?= !empty($row['receipt_pdf_path']) ? 'あり' : '未発行' ?></td>
            <td><?= h(format_datetime($row['paid_at'] ?? '')) ?></td>
            <td><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_detail.php?id=<?= urlencode((string)$row['id']) ?>">詳細</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php end_page(); ?>
