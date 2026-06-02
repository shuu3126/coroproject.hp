<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

function normalize_week_start(string $week): int {
    $base = time();
    if ($week !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week) && strtotime($week) !== false) {
        $base = strtotime($week);
    }

    $day_of_week = (int)date('N', $base);
    return strtotime('-' . ($day_of_week - 1) . ' days', $base);
}

function short_site_name(string $name): string {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($name, 'UTF-8') > 10 ? mb_substr($name, 0, 10, 'UTF-8') . '...' : $name;
    }

    return strlen($name) > 30 ? substr($name, 0, 30) . '...' : $name;
}

$week_start_ts = normalize_week_start(trim($_GET['week'] ?? ''));
$week_start = date('Y-m-d', $week_start_ts);
$week_end = date('Y-m-d', strtotime('+6 days', $week_start_ts));

$current_week_ts = normalize_week_start(date('Y-m-d'));
$current_week = date('Y-m-d', $current_week_ts);
$prev_week = date('Y-m-d', strtotime('-7 days', $week_start_ts));
$next_week = date('Y-m-d', strtotime('+7 days', $week_start_ts));

$week_days = [];
$weekday_labels = ['月', '火', '水', '木', '金', '土', '日'];
for ($i = 0; $i < 7; $i++) {
    $ts = strtotime('+' . $i . ' days', $week_start_ts);
    $week_days[] = [
        'date' => date('Y-m-d', $ts),
        'label' => $weekday_labels[$i] . date('m/d', $ts),
        'is_today' => date('Y-m-d', $ts) === date('Y-m-d'),
    ];
}

$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.job_type,
           a.site_id, s.name AS site_name,
           a.start_date, a.end_date
    FROM craftsmen c
    LEFT JOIN assignments a ON a.craftsman_id = c.id
        AND a.start_date <= :week_end
        AND (a.end_date IS NULL OR a.end_date >= :week_start)
    LEFT JOIN sites s ON a.site_id = s.id
    WHERE c.status = '稼働中'
    ORDER BY FIELD(c.job_type, '解体', '鍛冶', '大工', '電気', '水道', '内装', 'その他'), c.name, a.start_date, s.name
");
$stmt->execute([
    'week_end' => $week_end,
    'week_start' => $week_start,
]);
$rows = $stmt->fetchAll();

$craftsmen = [];
foreach ($rows as $row) {
    $craftsman_id = (int)$row['id'];
    if (!isset($craftsmen[$craftsman_id])) {
        $craftsmen[$craftsman_id] = [
            'id' => $craftsman_id,
            'name' => $row['name'],
            'job_type' => $row['job_type'],
            'days' => [],
        ];

        foreach ($week_days as $day) {
            $craftsmen[$craftsman_id]['days'][$day['date']] = [];
        }
    }

    if ($row['site_id']) {
        foreach ($week_days as $day) {
            $date = $day['date'];
            $end_date = $row['end_date'] ?: $week_end;
            if ($row['start_date'] <= $date && $end_date >= $date) {
                $craftsmen[$craftsman_id]['days'][$date][] = [
                    'site_id' => (int)$row['site_id'],
                    'site_name' => $row['site_name'],
                ];
            }
        }
    }
}

renderHead('週次カレンダー');
renderHeader('週次アサインカレンダー');
?>

