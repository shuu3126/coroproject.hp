<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$settings = load_app_settings($pdo, $config);
$fx = (float)$settings['fx_default_rate'];
$talents = accounting_list_talents($pdo, false);
$revenueCount = (int)$pdo->query('SELECT COUNT(*) FROM accounting_revenues')->fetchColumn();
$invoiceCount = (int)$pdo->query('SELECT COUNT(*) FROM accounting_invoices')->fetchColumn();
$journalCount = (int)$pdo->query('SELECT COUNT(*) FROM accounting_journal_entries')->fetchColumn();
$readyCount = 0;
foreach ($talents as $talent) {
    $months = accounting_get_uninvoiced_months_upto($pdo, $talent['id'], 9999, 12);
    $sum = 0.0;
    foreach ($months as $m) {
        $sum += accounting_calc_office_share_jpy_for_month($pdo, $talent['id'], $m['year'], $m['month'], $fx);
    }
    if ($sum >= accounting_threshold_yen()) $readyCount++;
}
start_page('会計システム', '収益入力、請求管理、記帳管理の入口です。');
?>
<main class="page-container">
  <section class="card-grid two">
    <div class="card stat-card"><div class="muted">収益登録件数</div><div class="stat-number"><?= h((string)$revenueCount) ?></div><p>過去分を含む全収益データ</p></div>
    <div class="card stat-card"><div class="muted">請求候補</div><div class="stat-number"><?= h((string)$readyCount) ?></div><p>未請求の取り分が5,000円以上のタレント</p></div>
    <div class="card stat-card"><div class="muted">請求書件数</div><div class="stat-number"><?= h((string)$invoiceCount) ?></div><p>会計システムで発行済みの請求</p></div>
    <div class="card stat-card"><div class="muted">記帳件数</div><div class="stat-number"><?= h((string)$journalCount) ?></div><p>自動記帳 + 手入力の合計</p></div>
  </section>

  <section class="card-grid three mt-24">
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/revenues.php"><h3>収益入力</h3><p>タレント別の月次収益を登録します。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/invoices.php"><h3>請求管理</h3><p>請求書の作成、入金、領収書発行を管理します。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/journals.php"><h3>会計一覧</h3><p>収入・支出・差引と手入力記帳を管理します。</p></a>
  </section>
</main>
<?php end_page(); ?>
