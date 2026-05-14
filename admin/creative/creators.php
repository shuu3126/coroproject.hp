<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id !== '') {
        $pdo->prepare('DELETE FROM cre_creators WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'cre_creator', $id, 'クリエイターを削除しました');
        set_flash('success', '削除しました。');
    }
    redirect_to($baseUrl . '/creative/creators.php');
}

$q    = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? '');
$sql  = 'SELECT * FROM cre_creators WHERE 1=1';
$params = [];
if ($q !== '') {
    if (admin_table_has_column($pdo, 'cre_creators', 'email')) {
        $sql .= ' AND (name LIKE ? OR contact LIKE ? OR email LIKE ?)';
        $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    } else {
        $sql .= ' AND (name LIKE ? OR contact LIKE ?)';
        $params[] = "%$q%"; $params[] = "%$q%";
    }
}
if ($type !== '') { $sql .= ' AND type = ?'; $params[] = $type; }
$sql .= ' ORDER BY is_active DESC, name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

start_page('クリエイターリスト', '');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <h1>クリエイターリスト</h1>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/creative/creator_edit.php">+ クリエイターを追加</a>
  </section>

  <form method="get" class="card form-card" style="padding:12px 16px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
      <div style="flex:2;min-width:160px;">
        <label style="margin:0;"><span>検索</span><input type="text" name="q" value="<?= h($q) ?>" placeholder="名前・連絡先"></label>
      </div>
      <div style="flex:1;min-width:110px;">
        <label style="margin:0;"><span>種別</span>
          <select name="type">
            <option value="">すべて</option>
            <option value="inhouse"  <?= selected($type, 'inhouse')  ?>>社内</option>
            <option value="external" <?= selected($type, 'external') ?>>外部</option>
          </select>
        </label>
      </div>
      <div class="actions-inline" style="padding-top:18px;">
        <button class="ghost-btn" type="submit">絞り込み</button>
        <?php if ($q !== '' || $type !== ''): ?>
          <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/creators.php">クリア</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="card table-card mt-16">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>名前</th>
            <th>種別</th>
            <th>スキル</th>
            <th>連絡先</th>
            <th>ポータル情報</th>
            <th>ポートフォリオ</th>
            <th>状態</th>
            <th style="width:100px;"></th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="empty-state">クリエイターが登録されていません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $skills = json_decode($r['skill_tags_json'] ?? '[]', true);
          $skills = is_array($skills) ? $skills : [];
          $showSkills  = array_slice($skills, 0, 4);
          $extraSkills = count($skills) - count($showSkills);
        ?>
          <tr>
            <td><strong><?= h($r['name']) ?></strong></td>
            <td><span class="status-badge muted"><?= $r['type'] === 'inhouse' ? '社内' : '外部' ?></span></td>
            <td>
              <?php foreach ($showSkills as $sk): ?>
                <span class="status-badge muted" style="margin:1px 2px;"><?= h($sk) ?></span>
              <?php endforeach; ?>
              <?php if ($extraSkills > 0): ?>
                <span class="muted" style="font-size:.78em;">+<?= $extraSkills ?></span>
              <?php endif; ?>
            </td>
            <td class="muted" style="font-size:.82em;"><?= h($r['contact'] ?? '—') ?></td>
            <td class="muted" style="font-size:.82em;">
              <?= h($r['email'] ?? '') ?><br>
              <?= !empty($r['bank_info']) ? '<span class="status-badge success">振込先あり</span>' : '<span class="status-badge muted">振込先未登録</span>' ?>
            </td>
            <td><?= $r['portfolio_url'] ? '<a href="' . h($r['portfolio_url']) . '" target="_blank" rel="noopener" style="color:var(--primary);">リンク</a>' : '<span class="muted">—</span>' ?></td>
            <td>
              <span class="status-badge <?= $r['is_active'] ? 'success' : 'muted' ?>">
                <?= $r['is_active'] ? '有効' : '無効' ?>
              </span>
            </td>
            <td>
              <div class="actions-inline">
                <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/creator_edit.php?id=<?= urlencode($r['id']) ?>">編集</a>
                <form method="post" data-confirm="「<?= h($r['name']) ?>」を削除しますか？">
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
<?php end_page(); ?>
