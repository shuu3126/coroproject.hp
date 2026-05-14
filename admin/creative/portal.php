<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$accountCount = 0;
$submissionPending = 0;
$invoicePending = 0;
$statementCount = 0;
$noticeCount = 0;
$activityCount = 0;

try {
    if (admin_table_has_column($pdo, 'creative_portal_accounts', 'id')) {
        $accountCount = (int)$pdo->query('SELECT COUNT(*) FROM creative_portal_accounts')->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'creative_project_submissions', 'status')) {
        $submissionPending = (int)$pdo->query("SELECT COUNT(*) FROM creative_project_submissions WHERE status = 'submitted'")->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'creative_project_invoices', 'status')) {
        $invoicePending = (int)$pdo->query("SELECT COUNT(*) FROM creative_project_invoices WHERE status = 'pending'")->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'creative_payment_statements', 'id')) {
        $statementCount = (int)$pdo->query('SELECT COUNT(*) FROM creative_payment_statements')->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'creative_portal_notices', 'id')) {
        $noticeCount = (int)$pdo->query('SELECT COUNT(*) FROM creative_portal_notices')->fetchColumn();
    }
    if (admin_table_has_column($pdo, 'creative_portal_activity_logs', 'id')) {
        $activityCount = (int)$pdo->query('SELECT COUNT(*) FROM creative_portal_activity_logs')->fetchColumn();
    }
} catch (Exception $e) {
}

$cards = [
    [
        'label' => 'ポータルアカウント',
        'desc' => '専属デザイナー向けログインID、パスワード、有効状態を管理します。',
        'href' => $baseUrl . '/creative/portal_accounts.php',
        'meta' => $accountCount . '件',
        'class' => 'muted',
    ],
    [
        'label' => '提出物確認',
        'desc' => 'ラフ、初稿、納品データを確認し、承認・修正依頼・差し戻しを行います。',
        'href' => $baseUrl . '/creative/portal_submissions.php',
        'meta' => $submissionPending > 0 ? $submissionPending . '件確認待ち' : '確認待ちなし',
        'class' => $submissionPending > 0 ? 'warning' : 'muted',
    ],
    [
        'label' => '支払・請求',
        'desc' => '支払明細の作成、請求書の確認、領収書の管理を行います。',
        'href' => $baseUrl . '/creative/portal_billing.php',
        'meta' => $invoicePending > 0 ? $invoicePending . '件確認待ち' : $statementCount . '件',
        'class' => $invoicePending > 0 ? 'warning' : 'muted',
    ],
    [
        'label' => 'ポータルお知らせ',
        'desc' => 'Creativeポータルに表示するお知らせを作成・編集します。',
        'href' => $baseUrl . '/creative/portal_notices.php',
        'meta' => $noticeCount . '件',
        'class' => 'muted',
    ],
    [
        'label' => '通知・操作ログ',
        'desc' => 'ログイン、提出、請求書アップロードなどの履歴を確認します。',
        'href' => $baseUrl . '/creative/portal_activity.php',
        'meta' => $activityCount . '件',
        'class' => 'muted',
    ],
    [
        'label' => '制作案件管理',
        'desc' => '案件をポータルに共有するには、案件編集画面で共有を有効にします。',
        'href' => $baseUrl . '/creative/projects.php',
        'meta' => '案件へ',
        'class' => 'info',
    ],
];

start_page('Creativeポータル管理', '専属デザイナー向けポータルのアカウント、提出物、支払書類を管理します。');
?>
<main class="page-container">
  <?php if (!creative_portal_ready($pdo)): ?>
    <div class="card alert-box alert-error">Creativeポータル用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block">
    <h1>Creativeポータル管理</h1>
    <p>外部の専属デザイナーと、案件・提出・請求・支払明細をPC向けの画面でやり取りできます。</p>
  </section>

  <section class="card-grid three">
    <?php foreach ($cards as $card): ?>
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
