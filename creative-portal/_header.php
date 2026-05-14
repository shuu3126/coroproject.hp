<?php
$flash = cp_flash_get();
$currentCreator = cp_current_creator();
$creatorInfo = $currentCreator ? (cp_get_creator_info($pdo, $currentCreator['creator_id']) ?: []) : [];
$projectBase = preg_replace('#/creative-portal/?$#', '', $creativePortalBase);
$logoUrl = rtrim($projectBase, '/') . '/images/logo.png';
$scriptName = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));
$notificationCount = $currentCreator ? cp_notification_count($pdo, $currentCreator['creator_id']) : 0;
$creatorDisplayName = '';
if ($currentCreator) {
    $creatorDisplayName = trim((string)($creatorInfo['display_name'] ?? ''));
    if ($creatorDisplayName === '') {
        $creatorDisplayName = trim((string)($creatorInfo['name'] ?? ($currentCreator['creator_name'] ?? 'Creator')));
    }
}

if (!function_exists('cp_nav_active')) {
    function cp_nav_active($file, $scriptName) {
        return $file === $scriptName ? 'active' : '';
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= cp_h($page_title ?? 'Creative Portal') ?> | CORO PROJECT</title>
  <link rel="icon" type="image/png" href="<?= cp_h($logoUrl) ?>">
  <link rel="apple-touch-icon" href="<?= cp_h($logoUrl) ?>">
  <link rel="stylesheet" href="<?= cp_h($creativePortalBase) ?>/assets/css/portal.css?v=20260514-mobile-refresh">
  <script>
    window.CORO_CREATIVE_PORTAL_CSRF = <?= json_encode(cp_csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  </script>
</head>
<body>
<div class="cp-shell">
  <aside class="cp-sidebar">
    <a class="cp-brand" href="<?= cp_h($creativePortalBase) ?>/dashboard.php">
      <img src="<?= cp_h($logoUrl) ?>" alt="" width="36" height="36">
      <span>
        <strong>ころぷろじぇくと</strong>
        <small>Creative Portal</small>
      </span>
    </a>

    <?php if ($currentCreator): ?>
    <div class="cp-mobile-actions">
      <a class="cp-mobile-bell" href="<?= cp_h($creativePortalBase) ?>/notifications.php" aria-label="通知">
        <span><?= (int)min(99, $notificationCount) ?></span>
      </a>
      <a class="cp-mobile-avatar" href="<?= cp_h($creativePortalBase) ?>/profile.php" aria-label="登録情報">
        <?= cp_h(mb_substr($creatorDisplayName !== '' ? $creatorDisplayName : 'C', 0, 1)) ?>
      </a>
    </div>
    <?php endif; ?>

    <nav class="cp-nav" aria-label="Creative Portal">
      <a class="<?= cp_nav_active('dashboard.php', $scriptName) ?>" href="<?= cp_h($creativePortalBase) ?>/dashboard.php">ダッシュボード</a>
      <a class="<?= cp_nav_active('projects.php', $scriptName) || cp_nav_active('project.php', $scriptName) ? 'active' : '' ?>" href="<?= cp_h($creativePortalBase) ?>/projects.php">制作案件</a>
      <a class="<?= cp_nav_active('billing.php', $scriptName) ?>" href="<?= cp_h($creativePortalBase) ?>/billing.php">支払・請求</a>
      <a class="<?= cp_nav_active('notifications.php', $scriptName) ?>" href="<?= cp_h($creativePortalBase) ?>/notifications.php">
        通知<?= $notificationCount > 0 ? '<span>' . cp_h((string)$notificationCount) . '</span>' : '' ?>
      </a>
      <a class="<?= cp_nav_active('activity.php', $scriptName) ?>" href="<?= cp_h($creativePortalBase) ?>/activity.php">操作ログ</a>
      <a class="<?= cp_nav_active('profile.php', $scriptName) ?>" href="<?= cp_h($creativePortalBase) ?>/profile.php">登録情報</a>
    </nav>

    <div class="cp-sidebar-foot">
      <div class="cp-user">
        <div class="cp-user-name"><?= cp_h($creatorDisplayName !== '' ? $creatorDisplayName : 'Creator') ?></div>
        <div class="cp-user-meta"><?= cp_h($currentCreator['login_id'] ?? '') ?></div>
      </div>
      <a class="cp-logout" href="<?= cp_h($creativePortalBase) ?>/logout.php">ログアウト</a>
    </div>
  </aside>

  <?php if ($currentCreator): ?>
  <nav class="cp-bottom-nav" aria-label="Creative Portal">
    <a href="<?= cp_h($creativePortalBase) ?>/dashboard.php" class="<?= cp_nav_active('dashboard.php', $scriptName) ?>">
      <span class="cp-bottom-icon home" aria-hidden="true"></span>
      <span>ホーム</span>
    </a>
    <a href="<?= cp_h($creativePortalBase) ?>/projects.php" class="<?= cp_nav_active('projects.php', $scriptName) || cp_nav_active('project.php', $scriptName) ? 'active' : '' ?>">
      <span class="cp-bottom-icon projects" aria-hidden="true"></span>
      <span>案件</span>
    </a>
    <a href="<?= cp_h($creativePortalBase) ?>/billing.php" class="<?= cp_nav_active('billing.php', $scriptName) ?>">
      <span class="cp-bottom-icon billing" aria-hidden="true"></span>
      <span>請求</span>
    </a>
    <a href="<?= cp_h($creativePortalBase) ?>/notifications.php" class="<?= cp_nav_active('notifications.php', $scriptName) || cp_nav_active('activity.php', $scriptName) ? 'active' : '' ?>">
      <span class="cp-bottom-icon notice" aria-hidden="true"></span>
      <span>通知</span>
    </a>
    <a href="<?= cp_h($creativePortalBase) ?>/profile.php" class="<?= cp_nav_active('profile.php', $scriptName) ?>">
      <span class="cp-bottom-icon profile" aria-hidden="true"></span>
      <span>マイページ</span>
    </a>
  </nav>
  <?php endif; ?>

  <main class="cp-main">
    <header class="cp-topbar">
      <div>
        <h1><?= cp_h($page_title ?? 'Creative Portal') ?></h1>
        <?php if (!empty($page_description)): ?>
          <p><?= cp_h($page_description) ?></p>
        <?php endif; ?>
      </div>
      <div class="cp-topbar-right">
        <span class="cp-chip"><?= cp_h($creatorInfo['availability_status'] ?? 'available') ?></span>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="cp-flash cp-flash-<?= cp_h($flash['type'] ?? 'info') ?>"><?= cp_h($flash['message'] ?? '') ?></div>
    <?php endif; ?>

    <section class="cp-content">
