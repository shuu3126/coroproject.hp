<?php
$_portalFlash   = portal_flash_get();
$_portalTalent  = current_portal_talent();
$_portalCsrf    = portal_csrf_token();
$_portalPageTitle = isset($portalPageTitle) ? $portalPageTitle . ' | ころぷろポータル' : 'ころぷろポータル';
$_portalInfo = null;
if ($_portalTalent && isset($pdo) && $pdo instanceof PDO) {
    $_portalInfo = portal_get_talent_info($pdo, $_portalTalent['talent_id']);
}
$_portalAvatar = $_portalInfo && !empty($_portalInfo['avatar']) ? '/' . ltrim((string)$_portalInfo['avatar'], '/') : '';
$_portalNoticeCount = 0;
if ($_portalTalent && isset($pdo) && $pdo instanceof PDO) {
    $_portalNoticeCount = portal_notification_count($pdo, $_portalTalent['talent_id']);
}
$_portalScript = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= portal_h($_portalPageTitle) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/png" href="<?= portal_h($portalBase) ?>/../images/logo.png">
  <link rel="stylesheet" href="<?= portal_h($portalBase) ?>/assets/css/portal.css?v=20260612-glossy-trend">
</head>
<body>

<div class="portal-shell">

  <header class="portal-header">
    <div class="portal-header-inner">
      <a class="portal-brand" href="<?= portal_h($portalBase) ?>/dashboard.php">
        <img class="portal-brand-logo" src="<?= portal_h($portalBase) ?>/../images/logo.png" alt="" aria-hidden="true">
        <span>
          <span class="portal-brand-name">ころぷろじぇくと</span>
          <span class="portal-brand-sub">タレントポータル</span>
        </span>
      </a>
      <?php if ($_portalTalent): ?>
      <nav class="portal-nav">
        <a href="<?= portal_h($portalBase) ?>/dashboard.php"  class="<?= $_portalScript === 'dashboard.php'  ? 'active' : '' ?>">ホーム</a>
        <a href="<?= portal_h($portalBase) ?>/submit.php"     class="<?= $_portalScript === 'submit.php'     ? 'active' : '' ?>">収益報告</a>
        <a href="<?= portal_h($portalBase) ?>/twitch.php"     class="<?= $_portalScript === 'twitch.php'     ? 'active' : '' ?>">Twitch CSV</a>
        <a href="<?= portal_h($portalBase) ?>/history.php"    class="<?= $_portalScript === 'history.php'    ? 'active' : '' ?>">提出履歴</a>
        <a href="<?= portal_h($portalBase) ?>/invoices.php"   class="<?= $_portalScript === 'invoices.php'   ? 'active' : '' ?>">書類</a>
        <a href="<?= portal_h($portalBase) ?>/settings.php"   class="<?= $_portalScript === 'settings.php'   ? 'active' : '' ?>">マイページ</a>
      </nav>
      <div class="portal-user">
        <a class="portal-bell" href="<?= portal_h($portalBase) ?>/activity.php" aria-label="通知">
          <?php if ($_portalNoticeCount > 0): ?><span><?= (int)min(99, $_portalNoticeCount) ?></span><?php endif; ?>
        </a>
        <span class="portal-user-name"><?= portal_h($_portalTalent['talent_name']) ?></span>
        <a class="portal-avatar" href="<?= portal_h($portalBase) ?>/settings.php" aria-label="マイページ">
          <?php if ($_portalAvatar): ?><img src="<?= portal_h($_portalAvatar) ?>" alt=""><?php endif; ?>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <?php if ($_portalTalent): ?>
  <nav class="portal-bottom-nav" aria-label="タレントポータル">
    <a href="<?= portal_h($portalBase) ?>/dashboard.php"  class="<?= $_portalScript === 'dashboard.php'  ? 'active' : '' ?>">
      <span class="portal-bottom-icon home" aria-hidden="true"></span>
      <span>ホーム</span>
    </a>
    <a href="<?= portal_h($portalBase) ?>/history.php"     class="<?= in_array($_portalScript, ['history.php', 'invoices.php', 'twitch.php'], true) ? 'active' : '' ?>">
      <span class="portal-bottom-icon revenue" aria-hidden="true"></span>
      <span>収益</span>
    </a>
    <a href="<?= portal_h($portalBase) ?>/submit.php"    class="<?= $_portalScript === 'submit.php' ? 'active' : '' ?>">
      <span class="portal-bottom-icon submit" aria-hidden="true"></span>
      <span>提出物</span>
    </a>
    <a href="<?= portal_h($portalBase) ?>/activity.php"   class="<?= $_portalScript === 'activity.php' ? 'active' : '' ?>">
      <span class="portal-bottom-icon notice" aria-hidden="true"></span>
      <span>通知</span>
    </a>
    <a href="<?= portal_h($portalBase) ?>/settings.php"   class="<?= $_portalScript === 'settings.php'   ? 'active' : '' ?>">
      <span class="portal-bottom-icon my" aria-hidden="true"></span>
      <span>マイページ</span>
    </a>
  </nav>
  <?php endif; ?>

  <main class="portal-main">

    <?php if ($_portalFlash): ?>
      <div class="portal-flash portal-flash--<?= portal_h($_portalFlash['type']) ?>">
        <?= portal_h($_portalFlash['message']) ?>
      </div>
    <?php endif; ?>
