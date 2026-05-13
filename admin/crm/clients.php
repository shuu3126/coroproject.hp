<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id !== '') {
        $pdo->prepare('DELETE FROM clients WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'client', $id, 'クライアントを削除しました');
        set_flash('success', 'クライアントを削除しました。');
    }
    redirect_to($baseUrl . '/crm/clients.php');
}

$q    = trim($_GET['q'] ?? '');
$rank = trim($_GET['rank'] ?? '');
$sql  = 'SELECT c.*, (SELECT COUNT(*) FROM biz_deals WHERE client_id = c.id) + (SELECT COUNT(*) FROM cre_projects WHERE client_id = c.id) AS deal_count FROM clients c WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (c.name LIKE ? OR c.contact_person LIKE ? OR c.email LIKE ?)';
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}
if ($rank !== '') {
    $sql .= ' AND c.rank = ?';
    $params[] = $rank;
}
$sql .= ' ORDER BY c.updated_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

start_page('クライアント管理', '全事業部で共通のクライアント・取引先を一元管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div><h1>クライアント管理</h1><p>Business・Creative 両事業部で共通利用するクライアント一覧です。</p></div>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/crm/client_edit.php">新規追加</a>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label><span>名前・担当者・メールで検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="form-grid two" style="gap:10px;">
      <label><span>取引ランク</span>
        <select name="rank">
          <option value="">すべて</option>
          <option value="new" <?= selected($rank, 'new') ?>>新規</option>
          <option value="existing" <?= selected($rank, 'existing') ?>>既存</option>
        </select>
      </label>
      <div class="actions-inline" style="align-self:end;">
        <button class="ghost-btn" type="submit">検索</button>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/crm/clients.php">リセット</a>
      </div>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead><tr><th>名前</th><th>区分</th><th>担当者</th><th>メール</th><th>取引ランク</th><th>案件数</th><th>操作</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="empty-state">クライアントがまだありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['name']) ?></td>
            <td><?= h($r['category'] === 'company' ? '企業' : ($r['category'] === 'organization' ? '団体' : '個人')) ?></td>
            <td><?= h($r['contact_person'] ?? '') ?></td>
            <td><?= h($r['email'] ?? '') ?></td>
            <td><span class="status-badge <?= $r['rank'] === 'existing' ? 'success' : 'muted' ?>"><?= $r['rank'] === 'existing' ? '既存' : '新規' ?></span></td>
            <td class="text-right"><?= h((string)$r['deal_count']) ?></td>
            <td class="actions-inline">
              <a class="ghost-btn" href="<?= h($baseUrl) ?>/crm/client_edit.php?id=<?= urlencode($r['id']) ?>">編集</a>
              <form method="post" data-confirm="このクライアントを削除しますか？">
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
