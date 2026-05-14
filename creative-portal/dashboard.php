<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();
$creatorInfo = cp_get_creator_info($pdo, $creator['creator_id']);
$projects = cp_fetch_projects($pdo, $creator['creator_id'], 100);
$latestProjects = array_slice($projects, 0, 6);
$submissions = cp_fetch_submissions($pdo, $creator['creator_id'], null, 5);
$invoices = cp_fetch_invoices($pdo, $creator['creator_id'], 5);
$statements = cp_fetch_statements($pdo, $creator['creator_id'], 5);
$notices = cp_fetch_notices($pdo, 4);

$activeCount = 0;
$dueSoonCount = 0;
$today = strtotime('today');
foreach ($projects as $project) {
    if (!in_array((string)$project['status'], ['完了', '納品'], true)) {
        $activeCount++;
        if (!empty($project['deadline'])) {
            $days = (int)((strtotime($project['deadline']) - $today) / 86400);
            if ($days >= 0 && $days <= 7) {
                $dueSoonCount++;
            }
        }
    }
}

$pendingInvoiceCount = 0;
foreach (cp_fetch_invoices($pdo, $creator['creator_id'], 100) as $invoice) {
    if (in_array((string)$invoice['status'], ['pending', 'rejected'], true)) {
        $pendingInvoiceCount++;
    }
}

$unpaidTotal = 0;
foreach (cp_fetch_statements($pdo, $creator['creator_id'], 100) as $statement) {
    if ((string)$statement['status'] !== 'paid') {
        $unpaidTotal += (float)$statement['net_amount'];
    }
}

cp_start_page('ダッシュボード', '制作進行と支払まわりをまとめて確認できます。');
?>
<div class="cp-grid four">
  <section class="cp-card cp-stat">
    <small>進行中案件</small>
    <strong><?= cp_h((string)$activeCount) ?></strong>
    <span>ポータル共有中の案件</span>
  </section>
  <section class="cp-card cp-stat">
    <small>7日以内の納期</small>
    <strong><?= cp_h((string)$dueSoonCount) ?></strong>
    <span>要確認の締切</span>
  </section>
  <section class="cp-card cp-stat">
    <small>請求対応</small>
    <strong><?= cp_h((string)$pendingInvoiceCount) ?></strong>
    <span>確認待ち・差し戻し</span>
  </section>
  <section class="cp-card cp-stat">
    <small>支払予定額</small>
    <strong><?= cp_h(cp_format_money($unpaidTotal)) ?></strong>
    <span>未支払の明細合計</span>
  </section>
</div>

<div class="cp-grid aside cp-mt">
  <div class="cp-grid">
    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>制作案件</h2>
          <p>共有されている案件の直近一覧です。</p>
        </div>
        <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/projects.php">一覧へ</a>
      </div>
      <div class="cp-table-wrap">
        <table class="cp-table">
          <thead>
            <tr>
              <th>案件</th>
              <th>ステータス</th>
              <th>納期</th>
              <th class="cp-text-right">支払予定</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$latestProjects): ?>
            <tr><td colspan="5" class="cp-empty">共有中の案件はまだありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($latestProjects as $project): ?>
            <tr>
              <td><strong><?= cp_h($project['title']) ?></strong><div class="cp-muted cp-small"><?= cp_h($project['category']) ?></div></td>
              <td><span class="cp-badge <?= cp_h(cp_project_status_class($project['status'])) ?>"><?= cp_h($project['status']) ?></span></td>
              <td><?= cp_h(cp_format_date($project['deadline'] ?? '')) ?></td>
              <td class="cp-text-right"><?= cp_h(cp_format_money($project['creator_amount'] ?? 0)) ?></td>
              <td><a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/project.php?id=<?= urlencode($project['id']) ?>">開く</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>最近の提出</h2>
          <p>提出物の確認状況です。</p>
        </div>
      </div>
      <div class="cp-table-wrap">
        <table class="cp-table">
          <thead>
            <tr>
              <th>案件</th>
              <th>種別</th>
              <th>状態</th>
              <th>提出日</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$submissions): ?>
            <tr><td colspan="4" class="cp-empty">提出履歴はありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($submissions as $submission): $st = cp_submission_status($submission['status']); ?>
            <tr>
              <td><?= cp_h($submission['project_title'] ?: $submission['project_id']) ?></td>
              <td><?= cp_h(cp_submission_type_label($submission['submission_type'])) ?></td>
              <td><span class="cp-badge <?= cp_h($st['class']) ?>"><?= cp_h($st['label']) ?></span></td>
              <td><?= cp_h(cp_format_datetime($submission['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <aside class="cp-grid">
    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>お知らせ</h2>
          <p>CORO PROJECTからの共有</p>
        </div>
        <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/notifications.php">通知へ</a>
      </div>
      <div class="cp-card-pad cp-note-list">
        <?php if (!$notices): ?>
          <div class="cp-empty">お知らせはありません。</div>
        <?php endif; ?>
        <?php foreach ($notices as $notice): ?>
          <article class="cp-note">
            <h3><?= cp_h($notice['title']) ?></h3>
            <p><?= cp_h(mb_substr((string)$notice['body'], 0, 120)) ?></p>
            <div class="cp-muted cp-small cp-mt"><?= cp_h(cp_format_datetime($notice['published_at'] ?: $notice['created_at'])) ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>支払明細</h2>
          <p>直近の支払予定・支払済</p>
        </div>
        <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/billing.php">開く</a>
      </div>
      <div class="cp-card-pad cp-note-list">
        <?php if (!$statements): ?>
          <div class="cp-empty">支払明細はまだありません。</div>
        <?php endif; ?>
        <?php foreach ($statements as $statement): $st = cp_statement_status($statement['status']); ?>
          <article class="cp-note">
            <h3><?= cp_h($statement['subject']) ?></h3>
            <p><?= cp_h(cp_format_money($statement['net_amount'], $statement['currency'])) ?> / <?= cp_h(cp_format_date($statement['scheduled_at'] ?: $statement['paid_at'])) ?></p>
            <span class="cp-badge <?= cp_h($st['class']) ?>"><?= cp_h($st['label']) ?></span>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </aside>
</div>
<?php cp_end_page(); ?>
