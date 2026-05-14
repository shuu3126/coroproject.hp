<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent = current_portal_talent();
$logs = portal_fetch_activity_logs($pdo, $talent['talent_id'], 100);
$notices = portal_fetch_notices($pdo);
$revenueAlerts = portal_fetch_rejected_revenue_alerts($pdo, $talent['talent_id'], 20);

$portalPageTitle = '通知';
require __DIR__ . '/_header.php';
?>

<section class="portal-page-hero compact">
  <div>
    <p class="portal-kicker">NOTIFICATIONS</p>
    <h1>通知</h1>
    <p>運営からのお知らせ、収益報告の差し戻し、操作履歴を確認できます。</p>
  </div>
  <div class="portal-hero-orbit" aria-hidden="true"></div>
</section>

<?php if (!portal_activity_ready($pdo)): ?>
  <div class="portal-flash portal-flash--warning">操作ログ用のDB更新が未実行です。管理者は admin/portal_migrate.sql を再実行してください。</div>
<?php endif; ?>

<div class="portal-card">
  <div class="portal-card-title">重要なお知らせ</div>
  <?php if (!$revenueAlerts && !$notices): ?>
    <div class="portal-table-empty">現在通知はありません。</div>
  <?php else: ?>
    <div class="portal-activity-list">
      <?php foreach ($revenueAlerts as $alert): ?>
        <div class="portal-activity-item">
          <div class="portal-activity-dot danger"></div>
          <div>
            <strong><?= portal_h(sprintf('%04d年%d月分の収益報告が却下されました', $alert['year'], $alert['month'])) ?></strong>
            <span>
              <?= portal_h(substr($alert['updated_at'], 0, 16)) ?>
              <?php if (!empty($alert['portal_note'])): ?>
                / 理由: <?= portal_h($alert['portal_note']) ?>
              <?php endif; ?>
            </span>
            <div class="portal-activity-actions">
              <a class="portal-btn portal-btn-outline portal-btn-sm"
                 href="<?= portal_h($portalBase) ?>/submit.php?year=<?= (int)$alert['year'] ?>&month=<?= (int)$alert['month'] ?>">
                修正して再送信
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php foreach ($notices as $notice): ?>
        <div class="portal-activity-item">
          <div class="portal-activity-dot"></div>
          <div>
            <strong><?= portal_h($notice['title']) ?></strong>
            <span><?= portal_h(substr($notice['published_at'] ?? $notice['created_at'], 0, 16)) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="portal-card">
  <div class="portal-card-title">操作ログ</div>
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
