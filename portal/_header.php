<?php
$_portalFlash   = portal_flash_get();
$_portalTalent  = current_portal_talent();
$_portalCsrf    = portal_csrf_token();
$_portalPageTitle = isset($portalPageTitle) ? $portalPageTitle . ' | ころぷろポータル' : 'ころぷろポータル';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= portal_h($_portalPageTitle) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/png" href="<?= portal_h($portalBase) ?>/../images/logo.png">
  <link rel="stylesheet" href="<?= portal_h($portalBase) ?>/assets/css/portal.css?v=20260513-2">
</head>
<body>

<div class="portal-shell">

  <header class="portal-header">
    <div class="portal-header-inner">
      <a class="portal-brand" href="<?= portal_h($portalBase) ?>/dashboard.php">
        <span class="portal-brand-name">CORO PROJECT</span>
        <span class="portal-brand-sub">タレントポータル</span>
      </a>
      <?php if ($_portalTalent): ?>
      <nav class="portal-nav">
        <a href="<?= portal_h($portalBase) ?>/dashboard.php"  class="<?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php'  ? 'active' : '' ?>">ホーム</a>
        <a href="<?= portal_h($portalBase) ?>/submit.php"     class="<?= basename($_SERVER['SCRIPT_NAME']) === 'submit.php'     ? 'active' : '' ?>">収益報告</a>
        <a href="<?= portal_h($portalBase) ?>/history.php"    class="<?= basename($_SERVER['SCRIPT_NAME']) === 'history.php'    ? 'active' : '' ?>">提出履歴</a>
        <a href="<?= portal_h($portalBase) ?>/invoices.php"   class="<?= basename($_SERVER['SCRIPT_NAME']) === 'invoices.php'   ? 'active' : '' ?>">請求書・領収書</a>
        <a href="<?= portal_h($portalBase) ?>/settings.php"   class="<?= basename($_SERVER['SCRIPT_NAME']) === 'settings.php'   ? 'active' : '' ?>">設定</a>
      </nav>
      <div class="portal-user">
        <span class="portal-user-name"><?= portal_h($_portalTalent['talent_name']) ?></span>
        <a class="portal-logout-btn" href="<?= portal_h($portalBase) ?>/logout.php">ログアウト</a>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <main class="portal-main">

    <?php if ($_portalFlash): ?>
      <div class="portal-flash portal-flash--<?= portal_h($_portalFlash['type']) ?>">
        <?= portal_h($_portalFlash['message']) ?>
      </div>
    <?php endif; ?>
