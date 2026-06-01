<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$filter_type = trim($_GET['type'] ?? '');
$filter_action = trim($_GET['action'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filter_type !== '') {
    $where[]  = 'target_type = ?';
    $params[] = $filter_type;
}
if ($filter_action !== '') {
    $where[]  = 'action = ?';
    $params[] = $filter_action;
}

$stmt = $pdo->prepare("
    SELECT * FROM activity_logs
    WHERE " . implode(' AND ', $where) . "
    ORDER BY created_at DESC
    LIMIT 300
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$action_badge = [
    'create' => 'bg-green-100 text-green-700',
    'update' => 'bg-blue-100 text-blue-700',
    'delete' => 'bg-red-100 text-red-700',
];
$action_label = ['create' => '登録', 'update' => '編集', 'delete' => '削除'];
$types = ['職人', '現場', 'アサイン', 'ユーザー'];

renderHead('操作ログ');
renderHeader('操作ログ');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-5xl mx-auto">

  <!-- フィルター -->
  <form method="get" class="flex flex-wrap gap-2 items-center mb-5">
    <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
      <option value="">対象：すべて</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= $t ?>" <?= $filter_type === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <select name="action" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
      <option value="">操作：すべて</option>
      <option value="create" <?= $filter_action === 'create' ? 'selected' : '' ?>>登録</option>
      <option value="update" <?= $filter_action === 'update' ? 'selected' : '' ?>>編集</option>
      <option value="delete" <?= $filter_action === 'delete' ? 'selected' : '' ?>>削除</option>
    </select>
    <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">絞り込み</button>
    <?php if ($filter_type || $filter_action): ?>
      <a href="/tamiya-home/pages/logs/index.php" class="text-xs text-gray-400 underline">リセット</a>
    <?php endif; ?>
    <span class="text-xs text-gray-400 ml-auto"><?= count($logs) ?> 件（最新300件）</span>
  </form>

  <?php if ($logs): ?>
    <!-- デスクトップ: テーブル -->
    <div class="hidden md:block bg-white rounded-xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-4 py-3 text-xs text-gray-400 font-medium w-40">日時</th>
            <th class="text-left px-4 py-3 text-xs text-gray-400 font-medium w-28">操作者</th>
            <th class="text-left px-4 py-3 text-xs text-gray-400 font-medium w-16">操作</th>
            <th class="text-left px-4 py-3 text-xs text-gray-400 font-medium w-20">対象種別</th>
            <th class="text-left px-4 py-3 text-xs text-gray-400 font-medium">対象名</th>
            <th class="text-left px-4 py-3 text-xs text-gray-400 font-medium">詳細</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $i => $log): ?>
            <tr class="border-b border-gray-50 last:border-0 <?= $i % 2 === 1 ? 'bg-gray-50/50' : '' ?>">
              <td class="px-4 py-3 text-xs text-gray-400"><?= $log['created_at'] ?></td>
              <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['user_name']) ?></td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-0.5 rounded font-medium <?= $action_badge[$log['action']] ?? '' ?>">
                  <?= $action_label[$log['action']] ?? $log['action'] ?>
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-gray-500"><?= htmlspecialchars($log['target_type']) ?></td>
              <td class="px-4 py-3 text-sm text-gray-800"><?= htmlspecialchars($log['target_name']) ?></td>
              <td class="px-4 py-3 text-xs text-gray-400"><?= htmlspecialchars($log['detail'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- モバイル: カード -->
    <div class="md:hidden space-y-2">
      <?php foreach ($logs as $log): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <span class="text-xs px-2 py-0.5 rounded font-medium <?= $action_badge[$log['action']] ?? '' ?>">
                <?= $action_label[$log['action']] ?? $log['action'] ?>
              </span>
              <span class="text-xs text-gray-400"><?= htmlspecialchars($log['target_type']) ?></span>
            </div>
            <span class="text-xs text-gray-300"><?= substr($log['created_at'], 0, 16) ?></span>
          </div>
          <div class="font-medium text-sm text-gray-800"><?= htmlspecialchars($log['target_name']) ?></div>
          <?php if ($log['detail']): ?>
            <div class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($log['detail']) ?></div>
          <?php endif; ?>
          <div class="text-xs text-gray-400 mt-1">by <?= htmlspecialchars($log['user_name']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-12 bg-white rounded-xl border border-gray-100">操作ログがありません</div>
  <?php endif; ?>

</main>

<?php
renderBottomNav('');
renderFoot();
?>
