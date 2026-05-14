<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id !== '') {
        $pdo->prepare('DELETE FROM cre_projects WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'cre_project', $id, '制作案件を削除しました');
        set_flash('success', '削除しました。');
    }
    redirect_to($baseUrl . '/creative/projects.php');
}

$q        = trim($_GET['q'] ?? '');
$status   = trim($_GET['status'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = 'SELECT p.*, COALESCE(c.name,\'—\') AS client_name, COALESCE(cr.name,\'—\') AS creator_name
        FROM cre_projects p
        LEFT JOIN clients c ON c.id = p.client_id
        LEFT JOIN cre_creators cr ON cr.id = p.creator_id
        WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.title LIKE ? OR c.name LIKE ? OR cr.name LIKE ?)';
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}
if ($status !== '')   { $sql .= ' AND p.status = ?';   $params[] = $status; }
if ($category !== '') { $sql .= ' AND p.category = ?'; $params[] = $category; }
$sql .= ' ORDER BY p.updated_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statuses   = ['受付', '企画・ラフ', '制作中', '確認中', '修正依頼', '納品', '完了'];
$categories = ['illustration' => 'イラスト', 'live2d' => 'Live2D', 'single_art' => '一枚絵', 'music' => '音楽', 'video' => '動画', 'other' => 'その他'];

start_page('制作案件管理', '');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <h1>制作案件管理</h1>
    <div class="actions-inline">
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/portal.php">Creativeポータル</a>
      <a class="primary-btn" href="<?= h($baseUrl) ?>/creative/project_edit.php">+ 新規案件</a>
    </div>
  </section>

  <form method="get" class="card form-card" style="padding:12px 16px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
      <div style="flex:2;min-width:180px;">
        <label style="margin:0;"><span>検索</span><input type="text" name="q" value="<?= h($q) ?>" placeholder="案件名・依頼者・クリエイター名"></label>
      </div>
      <div style="flex:1;min-width:120px;">
        <label style="margin:0;"><span>ステータス</span>
          <select name="status">
            <option value="">すべて</option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= h($s) ?>" <?= selected($status, $s) ?>><?= h($s) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div style="flex:1;min-width:120px;">
        <label style="margin:0;"><span>カテゴリ</span>
          <select name="category">
            <option value="">すべて</option>
            <?php foreach ($categories as $val => $label): ?>
              <option value="<?= h($val) ?>" <?= selected($category, $val) ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="actions-inline" style="padding-top:18px;">
        <button class="ghost-btn" type="submit">絞り込み</button>
        <?php if ($q !== '' || $status !== '' || $category !== ''): ?>
          <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/projects.php">クリア</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="card table-card mt-16">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>案件名</th>
            <th>依頼者</th>
            <th>カテゴリ</th>
            <th>担当</th>
            <th>ステータス</th>
            <th>納期</th>
            <th style="width:100px;"></th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="empty-state">案件がありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $deadline = $r['deadline'] ?? null;
          $daysLeft = $deadline ? (int)((strtotime($deadline) - strtotime('today')) / 86400) : null;
          $deadlineLabel = $deadline ?? '—';
          $deadlineStyle = '';
          if ($daysLeft !== null && !in_array($r['status'], ['完了', '納品'])) {
              if ($daysLeft < 0)     $deadlineStyle = 'color:var(--danger);font-weight:600;';
              elseif ($daysLeft <= 3) $deadlineStyle = 'color:var(--warning);font-weight:600;';
          }
        ?>
          <tr>
            <td>
              <a href="<?= h($baseUrl) ?>/creative/project_edit.php?id=<?= urlencode($r['id']) ?>" style="font-weight:600;"><?= h($r['title']) ?></a>
            </td>
            <td class="muted"><?= h($r['client_name']) ?></td>
            <td class="muted" style="font-size:.82em;"><?= h($categories[$r['category']] ?? $r['category']) ?></td>
            <td class="muted"><?= h($r['creator_name']) ?></td>
            <td><span class="status-badge <?= h(cre_project_badge($r['status'])) ?>"><?= h($r['status']) ?></span></td>
            <td style="<?= $deadlineStyle ?> font-size:.85em;"><?= h($deadlineLabel) ?></td>
            <td>
              <div class="actions-inline">
                <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/project_edit.php?id=<?= urlencode($r['id']) ?>">編集</a>
                <form method="post" data-confirm="「<?= h($r['title']) ?>」を削除しますか？">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= h($r['id']) ?>">
                  <button class="danger-btn" type="submit">削除</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php end_page();

function cre_project_badge($status) {
    switch ($status) {
        case '完了':   return 'success';
        case '納品':
        case '修正依頼':
        case '確認中': return 'warning';
        case '制作中': return 'info';
        default:       return 'muted';
    }
}
