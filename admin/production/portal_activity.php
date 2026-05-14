<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$ready = admin_table_has_column($pdo, 'talent_portal_activity_logs', 'id');
$talentId = trim((string)($_GET['talent_id'] ?? ''));
$rows = [];
$talents = $pdo->query('SELECT id, name FROM talents ORDER BY sort_order ASC, name ASC')->fetchAll();

if ($ready) {
    $sql = '
        SELECT l.*, t.name AS talent_name
        FROM talent_portal_activity_logs l
        LEFT JOIN talents t ON t.id = l.talent_id
    ';
    $params = [];
    if ($talentId !== '') {
        $sql .= ' WHERE l.talent_id = ?';
        $params[] = $talentId;
    }
    $sql .= ' ORDER BY l.created_at DESC, l.id DESC LIMIT 300';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

start_page('タレントポータル通知・操作ログ', 'タレントポータル上の通知、ログイン、提出、設定変更などを確認します。');
?>
<main class="page-container">
  <?php if (!$ready): ?>
    <div class="card alert-box alert-error">操作ログ用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block with-actions">
    <div>
      <h1>タレントポータル通知・操作ログ</h1>
      <p>却下通知や提出履歴など、タレントポータル側に残る履歴を確認できます。</p>
    </div>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label>
      <span>タレント</span>
      <select name="talent_id">
        <option value="">すべて</option>
        <?php foreach ($talents as $t): ?>
          <option value="<?= h($t['id']) ?>" <?= selected($talentId, $t['id']) ?>><?= h($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="actions-inline" style="align-self:end;">
      <button class="ghost-btn" type="submit">絞り込み</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/production/portal_activity.php">リセット</a>
    </div>
  </form>

  <section class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>日時</th><th>タレント</th><th>操作</th><th>内容</th><th>IP</th><th>User Agent</th></tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6" class="empty-state">操作ログはありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= h(format_datetime($row['created_at'])) ?></td>
              <td><?= h($row['talent_name'] ?: $row['talent_id']) ?></td>
              <td><span class="status-badge muted"><?= h($row['action']) ?></span></td>
              <td><?= h($row['detail']) ?></td>
              <td><?= h($row['ip']) ?></td>
              <td class="muted" style="max-width:320px;word-break:break-all;"><?= h($row['user_agent']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php end_page(); ?>
