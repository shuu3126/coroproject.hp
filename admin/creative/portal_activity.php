<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$ready = admin_table_has_column($pdo, 'creative_portal_activity_logs', 'id');
$creatorId = trim((string)($_GET['creator_id'] ?? ''));
$rows = [];
$creators = $pdo->query('SELECT id, name FROM cre_creators ORDER BY name ASC')->fetchAll();

if ($ready) {
    $sql = '
        SELECT l.*, c.name AS creator_name
        FROM creative_portal_activity_logs l
        LEFT JOIN cre_creators c ON c.id = l.creator_id
        WHERE 1=1
    ';
    $params = [];
    if ($creatorId !== '') {
        $sql .= ' AND l.creator_id = ?';
        $params[] = $creatorId;
    }
    $sql .= ' ORDER BY l.created_at DESC, l.id DESC LIMIT 300';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

start_page('Creativeポータル通知・操作ログ', 'Creativeポータル上のログイン、提出、請求書アップロードなどを確認します。');
?>
<main class="page-container">
  <?php if (!$ready): ?>
    <div class="card alert-box alert-error">操作ログ用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block">
    <h1>Creativeポータル通知・操作ログ</h1>
    <p>デザイナー側の操作履歴と通知につながる更新を確認できます。</p>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label>
      <span>クリエイター</span>
      <select name="creator_id">
        <option value="">すべて</option>
        <?php foreach ($creators as $creator): ?>
          <option value="<?= h($creator['id']) ?>" <?= selected($creatorId, $creator['id']) ?>><?= h($creator['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="actions-inline" style="align-self:end;">
      <button class="ghost-btn" type="submit">絞り込み</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/portal_activity.php">リセット</a>
    </div>
  </form>

  <section class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>日時</th><th>クリエイター</th><th>操作</th><th>内容</th><th>IP</th><th>User Agent</th></tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6" class="empty-state">操作ログはありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= h(format_datetime($row['created_at'])) ?></td>
              <td><?= h($row['creator_name'] ?: $row['creator_id']) ?></td>
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
