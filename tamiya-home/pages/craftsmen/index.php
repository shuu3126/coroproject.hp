<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$search   = trim($_GET['search']   ?? '');
$job_type = trim($_GET['job_type'] ?? '');
$status   = trim($_GET['status']   ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = 'c.name LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($job_type !== '') {
    $where[]  = 'c.job_type = ?';
    $params[] = $job_type;
}
if ($status !== '') {
    $where[]  = 'c.status = ?';
    $params[] = $status;
}

$sql = "
    SELECT c.*,
           GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ' / ') AS current_sites
    FROM craftsmen c
    LEFT JOIN assignments a
        ON a.craftsman_id = c.id
        AND a.start_date <= CURDATE()
        AND (a.end_date IS NULL OR a.end_date >= CURDATE())
    LEFT JOIN sites s ON a.site_id = s.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY c.id
    ORDER BY c.status = '退職', c.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$craftsmen = $stmt->fetchAll();

$job_types = ['解体', '鍛冶', '大工', '電気', '水道', '内装', 'その他'];
$statuses  = ['稼働中', '休業中', '退職'];
$status_badge = [
    '稼働中' => 'bg-green-100 text-green-700',
    '休業中' => 'bg-yellow-100 text-yellow-700',
    '退職'   => 'bg-gray-100 text-gray-400',
];

renderHead('職人一覧');
renderHeader('職人一覧');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-5xl mx-auto">

  <!-- 検索・絞り込み -->
  <form method="get" class="bg-white rounded-xl border border-gray-100 p-4 mb-4 space-y-3">
    <div class="flex gap-2">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
        placeholder="名前で検索..."
        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">検索</button>
    </div>
    <div class="flex gap-2">
      <select name="job_type" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">職種：すべて</option>
        <?php foreach ($job_types as $jt): ?>
          <option value="<?= $jt ?>" <?= $job_type === $jt ? 'selected' : '' ?>><?= $jt ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">状態：すべて</option>
        <?php foreach ($statuses as $st): ?>
          <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($search || $job_type || $status): ?>
      <a href="/tamiya-home/pages/craftsmen/index.php" class="block text-center text-xs text-gray-400 underline">リセット</a>
    <?php endif; ?>
  </form>

  <div class="flex items-center justify-between mb-3">
    <span class="text-sm text-gray-400"><?= count($craftsmen) ?> 人</span>
    <?php if (isAdmin()): ?>
      <a href="/tamiya-home/pages/craftsmen/create.php"
         class="bg-blue-600 text-white text-sm font-bold px-4 py-2 rounded-lg hover:bg-blue-700">+ 職人登録</a>
    <?php endif; ?>
  </div>

  <?php if ($craftsmen): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
      <?php foreach ($craftsmen as $c): ?>
        <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $c['id'] ?>"
           class="block bg-white rounded-xl border border-gray-100 p-4 hover:border-blue-200 transition <?= $c['status'] === '退職' ? 'opacity-50' : '' ?>">
          <div class="flex items-center justify-between mb-2">
            <div class="font-semibold text-gray-800"><?= htmlspecialchars($c['name']) ?></div>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $status_badge[$c['status']] ?>">
              <?= $c['status'] ?>
            </span>
          </div>
          <div class="flex items-center gap-2 mb-2">
            <?= job_badge($c['job_type']) ?>
            <?php if ($c['phone']): ?>
              <span class="text-xs text-gray-400"><?= htmlspecialchars($c['phone']) ?></span>
            <?php endif; ?>
          </div>
          <div class="text-sm <?= $c['current_sites'] ? 'text-blue-600' : 'text-gray-300' ?>">
            <?= $c['current_sites'] ? htmlspecialchars($c['current_sites']) : '現場なし' ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-12">職人が見つかりませんでした</div>
  <?php endif; ?>

</main>

<?php
renderBottomNav('craftsmen');
renderFoot();
?>
