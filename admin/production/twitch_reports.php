<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

function admin_twitch_chart_axis_scale($maxValue, $targetSteps = 4) {
    $maxValue = max(1.0, (float)$maxValue);
    $targetSteps = max(1, (int)$targetSteps);
    $roughStep = $maxValue / $targetSteps;
    $power = pow(10, floor(log10($roughStep)));
    $fraction = $roughStep / $power;

    if ($fraction <= 1.5) {
        $niceFraction = 1;
    } elseif ($fraction <= 3) {
        $niceFraction = 2;
    } elseif ($fraction <= 7) {
        $niceFraction = 5;
    } else {
        $niceFraction = 10;
    }

    $step = $niceFraction * $power;
    $axisMax = $step * ceil($maxValue / $step);
    return ['max' => max($axisMax, $step), 'step' => $step];
}

function admin_twitch_chart_stream_date_label($value, $fallback = '') {
    $value = trim((string)$value);
    if ($value === '') {
        return $fallback;
    }
    try {
        return (new DateTime($value))->format('n/j');
    } catch (Exception $e) {
        return $fallback;
    }
}

$twitchRequiredColumns = [
    'talent_twitch_csv_reports' => [
        'id', 'talent_id', 'report_year', 'report_month', 'original_filename', 'file_path',
        'row_count', 'total_streams', 'total_minutes', 'total_views', 'avg_viewers',
        'peak_viewers', 'followers_gained', 'chat_messages', 'estimated_revenue',
        'summary_json', 'status', 'created_at', 'updated_at',
    ],
    'talent_twitch_csv_rows' => [
        'id', 'report_id', 'stream_date', 'title', 'duration_minutes', 'views',
        'avg_viewers', 'peak_viewers', 'followers_gained', 'chat_messages',
        'estimated_revenue', 'raw_json', 'created_at',
    ],
];
$ready = true;
foreach ($twitchRequiredColumns as $table => $columns) {
    foreach ($columns as $column) {
        if (!admin_table_has_column($pdo, $table, $column)) {
            $ready = false;
            break 2;
        }
    }
}
$rows = [];
$selected = null;
$detailRows = [];
$chartPoints = [];
$chartYTicks = [];
$chartXLabels = [];
$chartPlotLeft = 64;
$chartPlotRight = 540;
$chartPlotTop = 28;
$chartPlotBottom = 156;

