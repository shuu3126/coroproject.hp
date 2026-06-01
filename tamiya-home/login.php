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
  <title>ログイン | タミヤホーム 職人管理</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Noto Sans JP', 'Inter', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-white flex">

  <!-- 左パネル（デスクトップのみ） -->
  <div class="hidden md:flex md:w-1/2 bg-zinc-900 flex-col justify-between p-12">
    <div>
      <div class="text-xs tracking-[0.3em] text-zinc-500 uppercase mb-3">Tamiya Home</div>
      <div class="text-white text-3xl font-light tracking-tight leading-snug">
        職人現場<br>管理システム
      </div>
    </div>
    <div>
      <div class="w-8 h-px bg-zinc-600 mb-6"></div>
      <p class="text-zinc-500 text-sm leading-relaxed font-light">
        現場アサインの一元管理。<br>
        誰がどこにいるか、いつでも把握。
      </p>
    </div>
  </div>

  <!-- 右パネル：フォーム -->
  <div class="w-full md:w-1/2 flex items-center justify-center px-8 py-12 bg-zinc-50">
    <div class="w-full max-w-sm">

      <!-- モバイル用ヘッダー -->
      <div class="md:hidden mb-10">
        <div class="text-xs tracking-widest text-zinc-400 uppercase mb-1">Tamiya Home</div>
        <div class="text-zinc-800 text-xl font-light">職人現場管理システム</div>
      </div>

      <h1 class="text-xl font-medium text-zinc-800 mb-1">ログイン</h1>
      <p class="text-sm text-zinc-400 mb-8 font-light">アカウント情報を入力してください</p>

      <?php if ($error): ?>
        <div class="border border-red-200 bg-red-50 text-red-600 text-sm rounded px-4 py-3 mb-6">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate class="space-y-5">
        <div>
          <label class="block text-xs font-medium text-zinc-400 tracking-widest uppercase mb-2">
            メールアドレス
          </label>
          <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            class="w-full bg-white border border-zinc-200 rounded px-4 py-3 text-sm text-zinc-800 placeholder-zinc-300 focus:outline-none focus:border-zinc-500 transition"
            placeholder="example@tamiya-home.jp"
            autocomplete="email"
          >
        </div>

        <div>
          <label class="block text-xs font-medium text-zinc-400 tracking-widest uppercase mb-2">
            パスワード
          </label>
          <input
            type="password"
            name="password"
            class="w-full bg-white border border-zinc-200 rounded px-4 py-3 text-sm text-zinc-800 focus:outline-none focus:border-zinc-500 transition"
            autocomplete="current-password"
          >
        </div>

        <button
          type="submit"
          class="w-full bg-zinc-900 hover:bg-zinc-700 text-white text-sm font-medium py-3 rounded tracking-widest uppercase transition"
        >
          ログイン
        </button>
      </form>

    </div>
  </div>

</body>
</html>
