<?php
require_once __DIR__ . '/_bootstrap.php';

if (current_admin_user()) {
    redirect_to($baseUrl . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = trim((string)($_POST['login_id'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE login_id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$loginId]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['admin_user'] = [
            'id' => (int)$user['id'],
            'login_id' => $user['login_id'],
            'display_name' => $user['display_name'],
        ];
        $_SESSION['login_at'] = time();

        $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        write_admin_log($pdo, (int)$user['id'], 'login', 'admin_user', (int)$user['id'], 'Admin login');

        redirect_to($baseUrl . '/index.php');
    }

    set_flash('error', 'Invalid login ID or password.');
    redirect_to($baseUrl . '/login.php');
}

<<<<<<< Updated upstream
$page_title = 'ログイン';
=======
>>>>>>> Stashed changes
$adminRoot = $baseUrl;
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | CORO PROJECT Admin</title>
  <link rel="stylesheet" href="<?= h($adminRoot) ?>/assets/css/admin.css?v=20260427-2">
</head>
<body class="login-body">
<div class="login-card">
  <div class="login-brand">CORO PROJECT</div>
  <h1>Admin Login</h1>
  <p>Access news, talents, inquiries, and accounting.</p>

  <?php $flash = get_flash(); if ($flash): ?>
    <div class="alert-box alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <form method="post" class="form-stack">
    <label>
      <span>Login ID</span>
      <input type="text" name="login_id" required>
    </label>

    <label>
      <span>Password</span>
      <input type="password" name="password" required>
    </label>

    <button class="primary-btn" type="submit">Login</button>
  </form>
</div>
</body>
</html>
