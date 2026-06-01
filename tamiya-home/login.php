<?php
require_once __DIR__ . '/db/connect.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /tamiya-home/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'メールアドレスとパスワードを入力してください。';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: /tamiya-home/dashboard.php');
            exit;
        } else {
            $error = 'メールアドレスまたはパスワードが正しくありません。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン | タミヤホーム</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 min-h-screen flex items-center justify-center px-4">
  <div class="bg-white rounded-2xl shadow-md w-full max-w-sm p-8">
    <div class="text-center mb-8">
      <div class="text-4xl mb-2">🏠</div>
      <h1 class="text-xl font-bold text-blue-700">タミヤホーム</h1>
      <p class="text-sm text-gray-400 mt-1">職人現場管理システム</p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3 mb-5">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
        <input
          type="email"
          name="email"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
          placeholder="admin@tamiya-home.jp"
          autocomplete="email"
        >
      </div>
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
        <input
          type="password"
          name="password"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
          autocomplete="current-password"
        >
      </div>
      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg text-sm transition"
      >
        ログイン
      </button>
    </form>
  </div>
</body>
</html>
