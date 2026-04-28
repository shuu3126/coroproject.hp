<?php
global $baseUrl, $config;

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    $baseUrl = $headerBaseUrl;
}

if (!function_exists('admin_header_trim_url_path')) {
    function admin_header_trim_url_path($path) {
        $path = '/' . trim((string)$path, '/');
        return $path === '/' ? '' : $path;
    }
}

if (!function_exists('admin_header_detect_root')) {
    function admin_header_detect_root($configuredBaseUrl = '') {
        $requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        if ($requestPath !== '') {
            $adminPos = strpos($requestPath, '/admin');
            if ($adminPos !== false) {
                $afterAdmin = substr($requestPath, $adminPos + 6, 1);
                if ($afterAdmin === '' || $afterAdmin === '/') {
                    return admin_header_trim_url_path(substr($requestPath, 0, $adminPos + 6));
                }
            }
        }

        $configuredBaseUrl = trim((string)$configuredBaseUrl);
        if ($configuredBaseUrl !== '') {
            return admin_header_trim_url_path($configuredBaseUrl);
        }

        $scriptPath = (string)(parse_url((string)($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH) ?: '');
        $scriptDir = admin_header_trim_url_path(dirname($scriptPath));
        if (basename($scriptDir) === 'accounting') {
            $scriptDir = admin_header_trim_url_path(dirname($scriptDir));
        }

        return $scriptDir !== '' ? $scriptDir : '/production/html/admin';
    }
}

$baseUrl = admin_header_detect_root($config['app']['base_url'] ?? ($baseUrl ?? ''));
$adminRoot = rtrim($baseUrl, '/');
if ($adminRoot === '') {
    $adminRoot = '/production/html/admin';
}
$flash = function_exists('get_flash') ? get_flash() : null;
$currentUser = function_exists('current_admin_user') ? current_admin_user() : null;
$titleText = isset($page_title) ? $page_title : 'Admin';
$siteTitleText = 'CORO PROJECT 管理画面';
?>
<!doctype html>
<html lang="ja">
<head>
  <!-- admin-header-fix-20260427-5 -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titleText) ?> | <?= h($siteTitleText) ?></title>
  <link rel="stylesheet" href="<?= h($adminRoot) ?>/assets/css/admin.css?v=20260427-5">
  <script defer src="<?= h($adminRoot) ?>/assets/js/admin.js?v=20260427-5"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      CORO PROJECT
      <span>Admin Console</span>
    </div>

    <nav class="nav-menu">
      <a href="<?= h($adminRoot) ?>/index.php">ホーム</a>
      <a href="<?= h($adminRoot) ?>/news.php">お知らせ管理</a>
      <a href="<?= h($adminRoot) ?>/talents.php">タレント管理</a>
      <a href="<?= h($adminRoot) ?>/inquiries.php">お問い合わせ管理</a>
      <a href="<?= h($adminRoot) ?>/accounting/index.php">会計システム</a>
      <a href="<?= h($adminRoot) ?>/logs.php">操作ログ</a>
      <a href="<?= h($adminRoot) ?>/settings.php">設定</a>
    </nav>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <div>
        <div class="topbar-title"><?= h($titleText) ?></div>
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
