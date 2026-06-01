<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$date = trim($_GET['date'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("
    SELECT a.id, a.start_date, a.end_date,
           c.id AS craftsman_id, c.name AS craftsman_name, c.job_type,
           s.id AS site_id, s.name AS site_name
    FROM assignments a
    JOIN craftsmen c ON a.craftsman_id = c.id
    JOIN sites s ON a.site_id = s.id
    WHERE a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?)
    ORDER BY c.job_type, c.name
");
$stmt->execute([$date, $date]);
$assignments = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT id, name, job_type FROM craftsmen
    WHERE status = '稼働中'
      AND id NOT IN (
          SELECT craftsman_id FROM assignments
          WHERE start_date <= ? AND (end_date IS NULL OR end_date >= ?)
      )
    ORDER BY job_type, name
");
$stmt->execute([$date, $date]);
$unassigned = $stmt->fetchAll();

renderHead('アサイン管理');
renderHeader('アサイン状況');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-5xl mx-auto">

  <form method="get" class="flex flex-wrap gap-2 items-center mb-5">
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"
      class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">確認</button>
    <?php if ($date !== date('Y-m-d')): ?>
      <a href="/tamiya-home/pages/assignments/index.php" class="text-xs text-blue-500 underline">今日に戻る</a>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
      <a href="/tamiya-home/pages/assignments/create.php"
         class="ml-auto bg-indigo-600 text-white text-sm font-bold px-4 py-2 rounded-lg hover:bg-indigo-700">+ アサイン登録</a>
    <?php endif; ?>
  </form>

  <div class="md:grid md:grid-cols-3 md:gap-5">

    <div class="md:col-span-2 mb-4 md:mb-0">
      <div class="text-xs text-gray-400 font-medium mb-2">アサイン中　<?= count($assignments) ?> 人</div>
      <?php if ($assignments): ?>
        <div class="space-y-2">
          <?php foreach ($assignments as $a): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-3 flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                <?= job_badge($a['job_type']) ?>
                <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $a['craftsman_id'] ?>"
                   class="font-medium text-sm text-gray-800 hover:text-blue-600 truncate">
                  <?= htmlspecialchars($a['craftsman_name']) ?>
                </a>
              </div>
              <div class="flex items-center gap-3 shrink-0 ml-2">
                <div class="text-right">
                  <a href="/tamiya-home/pages/sites/detail.php?id=<?= $a['site_id'] ?>"
                     class="text-sm text-gray-600 hover:text-blue-600 block">
                    <?= htmlspecialchars($a['site_name']) ?>
                  </a>
                  <div class="text-xs text-gray-300">〜 <?= $a['end_date'] ?? '終了日未定' ?></div>
                </div>
                <?php if (isAdmin()): ?>
                  <a href="/tamiya-home/pages/assignments/edit.php?id=<?= $a['id'] ?>"
                     class="text-xs text-gray-400 hover:text-blue-500">編集</a>
                  <form method="post" action="/tamiya-home/pages/assignments/delete.php"
                        onsubmit="return confirm('このアサインを解除しますか？')">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <input type="hidden" name="redirect" value="index">
                    <button type="submit" class="text-gray-200 hover:text-red-400 text-lg leading-none font-bold">×</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center text-gray-400 py-10 bg-white rounded-xl border border-gray-100">この日のアサインはありません</div>
      <?php endif; ?>
    </div>

    <div>
      <div class="text-xs text-gray-400 font-medium mb-2">未アサイン（待機）　<?= count($unassigned) ?> 人</div>
      <?php if ($unassigned): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-2">
          <?php foreach ($unassigned as $c): ?>
            <div class="flex items-center justify-between py-1 border-b border-gray-50 last:border-0">
              <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $c['id'] ?>"
                 class="text-sm text-gray-700 hover:text-blue-600">
                <?= htmlspecialchars($c['name']) ?>
              </a>
              <?= job_badge($c['job_type']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center text-gray-400 py-6 bg-white rounded-xl border border-gray-100 text-sm">全員アサイン済み</div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php
renderBottomNav('assignments');
renderFoot();
?>
