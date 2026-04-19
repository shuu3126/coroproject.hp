<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) ? $_POST['action'] : '') === 'delete') {
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM news WHERE id = ?');
        $stmt->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'news', $id, 'ニュースを削除しました');
        set_flash('success', 'ニュースを削除しました。');
    }
    redirect_to($baseUrl . '/news.php');
}

$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$sql = 'SELECT id, title, date, tag, thumb, excerpt, url, is_published, sort_order FROM news';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE title LIKE ? OR tag LIKE ?';
    $params = ["%{$q}%", "%{$q}%"];
}
$sql .= ' ORDER BY sort_order ASC, date DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
start_page('お知らせ管理', '公開サイトのニュース情報を管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div>
      <h1>お知らせ管理</h1>
      <p>公開 news.php と同じDB構造に合わせて保存します。</p>
    </div>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/news_edit.php">新しく追加する</a>
  </section>
  <form method="get" class="card form-card form-grid two">
    <label><span>タイトル・タグで検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="actions-inline" style="align-self:end;"><button class="ghost-btn" type="submit">検索する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/news.php">条件をリセット</a></div>
  </form>
  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>日付</th><th>タグ</th><th>タイトル</th><th>公開</th><th>並び順</th><th>操作</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="empty-state">まだニュースがありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= h($row['id']) ?></td>
            <td><?= h($row['date']) ?></td>
            <td><?= h($row['tag']) ?></td>
            <td><?= h($row['title']) ?></td>
            <td><span class="status-badge <?= status_badge_class($row['is_published'] ? 'published' : 'draft') ?>"><?= $row['is_published'] ? '公開' : '非公開' ?></span></td>
            <td><?= h((string)$row['sort_order']) ?></td>
            <td class="actions-inline">
              <a class="ghost-btn" href="<?= h($baseUrl) ?>/news_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a>
              <form method="post" data-confirm="このニュースを削除しますか？">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                <button class="danger-btn" type="submit">削除</button>
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
