<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent  = current_portal_talent();
$info    = portal_get_talent_info($pdo, $talent['talent_id']);
$history = portal_fetch_revenue_history($pdo, $talent['talent_id']);
$notices = portal_fetch_notices($pdo);
$revenueAlerts = portal_fetch_rejected_revenue_alerts($pdo, $talent['talent_id'], 3);
$twitchReports = portal_fetch_twitch_reports($pdo, $talent['talent_id'], 6);
$latestPublicRequest = portal_fetch_latest_public_profile_request($pdo, $talent['talent_id']);

$now = new DateTime();
$thisYear = (int)$now->format('Y');
$thisMonth = (int)$now->format('n');
$prev = (clone $now)->modify('-1 month');
$prevYear = (int)$prev->format('Y');
$prevMonth = (int)$prev->format('n');

$monthly = [];
$yearTotal = 0.0;
$pendingAmount = 0.0;
foreach ($history as $row) {
    $sum = (float)$row['amount_streaming'] + (float)$row['amount_goods'] + (float)$row['amount_sponsor'];
    if ((int)$row['year'] === $thisYear) $yearTotal += $sum;
    if (($row['status'] ?? '') === 'pending') $pendingAmount += $sum;
    $monthly[sprintf('%04d-%02d', $row['year'], $row['month'])] = $sum;
}
$currentKey = sprintf('%04d-%02d', $thisYear, $thisMonth);
$prevKey = sprintf('%04d-%02d', $prevYear, $prevMonth);
$currentRevenue = $monthly[$currentKey] ?? 0.0;
$prevRevenue = $monthly[$prevKey] ?? 0.0;
$growth = $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0.0;
$hasPrevTwitchReport = false;
foreach ($twitchReports as $report) {
    if ((int)$report['report_year'] === $prevYear && (int)$report['report_month'] === $prevMonth) {
        $hasPrevTwitchReport = true;
        break;
    }
}

$chartValues = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = (clone $now)->modify('-' . $i . ' month');
    $chartValues[] = $monthly[$dt->format('Y-m')] ?? 0;
}
$maxChart = max($chartValues) ?: 1;
$chartPoints = [];
foreach ($chartValues as $idx => $value) {
    $x = 12 + ($idx / max(1, count($chartValues) - 1)) * 260;
    $y = 94 - (($value / $maxChart) * 60);
    $chartPoints[] = round($x, 1) . ',' . round($y, 1);
}

$submissionRows = [
    [
        'title' => sprintf('月次収益レポート（%d月分）', $prevMonth),
        'sub' => '提出期限：' . (new DateTime(sprintf('%04d-%02d-20', $thisYear, $thisMonth)))->format('Y/m/d'),
        'status' => isset($monthly[$prevKey]) ? '提出済み' : '未提出',
        'class' => isset($monthly[$prevKey]) ? 'done' : 'muted',
        'href' => $portalBase . '/submit.php',
    ],
    [
        'title' => sprintf('Twitch CSV（%d月分）', $prevMonth),
        'sub' => '配信概要CSVの提出',
        'status' => $hasPrevTwitchReport ? '提出済み' : '未提出',
        'class' => $hasPrevTwitchReport ? 'done' : 'muted',
        'href' => $portalBase . '/twitch.php',
    ],
    [
        'title' => 'HP掲載情報の変更申請',
        'sub' => 'プロフィール・リンク・画像',
        'status' => $latestPublicRequest ? ($latestPublicRequest['status'] === 'pending' ? '確認中' : '提出済み') : '任意',
        'class' => $latestPublicRequest && $latestPublicRequest['status'] === 'pending' ? 'pending' : 'muted',
        'href' => $portalBase . '/settings.php',
    ],
];

$portalPageTitle = 'ホーム';
require __DIR__ . '/_header.php';
?>

<section class="portal-home-hero">
  <div>
    <span>ようこそ！</span>
    <h1><?= portal_h($talent['talent_name']) ?> <small>さん</small></h1>
    <p>いつもありがとうございます！</p>
  </div>
  <div class="portal-hero-star large"></div>
  <div class="portal-hero-spark s1"></div>
  <div class="portal-hero-spark s2"></div>
</section>

