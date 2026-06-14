<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent = current_portal_talent();
$defDt = new DateTime('first day of last month');
$defYear = (int)$defDt->format('Y');
$defMonth = (int)$defDt->format('n');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf($_POST['_csrf'] ?? '')) {
        $errors[] = '不正なリクエストです。ページを再読み込みして再試行してください。';
    } else {
        $year = (int)($_POST['year'] ?? $defYear);
        $month = (int)($_POST['month'] ?? $defMonth);
        $result = portal_save_twitch_csv_report(
            $pdo,
            $talent['talent_id'],
            (int)$talent['id'],
            $year,
            $month,
            $_FILES['twitch_csv'] ?? [],
            $_POST['note'] ?? ''
        );
        if (isset($result['success'])) {
            portal_flash_set('success', sprintf('%d年%d月分のTwitch CSVを提出しました。解析結果を保存しました。', $year, $month));
            portal_redirect($portalBase . '/twitch.php');
        }
        $errors[] = $result['error'];
    }
}

$reports = portal_fetch_twitch_reports($pdo, $talent['talent_id'], 12);
$latest = $reports[0] ?? null;
$previousReport = portal_find_previous_twitch_report($reports, $latest);
$latestRows = $latest ? portal_fetch_twitch_report_rows($pdo, (int)$latest['id'], $talent['talent_id']) : [];
$yearOptions = [];
$curYear = (int)(new DateTime())->format('Y');
for ($y = $curYear; $y >= $curYear - 2; $y--) $yearOptions[] = $y;

$portalPageTitle = 'Twitch CSV';
require __DIR__ . '/_header.php';
?>

<section class="portal-page-hero compact">
  <div>
    <p class="portal-kicker">TWITCH ANALYTICS</p>
    <h1>Twitch配信概要CSV</h1>
    <p>月ごとの配信CSVを提出すると、視聴数・平均視聴者・配信時間などを自動集計します。</p>
  </div>
  <div class="portal-hero-orbit" aria-hidden="true"></div>
</section>

<?php foreach ($errors as $e): ?>
  <div class="portal-flash portal-flash--error"><?= portal_h($e) ?></div>
<?php endforeach; ?>

<?php if (!portal_twitch_ready($pdo)): ?>
  <div class="portal-flash portal-flash--warning">Twitch CSV用のDB更新が未実行です。管理者は admin/portal_migrate.sql を再実行してください。</div>
<?php endif; ?>

<div class="portal-card portal-motion-card" style="margin-bottom:16px;">
  <div class="portal-card-title">CSVのダウンロード方法</div>
  <ol class="portal-howto-list">
    <li><a href="https://dashboard.twitch.tv" target="_blank" rel="noopener">Twitchクリエイターダッシュボード</a>を開く</li>
    <li>左メニュー「<strong>インサイト</strong>」→「<strong>ストリームサマリー</strong>」をクリック</li>
    <li>対象月の配信をすべて表示した状態で、右上の「<strong>CSVをエクスポート</strong>」ボタンをクリック</li>
    <li>ダウンロードしたCSVファイルを下のフォームからアップロード</li>
  </ol>
</div>

<form method="post" enctype="multipart/form-data" class="portal-card portal-motion-card">
  <input type="hidden" name="_csrf" value="<?= portal_h(portal_csrf_token()) ?>">
  <div class="portal-card-title">CSVを提出</div>
  <div class="portal-form-row">
    <div class="portal-form-group">
      <label for="year">対象年</label>
      <select id="year" name="year">
        <?php foreach ($yearOptions as $y): ?>
          <option value="<?= $y ?>" <?= $y === $defYear ? 'selected' : '' ?>><?= $y ?>年</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="portal-form-group">
      <label for="month">対象月</label>
      <select id="month" name="month">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $defMonth ? 'selected' : '' ?>><?= $m ?>月</option>
        <?php endfor; ?>
      </select>
    </div>
  </div>
  <div class="portal-upload-box" id="twitchUploadBox">
    <input type="file" id="twitchCsvInput" name="twitch_csv" accept=".csv,text/csv" required>
    <div class="portal-upload-label" id="twitchUploadLabel">
      <strong>TwitchのCSVを選択</strong><br>
      <span style="font-size:11px;">最大5MB / CSVのみ</span>
    </div>
  </div>
  <div class="portal-form-group" style="margin-top:16px;">
    <label for="note">メモ</label>
    <textarea id="note" name="note" rows="3" placeholder="特記事項があれば入力してください"></textarea>
  </div>
  <button class="portal-btn portal-btn-primary" type="submit">提出して解析する</button>