<main class="px-4 py-4 md:px-8 md:py-6 w-full">

  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-5">
    <div>
      <div class="text-xs text-gray-400 mb-1">表示期間</div>
      <div class="text-lg font-bold text-gray-800"><?= htmlspecialchars($week_start) ?> 〜 <?= htmlspecialchars($week_end) ?></div>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="/tamiya-home/pages/assignments/calendar.php?week=<?= $prev_week ?>"
         class="bg-white border border-gray-200 text-gray-600 text-sm font-bold px-4 py-2 rounded-lg hover:bg-gray-50">前の週</a>
      <a href="/tamiya-home/pages/assignments/calendar.php?week=<?= $current_week ?>"
         class="bg-blue-600 text-white text-sm font-bold px-4 py-2 rounded-lg hover:bg-blue-700">今週</a>
      <a href="/tamiya-home/pages/assignments/calendar.php?week=<?= $next_week ?>"
         class="bg-white border border-gray-200 text-gray-600 text-sm font-bold px-4 py-2 rounded-lg hover:bg-gray-50">次の週</a>
      <a href="/tamiya-home/pages/assignments/index.php"
         class="bg-white border border-gray-200 text-gray-500 text-sm font-bold px-4 py-2 rounded-lg hover:bg-gray-50">日別一覧</a>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[820px] table-fixed border-collapse">
        <thead>
          <tr class="bg-gray-50 text-xs text-gray-500">
            <th class="sticky left-0 z-20 bg-gray-50 px-3 py-3 text-left font-semibold w-28">職人名</th>
            <?php foreach ($week_days as $day): ?>
              <th class="px-2 py-3 text-center font-semibold <?= $day['is_today'] ? 'bg-blue-50/30' : '' ?>">
                <?= htmlspecialchars($day['label']) ?>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($craftsmen): ?>
            <?php $current_job = ''; ?>
            <?php foreach ($craftsmen as $craftsman): ?>
              <?php if ($craftsman['job_type'] !== $current_job): ?>
                <?php $current_job = $craftsman['job_type']; ?>
                <tr class="bg-gray-50">
                  <td colspan="8" class="px-3 py-2 text-xs font-semibold text-gray-500">
                    <?= htmlspecialchars($current_job) ?>
                  </td>
                </tr>
              <?php endif; ?>
              <tr>
                <td class="sticky left-0 z-10 bg-white border-t border-gray-100 px-3 py-3 font-medium text-gray-800 text-sm w-24 shrink-0">
                  <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $craftsman['id'] ?>"
                     class="block truncate hover:text-blue-600">
                    <?= htmlspecialchars($craftsman['name']) ?>
                  </a>
                </td>
                <?php foreach ($week_days as $day): ?>
                  <?php $items = $craftsman['days'][$day['date']]; ?>
                  <?php if ($day['is_today']): ?>
                  <td class="today-cell border-t border-gray-100 px-2 py-2 align-top h-14 bg-blue-50/30 transition-colors"
                      data-craftsman-id="<?= $craftsman['id'] ?>"
                      data-site-id="<?= $items ? $items[0]['site_id'] : '' ?>"
                      data-date="<?= $day['date'] ?>">
                    <?php if ($items): ?>
                      <div class="space-y-1">
                        <?php foreach ($items as $item): ?>
                          <div class="today-chip cursor-grab active:cursor-grabbing select-none text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded px-1 py-0.5 truncate"
                               data-craftsman-id="<?= $craftsman['id'] ?>"
                               data-site-id="<?= $item['site_id'] ?>"
                               title="<?= htmlspecialchars($item['site_name']) ?>">
                            <?= htmlspecialchars(short_site_name($item['site_name'])) ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="text-gray-300 text-xs text-center py-2">ここへドロップ</div>
                    <?php endif; ?>
                  </td>
                  <?php else: ?>
                  <td class="border-t border-gray-100 px-2 py-2 align-top h-14">
                    <?php if ($items): ?>
                      <div class="space-y-1">
                        <?php foreach ($items as $item): ?>
                          <a href="/tamiya-home/pages/sites/detail.php?id=<?= $item['site_id'] ?>"
                             title="<?= htmlspecialchars($item['site_name']) ?>"
                             class="block text-xs text-blue-700 bg-blue-50 rounded px-1 py-0.5 truncate">
                            <?= htmlspecialchars(short_site_name($item['site_name'])) ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="text-gray-200 text-xs text-center py-1">─</div>
                    <?php endif; ?>
                  </td>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center text-gray-400 py-12 text-sm">
                稼働中の職人が登録されていません
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<script>
(function () {
  const today = '<?= date('Y-m-d') ?>';
  let dragData = null;

  document.querySelectorAll('.today-chip').forEach(chip => {
    chip.draggable = true;
    chip.addEventListener('dragstart', e => {
      dragData = { craftsmanId: chip.dataset.craftsmanId, siteId: chip.dataset.siteId };
      e.dataTransfer.effectAllowed = 'move';
      chip.classList.add('opacity-40');
    });
    chip.addEventListener('dragend', () => {
      chip.classList.remove('opacity-40');
      dragData = null;
    });
  });

  document.querySelectorAll('.today-cell').forEach(cell => {
    cell.addEventListener('dragover', e => {
      if (!dragData) return;
      e.preventDefault();
      cell.classList.add('ring-2', 'ring-inset', 'ring-blue-400', '!bg-blue-100');
    });
    cell.addEventListener('dragleave', () => {
      cell.classList.remove('ring-2', 'ring-inset', 'ring-blue-400', '!bg-blue-100');
    });
    cell.addEventListener('drop', async e => {
      e.preventDefault();
      cell.classList.remove('ring-2', 'ring-inset', 'ring-blue-400', '!bg-blue-100');
      if (!dragData) return;

      const fromId   = parseInt(dragData.craftsmanId);
      const fromSite = dragData.siteId ? parseInt(dragData.siteId) : null;
      const toId     = parseInt(cell.dataset.craftsmanId);
      const toSite   = cell.dataset.siteId ? parseInt(cell.dataset.siteId) : null;

      if (fromId === toId) return;

      try {
        await Promise.all([
          post({ craftsman_id: fromId, site_id: toSite, date: today }),
          post({ craftsman_id: toId,   site_id: fromSite, date: today }),
        ]);
        toast('アサインを更新しました');
        setTimeout(() => location.reload(), 700);
      } catch {
        toast('エラーが発生しました', true);
      }
    });
  });

  function post(body) {
    return fetch('/tamiya-home/pages/assignments/api_reassign_today.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).then(r => { if (!r.ok) throw new Error(); });
  }

  function toast(msg, isError = false) {
    const el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = `position:fixed;bottom:80px;left:50%;transform:translateX(-50%);
      background:${isError ? '#ef4444' : '#1e3a5f'};color:#fff;
      padding:10px 24px;border-radius:8px;font-size:13px;z-index:9999;
      box-shadow:0 4px 16px rgba(0,0,0,0.2);pointer-events:none;`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2000);
  }
})();
</script>

<?php
renderBottomNav('calendar');
renderFoot();
?>
