<?php
require_once __DIR__ . '/_bootstrap.php';

if (cp_current_creator()) {
    cp_redirect($creativePortalBase . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cp_verify_csrf($_POST['_csrf'] ?? '')) {
        $error = '不正なリクエストです。ページを再読み込みしてください。';
    } else {
        $result = cp_login($pdo, $_POST['login_id'] ?? '', $_POST['password'] ?? '');
        if (!empty($result['success'])) {
            cp_redirect($creativePortalBase . '/dashboard.php');
        }
        $error = $result['error'] ?? 'ログインに失敗しました。';
    }
}

$projectBase = preg_replace('#/creative-portal/?$#', '', $creativePortalBase);
$logoUrl = rtrim($projectBase, '/') . '/images/logo.png';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Creative Portal Login | CORO PROJECT</title>
  <link rel="icon" type="image/png" href="<?= cp_h($logoUrl) ?>">
  <link rel="stylesheet" href="<?= cp_h($creativePortalBase) ?>/assets/css/portal.css?v=20260514-creative-portal">
</head>
<body class="cp-login-body">
  <form method="post" class="cp-login-card cp-form">
    <input type="hidden" name="_csrf" value="<?= cp_h(cp_csrf_token()) ?>">
    <div class="cp-login-brand">
      <img src="<?= cp_h($logoUrl) ?>" alt="">
      <span>
        <strong>ころぷろじぇくと</strong>
        <small>Creative Portal</small>
      </span>
    </div>
    <div>
      <h1>ログイン</h1>
      <p>制作案件、請求書、支払明細を確認できます。</p>
    </div>
    <?php if ($error !== ''): ?>
      <div class="cp-alert"><strong>ログインできませんでした</strong><?= cp_h($error) ?></div>
    <?php endif; ?>
    <label>
      <span class="cp-label">ログインID</span>
      <input type="text" name="login_id" autocomplete="username" required>
    </label>
    <label>
      <span class="cp-label">パスワード</span>
      <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button class="cp-btn" type="submit">ログイン</button>
  </form>
</body>
</html>
