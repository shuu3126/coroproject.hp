<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent  = current_portal_talent();
$info    = portal_get_talent_info($pdo, $talent['talent_id']);
$history = portal_fetch_revenue_history($pdo, $talent['talent_id']);
$notices = portal_fetch_notices($pdo);
$revenueAlerts = portal_fetch_rejected_revenue_alerts($pdo, $talent['talent_id'], 3);
$twitchReports = portal_fetch_twitch_reports($pdo, $talent['talent_id'], 6);
$latestTwitchReport = $twitchReports[0] ?? null;
$previousTwitchReport = portal_find_previous_twitch_report($twitchReports, $latestTwitchReport);
$latestTwitchRows = $latestTwitchReport ? portal_fetch_twitch_report_rows($pdo, (int)$latestTwitchReport['id'], $talent['talent_id']) : [];
$latestPublicRequest = portal_fetch_latest_public_profile_request($pdo, $talent['talent_id']);

$now = new DateTime();
$thisYear = (int)$now->format('Y');
$thisMonth = (int)$now->format('n');
$prev = (clone $now)->modify('-1 month');
$prevYear = (int)$prev->format('Y');
$prevMonth = (int)$prev->format('n');

$monthly = [];
foreach ($history as $row) {
    $sum = (float)$row['amount_streaming'] + (float)$row['amount_goods'] + (float)$row['amount_sponsor'];
    $monthly[sprintf('%04d-%02d', $row['year'], $row['month'])] = $sum;
}
$prevKey = sprintf('%04d-%02d', $prevYear, $prevMonth);
$hasPrevTwitchReport = false;
foreach ($twitchReports as $report) {
    if ((int)$report['report_year'] === $prevYear && (int)$report['report_month'] === $prevMonth) {
        $hasPrevTwitchReport = true;
        break;
    }
}

