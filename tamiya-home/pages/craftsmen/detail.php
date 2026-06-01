<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /tamiya-home/pages/craftsmen/index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM craftsmen WHERE id = ?');
$stmt->execute([$id]);
$craftsman = $stmt->fetch();
if (!$craftsman) { header('Location: /tamiya-home/pages/craftsmen/index.php'); exit; }

$today = date('Y-m-d');
$warn_date = date('Y-m-d', strtotime('+60 days'));

$qualifications = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM qualifications WHERE craftsman_id = ? ORDER BY expiry_date IS NULL, expiry_date ASC");
    $stmt->execute([$id]);
    $qualifications = $stmt->fetchAll();
} catch (PDOException $e) {
    // qualifications テーブル未作成時はスキップ
}

$stmt = $pdo->prepare("
    SELECT a.*, s.name AS site_name
    FROM assignments a
    JOIN sites s ON a.site_id = s.id
    WHERE a.craftsman_id = ?
    ORDER BY a.start_date DESC
    LIMIT 50
");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$status_badge = [
    '稼働中' => 'bg-green-100 text-green-700',
    '休業中' => 'bg-yellow-100 text-yellow-700',
    '退職'   => 'bg-gray-100 text-gray-400',
];

renderHead($craftsman['name']);
renderHeader('職人詳細');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-3xl mx-auto">

  <div class="bg-white rounded-xl border border-gray-100 p-5 mb-4">
    <div class="flex items-center justify-between mb-3">
      <div>
        <div class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($craftsman['name']) ?></div>
        <div class="flex items-center gap-2">
          <?= job_badge($craftsman['job_type']) ?>
          <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $status_badge[$craftsman['status']] ?>">
            <?= $craftsman['status'] ?>
          </span>
        </div>
      </div>
    </div>

    <?php if ($craftsman['phone']): ?>
      <div class="text-sm text-gray-600 mb-2">
        <span class="text-gray-400 text-xs mr-1">TEL</span>
        <a href="tel:<?= htmlspecialchars($craftsman['phone']) ?>" class="text-blue-600">
          <?= htmlspecialchars($craftsman['phone']) ?>
        </a>
      </div>
    <?php endif; ?>

    <?php if ($craftsman['memo']): ?>
      <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 mt-3">
        <?= nl2br(htmlspecialchars($craftsman['memo'])) ?>
      </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
      <a href="/tamiya-home/pages/craftsmen/edit.php?id=<?= $id ?>"
         class="block mt-4 text-center border border-gray-300 text-gray-600 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50">
        編集する
      </a>
    <?php endif; ?>
  </div>

  <div class="flex items-center justify-between mb-2">
    <h2 class="text-sm font-semibold text-gray-500">資格・免許</h2>
    <?php if (isAdmin()): ?>
      <a href="/tamiya-home/pages/qualifications/create.php?craftsman_id=<?= $id ?>"
         class="text-sm bg-blue-600 text-white font-bold px-3 py-2 rounded-lg hover:bg-blue-700">＋ 資格追加</a>
    <?php endif; ?>
  </div>
  <?php if ($qualifications): ?>
    <div class="space-y-2 mb-5">
      <?php foreach ($qualifications as $q): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="font-medium text-sm text-gray-800"><?= htmlspecialchars($q['name']) ?></div>
              <div class="text-xs text-gray-400 mt-1">
                取得日: <?= $q['issued_date'] ? htmlspecialchars($q['issued_date']) : '未設定' ?>
              </div>
            </div>
            <div class="shrink-0"><?= qualification_expiry_badge($q['expiry_date']) ?></div>
          </div>

          <?php if ($q['note']): ?>
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 mt-3">
              <?= nl2br(htmlspecialchars($q['note'])) ?>
            </div>
          <?php endif; ?>

          <?php if (isAdmin()): ?>
            <form method="post" action="/tamiya-home/pages/qualifications/delete.php"
                  onsubmit="return confirm('<?= htmlspecialchars($q['name']) ?> を削除しますか？')"
                  class="mt-3 text-right">
              <input type="hidden" name="id" value="<?= $q['id'] ?>">
              <button type="submit" class="text-xs text-red-500 hover:text-red-700 border border-red-200 rounded-lg px-3 py-1.5">
                削除
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-8 bg-white rounded-xl border border-gray-100 mb-5">資格・免許が登録されていません</div>
  <?php endif; ?>

  <h2 class="text-sm font-semibold text-gray-500 mb-2">アサイン履歴</h2>
  <?php if ($history): ?>
    <div class="space-y-2">
      <?php foreach ($history as $row):
        $is_active = $row['start_date'] <= $today && ($row['end_date'] === null || $row['end_date'] >= $today);
      ?>
        <div class="bg-white rounded-xl border <?= $is_active ? 'border-blue-200' : 'border-gray-100' ?> p-4">
          <div class="flex items-center justify-between">
            <a href="/tamiya-home/pages/sites/detail.php?id=<?= $row['site_id'] ?>"
               class="font-medium text-sm <?= $is_active ? 'text-blue-700' : 'text-gray-700' ?> hover:underline">
              <?= htmlspecialchars($row['site_name']) ?>
            </a>
            <?php if ($is_active): ?>
              <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">稼働中</span>
            <?php endif; ?>
          </div>
          <div class="text-xs text-gray-400 mt-1">
            <?= $row['start_date'] ?> 〜 <?= $row['end_date'] ?? '終了日未定' ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-8 bg-white rounded-xl border border-gray-100">アサイン履歴がありません</div>
  <?php endif; ?>

</main>

<?php
renderBottomNav('craftsmen');
renderFoot();
?>
