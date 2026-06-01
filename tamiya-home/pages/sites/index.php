<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$search    = trim($_GET['search']    ?? '');
$status    = trim($_GET['status']    ?? '');
$work_type = trim($_GET['work_type'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(s.name LIKE ? OR s.address LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($status !== '') {
    $where[]  = 's.status = ?';
    $params[] = $status;
}
if ($work_type !== '') {
    $where[]  = 's.work_type = ?';
    $params[] = $work_type;
}

$sql = "
    SELECT s.*,
           u.name AS supervisor_name,
           COUNT(DISTINCT a.craftsman_id) AS assigned_count
    FROM sites s
    LEFT JOIN users u ON s.supervisor_id = u.id
    LEFT JOIN assignments a
        ON a.site_id = s.id
        AND a.start_date <= CURDATE()
        AND (a.end_date IS NULL OR a.end_date >= CURDATE())
    WHERE " . implode(' AND ', $where) . "
    GROUP BY s.id
    ORDER BY FIELD(s.status,'施工中','準備中','中断','完了'), s.start_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sites = $stmt->fetchAll();

// 工事種類一覧（既存データから取得）
$work_types_stmt = $pdo->query("SELECT DISTINCT work_type FROM sites WHERE work_type IS NOT NULL AND work_type != '' ORDER BY work_type");
$work_types = $work_types_stmt->fetchAll(PDO::FETCH_COLUMN);

$statuses = ['準備中', '施工中', '完了', '中断'];
$status_badge = [
    '準備中' => 'bg-blue-100 text-blue-700',
    '施工中' => 'bg-green-100 text-green-700',
    '完了'   => 'bg-gray-100 text-gray-500',
    '中断'   => 'bg-red-100 text-red-600',
];

renderHead('現場一覧');
renderHeader('現場一覧');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-5xl mx-auto">

  <!-- 検索・絞り込み -->
  <form method="get" class="bg-white rounded-xl shadow-sm p-4 mb-4 space-y-3">
    <div class="flex gap-2">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
        placeholder="現場名・住所で検索..."
        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">検索</button>
    </div>
    <div class="flex gap-2">
      <select name="status" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">状態：すべて</option>
        <?php foreach ($statuses as $st): ?>
          <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($work_types): ?>
      <select name="work_type" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">工事種類：すべて</option>
        <?php foreach ($work_types as $wt): ?>
          <option value="<?= htmlspecialchars($wt) ?>" <?= $work_type === $wt ? 'selected' : '' ?>><?= htmlspecialchars($wt) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
    </div>
    <?php if ($search || $status || $work_type): ?>
      <a href="/tamiya-home/pages/sites/index.php" class="block text-center text-xs text-gray-400 underline">絞り込みをリセット</a>
    <?php endif; ?>
  </form>

  <!-- 件数 + 登録ボタン -->
  <div class="flex items-center justify-between mb-3">
    <span class="text-sm text-gray-500"><?= count($sites) ?> 件</span>
    <?php if (isAdmin()): ?>
      <a href="/tamiya-home/pages/sites/create.php"
         class="bg-green-600 text-white text-sm font-bold px-4 py-2 rounded-lg">＋ 現場登録</a>
    <?php endif; ?>
  </div>

  <!-- 現場リスト -->
  <?php if ($sites): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
      <?php foreach ($sites as $site): ?>
        <a href="/tamiya-home/pages/sites/detail.php?id=<?= $site['id'] ?>"
           class="block bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition <?= $site['status'] === '完了' ? 'opacity-60' : '' ?>">
          <div class="flex items-start justify-between mb-2">
            <div class="font-bold text-gray-800 text-sm leading-snug pr-2"><?= htmlspecialchars($site['name']) ?></div>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0 <?= $status_badge[$site['status']] ?>">
              <?= $site['status'] ?>
            </span>
          </div>
          <?php if ($site['address']): ?>
            <div class="text-xs text-gray-400 mb-1">📍 <?= htmlspecialchars($site['address']) ?></div>
          <?php endif; ?>
          <?php if ($site['work_type']): ?>
            <div class="text-xs text-gray-400 mb-1">🔧 <?= htmlspecialchars($site['work_type']) ?></div>
          <?php endif; ?>
          <div class="flex items-center justify-between mt-2 text-xs text-gray-400">
            <span><?= $site['start_date'] ?? '—' ?> 〜 <?= $site['end_date'] ?? '—' ?></span>
            <span class="text-blue-600 font-medium">👷 <?= $site['assigned_count'] ?>人</span>
          </div>
          <?php if ($site['supervisor_name']): ?>
            <div class="text-xs text-gray-400 mt-1">担当: <?= htmlspecialchars($site['supervisor_name']) ?></div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-12">現場が見つかりませんでした</div>
  <?php endif; ?>

</main>

<?php
renderBottomNav('sites');
renderFoot();
?>
