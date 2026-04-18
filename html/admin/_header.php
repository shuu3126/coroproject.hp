<?php $flash = get_flash(); $currentUser = current_admin_user(); ?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(($page_title ?? '管理画面') . ' | ' . ($config['app']['site_title'] ?? 'CORO PROJECT 管理画面')) ?></title>
  <link rel="stylesheet" href="<?= h($baseUrl) ?>/assets/css/admin.css">
  <script defer src="<?= h($baseUrl) ?>/assets/js/admin.js"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">CORO PROJECT<span>統合管理画面</span></div>
    <nav class="nav-menu">
      <a href="<?= h($baseUrl) ?>/index.php">ホーム</a>
      <a href="<?= h($baseUrl) ?>/news.php">お知らせ管理</a>
      <a href="<?= h($baseUrl) ?>/talents.php">タレント管理</a>
      <a href="<?= h($baseUrl) ?>/accounting/index.php">会計システム</a>
      <a href="<?= h($baseUrl) ?>/logs.php">操作ログ</a>
      <a href="<?= h($baseUrl) ?>/settings.php">設定</a>
    </nav>
  </aside>
  <div class="main-area">
    <header class="topbar">
      <div>
        <div class="topbar-title"><?= h($page_title ?? '管理画面') ?></div>
        <?php if (!empty($page_description)): ?><div class="topbar-sub"><?= h($page_description) ?></div><?php endif; ?>
      </div>
      <div class="topbar-right">
        <?php if ($currentUser): ?>
          <span class="user-chip"><?= h($currentUser['display_name']) ?></span>
          <a class="ghost-btn" href="<?= h($baseUrl) ?>/logout.php">ログアウト</a>
        <?php endif; ?>
      </div>
    </header>
    <?php if ($flash): ?>
      <div class="alert-box alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