$streamChartValues = array_map(static function ($row) {
    return max(0, (int)$row['views']);
}, $latestTwitchRows);
if (!$streamChartValues) {
    $streamChartValues = [0, 0];
}
$streamChartMax = max($streamChartValues) ?: 1;
$streamChartScale = portal_chart_axis_scale($streamChartMax, 4);
$streamAxisMax = (float)$streamChartScale['max'];
$streamAxisStep = (float)$streamChartScale['step'];
$streamPlotLeft = 42;
$streamPlotRight = 408;
$streamPlotTop = 12;
$streamPlotBottom = 126;
$streamChartPoints = [];
$streamChartCount = count($streamChartValues);
foreach ($streamChartValues as $idx => $value) {
    $x = $streamPlotLeft + ($streamChartCount <= 1 ? 0 : ($idx / ($streamChartCount - 1)) * ($streamPlotRight - $streamPlotLeft));
    $y = $streamPlotBottom - (($value / $streamAxisMax) * ($streamPlotBottom - $streamPlotTop));
    $streamChartPoints[] = round($x, 1) . ',' . round($y, 1);
}
$streamChartYTicks = [];
for ($tick = 0.0; $tick <= $streamAxisMax + ($streamAxisStep / 2); $tick += $streamAxisStep) {
    $y = $streamPlotBottom - (($tick / $streamAxisMax) * ($streamPlotBottom - $streamPlotTop));
    $streamChartYTicks[] = ['value' => (int)round($tick), 'y' => round($y, 1)];
}
$streamChartXLabels = [];
if ($latestTwitchRows) {
    $labelStep = max(1, (int)ceil(count($latestTwitchRows) / 4));
    foreach ($latestTwitchRows as $idx => $row) {
        if ($idx % $labelStep !== 0 && $idx !== count($latestTwitchRows) - 1) {
            continue;
        }
        $x = $streamPlotLeft + (count($latestTwitchRows) <= 1 ? 0 : ($idx / (count($latestTwitchRows) - 1)) * ($streamPlotRight - $streamPlotLeft));
        $streamChartXLabels[] = [
            'label' => portal_chart_stream_date_label($row['stream_date'] ?? '', (string)($idx + 1)),
            'x' => round($x, 1),
        ];
    }
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
  <h2><span class="portal-section-icon bars"></span>配信概要 最新解析</h2>
  <a href="<?= portal_h($portalBase) ?>/twitch.php">CSVを見る</a>
</section>
<?php if ($latestTwitchReport): ?>
  <section class="portal-analytics-card portal-motion-card">
    <div class="portal-analytics-copy">
      <span><?= portal_h(sprintf('%04d年%02d月', $latestTwitchReport['report_year'], $latestTwitchReport['report_month'])) ?></span>
      <strong><?= portal_h(number_format((int)$latestTwitchReport['total_views'])) ?><?= portal_twitch_trend_badge($latestTwitchReport['total_views'], $previousTwitchReport['total_views'] ?? null) ?></strong>
      <small>総視聴数 / 配信 <?= portal_h((string)$latestTwitchReport['total_streams']) ?> 回</small>
    </div>
    <svg class="portal-line-chart" viewBox="0 0 420 144" role="img" aria-label="Twitch視聴推移">
      <?php foreach ($streamChartYTicks as $tick): ?>
        <line x1="<?= $streamPlotLeft ?>" y1="<?= $tick['y'] ?>" x2="<?= $streamPlotRight ?>" y2="<?= $tick['y'] ?>" stroke="#ece7f8" stroke-width="1"/>
        <text x="<?= $streamPlotLeft - 8 ?>" y="<?= $tick['y'] + 4 ?>" text-anchor="end" fill="#8a7f99" font-size="10"><?= portal_h(number_format($tick['value'])) ?></text>
      <?php endforeach; ?>
      <line x1="<?= $streamPlotLeft ?>" y1="<?= $streamPlotTop ?>" x2="<?= $streamPlotLeft ?>" y2="<?= $streamPlotBottom ?>" stroke="#d8cdec" stroke-width="1.2"/>
      <line x1="<?= $streamPlotLeft ?>" y1="<?= $streamPlotBottom ?>" x2="<?= $streamPlotRight ?>" y2="<?= $streamPlotBottom ?>" stroke="#d8cdec" stroke-width="1.2"/>
      <polyline points="<?= portal_h(implode(' ', $streamChartPoints)) ?>" fill="none" stroke="#7b4dea" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
      <?php foreach ($streamChartPoints as $point): [$x, $y] = array_map('floatval', explode(',', $point)); ?>
        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="5" fill="#7b4dea"/>
      <?php endforeach; ?>
      <?php foreach ($streamChartXLabels as $label): ?>
        <text x="<?= $label['x'] ?>" y="140" text-anchor="middle" fill="#8a7f99" font-size="10"><?= portal_h($label['label']) ?></text>
      <?php endforeach; ?>
    </svg>
    <div class="portal-metric-grid">
      <div><span>配信時間</span><strong><?= portal_h(number_format((float)$latestTwitchReport['total_minutes'] / 60, 1)) ?>h<?= portal_twitch_trend_badge($latestTwitchReport['total_minutes'], $previousTwitchReport['total_minutes'] ?? null) ?></strong></div>
      <div><span>平均視聴者</span><strong><?= portal_h(number_format((float)$latestTwitchReport['avg_viewers'], 1)) ?><?= portal_twitch_trend_badge($latestTwitchReport['avg_viewers'], $previousTwitchReport['avg_viewers'] ?? null) ?></strong></div>
      <div><span>最大視聴者</span><strong><?= portal_h(number_format((int)$latestTwitchReport['peak_viewers'])) ?><?= portal_twitch_trend_badge($latestTwitchReport['peak_viewers'], $previousTwitchReport['peak_viewers'] ?? null) ?></strong></div>
      <div><span>フォロワー増</span><strong><?= portal_h(number_format((int)$latestTwitchReport['followers_gained'])) ?><?= portal_twitch_trend_badge($latestTwitchReport['followers_gained'], $previousTwitchReport['followers_gained'] ?? null) ?></strong></div>
    </div>
  </section>
<?php else: ?>
  <section class="portal-home-card portal-motion-card">
    <div class="portal-empty-line">まだTwitch CSVの解析結果はありません。</div>
    <div style="margin-top:14px;">
      <a class="portal-btn portal-btn-primary" href="<?= portal_h($portalBase) ?>/twitch.php">CSVを提出する</a>
    </div>
  </section>
<?php endif; ?>

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
