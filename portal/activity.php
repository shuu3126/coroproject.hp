<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent = current_portal_talent();
$logs = portal_fetch_activity_logs($pdo, $talent['talent_id'], 100);

$portalPageTitle = '操作ログ';
require __DIR__ . '/_header.php';
?>

<section class="portal-page-hero compact">
  <div>
    <p class="portal-kicker">ACTIVITY LOG</p>
    <h1>操作ログ</h1>
    <p>ログイン、提出、設定変更、ファイル確認などの履歴を確認できます。</p>
  </div>
  <div class="portal-hero-orbit" aria-hidden="true"></div>
</section>

<?php if (!portal_activity_ready($pdo)): ?>
  <div class="portal-flash portal-flash--warning">操作ログ用のDB更新が未実行です。管理者は admin/portal_migrate.sql を再実行してください。</div>
<?php endif; ?>

<div class="portal-card">
  <?php if (!$logs): ?>
    <div class="portal-table-empty">まだ操作ログがありません。</div>
  <?php else: ?>
    <div class="portal-activity-list">
      <?php foreach ($logs as $log): ?>
        <div class="portal-activity-item">
          <div class="portal-activity-dot"></div>
          <div>
            <strong><?= portal_h($log['detail'] ?: $log['action']) ?></strong>
            <span><?= portal_h(substr($log['created_at'], 0, 16)) ?> / <?= portal_h($log['ip'] ?: '-') ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
