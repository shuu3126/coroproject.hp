<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';
require_admin_login();
$page_title = '今日やること';
$page_description = '今日対応が必要な作業や、よく使う管理画面への入口です。';

$nowYear = (int)date('Y');
$nowMonth = (int)date('n');
$cntTalents = (int)$pdo->query('SELECT COUNT(*) FROM talents WHERE is_published = 1')->fetchColumn();
$unsubmitted = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounting_talents t WHERE t.status = 'active' AND NOT EXISTS (SELECT 1 FROM accounting_revenues r WHERE r.talent_id = t.id AND r.year = ? AND r.month = ?)");
    $stmt->execute([$nowYear, $nowMonth]);
    $unsubmitted = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $unsubmitted = 0;
}
$invoiceReady = 0;
try {
    $talents = $pdo->query("SELECT id FROM accounting_talents WHERE status = 'active' ORDER BY display_name ASC")->fetchAll();
    foreach ($talents as $t) {
        $st = $pdo->prepare("SELECT currency, SUM(amount_streaming + amount_goods + amount_sponsor) total FROM accounting_revenues r LEFT JOIN accounting_invoiced_months im ON im.talent_id = r.talent_id AND im.year = r.year AND im.month = r.month WHERE r.talent_id = ? AND im.id IS NULL GROUP BY currency");
        $st->execute([$t['id']]);
        $rows = $st->fetchAll();
        $totalJpy = 0.0;
        foreach ($rows as $r) {
            $sum = (float)((isset($r['total']) ? $r['total'] : 0));
            $totalJpy += strtoupper((string)$r['currency']) === 'USD' ? $sum * 150 : $sum;
        }
        if ($totalJpy * 0.3 >= 5000) $invoiceReady++;
    }
} catch (Exception $e) {
    $invoiceReady = 0;
}
$unpaid = 0;
$receiptPending = 0;
try {
    $unpaid = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices WHERE status = 'issued'")->fetchColumn();
    $receiptPending = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices WHERE status = 'paid'")->fetchColumn();
} catch (Exception $e) {
    $unpaid = 0;
    $receiptPending = 0;
}
$recentLogs = [];
try {
    $recentLogs = $pdo->query("SELECT l.created_at, l.summary, l.target_type, COALESCE(u.display_name, 'system') AS user_name FROM admin_logs l LEFT JOIN admin_users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 10")->fetchAll();
} catch (Exception $e) {
    $recentLogs = [];
}
require_once __DIR__ . '/_header.php';
?>
<main class="page-container">
  <section class="card-grid two">
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/revenues.php"><div class="muted">未提出収益</div><div class="stat-number"><?= h((string)$unsubmitted) ?></div><p>今月まだ収益が登録されていない会計タレント</p></a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php"><div class="muted">請求可能</div><div class="stat-number"><?= h((string)$invoiceReady) ?></div><p>30%換算で5,000円以上の対象</p></a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php"><div class="muted">未入金</div><div class="stat-number"><?= h((string)$unpaid) ?></div><p>請求済みで入金待ちの請求</p></a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php"><div class="muted">領収書未発行</div><div class="stat-number"><?= h((string)$receiptPending) ?></div><p>入金済みで領収書が未発行の請求</p></a>
  </section>

  <section class="card-grid three mt-24">
    <a class="card menu-card" href="<?= h($baseUrl) ?>/news.php"><h3>お知らせ管理</h3><p>ニュースの追加・編集・削除を行います。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/talents.php"><h3>タレント管理</h3><p>公開サイトのタレント情報を管理します。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/index.php"><h3>会計システム</h3><p>収益入力・請求管理・会計一覧へ進みます。</p></a>
  </section>

  <section class="card mt-24">
    <h3>最近更新したデータ</h3>
    <?php if (!$recentLogs): ?>
      <div class="empty-state">まだ操作ログがありません。</div>
    <?php else: ?>
      <div class="summary-list">
        <?php foreach ($recentLogs as $log): ?>
          <div class="summary-row"><span><?= h(format_datetime($log['created_at'])) ?> / <?= h($log['user_name']) ?></span><strong><?= h($log['summary']) ?></strong></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
