<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();
$status = trim((string)($_GET['status'] ?? ''));
$projects = cp_fetch_projects($pdo, $creator['creator_id'], 100, $status);
$statuses = ['受付', '企画・ラフ', '制作中', '確認中', '修正依頼', '納品', '完了'];

cp_start_page('制作案件', 'CORO PROJECTから共有された制作案件です。');
?>
<section class="cp-card">
  <div class="cp-card-head">
    <div>
      <h2>案件一覧</h2>
      <p>提出やコメントは各案件の詳細から行えます。</p>
    </div>
    <form method="get" class="cp-actions">
      <select name="status" aria-label="ステータス">
        <option value="">すべて</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= cp_h($s) ?>" <?= (string)$status === (string)$s ? 'selected' : '' ?>><?= cp_h($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="cp-btn-muted" type="submit">絞り込み</button>
      <?php if ($status !== ''): ?>
        <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/projects.php">解除</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="cp-table-wrap">
    <table class="cp-table">
      <thead>
        <tr>
          <th>案件</th>
          <th>カテゴリ</th>
          <th>ステータス</th>
          <th>納期</th>
          <th class="cp-text-right">支払予定</th>
          <th>更新日</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$projects): ?>
        <tr><td colspan="7" class="cp-empty">共有中の案件はありません。</td></tr>
      <?php endif; ?>
      <?php foreach ($projects as $project):
        $deadlineStyle = '';
        if (!empty($project['deadline']) && !in_array((string)$project['status'], ['納品', '完了'], true)) {
            $days = (int)((strtotime($project['deadline']) - strtotime('today')) / 86400);
            if ($days < 0) {
                $deadlineStyle = 'color:var(--danger);font-weight:800;';
            } elseif ($days <= 3) {
                $deadlineStyle = 'color:var(--warning);font-weight:800;';
            }
        }
      ?>
        <tr>
          <td>
            <strong><?= cp_h($project['title']) ?></strong>
            <?php if (!empty($project['portal_status_note'])): ?>
              <div class="cp-muted cp-small"><?= cp_h(mb_substr($project['portal_status_note'], 0, 80)) ?></div>
            <?php endif; ?>
          </td>
          <td><?= cp_h($project['category']) ?></td>
          <td><span class="cp-badge <?= cp_h(cp_project_status_class($project['status'])) ?>"><?= cp_h($project['status']) ?></span></td>
          <td style="<?= $deadlineStyle ?>"><?= cp_h(cp_format_date($project['deadline'] ?? '')) ?></td>
          <td class="cp-text-right"><?= cp_h(cp_format_money($project['creator_amount'] ?? 0)) ?></td>
          <td><?= cp_h(cp_format_datetime($project['updated_at'] ?? '')) ?></td>
          <td><a class="cp-btn" href="<?= cp_h($creativePortalBase) ?>/project.php?id=<?= urlencode($project['id']) ?>">詳細</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php cp_end_page(); ?>
