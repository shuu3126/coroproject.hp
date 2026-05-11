<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id !== '') {
        $pdo->prepare('DELETE FROM biz_ext_talents WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'biz_ext_talent', $id, '所属外VTuberを削除しました');
        set_flash('success', '削除しました。');
    }
    redirect_to($baseUrl . '/business/ext_talents.php');
}

$q    = trim($_GET['q'] ?? '');
$sql  = 'SELECT * FROM biz_ext_talents WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR genre LIKE ?)';
    $params = ["%$q%", "%$q%"];
}
$sql .= ' ORDER BY name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

start_page('所属外VTuberリスト', '案件候補として提案できる所属外VTuberを管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div><h1>所属外VTuberリスト</h1><p>Business案件でキャスティング候補として使用するVTuberのリストです。</p></div>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/business/ext_talent_edit.php">新規追加</a>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label><span>名前・ジャンルで検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="actions-inline" style="align-self:end;">
      <button class="ghost-btn" type="submit">検索</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/business/ext_talents.php">リセット</a>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead><tr><th>名前</th><th>ジャンル</th><th>登録者数</th><th>チャンネルURL</th><th>操作</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="empty-state">まだ登録されていません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['name']) ?></td>
            <td><?= h($r['genre'] ?? '—') ?></td>
            <td class="text-right"><?= $r['subscriber_count'] ? h(format_money($r['subscriber_count'])) : '—' ?></td>
            <td><?= $r['channel_url'] ? '<a href="' . h($r['channel_url']) . '" target="_blank" rel="noopener">リンク</a>' : '—' ?></td>
            <td class="actions-inline">
              <a class="ghost-btn" href="<?= h($baseUrl) ?>/business/ext_talent_edit.php?id=<?= urlencode($r['id']) ?>">編集</a>
              <form method="post" data-confirm="削除しますか？">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= h($r['id']) ?>">
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
