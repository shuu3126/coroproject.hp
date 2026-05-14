<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$portalRevenuePending = function_exists('accounting_portal_pending_count') ? accounting_portal_pending_count($pdo) : 0;
$profilePending = 0;
$twitchCount = 0;
$accountCount = 0;
$portalNoticeCount = 0;
$activityCount = 0;

try {
    if (admin_table_has_column($pdo, 'talent_profile_change_requests', 'status')) {
        $profilePending = (int)$pdo->query("SELECT COUNT(*) FROM talent_profile_change_requests WHERE status = 'pending'")->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'talent_twitch_csv_reports', 'id')) {
        $twitchCount = (int)$pdo->query('SELECT COUNT(*) FROM talent_twitch_csv_reports')->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'talent_portal_accounts', 'id')) {
        $accountCount = (int)$pdo->query('SELECT COUNT(*) FROM talent_portal_accounts')->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'talent_portal_notices', 'id')) {
        $portalNoticeCount = (int)$pdo->query('SELECT COUNT(*) FROM talent_portal_notices')->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'talent_portal_activity_logs', 'id')) {
        $activityCount = (int)$pdo->query('SELECT COUNT(*) FROM talent_portal_activity_logs')->fetchColumn();
    }
} catch (Exception $e) {
}

$portalCards = [
    [
        'label' => '収益確認',
        'desc' => 'ポータルから提出された収益の承認・却下を行います。',
        'href' => $baseUrl . '/accounting/revenues.php',
        'meta' => $portalRevenuePending > 0 ? $portalRevenuePending . '件確認待ち' : '確認待ちなし',
        'class' => $portalRevenuePending > 0 ? 'warning' : 'muted',
    ],
    [
        'label' => 'HP掲載情報申請',
        'desc' => 'タレント本人から届いた公開プロフィール変更申請を確認します。',
        'href' => $baseUrl . '/production/profile_requests.php',
        'meta' => $profilePending > 0 ? $profilePending . '件確認待ち' : '確認待ちなし',
        'class' => $profilePending > 0 ? 'warning' : 'muted',
    ],
    [
        'label' => 'Twitch CSV解析',
        'desc' => '提出CSVの集計結果と明細を確認します。',
        'href' => $baseUrl . '/production/twitch_reports.php',
        'meta' => $twitchCount . '件',
        'class' => 'muted',
    ],
    [
        'label' => 'ポータルアカウント',
        'desc' => 'ログインID、パスワード再設定、有効・無効を管理します。',
        'href' => $baseUrl . '/production/talent_portal.php',
        'meta' => $accountCount . '件',
        'class' => 'muted',
    ],
    [
        'label' => 'ポータルお知らせ',
        'desc' => 'タレントポータルに表示するお知らせを作成・編集します。',
        'href' => $baseUrl . '/production/notices.php',
        'meta' => $portalNoticeCount . '件',
        'class' => 'muted',
    ],
    [
        'label' => '通知・操作ログ',
        'desc' => 'ログイン、提出、差し戻し通知などの履歴を確認します。',
        'href' => $baseUrl . '/production/portal_activity.php',
        'meta' => $activityCount . '件',
        'class' => 'muted',
    ],
];

start_page('ポータル管理', 'タレントポータル関連の機能をまとめて管理します。');
?>
<main class="page-container">
  <section class="page-header-block">
    <h1>ポータル管理</h1>
    <p>タレント向けポータルの提出物、アカウント、お知らせ、ログをここから確認できます。</p>
  </section>

  <section class="card-grid three">
    <?php foreach ($portalCards as $card): ?>
      <a class="card menu-card" href="<?= h($card['href']) ?>">
        <div class="menu-card-head">
          <h3><?= h($card['label']) ?></h3>
          <span class="status-badge <?= h($card['class']) ?>"><?= h($card['meta']) ?></span>
        </div>
        <p><?= h($card['desc']) ?></p>
      </a>
    <?php endforeach; ?>
  </section>
</main>
<?php end_page(); ?>
