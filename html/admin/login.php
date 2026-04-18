<?php
require_once __DIR__ . '/_bootstrap.php';

if (current_admin_user()) {
    redirect_to($baseUrl . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = trim((isset($_POST['login_id']) ? $_POST['login_id'] : ''));
    $password = (isset($_POST['password']) ? $_POST['password'] : '');

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE login_id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$loginId]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_user'] = [
            'id' => (int)$user['id'],
            'login_id' => $user['login_id'],
            'display_name' => $user['display_name'],
        ];
        $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        write_admin_log($pdo, (int)$user['id'], 'login', 'admin_user', (int)$user['id'], 'ログインしました');
        redirect_to($baseUrl . '/index.php');
    }

    set_flash('error', 'ログイン情報が正しくありません。');
    redirect_to($baseUrl . '/login.php');
}
$page_title = 'ログイン';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ログイン | <?= h($config['app']['site_title']) ?></title>
  <link rel="stylesheet" href="<?= h($baseUrl) ?>/assets/css/admin.css">
</head>
<body class="login-body">
<div class="login-card">
  <div class="login-brand">CORO PROJECT</div>
  <h1>管理システム</h1>
  <p>お知らせ管理・タレント管理・会計システムをまとめて操作できます。</p>
  <?php $flash = get_flash(); if ($flash): ?>
    <div class="alert-box alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>
  <form method="post" class="form-stack">
    <label><span>ログインID</span><input type="text" name="login_id" required></label>
    <label><span>パスワード</span><input type="password" name="password" required></label>
    <button class="primary-btn" type="submit">ログインする</button>
  </form>
</div>
</body>
</html>
