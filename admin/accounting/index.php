<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$unpaid        = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices WHERE status = 'issued'")->fetchColumn();
$receiptPending = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices WHERE status = 'paid'")->fetchColumn();
$invoiceTotal  = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices")->fetchColumn();
$journalTotal  = (int)$pdo->query("SELECT COUNT(*) FROM accounting_journal_entries")->fetchColumn();

$divStats = $pdo->query("
    SELECT division,
           COUNT(*) AS total,
           SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) AS unpaid,
           SUM(CASE WHEN status IN ('paid','receipt_issued') THEN amount_jpy ELSE 0 END) AS paid_amount
    FROM accounting_invoices
    GROUP BY division
")->fetchAll();
$divMap = [];
foreach ($divStats as $d) {
    $divMap[$d['division']] = $d;
}

$recentInvoices = $pdo->query("
    SELECT i.id, i.invoice_no, i.subject, i.amount_jpy, i.status, i.division,
           COALESCE(c.name, t.name, '—') AS party_name
    FROM accounting_invoices i
    LEFT JOIN talents t ON t.id = i.talent_id
    LEFT JOIN clients c ON c.id = i.client_id
    ORDER BY i.created_at DESC LIMIT 8
")->fetchAll();

start_page('会計ダッシュボード', '全事業部の請求・入金・記帳状況を確認できます。');
?>
<main class="page-container">
  <section class="card-grid two">
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php?status=issued">
      <div class="muted">未入金</div><div class="stat-number"><?= h((string)$unpaid) ?></div><p>請求済みで入金待ちの請求書</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php?status=paid">
      <div class="muted">領収書未発行</div><div class="stat-number"><?= h((string)$receiptPending) ?></div><p>入金済みで領収書が未発行</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php">
      <div class="muted">請求書総数</div><div class="stat-number"><?= h((string)$invoiceTotal) ?></div><p>全事業部の累計請求書数</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/journals.php">
      <div class="muted">記帳件数</div><div class="stat-number"><?= h((string)$journalTotal) ?></div><p>自動記帳 + 手入力の合計</p>
    </a>
  </section>

  <section class="card-grid three mt-24">
    <?php foreach (['production' => 'Production', 'business' => 'Business', 'creative' => 'Creative'] as $div => $label): ?>
      <?php $d = $divMap[$div] ?? ['total' => 0, 'unpaid' => 0, 'paid_amount' => 0]; ?>
      <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/invoices.php?division=<?= h($div) ?>">
        <h3><?= h($label) ?></h3>
        <p>請求書 <strong><?= h((string)$d['total']) ?></strong> 件 ／ 未入金 <strong><?= h((string)$d['unpaid']) ?></strong> 件</p>
        <p class="muted">入金済み合計: ¥<?= h(format_money($d['paid_amount'])) ?></p>
      </a>
    <?php endforeach; ?>
  </section>

  <section class="card-grid three mt-24">
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/invoices.php"><h3>請求管理</h3><p>全事業部の請求書・入金・領収書を管理します。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/journals.php"><h3>記帳管理</h3><p>収入・支出・差引と手入力記帳を管理します。</p></a>
  </section>

  <?php if ($recentInvoices): ?>
  <section class="card mt-24">
    <h3>最近の請求書</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>請求書番号</th><th>事業部</th><th>宛先</th><th>件名</th><th>金額</th><th>状態</th></tr></thead>
        <tbody>
        <?php foreach ($recentInvoices as $inv): ?>
          <tr>
            <td><a href="<?= h($baseUrl) ?>/accounting/invoice_detail.php?id=<?= urlencode((string)$inv['id']) ?>"><?= h($inv['invoice_no']) ?></a></td>
            <td><span class="status-badge muted"><?= h(accounting_division_label($inv['division'])) ?></span></td>
            <td><?= h($inv['party_name']) ?></td>
            <td><?= h($inv['subject']) ?></td>
            <td class="text-right">¥<?= h(format_money($inv['amount_jpy'])) ?></td>
            <td><span class="status-badge <?= status_badge_class($inv['status']) ?>"><?= h(invoice_status_label($inv['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>
</main>
<?php end_page(); ?>
