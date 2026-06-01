<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

$errors = [];
$input  = ['name' => '', 'email' => '', 'role' => 'supervisor', 'password' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'name'     => trim($_POST['name']     ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'role'     => trim($_POST['role']     ?? ''),
        'password' => trim($_POST['password'] ?? ''),
    ];

    if ($input['name'] === '')     $errors[] = '名前は必須です。';
    if ($input['email'] === '')    $errors[] = 'メールアドレスは必須です。';
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'メールアドレスの形式が正しくありません。';
    if (strlen($input['password']) < 8) $errors[] = 'パスワードは8文字以上で入力してください。';
    if (!in_array($input['role'], ['admin', 'supervisor'])) $errors[] = 'ロールが不正です。';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$input['email']]);
        if ($check->fetch()) {
            $errors[] = 'このメールアドレスは既に使用されています。';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$input['name'], $input['email'], password_hash($input['password'], PASSWORD_DEFAULT), $input['role']]);
        log_action($pdo, 'create', 'ユーザー', $input['name'], $input['role'] === 'admin' ? '管理者' : '現場監督');
        header('Location: /tamiya-home/pages/users/index.php');
        exit;
    }
}

renderHead('ユーザー追加');
renderHeader('ユーザー追加');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-2xl mx-auto">

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
      <?php foreach ($errors as $e): ?><div>・<?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white rounded-xl shadow-sm p-5 space-y-4">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">名前 <span class="text-red-500">*</span></label>
      <input type="text" name="name" value="<?= htmlspecialchars($input['name']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="山田 太郎">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
      <input type="email" name="email" value="<?= htmlspecialchars($input['email']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="yamada@tamiya-home.jp">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">パスワード <span class="text-red-500">*</span></label>
      <input type="password" name="password"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="8文字以上">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">ロール</label>
      <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="supervisor" <?= $input['role'] === 'supervisor' ? 'selected' : '' ?>>現場監督（閲覧のみ）</option>
        <option value="admin"      <?= $input['role'] === 'admin'      ? 'selected' : '' ?>>管理者（全機能）</option>
      </select>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="/tamiya-home/pages/users/index.php"
         class="flex-1 text-center bg-gray-100 text-gray-600 font-bold py-3 rounded-xl text-sm">キャンセル</a>
      <button type="submit"
        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-xl text-sm">追加する</button>
    </div>

  </form>
</main>

<?php
renderBottomNav('');
renderFoot();
?>