</form>

<script>
(function () {
  var input = document.getElementById('twitchCsvInput');
  var box   = document.getElementById('twitchUploadBox');
  var label = document.getElementById('twitchUploadLabel');

  input.addEventListener('change', function () {
    if (!input.files[0]) return;
    var file = input.files[0];
    var kb   = (file.size / 1024).toFixed(0);
    box.classList.add('selected');
    label.innerHTML =
      '<svg viewBox="0 0 20 20" fill="none" style="width:28px;height:28px;margin-bottom:6px;color:var(--accent)">' +
        '<path d="M10 3v9m0-9L7 6m3-3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<rect x="3" y="13" width="14" height="4" rx="2" stroke="currentColor" stroke-width="2"/>' +
      '</svg>' +
      '<br><strong style="color:var(--accent);">' + file.name + '</strong>' +
      '<br><span style="font-size:11px;color:var(--sub);">' + kb + ' KB — 選択済み</span>';
  });

  box.addEventListener('dragover', function (e) { e.preventDefault(); box.classList.add('dragover'); });
  box.addEventListener('dragleave', function () { box.classList.remove('dragover'); });
  box.addEventListener('drop', function (e) {
    e.preventDefault();
    box.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
      var dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    }
  });
})();
</script>

<?php if ($latest): ?>
<section class="portal-section-head">
  <h2>最新解析</h2>
  <a href="#history">履歴を見る</a>
