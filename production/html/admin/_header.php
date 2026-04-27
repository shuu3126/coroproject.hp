<?php
global $baseUrl, $config;
$flash = get_flash();
$currentUser = current_admin_user();
$headerBaseUrl = isset($baseUrl) ? trim((string)$baseUrl) : '';
if ($headerBaseUrl === '') {
    if (function_exists('admin_detect_base_url')) {
        $headerBaseUrl = admin_detect_base_url($config['app']['base_url'] ?? '');
    } else {
        $requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $adminPos = strpos($requestPath, '/admin');
        $headerBaseUrl = $adminPos !== false
            ? '/' . trim(substr($requestPath, 0, $adminPos + 6), '/')
            : '/production/html/admin';
    }
    $baseUrl = $headerBaseUrl;
}
$adminRoot = rtrim($headerBaseUrl, '/');
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h((isset($page_title) ? $page_title : '管理画面')) . ' | ' . ($config['app']['site_title'] ?? 'CORO PROJECT 管理画面') ?></title>
  <link rel="stylesheet" href="<?= h($adminRoot) ?>/assets/css/admin.css?v=20260419-3">
  <script defer src="<?= h($adminRoot) ?>/assets/js/admin.js?v=20260419-3"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      CORO PROJECT
      <span>統合管理画面</span>
    </div>

    <nav class="nav-menu">
      <a href="<?= h($adminRoot) ?>/index.php">ホーム</a>
      <a href="<?= h($adminRoot) ?>/news.php">お知らせ管理</a>
      <a href="<?= h($adminRoot) ?>/talents.php">タレント管理</a>
      <a href="<?= h($adminRoot) ?>/accounting/index.php">会計システム</a>
      <a href="<?= h($adminRoot) ?>/logs.php">操作ログ</a>
      <a href="<?= h($adminRoot) ?>/settings.php">設定</a>
    </nav>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <div>
        <div class="topbar-title"><?= h(isset($page_title) ? $page_title : '管理画面') ?></div>
        <?php if (!empty($page_description)): ?>
          <div class="topbar-sub"><?= h($page_description) ?></div>
        <?php endif; ?>
      </div>

      <div class="topbar-right">
        <?php if ($currentUser): ?>
          <span class="user-chip"><?= h($currentUser['display_name']) ?></span>
        <?php endif; ?>
        <a class="ghost-btn" href="<?= h($adminRoot) ?>/logout.php">ログアウト</a>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="alert-box alert-<?= h($flash['type']) ?>">
        <?= h($flash['message']) ?>
      </div>
    <?php endif; ?>
