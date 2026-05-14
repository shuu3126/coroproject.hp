<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();
$notices = cp_fetch_notices($pdo, 50);
$submissions = [];
foreach (cp_fetch_submissions($pdo, $creator['creator_id'], null, 100) as $submission) {
    if (in_array((string)$submission['status'], ['revision_requested', 'rejected'], true)) {
        $submissions[] = $submission;
    }
}
$invoices = [];
foreach (cp_fetch_invoices($pdo, $creator['creator_id'], 100) as $invoice) {
    if ((string)$invoice['status'] === 'rejected') {
        $invoices[] = $invoice;
    }
}

cp_start_page('通知', 'お知らせ、修正依頼、差し戻しを確認できます。');
?>
<div class="cp-grid two">
  <section class="cp-card">
    <div class="cp-card-head">
      <div>
        <h2>お知らせ</h2>
        <p>CORO PROJECTからの共通連絡です。</p>
      </div>
    </div>
    <div class="cp-card-pad cp-note-list">
      <?php if (!$notices): ?>
        <div class="cp-empty">お知らせはありません。</div>
      <?php endif; ?>
      <?php foreach ($notices as $notice): ?>
        <article class="cp-note">
          <h3><?= cp_h($notice['title']) ?></h3>
          <p><?= nl2br(cp_h($notice['body'])) ?></p>
          <div class="cp-muted cp-small cp-mt"><?= cp_h(cp_format_datetime($notice['published_at'] ?: $notice['created_at'])) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="cp-card">
    <div class="cp-card-head">
      <div>
        <h2>要対応</h2>
        <p>修正依頼や差し戻しの一覧です。</p>
      </div>
    </div>
    <div class="cp-card-pad cp-note-list">
      <?php if (!$submissions && !$invoices): ?>
        <div class="cp-empty">要対応の通知はありません。</div>
      <?php endif; ?>
      <?php foreach ($submissions as $submission): $st = cp_submission_status($submission['status']); ?>
        <article class="cp-note">
          <h3><?= cp_h($submission['project_title'] ?: $submission['project_id']) ?> / <?= cp_h($st['label']) ?></h3>
          <p><?= $submission['admin_note'] ? nl2br(cp_h($submission['admin_note'])) : '確認コメントはありません。' ?></p>
          <div class="cp-actions cp-mt">
            <span class="cp-badge <?= cp_h($st['class']) ?>"><?= cp_h($st['label']) ?></span>
            <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/project.php?id=<?= urlencode($submission['project_id']) ?>">案件を開く</a>
          </div>
        </article>
      <?php endforeach; ?>
      <?php foreach ($invoices as $invoice): ?>
        <article class="cp-note">
          <h3>請求書が差し戻されました</h3>
          <p><?= $invoice['admin_note'] ? nl2br(cp_h($invoice['admin_note'])) : '確認コメントはありません。' ?></p>
          <div class="cp-actions cp-mt">
            <span class="cp-badge danger">差し戻し</span>
            <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/billing.php">請求を確認</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<?php cp_end_page(); ?>
