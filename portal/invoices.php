<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent   = current_portal_talent();
$invoices = portal_fetch_invoices($pdo, $talent['talent_id']);

function portal_invoice_status_label($status) {
    switch ((string)$status) {
        case 'issued':          return ['label' => '発行済',       'class' => 'badge-warning'];
        case 'paid':            return ['label' => '入金済',       'class' => 'badge-success'];
        case 'receipt_issued':  return ['label' => '領収書発行済', 'class' => 'badge-info'];
        default:                return ['label' => (string)$status, 'class' => 'badge-muted'];
    }
}

$portalPageTitle = '請求書・領収書';
require __DIR__ . '/_header.php';
?>

<h1 class="portal-page-title">請求書・領収書</h1>
<p class="portal-page-desc">発行済みの請求書と領収書の一覧です。PDFをダウンロードして保管してください。</p>

<div class="portal-card">
  <?php if (!$invoices): ?>
    <div class="portal-table-empty">まだ請求書・領収書がありません。</div>
  <?php else: ?>
  <div class="portal-table-wrap">
    <table class="portal-table">
      <thead>
        <tr>
          <th>請求書番号</th>
          <th>対象年月</th>
          <th>件名</th>
          <th class="text-right">金額（円）</th>
          <th>状態</th>
          <th>入金日</th>
          <th>請求書</th>
          <th>領収書</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv):
          $st = portal_invoice_status_label($inv['status']);
        ?>
          <tr>
            <td style="font-family:monospace;font-size:12px;"><?= portal_h($inv['invoice_no']) ?></td>
            <td><?= portal_h(sprintf('%04d年%d月', $inv['close_year'], $inv['close_month'])) ?></td>
            <td><?= portal_h($inv['subject']) ?></td>
            <td class="text-right">¥<?= portal_h(number_format((float)$inv['amount_jpy'])) ?></td>
            <td><span class="badge <?= portal_h($st['class']) ?>"><?= portal_h($st['label']) ?></span></td>
            <td style="font-size:12px;color:var(--sub);">
              <?= $inv['paid_at'] ? portal_h(substr($inv['paid_at'], 0, 10)) : '-' ?>
            </td>
            <td>
              <?php if ($inv['invoice_pdf_path']): ?>
                <a class="portal-btn portal-btn-outline portal-btn-sm"
                   href="<?= portal_h($portalBase) ?>/download.php?type=invoice&id=<?= (int)$inv['id'] ?>"
                   target="_blank" rel="noopener" download>
                  DL
                </a>
              <?php else: ?>
                <span style="color:var(--muted);font-size:12px;">未発行</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($inv['receipt_pdf_path']): ?>
                <a class="portal-btn portal-btn-primary portal-btn-sm"
                   href="<?= portal_h($portalBase) ?>/download.php?type=receipt&id=<?= (int)$inv['id'] ?>"
                   target="_blank" rel="noopener" download>
                  DL
                </a>
              <?php else: ?>
                <span style="color:var(--muted);font-size:12px;">未発行</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="portal-card" style="background:rgba(159,139,255,.05);">
  <div style="font-size:12px;color:var(--sub);">
    <strong style="color:var(--accent-2);">ご注意</strong><br>
    請求書・領収書は管理者が発行します。入金確認後に領収書が発行されます。<br>
    書類に関するご不明点は運営までお問い合わせください。
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
