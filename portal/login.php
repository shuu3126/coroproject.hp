<?php
require_once __DIR__ . '/_bootstrap.php';

if (current_portal_talent()) {
    portal_redirect($portalBase . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId  = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $result   = portal_login($pdo, $loginId, $password);
    if (isset($result['success'])) {
        portal_redirect($portalBase . '/dashboard.php');
    } else {
        $error = $result['error'];
    }
}

$flash = portal_flash_get();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ログイン | ころぷろタレントポータル</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="<?= portal_h($portalBase) ?>/assets/css/portal.css?v=20260513">
</head>
<body class="portal-login-body">

<div class="portal-login-card">
  <div class="portal-login-brand">
    <span class="portal-login-brand-name">ころぷろじぇくと！</span>
    <span class="portal-login-brand-sub">タレントポータル</span>
  </div>

  <?php if ($flash): ?>
    <div class="portal-flash portal-flash--<?= portal_h($flash['type']) ?>" style="margin-bottom:20px;">
      <?= portal_h($flash['message']) ?>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="portal-flash portal-flash--error" style="margin-bottom:20px;">
      <?= portal_h($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="portal-form-group">
      <label for="login_id">ログインID</label>
      <input type="text" id="login_id" name="login_id"
             value="<?= portal_h($_POST['login_id'] ?? '') ?>"
             required autocomplete="username">
    </div>

    <div class="portal-form-group">
      <label for="password">パスワード</label>
      <input type="password" id="password" name="password"
             required autocomplete="current-password">
    </div>

    <button class="portal-btn portal-btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:8px;">
      ログイン
    </button>
  </form>

  <p style="font-size:11px;color:var(--muted);text-align:center;margin-top:20px;">
    ログインIDとパスワードは運営から発行されます。<br>
    不明な場合はDiscordまたはメールでご連絡ください。
  </p>
</div>

</body>
</html>
