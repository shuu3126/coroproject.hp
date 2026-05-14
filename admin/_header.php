<?php
$flash = get_flash();
$currentUser = current_admin_user();
$adminRoot = $baseUrl;
$faviconUrl = rtrim(admin_project_base_url($adminRoot), '/') . '/images/logo.png';
$_navCanManageUsers = admin_user_can_manage_users($currentUser);
$_navPortalPaths = [
    'production/portal.php',
    'production/profile_requests.php',
    'production/twitch_reports.php',
    'production/portal_activity.php',
    'production/talent_portal.php',
    'production/notices.php',
];
$_navCreativePortalPaths = [
    'creative/portal.php',
    'creative/portal_accounts.php',
    'creative/portal_submissions.php',
    'creative/portal_billing.php',
    'creative/portal_notices.php',
    'creative/portal_activity.php',
];

$_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$_scriptBasename = basename($_script);

// ナビセクション判定
$_navSection = 'production';
if ($_scriptBasename === 'index.php'
    && strpos($_script, '/admin/business/') === false
    && strpos($_script, '/admin/creative/') === false
    && strpos($_script, '/admin/accounting/') === false) {
    $_navSection = 'dashboard';
} elseif (strpos($_script, '/admin/business') !== false)       $_navSection = 'business';
elseif (strpos($_script, '/admin/crm') !== false)              $_navSection = 'business';
elseif (strpos($_script, '/admin/production') !== false)       $_navSection = 'production';
elseif (strpos($_script, '/admin/content') !== false)          $_navSection = 'production';
elseif (strpos($_script, '/admin/creative') !== false)         $_navSection = 'creative';
elseif (strpos($_script, '/admin/accounting/revenues.php') !== false
         || strpos($_script, '/admin/accounting/revenue_edit.php') !== false) $_navSection = 'production';
elseif (strpos($_script, '/admin/accounting') !== false)       $_navSection = 'accounting';
elseif (strpos($_script, '/admin/mail/') !== false
         || strpos($_script, '/admin/inquiries/') !== false
         || in_array($_scriptBasename, ['mail.php', 'mail_compose.php', 'mail_contacts.php', 'mail_detail.php', 'mail_settings.php', 'messages.php', 'message_detail.php'])) $_navSection = 'mail';
elseif ($_scriptBasename === 'logs.php')                       $_navSection = 'logs';
elseif ($_scriptBasename === 'settings.php')                   $_navSection = 'settings';

if (!function_exists('_nav_is_active')) {
    function _nav_is_active($href, $adminRoot) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $full = rtrim($adminRoot, '/') . '/' . ltrim($href, '/');
        return $script === $full || $script === rtrim($full, '/');
    }
}
if (!function_exists('_nav_is_any_active')) {
    function _nav_is_any_active($hrefs, $adminRoot) {
        foreach ((array)$hrefs as $href) {
            if (_nav_is_active($href, $adminRoot)) {
                return true;
            }
        }
        return false;
    }
}