</section>
<div class="portal-analytics-card portal-motion-card">
  <div class="portal-analytics-copy">
    <span><?= portal_h(sprintf('%04d年%02d月', $latest['report_year'], $latest['report_month'])) ?></span>
    <strong><?= portal_h(number_format((int)$latest['total_views'])) ?><?= portal_twitch_trend_badge($latest['total_views'], $previousReport['total_views'] ?? null) ?></strong>
    <small>総視聴数 / 配信 <?= portal_h((string)$latest['total_streams']) ?> 回</small>
  </div>
  <svg class="portal-line-chart" viewBox="0 0 420 144" role="img" aria-label="Twitch視聴推移">
    <?php
      $values = array_map(static function ($r) { return max(0, (int)$r['views']); }, $latestRows);
      if (!$values) $values = [0, 0];
      $max = max($values) ?: 1;
      $scale = portal_chart_axis_scale($max, 4);
      $axisMax = (float)$scale['max'];
      $axisStep = (float)$scale['step'];
      $plotLeft = 42;
      $plotRight = 408;
      $plotTop = 12;
      $plotBottom = 126;
      $points = [];
      $count = count($values);
      foreach ($values as $idx => $value) {
          $x = $plotLeft + ($count <= 1 ? 0 : ($idx / ($count - 1)) * ($plotRight - $plotLeft));
          $y = $plotBottom - (($value / $axisMax) * ($plotBottom - $plotTop));
          $points[] = round($x, 1) . ',' . round($y, 1);
      }
      $yTicks = [];
      for ($tick = 0.0; $tick <= $axisMax + ($axisStep / 2); $tick += $axisStep) {
          $y = $plotBottom - (($tick / $axisMax) * ($plotBottom - $plotTop));
          $yTicks[] = ['value' => (int)round($tick), 'y' => round($y, 1)];
      }
      $xLabels = [];
      if ($latestRows) {
          $labelStep = max(1, (int)ceil(count($latestRows) / 4));
          foreach ($latestRows as $idx => $row) {
              if ($idx % $labelStep !== 0 && $idx !== count($latestRows) - 1) {
                  continue;
              }
              $x = $plotLeft + (count($latestRows) <= 1 ? 0 : ($idx / (count($latestRows) - 1)) * ($plotRight - $plotLeft));
              $xLabels[] = [
                  'label' => portal_chart_stream_date_label($row['stream_date'] ?? '', (string)($idx + 1)),
                  'x' => round($x, 1),
              ];
          }
      }
    ?>
    <?php foreach ($yTicks as $tick): ?>
      <line x1="<?= $plotLeft ?>" y1="<?= $tick['y'] ?>" x2="<?= $plotRight ?>" y2="<?= $tick['y'] ?>" stroke="#ece7f8" stroke-width="1"/>
      <text x="<?= $plotLeft - 8 ?>" y="<?= $tick['y'] + 4 ?>" text-anchor="end" fill="#8a7f99" font-size="10"><?= portal_h(number_format($tick['value'])) ?></text>
    <?php endforeach; ?>
    <line x1="<?= $plotLeft ?>" y1="<?= $plotTop ?>" x2="<?= $plotLeft ?>" y2="<?= $plotBottom ?>" stroke="#d8cdec" stroke-width="1.2"/>
    <line x1="<?= $plotLeft ?>" y1="<?= $plotBottom ?>" x2="<?= $plotRight ?>" y2="<?= $plotBottom ?>" stroke="#d8cdec" stroke-width="1.2"/>
    <polyline points="<?= portal_h(implode(' ', $points)) ?>" fill="none" stroke="#7b4dea" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
    <?php foreach ($points as $p): [$x, $y] = array_map('floatval', explode(',', $p)); ?>
      <circle cx="<?= $x ?>" cy="<?= $y ?>" r="5" fill="#7b4dea"/>
    <?php endforeach; ?>
    <?php foreach ($xLabels as $label): ?>
      <text x="<?= $label['x'] ?>" y="140" text-anchor="middle" fill="#8a7f99" font-size="10"><?= portal_h($label['label']) ?></text>
    <?php endforeach; ?>
  </svg>
  <div class="portal-metric-grid">
    <div><span>配信時間</span><strong><?= portal_h(number_format((float)$latest['total_minutes'] / 60, 1)) ?>h<?= portal_twitch_trend_badge($latest['total_minutes'], $previousReport['total_minutes'] ?? null) ?></strong></div>
    <div><span>平均視聴者</span><strong><?= portal_h(number_format((float)$latest['avg_viewers'], 1)) ?><?= portal_twitch_trend_badge($latest['avg_viewers'], $previousReport['avg_viewers'] ?? null) ?></strong></div>
    <div><span>最大視聴者</span><strong><?= portal_h(number_format((int)$latest['peak_viewers'])) ?><?= portal_twitch_trend_badge($latest['peak_viewers'], $previousReport['peak_viewers'] ?? null) ?></strong></div>
    <div><span>フォロワー増</span><strong><?= portal_h(number_format((int)$latest['followers_gained'])) ?><?= portal_twitch_trend_badge($latest['followers_gained'], $previousReport['followers_gained'] ?? null) ?></strong></div>
  </div>
</div>
<?php endif; ?>

<section id="history" class="portal-section-head">
  <h2>提出履歴</h2>
</section>
<div class="portal-card">
  <?php if (!$reports): ?>
    <div class="portal-table-empty">まだTwitch CSVの提出がありません。</div>
  <?php else: ?>
  <div class="portal-table-wrap">
    <table class="portal-table">
      <thead>
        <tr>
          <th>対象月</th>
          <th class="text-right">配信</th>
          <th class="text-right">視聴数</th>
          <th class="text-right">配信時間</th>
          <th class="text-right">平均</th>
          <th class="text-right">最大</th>
          <th>提出日</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $report): ?>
          <tr>
            <td><?= portal_h(sprintf('%04d-%02d', $report['report_year'], $report['report_month'])) ?></td>
            <td class="text-right"><?= portal_h((string)$report['total_streams']) ?></td>
            <td class="text-right"><?= portal_h(number_format((int)$report['total_views'])) ?></td>
            <td class="text-right"><?= portal_h(number_format((float)$report['total_minutes'] / 60, 1)) ?>h</td>
            <td class="text-right"><?= portal_h(number_format((float)$report['avg_viewers'], 1)) ?></td>
            <td class="text-right"><?= portal_h(number_format((int)$report['peak_viewers'])) ?></td>
            <td><?= portal_h(substr($report['created_at'], 0, 10)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
