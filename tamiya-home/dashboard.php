<?php
require_once __DIR__ . '/db/connect.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();

$today = date('Y-m-d');

$stmt = $pdo->query("SELECT COUNT(*) FROM craftsmen WHERE status = '稼働中'");
$active_craftsmen = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = '施工中'");
$active_sites = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT craftsman_id) FROM assignments
    WHERE start_date <= ? AND (end_date IS NULL OR end_date >= ?)
");
$stmt->execute([$today, $today]);
$assigned_today = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM craftsmen
    WHERE status = '稼働中'
      AND id NOT IN (
          SELECT craftsman_id FROM assignments
          WHERE start_date <= ? AND (end_date IS NULL OR end_date >= ?)
      )
");
$stmt->execute([$today, $today]);
$unassigned = (int)$stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*) FROM qualifications
    WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
");
$expiring_qualification_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT c.name AS craftsman_name, c.job_type, s.name AS site_name
    FROM assignments a
    JOIN craftsmen c ON a.craftsman_id = c.id
    JOIN sites     s ON a.site_id = s.id
    WHERE a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?)
    ORDER BY c.job_type, c.name
    LIMIT 30
");
$stmt->execute([$today, $today]);
$today_assignments = $stmt->fetchAll();

$three_days_later = date('Y-m-d', strtotime('+3 days'));
$stmt = $pdo->prepare("
    SELECT name, end_date FROM sites
    WHERE status IN ('準備中','施工中') AND end_date BETWEEN ? AND ?
    ORDER BY end_date
");
$stmt->execute([$today, $three_days_later]);
$ending_soon = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT q.name AS qualification_name, q.expiry_date,
           c.name AS craftsman_name,
           DATEDIFF(q.expiry_date, CURDATE()) AS remaining_days
    FROM qualifications q
    JOIN craftsmen c ON q.craftsman_id = c.id
    WHERE q.expiry_date IS NOT NULL
      AND q.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY q.expiry_date ASC, c.name, q.name
    LIMIT 5
");
$expiring_qualifications = $stmt->fetchAll();

renderHead('ダッシュボード');
renderHeader('ダッシュボード');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-5xl mx-auto">

  <!-- サマリーカード -->
  <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
      <div class="text-3xl font-bold text-blue-600"><?= $active_craftsmen ?></div>
      <div class="text-xs text-gray-400 mt-1">稼働中の職人</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
      <div class="text-3xl font-bold text-green-600"><?= $active_sites ?></div>
      <div class="text-xs text-gray-400 mt-1">施工中の現場</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
      <div class="text-3xl font-bold text-indigo-600"><?= $assigned_today ?></div>
      <div class="text-xs text-gray-400 mt-1">今日の出動人数</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
      <div class="text-3xl font-bold text-orange-500"><?= $unassigned ?></div>
      <div class="text-xs text-gray-400 mt-1">未アサイン（待機）</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center col-span-2 md:col-span-1">
      <div class="text-3xl font-bold text-red-500"><?= $expiring_qualification_count ?></div>
      <div class="text-xs text-gray-400 mt-1">期限切れ・間近の資格</div>
    </div>
  </div>

  <?php if ($ending_soon || $expiring_qualifications): ?>
  <div class="md:grid md:grid-cols-2 md:gap-5 mb-5">

    <!-- 工期終了アラート -->
    <?php if ($ending_soon): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4 md:mb-0">
      <div class="text-sm font-semibold text-yellow-700 mb-2">工期終了が近い現場</div>
      <?php foreach ($ending_soon as $site): ?>
        <div class="flex justify-between text-sm py-1.5 border-b border-yellow-100 last:border-0">
          <span class="text-gray-700"><?= htmlspecialchars($site['name']) ?></span>
          <span class="text-yellow-600 font-medium"><?= $site['end_date'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 資格期限アラート -->
    <?php if ($expiring_qualifications): ?>
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 md:mb-0">
      <div class="flex items-center justify-between mb-2">
        <div class="text-sm font-semibold text-orange-700">期限切れ・期限間近の資格</div>
        <?php if (isAdmin()): ?>
          <a href="/tamiya-home/pages/qualifications/index.php" class="text-xs text-orange-700 hover:underline">もっと見る</a>
        <?php endif; ?>
      </div>
      <?php foreach ($expiring_qualifications as $q): ?>
        <div class="flex items-center justify-between gap-3 text-sm py-1.5 border-b border-orange-100 last:border-0">
          <div class="min-w-0">
            <span class="font-medium text-gray-700"><?= htmlspecialchars($q['craftsman_name']) ?></span>
            <span class="text-gray-400"> / </span>
            <span class="text-gray-600"><?= htmlspecialchars($q['qualification_name']) ?></span>
          </div>
          <span class="shrink-0 text-orange-700 font-medium">
            <?= ((int)$q['remaining_days'] < 0) ? '期限切れ' : '残' . (int)$q['remaining_days'] . '日' ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>

  <!-- 今日のアサイン状況 -->
  <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4">
    <div class="text-sm font-semibold text-gray-700 mb-3">今日のアサイン状況</div>
    <?php if ($today_assignments): ?>
      <?php foreach ($today_assignments as $row): ?>
        <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0 text-sm">
          <?= job_badge($row['job_type']) ?>
          <span class="font-medium text-gray-800 w-20 shrink-0"><?= htmlspecialchars($row['craftsman_name']) ?></span>
          <span class="text-gray-300">—</span>
          <span class="text-gray-600"><?= htmlspecialchars($row['site_name']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-sm text-gray-400 text-center py-4">今日のアサインはありません</p>
    <?php endif; ?>
  </div>

  <?php if (isAdmin()): ?>
  <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-5">
    <a href="/tamiya-home/pages/craftsmen/create.php"
       class="bg-blue-600 text-white text-sm font-bold text-center py-3 rounded-xl hover:bg-blue-700">職人登録</a>
    <a href="/tamiya-home/pages/sites/create.php"
       class="bg-green-600 text-white text-sm font-bold text-center py-3 rounded-xl hover:bg-green-700">現場登録</a>
    <a href="/tamiya-home/pages/assignments/create.php"
       class="bg-indigo-600 text-white text-sm font-bold text-center py-3 rounded-xl col-span-2 md:col-span-1 hover:bg-indigo-700">アサイン登録</a>
  </div>
  <?php endif; ?>

</main>

<?php
renderBottomNav('dashboard');
renderFoot();
?>
