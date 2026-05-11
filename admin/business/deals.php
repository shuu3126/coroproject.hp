<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id !== '') {
        $pdo->prepare('DELETE FROM biz_deal_candidates WHERE deal_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM biz_deals WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'biz_deal', $id, '案件を削除しました');
        set_flash('success', '案件を削除しました。');
    }
    redirect_to($baseUrl . '/business/deals.php');
}

$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$sql = 'SELECT d.*, COALESCE(c.name,\'—\') AS client_name FROM biz_deals d LEFT JOIN clients c ON c.id = d.client_id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (d.title LIKE ? OR c.name LIKE ?)';
    $params = array_merge($params, ["%$q%", "%$q%"]);
}
if ($status !== '') {
    $sql .= ' AND d.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY d.updated_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statuses = ['相談中', '提案済み', '条件交渉中', '実施中', '完了', '不成立'];

start_page('案件管理', 'Business 事業部の全案件を管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div><h1>案件管理</h1><p>クライアントとVTuberをつなぐ全案件の一覧です。</p></div>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/business/deal_edit.php">新規案件を追加</a>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label><span>案件名・クライアント名で検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="form-grid two" style="gap:10px;">
      <label><span>ステータス</span>
        <select name="status">
          <option value="">すべて</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= h($s) ?>" <?= selected($status, $s) ?>><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="actions-inline" style="align-self:end;">
        <button class="ghost-btn" type="submit">検索</button>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/business/deals.php">リセット</a>
      </div>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead><tr><th>案件名</th><th>クライアント</th><th>ステータス</th><th>予算</th><th>開始日</th><th>終了日</th><th>操作</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="empty-state">案件がまだありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="<?= h($baseUrl) ?>/business/deal_edit.php?id=<?= urlencode($r['id']) ?>"><?= h($r['title']) ?></a></td>
            <td><?= h($r['client_name']) ?></td>
            <td><span class="status-badge <?= h(biz_deal_badge($r['status'])) ?>"><?= h($r['status']) ?></span></td>
            <td class="text-right"><?= $r['budget'] !== null ? '¥' . h(format_money($r['budget'])) : '—' ?></td>
            <td><?= h($r['start_date'] ?? '—') ?></td>
            <td><?= h($r['end_date'] ?? '—') ?></td>
            <td class="actions-inline">
              <a class="ghost-btn" href="<?= h($baseUrl) ?>/business/deal_edit.php?id=<?= urlencode($r['id']) ?>">編集</a>
              <form method="post" data-confirm="この案件を削除しますか？">
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
<?php end_page();

function biz_deal_badge($status) {
    switch ($status) {
        case '完了': return 'success';
        case '不成立': return 'danger';
        case '実施中': return 'warning';
        default: return 'muted';
    }
}
