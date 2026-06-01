<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /tamiya-home/pages/sites/index.php'); exit; }

$stmt = $pdo->prepare('SELECT s.*, u.name AS supervisor_name FROM sites s LEFT JOIN users u ON s.supervisor_id = u.id WHERE s.id = ?');
$stmt->execute([$id]);
$site = $stmt->fetch();
if (!$site) { header('Location: /tamiya-home/pages/sites/index.php'); exit; }

$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.job_type, a.id AS assign_id, a.start_date, a.end_date
    FROM assignments a JOIN craftsmen c ON a.craftsman_id = c.id
    WHERE a.site_id = ? AND a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?)
    ORDER BY c.job_type, c.name
");
$stmt->execute([$id, $today, $today]);
$current_craftsmen = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT c.name, c.job_type, a.start_date, a.end_date
    FROM assignments a JOIN craftsmen c ON a.craftsman_id = c.id
    WHERE a.site_id = ? AND NOT (a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?))
    ORDER BY a.end_date DESC LIMIT 30
");
$stmt->execute([$id, $today, $today]);
$past_craftsmen = $stmt->fetchAll();

$comments = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM site_comments WHERE site_id = ? ORDER BY created_at DESC LIMIT 50
    ");
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    // site_comments テーブル未作成時はスキップ
}

$status_badge = [
    '準備中' => 'bg-blue-100 text-blue-700',
    '施工中' => 'bg-green-100 text-green-700',
    '完了'   => 'bg-gray-100 text-gray-500',
    '中断'   => 'bg-red-100 text-red-600',
];

renderHead($site['name']);
renderHeader('現場詳細');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-3xl mx-auto">

  <div class="bg-white rounded-xl border border-gray-100 p-5 mb-4">
    <div class="flex items-start justify-between mb-3">
      <h2 class="text-lg font-bold text-gray-800 leading-snug pr-3"><?= htmlspecialchars($site['name']) ?></h2>
      <span class="text-xs px-2 py-1 rounded-full font-medium shrink-0 <?= $status_badge[$site['status']] ?>">
        <?= $site['status'] ?>
      </span>
    </div>

    <div class="space-y-1.5 text-sm text-gray-600">
      <?php if ($site['address']): ?>
        <div><span class="text-xs text-gray-400 w-16 inline-block">住所</span><?= htmlspecialchars($site['address']) ?></div>
      <?php endif; ?>
      <?php if ($site['work_type']): ?>
        <div><span class="text-xs text-gray-400 w-16 inline-block">工事種類</span><?= htmlspecialchars($site['work_type']) ?></div>
      <?php endif; ?>
      <?php if ($site['start_date'] || $site['end_date']): ?>
        <div><span class="text-xs text-gray-400 w-16 inline-block">工期</span><?= $site['start_date'] ?? '—' ?> 〜 <?= $site['end_date'] ?? '—' ?></div>
      <?php endif; ?>
      <?php if ($site['supervisor_name']): ?>
        <div><span class="text-xs text-gray-400 w-16 inline-block">担当</span><?= htmlspecialchars($site['supervisor_name']) ?></div>
      <?php endif; ?>
    </div>

    <?php if ($site['memo']): ?>
      <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 mt-3">
        <?= nl2br(htmlspecialchars($site['memo'])) ?>
      </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
      <div class="flex gap-2 mt-4">
        <a href="/tamiya-home/pages/sites/edit.php?id=<?= $id ?>"
           class="flex-1 text-center border border-gray-300 text-gray-600 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50">編集する</a>
        <a href="/tamiya-home/pages/assignments/create.php?site_id=<?= $id ?>"
           class="flex-1 text-center bg-indigo-600 text-white font-semibold py-2 rounded-lg text-sm hover:bg-indigo-700">職人をアサイン</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="text-xs text-gray-400 font-medium mb-2">アサイン中　<?= count($current_craftsmen) ?> 人</div>
  <?php if ($current_craftsmen): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4 space-y-2">
      <?php foreach ($current_craftsmen as $c): ?>
        <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
          <div class="flex items-center gap-2">
            <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $c['id'] ?>"
               class="font-medium text-sm text-blue-700 hover:underline"><?= htmlspecialchars($c['name']) ?></a>
            <?= job_badge($c['job_type']) ?>
          </div>
          <span class="text-xs text-gray-400"><?= $c['start_date'] ?> 〜</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-6 bg-white rounded-xl border border-gray-100 mb-4">アサイン中の職人はいません</div>
  <?php endif; ?>

  <?php if ($past_craftsmen): ?>
    <div class="text-xs text-gray-400 font-medium mb-2">過去のアサイン</div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4 space-y-1.5">
      <?php foreach ($past_craftsmen as $c): ?>
        <div class="flex items-center justify-between py-1 border-b border-gray-50 last:border-0 text-sm text-gray-500">
          <div class="flex items-center gap-2">
            <span><?= htmlspecialchars($c['name']) ?></span>
            <?= job_badge($c['job_type']) ?>
          </div>
          <span class="text-xs text-gray-400"><?= $c['start_date'] ?> 〜 <?= $c['end_date'] ?? '—' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div id="comments">
    <div class="text-xs text-gray-400 font-medium mb-2">進捗コメント</div>

    <form method="post" action="/tamiya-home/pages/sites/comment_add.php"
          class="bg-white rounded-xl border border-gray-100 p-4 mb-3">
      <input type="hidden" name="site_id" value="<?= $id ?>">
      <textarea name="body" rows="2" maxlength="1000"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="現場の状況を記録..."></textarea>
      <div class="flex justify-end mt-3">
        <button type="submit"
          class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-gray-900">
          コメントを追加
        </button>
      </div>
    </form>

    <?php if ($comments): ?>
      <?php foreach ($comments as $c): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-2">
          <div class="flex justify-between items-start gap-3">
            <div class="min-w-0">
              <div class="text-sm text-gray-800 whitespace-pre-line"><?= htmlspecialchars($c['body']) ?></div>
              <div class="text-xs text-gray-400 mt-1">
                <?= htmlspecialchars($c['user_name']) ?> · <?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?>
              </div>
            </div>
            <?php if (isAdmin()): ?>
              <form method="post" action="/tamiya-home/pages/sites/comment_delete.php"
                    onsubmit="return confirm('削除しますか？')"
                    class="shrink-0">
                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="site_id" value="<?= $id ?>">
                <button type="submit" class="text-xs text-gray-300 hover:text-red-400">
                  削除
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="text-center text-gray-400 py-6 bg-white rounded-xl border border-gray-100">
        まだコメントはありません
      </div>
    <?php endif; ?>
  </div>

</main>

<?php
renderBottomNav('sites');
renderFoot();
?>
