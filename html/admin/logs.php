<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';
require_admin_login();
$q = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$sql = 'SELECT l.*, COALESCE(u.display_name, "system") AS user_name FROM admin_logs l LEFT JOIN admin_users u ON u.id = l.user_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE l.summary LIKE ? OR l.target_type LIKE ? OR l.action_type LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= ' ORDER BY l.created_at DESC LIMIT 300';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
start_page('操作ログ', '管理画面で行われた操作履歴を確認します。');
?>
<main class="page-container">
  <form method="get" class="card form-card form-grid two">
    <label><span>キーワード検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="actions-inline" style="align-self:end;"><button class="ghost-btn" type="submit">検索する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/logs.php">条件をリセット</a></div>
  </form>
  <div class="card table-card mt-24"><div class="table-wrap"><table><thead><tr><th>日時</th><th>ユーザー</th><th>操作</th><th>対象</th><th>概要</th></tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr><td><?= h(format_datetime($row['created_at'])) ?></td><td><?= h($row['user_name']) ?></td><td><?= h($row['action_type']) ?></td><td><?= h($row['target_type']) ?><?= $row['target_id'] ? ' #' . h((string)$row['target_id']) : '' ?></td><td><?= h($row['summary']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
</main>
<?php end_page(); ?>