if ($ready) {
    $stmt = $pdo->query('
        SELECT r.*, t.name AS talent_name
        FROM talent_twitch_csv_reports r
        LEFT JOIN talents t ON t.id = r.talent_id
        ORDER BY r.report_year DESC, r.report_month DESC, r.created_at DESC
        LIMIT 200
    ');
    $rows = $stmt->fetchAll();

    $selectedId = (int)($_GET['id'] ?? 0);
    if ($selectedId > 0) {
        $stmt = $pdo->prepare('
            SELECT r.*, t.name AS talent_name
            FROM talent_twitch_csv_reports r
            LEFT JOIN talents t ON t.id = r.talent_id
            WHERE r.id = ?
            LIMIT 1
        ');
        $stmt->execute([$selectedId]);
        $selected = $stmt->fetch() ?: null;
        if ($selected) {
            $stmt = $pdo->prepare('SELECT * FROM talent_twitch_csv_rows WHERE report_id = ? ORDER BY stream_date ASC, id ASC');
            $stmt->execute([$selectedId]);
            $detailRows = $stmt->fetchAll();
            $values = array_map(static function ($row) {
                return max(0, (int)$row['views']);
            }, $detailRows);
            if (!$values) {
                $values = [0, 0];
            }
            $max = max($values) ?: 1;
            $scale = admin_twitch_chart_axis_scale($max, 4);
            $axisMax = (float)$scale['max'];
            $axisStep = (float)$scale['step'];
            $count = count($values);
            foreach ($values as $idx => $value) {
                $x = $chartPlotLeft + ($count <= 1 ? 0 : ($idx / ($count - 1)) * ($chartPlotRight - $chartPlotLeft));
                $y = $chartPlotBottom - (($value / $axisMax) * ($chartPlotBottom - $chartPlotTop));
                $chartPoints[] = round($x, 1) . ',' . round($y, 1);
            }
            for ($tick = 0.0; $tick <= $axisMax + ($axisStep / 2); $tick += $axisStep) {
                $y = $chartPlotBottom - (($tick / $axisMax) * ($chartPlotBottom - $chartPlotTop));
                $chartYTicks[] = ['value' => (int)round($tick), 'y' => round($y, 1)];
            }
            if ($detailRows) {
                $labelStep = max(1, (int)ceil(count($detailRows) / 5));
                foreach ($detailRows as $idx => $row) {
                    if ($idx % $labelStep !== 0 && $idx !== count($detailRows) - 1) {
                        continue;
                    }
                    $x = $chartPlotLeft + (count($detailRows) <= 1 ? 0 : ($idx / (count($detailRows) - 1)) * ($chartPlotRight - $chartPlotLeft));
                    $chartXLabels[] = [
                        'label' => admin_twitch_chart_stream_date_label($row['stream_date'] ?? '', (string)($idx + 1)),
                        'x' => round($x, 1),
                    ];
                }
            }
        }
    }
}

start_page('Twitch CSV解析', 'タレントが提出したTwitch配信概要CSVの解析結果を確認します。');
?>
<main class="page-container">
  <?php if (!$ready): ?>
    <div class="card alert-box alert-error">Twitch CSV用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block with-actions">
    <div>
      <h1>Twitch CSV解析</h1>
      <p>提出CSVから配信回数、配信時間、視聴数、平均視聴者などを自動集計します。</p>
    </div>
  </section>

  <?php if ($selected): ?>
    <section class="card form-card mt-24">
      <h2 class="section-heading"><?= h($selected['talent_name'] ?: $selected['talent_id']) ?> / <?= h(sprintf('%04d-%02d', $selected['report_year'], $selected['report_month'])) ?></h2>
      <div class="card-grid four">
        <div class="card stat-card"><div class="muted">配信回数</div><div class="stat-number"><?= h((string)$selected['total_streams']) ?></div></div>
        <div class="card stat-card"><div class="muted">総視聴数</div><div class="stat-number"><?= h(number_format((int)$selected['total_views'])) ?></div></div>
        <div class="card stat-card"><div class="muted">配信時間</div><div class="stat-number"><?= h(number_format((float)$selected['total_minutes'] / 60, 1)) ?>h</div></div>
        <div class="card stat-card"><div class="muted">平均視聴者</div><div class="stat-number"><?= h(number_format((float)$selected['avg_viewers'], 1)) ?></div></div>
      </div>
      <div class="card mt-24" style="padding:20px;background:linear-gradient(180deg,#ffffff 0%,#f8f5ff 100%);">
        <div class="section-heading" style="margin-bottom:10px;">視聴数推移</div>
        <svg viewBox="0 0 560 210" role="img" aria-label="Twitch視聴数推移" style="width:100%;height:250px;display:block;">
          <?php foreach ($chartYTicks as $tick): ?>
            <line x1="<?= h((string)$chartPlotLeft) ?>" y1="<?= h((string)$tick['y']) ?>" x2="<?= h((string)$chartPlotRight) ?>" y2="<?= h((string)$tick['y']) ?>" stroke="#ece7f8" stroke-width="1"/>
            <text x="<?= h((string)($chartPlotLeft - 8)) ?>" y="<?= h((string)($tick['y'] + 4)) ?>" text-anchor="end" fill="#7c708f" font-size="11"><?= h(number_format($tick['value'])) ?></text>
          <?php endforeach; ?>
          <line x1="<?= h((string)$chartPlotLeft) ?>" y1="<?= h((string)$chartPlotTop) ?>" x2="<?= h((string)$chartPlotLeft) ?>" y2="<?= h((string)$chartPlotBottom) ?>" stroke="#d8cdec" stroke-width="1.2"/>
          <line x1="<?= h((string)$chartPlotLeft) ?>" y1="<?= h((string)$chartPlotBottom) ?>" x2="<?= h((string)$chartPlotRight) ?>" y2="<?= h((string)$chartPlotBottom) ?>" stroke="#d8cdec" stroke-width="1.2"/>
          <polyline points="<?= h(implode(' ', $chartPoints)) ?>" fill="none" stroke="#7b4dea" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
          <?php foreach ($chartPoints as $point): [$x, $y] = array_map('floatval', explode(',', $point)); ?>
            <circle cx="<?= h((string)$x) ?>" cy="<?= h((string)$y) ?>" r="5" fill="#7b4dea"/>
          <?php endforeach; ?>
          <?php foreach ($chartXLabels as $label): ?>
            <text x="<?= h((string)$label['x']) ?>" y="184" text-anchor="middle" fill="#7c708f" font-size="11"><?= h($label['label']) ?></text>
          <?php endforeach; ?>
          <text x="<?= h((string)(($chartPlotLeft + $chartPlotRight) / 2)) ?>" y="204" text-anchor="middle" fill="#7c708f" font-size="11">配信日</text>
          <text x="16" y="<?= h((string)(($chartPlotTop + $chartPlotBottom) / 2)) ?>" text-anchor="middle" fill="#7c708f" font-size="11" transform="rotate(-90 16 <?= h((string)(($chartPlotTop + $chartPlotBottom) / 2)) ?>)">視聴数</text>
        </svg>
      </div>
      <div class="table-wrap mt-24">
        <table class="data-table">
          <thead>
            <tr>
              <th>日時</th><th>タイトル</th><th class="text-right">時間</th><th class="text-right">視聴数</th><th class="text-right">平均</th><th class="text-right">最大</th><th class="text-right">フォロワー増</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($detailRows as $r): ?>
              <tr>
                <td><?= h(format_datetime($r['stream_date'])) ?></td>
                <td><?= h($r['title']) ?></td>
                <td class="text-right"><?= h(number_format((float)$r['duration_minutes'] / 60, 1)) ?>h</td>
                <td class="text-right"><?= h(number_format((int)$r['views'])) ?></td>
                <td class="text-right"><?= h(number_format((float)$r['avg_viewers'], 1)) ?></td>
                <td class="text-right"><?= h(number_format((int)$r['peak_viewers'])) ?></td>
                <td class="text-right"><?= h(number_format((int)$r['followers_gained'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <section class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>タレント</th><th>対象月</th><th class="text-right">配信</th><th class="text-right">視聴数</th><th class="text-right">配信時間</th><th class="text-right">平均</th><th class="text-right">最大</th><th>提出日</th><th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="9" class="empty-state">まだCSV提出がありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= h($row['talent_name'] ?: $row['talent_id']) ?></td>
              <td><?= h(sprintf('%04d-%02d', $row['report_year'], $row['report_month'])) ?></td>
              <td class="text-right"><?= h((string)$row['total_streams']) ?></td>
              <td class="text-right"><?= h(number_format((int)$row['total_views'])) ?></td>
              <td class="text-right"><?= h(number_format((float)$row['total_minutes'] / 60, 1)) ?>h</td>
              <td class="text-right"><?= h(number_format((float)$row['avg_viewers'], 1)) ?></td>
              <td class="text-right"><?= h(number_format((int)$row['peak_viewers'])) ?></td>
              <td><?= h(substr($row['created_at'], 0, 16)) ?></td>
              <td><a class="ghost-btn" href="<?= h($baseUrl) ?>/production/twitch_reports.php?id=<?= (int)$row['id'] ?>">詳細</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php end_page(); ?>
