<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent   = current_portal_talent();
$info     = portal_get_talent_info($pdo, $talent['talent_id']);
$history  = portal_fetch_revenue_history($pdo, $talent['talent_id']);
$notices  = portal_fetch_notices($pdo);

// 直近の月（今月・先月）の状況を集計
$now          = new DateTime();
$thisYear     = (int)$now->format('Y');
$thisMonth    = (int)$now->format('n');
$prevDt       = clone $now;
$prevDt->modify('-1 month');
$prevYear     = (int)$prevDt->format('Y');
$prevMonth    = (int)$prevDt->format('n');

$latestPending = 0;
$latestConfirmed = 0;
foreach ($history as $row) {
    if ($row['status'] === 'pending')   $latestPending++;
    if ($row['status'] === 'confirmed') $latestConfirmed++;
}

$portalPageTitle = 'ホーム';
require __DIR__ . '/_header.php';
?>

<h1 class="portal-page-title">ようこそ、<?= portal_h($talent['talent_name']) ?> さん</h1>

<?php if ($notices): ?>
<div class="portal-card">
  <div class="portal-card-title">お知らせ</div>
  <?php foreach ($notices as $notice): ?>
    <div class="portal-notice">
      <div class="portal-notice-title"><?= portal_h($notice['title']) ?></div>
      <div class="portal-notice-body"><?= portal_h($notice['body']) ?></div>
      <div class="portal-notice-date"><?= portal_h(substr($notice['published_at'] ?? $notice['created_at'], 0, 10)) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="portal-grid-3" style="margin-bottom:24px;">
  <div class="portal-stat">
    <div class="portal-stat-label">提出済み月数</div>
    <div class="portal-stat-value"><?= count($history) ?></div>
    <div class="portal-stat-unit">件</div>
  </div>
  <div class="portal-stat">
    <div class="portal-stat-label">確認待ち</div>
    <div class="portal-stat-value"><?= $latestPending ?></div>
    <div class="portal-stat-unit">件</div>
  </div>
  <div class="portal-stat">
    <div class="portal-stat-label">確定済み</div>
    <div class="portal-stat-value"><?= $latestConfirmed ?></div>
    <div class="portal-stat-unit">件</div>
  </div>
</div>

<div class="portal-grid-2">
  <div class="portal-card">
    <div class="portal-card-title">月次収益を報告する</div>
    <p style="font-size:13px;color:var(--sub);margin:0 0 16px;">
      配信収益・グッズ売上・スポンサー収入をまとめて報告できます。<br>
      エビデンス（スクリーンショット等）も一緒に添付してください。
    </p>
    <a class="portal-btn portal-btn-primary portal-btn-sm" href="<?= portal_h($portalBase) ?>/submit.php">
      収益を報告する →
    </a>
  </div>

  <div class="portal-card">
    <div class="portal-card-title">請求書・領収書</div>
    <p style="font-size:13px;color:var(--sub);margin:0 0 16px;">
      過去に発行された請求書・領収書を確認・ダウンロードできます。
    </p>
    <a class="portal-btn portal-btn-outline portal-btn-sm" href="<?= portal_h($portalBase) ?>/invoices.php">
      書類を確認する →
    </a>
  </div>

  <div class="portal-card">
    <div class="portal-card-title">登録情報</div>
    <p style="font-size:13px;color:var(--sub);margin:0 0 16px;">
      本名・連絡先・住所・振込口座を更新できます。管理画面のタレント情報にも反映されます。
    </p>
    <a class="portal-btn portal-btn-outline portal-btn-sm" href="<?= portal_h($portalBase) ?>/settings.php">
      設定を開く →
    </a>
  </div>
</div>

<?php if ($history): ?>
<div class="portal-card">
  <div class="portal-card-title">直近の提出履歴</div>
  <div class="portal-table-wrap">
    <table class="portal-table">
      <thead>
        <tr>
          <th>年月</th>
          <th>通貨</th>
          <th class="text-right">配信</th>
          <th class="text-right">グッズ</th>
          <th class="text-right">スポンサー</th>
          <th>状態</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($history, 0, 5) as $row):
          $st = portal_revenue_status($row['status']);
          $sum = (float)$row['amount_streaming'] + (float)$row['amount_goods'] + (float)$row['amount_sponsor'];
        ?>
          <tr>
            <td><?= portal_h(sprintf('%04d年%d月', $row['year'], $row['month'])) ?></td>
            <td><?= portal_h($row['currency']) ?></td>
            <td class="text-right"><?= portal_h(portal_format_money($row['amount_streaming'])) ?></td>
            <td class="text-right"><?= portal_h(portal_format_money($row['amount_goods'])) ?></td>
            <td class="text-right"><?= portal_h(portal_format_money($row['amount_sponsor'])) ?></td>
            <td><span class="badge <?= portal_h($st['class']) ?>"><?= portal_h($st['label']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($history) > 5): ?>
    <div style="margin-top:12px;">
      <a class="portal-btn portal-btn-outline portal-btn-sm" href="<?= portal_h($portalBase) ?>/history.php">すべての履歴を見る</a>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
