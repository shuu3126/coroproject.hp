<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY role, name")->fetchAll();

$role_badge = [
    'admin'      => 'bg-purple-100 text-purple-700',
    'supervisor' => 'bg-blue-100 text-blue-700',
];
$role_label = ['admin' => '管理者', 'supervisor' => '現場監督'];

renderHead('ユーザー管理');
renderHeader('ユーザー管理');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-3xl mx-auto">

  <div class="flex items-center justify-between mb-4">
    <span class="text-sm text-gray-500"><?= count($users) ?> アカウント</span>
    <a href="/tamiya-home/pages/users/create.php"
       class="bg-purple-600 text-white text-sm font-bold px-4 py-2 rounded-lg">＋ ユーザー追加</a>
  </div>

  <div class="space-y-2">
    <?php foreach ($users as $u): ?>
      <div class="bg-white rounded-xl shadow-sm p-4 flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 mb-0.5">
            <span class="font-bold text-gray-800"><?= htmlspecialchars($u['name']) ?></span>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $role_badge[$u['role']] ?>">
              <?= $role_label[$u['role']] ?>
            </span>
          </div>
          <div class="text-xs text-gray-400"><?= htmlspecialchars($u['email']) ?></div>
        </div>
        <?php if ($u['id'] != currentUser()['id']): ?>
          <a href="/tamiya-home/pages/users/edit.php?id=<?= $u['id'] ?>"
             class="text-sm text-blue-500 hover:underline shrink-0">編集</a>
        <?php else: ?>
          <span class="text-xs text-gray-300">（自分）</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

</main>

<?php
renderBottomNav('');
renderFoot();
?>
