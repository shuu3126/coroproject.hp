<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ((isset($_POST['action']) ? $_POST['action'] : '')) === 'delete') {
    $id = trim((isset($_POST['id']) ? $_POST['id'] : ''));
    if ($id !== '') {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM talent_platforms WHERE talent_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM talent_links WHERE talent_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM talents WHERE id = ?')->execute([$id]);
            $pdo->commit();
            write_admin_log($pdo, (int)$user['id'], 'delete', 'talent', null, 'タレントを削除しました', ['talent_id' => $id]);
            set_flash('success', 'タレントを削除しました。');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash('error', '削除に失敗しました: ' . $e->getMessage());
        }
    }
    redirect_to($baseUrl . '/talents.php');
}
$q = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$sql = 'SELECT id, name, kana, talent_group, status, debut, sort_order, is_published, updated_at FROM talents';
$params=[];
if ($q !== '') {
    $sql .= ' WHERE name LIKE ? OR kana LIKE ? OR id LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= ' ORDER BY sort_order ASC, debut ASC, name ASC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
start_page('タレント管理', '公開サイトのタレント情報を管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div><h1>タレント管理</h1><p>公開サイトで使用するタレント情報を管理します。</p></div>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/talent_edit.php">新しく追加する</a>
  </section>
  <form method="get" class="card form-card form-grid two">
    <label><span>ID・名前・かなで検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="actions-inline" style="align-self:end;"><button class="ghost-btn" type="submit">検索する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/talents.php">条件をリセット</a></div>
  </form>
  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>名前</th><th>かな</th><th>グループ</th><th>ステータス</th><th>デビュー日</th><th>公開</th><th>更新日</th><th>操作</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="9" class="empty-state">タレントがまだありません。</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= h($row['id']) ?></td>
            <td><?= h($row['name']) ?></td>
            <td><?= h($row['kana']) ?></td>
            <td><?= h($row['talent_group']) ?></td>
            <td><?= h($row['status']) ?></td>
            <td><?= h($row['debut']) ?></td>
            <td><span class="status-badge <?= status_badge_class($row['is_published'] ? 'published' : 'draft') ?>"><?= $row['is_published'] ? '公開' : '非公開' ?></span></td>
            <td><?= h(format_datetime((isset($row['updated_at']) ? $row['updated_at'] : ''))) ?></td>
            <td class="actions-inline">
              <a class="ghost-btn" href="<?= h($baseUrl) ?>/talent_edit.php?id=<?= urlencode($row['id']) ?>">編集</a>
              <form method="post" data-confirm="このタレントを削除しますか？">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($row['id']) ?>"><button class="danger-btn" type="submit">削除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php end_page(); ?>