<section class="portal-home-card portal-motion-card">
  <div class="portal-section-head inline">
    <h2><span class="portal-section-icon">!</span>事務所からのお知らせ</h2>
    <a href="<?= portal_h($portalBase) ?>/activity.php">通知を見る</a>
  </div>
  <?php if (!$notices && !$revenueAlerts): ?>
    <div class="portal-empty-line">現在お知らせはありません。</div>
  <?php else: ?>
    <div class="portal-notice-list">
      <?php foreach ($revenueAlerts as $alert): ?>
        <a class="portal-notice-row portal-notice-row-alert"
           href="<?= portal_h($portalBase) ?>/submit.php?year=<?= (int)$alert['year'] ?>&month=<?= (int)$alert['month'] ?>">
          <span class="portal-dot"></span>
          <time><?= portal_h(str_replace('-', '/', substr($alert['updated_at'], 0, 10))) ?></time>
          <span class="portal-pill portal-pill-danger">要修正</span>
          <strong>
            <?= portal_h(sprintf('%04d年%d月分の収益報告が却下されました', $alert['year'], $alert['month'])) ?>
            <?php if (!empty($alert['portal_note'])): ?>
              <span class="portal-notice-meta">理由: <?= portal_h($alert['portal_note']) ?></span>
            <?php endif; ?>
          </strong>
          <span class="portal-row-arrow">›</span>
        </a>
      <?php endforeach; ?>
      <?php foreach (array_slice($notices, 0, 3) as $notice): ?>
        <div class="portal-notice-row">
          <span class="portal-dot"></span>
          <time><?= portal_h(str_replace('-', '/', substr($notice['published_at'] ?? $notice['created_at'], 0, 10))) ?></time>
          <span class="portal-pill">お知らせ</span>
          <strong>
            <?= portal_h($notice['title']) ?>
            <?php if (!empty($notice['body'])): ?>
              <span class="portal-notice-meta"><?= nl2br(portal_h($notice['body'])) ?></span>
            <?php endif; ?>
          </strong>
          <span class="portal-row-arrow">›</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="portal-section-head">
  <h2><span class="portal-section-icon bars"></span>収益サマリー</h2>
  <a href="<?= portal_h($portalBase) ?>/history.php">詳細を見る</a>
</section>
<section class="portal-revenue-card portal-motion-card">
  <div class="portal-revenue-top">
    <div>
      <span>今月の収益（<?= (int)$thisMonth ?>月）</span>
      <strong>¥<?= portal_h(number_format($currentRevenue, 0)) ?></strong>
      <small>先月比 <b class="<?= $growth >= 0 ? 'up' : 'down' ?>"><?= $growth >= 0 ? '↑' : '↓' ?> <?= portal_h(number_format(abs($growth), 1)) ?>%</b></small>
    </div>
    <svg class="portal-mini-chart" viewBox="0 0 284 110" aria-hidden="true">
      <polyline points="<?= portal_h(implode(' ', $chartPoints)) ?>" fill="none" stroke="#7b4dea" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
      <?php foreach ($chartPoints as $p): [$x, $y] = array_map('floatval', explode(',', $p)); ?>
        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4" fill="#7b4dea"/>
      <?php endforeach; ?>
    </svg>
  </div>
  <div class="portal-revenue-stats">
    <div><span>累計収益（<?= (int)$thisYear ?>年）</span><strong>¥<?= portal_h(number_format($yearTotal, 0)) ?></strong></div>
    <div><span>未確定金額</span><strong>¥<?= portal_h(number_format($pendingAmount, 0)) ?></strong></div>
    <div><span>次回目安</span><strong><?= portal_h((new DateTime('last day of this month'))->format('Y/m/d')) ?></strong></div>
  </div>
</section>

<section class="portal-section-head">
  <h2><span class="portal-section-icon doc"></span>提出物・申請状況</h2>
  <a href="<?= portal_h($portalBase) ?>/history.php">すべて見る</a>
</section>
<section class="portal-home-card portal-motion-card">
  <div class="portal-submit-list">
    <?php foreach ($submissionRows as $item): ?>
      <a class="portal-submit-row" href="<?= portal_h($item['href']) ?>">
        <span class="portal-submit-icon"></span>
        <span><strong><?= portal_h($item['title']) ?></strong><small><?= portal_h($item['sub']) ?></small></span>
        <em class="<?= portal_h($item['class']) ?>"><?= portal_h($item['status']) ?></em>
        <i>›</i>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="portal-section-head quick">
  <h2><span class="portal-section-icon folder"></span>クイックメニュー</h2>
</section>
<section class="portal-quick-grid">
  <a href="<?= portal_h($portalBase) ?>/history.php"><span class="q bars"></span><strong>収益明細</strong></a>
  <a href="<?= portal_h($portalBase) ?>/submit.php"><span class="q clip"></span><strong>収益報告</strong></a>
  <a href="<?= portal_h($portalBase) ?>/twitch.php"><span class="q twitch"></span><strong>Twitch CSV</strong></a>
  <a href="<?= portal_h($portalBase) ?>/activity.php"><span class="q bell"></span><strong>通知</strong></a>
  <a href="<?= portal_h($portalBase) ?>/settings.php"><span class="q mail"></span><strong>マイページ</strong></a>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