// 未読メール数（メール + お問い合わせ合算）
$_nav_mail_unread = 0;
$_nav_profile_request_pending = 0;
$_nav_portal_pending = 0;
$_nav_creative_portal_pending = 0;
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        if (admin_table_has_column($pdo, 'mail_messages', 'status')) {
            $_nav_mail_unread += (int)$pdo->query("SELECT COUNT(*) FROM mail_messages WHERE mailbox = 'inbox' AND status = 'unread'")->fetchColumn();
        }
        if (admin_table_has_column($pdo, 'inquiries', 'status')) {
            $_nav_mail_unread += (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
        }
        if (admin_table_has_column($pdo, 'talent_profile_change_requests', 'status')) {
            $_nav_profile_request_pending = (int)$pdo->query("SELECT COUNT(*) FROM talent_profile_change_requests WHERE status = 'pending'")->fetchColumn();
        }
        $_nav_portal_pending = $_nav_profile_request_pending;
        if (function_exists('accounting_portal_pending_count')) {
            $_nav_portal_pending += accounting_portal_pending_count($pdo);
        }
        if (function_exists('creative_portal_pending_count')) {
            $_nav_creative_portal_pending = creative_portal_pending_count($pdo);
        }
    } catch (Exception $e) {}
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h((isset($page_title) ? $page_title : '管理画面')) . ' | ' . ($config['app']['site_title'] ?? 'CORO PROJECT 管理画面') ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= h($faviconUrl) ?>">
  <link rel="icon" type="image/png" sizes="192x192" href="<?= h($faviconUrl) ?>">
  <link rel="apple-touch-icon" href="<?= h($faviconUrl) ?>">
  <link rel="shortcut icon" href="<?= h($faviconUrl) ?>">
  <link rel="stylesheet" href="<?= h($adminRoot) ?>/assets/css/admin.css?v=20260514-header-pin">
  <script>
    window.CORO_ADMIN_SESSION = <?= json_encode([
        'timeoutMs' => ((int)($adminSessionIdleTimeout ?? 3600)) * 1000,
        'touchUrl' => rtrim($adminRoot, '/') . '/system/session_touch.php',
        'logoutUrl' => rtrim($adminRoot, '/') . '/logout.php?reason=timeout',
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    window.CORO_ADMIN_CSRF = <?= json_encode(admin_csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  </script>
  <script defer src="<?= h($adminRoot) ?>/assets/js/admin.js?v=20260430-1"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      CORO PROJECT
      <span>管理画面</span>
    </div>

    <nav class="nav-menu">

      <!-- 総合 -->
      <div class="nav-section">
        <a class="nav-section-toggle nav-direct-link <?= $_navSection === 'dashboard' ? 'active' : '' ?>" href="<?= h($adminRoot) ?>/index.php">
          <span class="nav-section-label">総合</span>
          <span class="nav-arrow">›</span>
        </a>
      </div>

      <div class="nav-divider"></div>

      <!-- Production -->
      <div class="nav-section">
        <button class="nav-section-toggle <?= $_navSection === 'production' ? 'open' : '' ?>" data-section="production">
          <span class="nav-section-label">Production</span>
          <span class="nav-arrow">›</span>
        </button>
        <div class="nav-section-items <?= $_navSection === 'production' ? 'open' : '' ?>">
          <a href="<?= h($adminRoot) ?>/production/talents.php" class="<?= _nav_is_active('production/talents.php', $adminRoot) ? 'active' : '' ?>">タレント管理</a>
          <a href="<?= h($adminRoot) ?>/content/news.php" class="<?= _nav_is_active('content/news.php', $adminRoot) ? 'active' : '' ?>">お知らせ管理</a>
          <a href="<?= h($adminRoot) ?>/accounting/revenues.php" class="<?= _nav_is_any_active(['accounting/revenues.php', 'accounting/revenue_edit.php'], $adminRoot) ? 'active' : '' ?>">収益入力</a>
          <a href="<?= h($adminRoot) ?>/production/portal.php" class="<?= _nav_is_any_active($_navPortalPaths, $adminRoot) ? 'active' : '' ?>">
            ポータル管理<?= $_nav_portal_pending > 0 ? '（' . h((string)$_nav_portal_pending) . '）' : '' ?>
          </a>
        </div>
      </div>

      <div class="nav-divider"></div>

      <!-- Business -->
      <div class="nav-section">
        <button class="nav-section-toggle <?= $_navSection === 'business' ? 'open' : '' ?>" data-section="business">
          <span class="nav-section-label">Business</span>
          <span class="nav-arrow">›</span>
        </button>
        <div class="nav-section-items <?= $_navSection === 'business' ? 'open' : '' ?>">
          <a href="<?= h($adminRoot) ?>/business/deals.php" class="<?= _nav_is_active('business/deals.php', $adminRoot) ? 'active' : '' ?>">案件管理</a>
          <a href="<?= h($adminRoot) ?>/business/ext_talents.php" class="<?= _nav_is_active('business/ext_talents.php', $adminRoot) ? 'active' : '' ?>">所属外VTuberリスト</a>
          <a href="<?= h($adminRoot) ?>/crm/clients.php" class="<?= _nav_is_active('crm/clients.php', $adminRoot) ? 'active' : '' ?>">クライアント管理</a>
          <a href="<?= h($adminRoot) ?>/content/news.php" class="<?= _nav_is_active('content/news.php', $adminRoot) ? 'active' : '' ?>">お知らせ管理</a>
        </div>
      </div>

      <div class="nav-divider"></div>

      <!-- Creative -->
      <div class="nav-section">
        <button class="nav-section-toggle <?= $_navSection === 'creative' ? 'open' : '' ?>" data-section="creative">
          <span class="nav-section-label">Creative</span>
          <span class="nav-arrow">›</span>
        </button>
        <div class="nav-section-items <?= $_navSection === 'creative' ? 'open' : '' ?>">
          <a href="<?= h($adminRoot) ?>/creative/projects.php" class="<?= _nav_is_active('creative/projects.php', $adminRoot) ? 'active' : '' ?>">制作案件管理</a>
          <a href="<?= h($adminRoot) ?>/creative/creators.php" class="<?= _nav_is_active('creative/creators.php', $adminRoot) ? 'active' : '' ?>">クリエイターリスト</a>
          <a href="<?= h($adminRoot) ?>/creative/portal.php" class="<?= _nav_is_any_active($_navCreativePortalPaths, $adminRoot) ? 'active' : '' ?>">
            Creativeポータル<?= $_nav_creative_portal_pending > 0 ? '（' . h((string)$_nav_creative_portal_pending) . '）' : '' ?>
          </a>
          <a href="<?= h($adminRoot) ?>/crm/clients.php" class="<?= _nav_is_active('crm/clients.php', $adminRoot) ? 'active' : '' ?>">クライアント管理</a>
          <a href="<?= h($adminRoot) ?>/content/news.php" class="<?= _nav_is_active('content/news.php', $adminRoot) ? 'active' : '' ?>">お知らせ管理</a>
        </div>
      </div>

      <div class="nav-divider"></div>

      <!-- 会計 -->
      <div class="nav-section">
        <button class="nav-section-toggle <?= $_navSection === 'accounting' ? 'open' : '' ?>" data-section="accounting">
          <span class="nav-section-label">会計</span>
          <span class="nav-arrow">›</span>
        </button>
        <div class="nav-section-items <?= $_navSection === 'accounting' ? 'open' : '' ?>">
          <a href="<?= h($adminRoot) ?>/accounting/invoices.php" class="<?= _nav_is_active('accounting/invoices.php', $adminRoot) ? 'active' : '' ?>">請求管理</a>
          <a href="<?= h($adminRoot) ?>/accounting/journals.php" class="<?= _nav_is_active('accounting/journals.php', $adminRoot) ? 'active' : '' ?>">記帳管理</a>
        </div>
      </div>


      <div class="nav-divider"></div>

      <!-- メール -->
      <div class="nav-section">
        <button class="nav-section-toggle <?= $_navSection === 'mail' ? 'open' : '' ?>" data-section="mail">
          <span class="nav-section-label">
            メール
            <?php if ($_nav_mail_unread > 0): ?>
              <span style="background:#e53e3e;color:#fff;border-radius:10px;padding:1px 7px;font-size:0.72em;margin-left:6px;font-weight:700;"><?= $_nav_mail_unread ?></span>
            <?php endif; ?>
          </span>
          <span class="nav-arrow">›</span>
        </button>
        <div class="nav-section-items <?= $_navSection === 'mail' ? 'open' : '' ?>">
          <a href="<?= h($adminRoot) ?>/mail/index.php?mailbox=inbox" class="<?= _nav_is_active('mail/index.php', $adminRoot) ? 'active' : '' ?>">受信トレイ</a>
          <a href="<?= h($adminRoot) ?>/mail/compose.php" class="<?= _nav_is_active('mail/compose.php', $adminRoot) ? 'active' : '' ?>">新規作成</a>
          <a href="<?= h($adminRoot) ?>/mail/contacts.php" class="<?= _nav_is_active('mail/contacts.php', $adminRoot) ? 'active' : '' ?>">宛先管理</a>
          <a href="<?= h($adminRoot) ?>/mail/settings.php" class="<?= _nav_is_active('mail/settings.php', $adminRoot) ? 'active' : '' ?>">メール設定</a>
        </div>
      </div>

      <div class="nav-divider"></div>

      <!-- 操作ログ -->
      <div class="nav-section">
        <a class="nav-section-toggle nav-direct-link <?= $_navSection === 'logs' ? 'active' : '' ?>" href="<?= h($adminRoot) ?>/system/logs.php">
          <span class="nav-section-label">操作ログ</span>
          <span class="nav-arrow">›</span>
        </a>
      </div>

      <div class="nav-divider"></div>

      <!-- 設定 -->
      <div class="nav-section">
        <button class="nav-section-toggle <?= $_navSection === 'settings' ? 'open' : '' ?>" data-section="settings">
          <span class="nav-section-label">設定</span>
          <span class="nav-arrow">›</span>
        </button>
        <div class="nav-section-items <?= $_navSection === 'settings' ? 'open' : '' ?>">
          <a href="<?= h($adminRoot) ?>/system/settings.php#settings-general">全体設定</a>
          <a href="<?= h($adminRoot) ?>/system/settings.php#settings-public-links">公開SNS・連絡先リンク</a>
          <a href="<?= h($adminRoot) ?>/system/settings.php#settings-mail">メール送信設定</a>
          <a href="<?= h($adminRoot) ?>/system/settings.php#settings-accounting">Production会計設定</a>
          <a href="<?= h($adminRoot) ?>/system/settings.php#settings-pdf">PDF設定</a>
          <?php if ($_navCanManageUsers): ?>
            <a href="<?= h($adminRoot) ?>/system/settings.php#settings-data-transfer">データ入出力</a>
            <a href="<?= h($adminRoot) ?>/system/settings.php#settings-admin-users">ログインユーザー管理</a>
          <?php endif; ?>
        </div>
      </div>

    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <div class="main-area">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" type="button" aria-label="メニュー">
        <span></span><span></span><span></span>
      </button>
      <div style="flex:1;min-width:0;">
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
      <div class="alert-box alert-<?= h($flash['type']) ?>" id="flash-banner">
        <span class="alert-icon"><?= $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : 'ℹ') ?></span>
        <?= h($flash['message']) ?>
      </div>
    <?php endif; ?>
<script>
(function () {
  var btn = document.getElementById('hamburger-btn');
  var sidebar = document.querySelector('.sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  if (!btn || !sidebar || !overlay) return;
  btn.addEventListener('click', function () {
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('open');
  });
  overlay.addEventListener('click', function () {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('open');
  });
})();
(function () {
  var banner = document.getElementById('flash-banner');
  if (!banner) return;
  requestAnimationFrame(function () {
    banner.classList.add('is-visible');
    setTimeout(function () {
      banner.classList.add('is-hiding');
      banner.addEventListener('transitionend', function () { banner.remove(); }, { once: true });
    }, 3500);
  });
})();
(function () {
  var token = window.CORO_ADMIN_CSRF || '';
  if (!token || !window.HTMLFormElement) return;

  function addCsrf(form) {
    if (!form || String(form.method || '').toLowerCase() !== 'post') return;
    var field = form.querySelector('input[name="_csrf"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = '_csrf';
      form.appendChild(field);
    }
    field.value = token;
  }

  document.addEventListener('submit', function (event) {
    addCsrf(event.target);
  }, true);

  var nativeSubmit = window.HTMLFormElement.prototype.submit;
  window.HTMLFormElement.prototype.submit = function () {
    addCsrf(this);
    return nativeSubmit.call(this);
  };

  document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(addCsrf);
})();
</script>
