<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $result = creative_portal_account_create(
            $pdo,
            $_POST['creator_id'] ?? '',
            $_POST['login_id'] ?? '',
            $_POST['password'] ?? '',
            (int)$user['id']
        );
        if (!empty($result['success'])) {
            write_admin_log($pdo, (int)$user['id'], 'create', 'creative_portal_account', 0, 'Creativeポータルアカウントを作成しました');
            set_flash('success', 'Creativeポータルアカウントを作成しました。');
        } else {
            set_flash('error', $result['error'] ?? '作成に失敗しました。');
        }
    } elseif ($action === 'update') {
        $data = [
            'login_id' => trim((string)($_POST['login_id'] ?? '')),
            'is_active' => (int)($_POST['is_active'] ?? 0),
        ];
        if ((string)($_POST['password'] ?? '') !== '') {
            $data['password'] = (string)$_POST['password'];
        }
        $result = creative_portal_account_update($pdo, (int)($_POST['id'] ?? 0), $data, (int)$user['id']);
        if (!empty($result['success'])) {
            write_admin_log($pdo, (int)$user['id'], 'update', 'creative_portal_account', (int)($_POST['id'] ?? 0), 'Creativeポータルアカウントを更新しました');
            set_flash('success', 'Creativeポータルアカウントを更新しました。');
        } else {
            set_flash('error', $result['error'] ?? '更新に失敗しました。');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $result = creative_portal_account_delete($pdo, $id);
        if (!empty($result['success'])) {
            write_admin_log($pdo, (int)$user['id'], 'delete', 'creative_portal_account', $id, 'Creativeポータルアカウントを削除しました');
            set_flash('success', 'Creativeポータルアカウントを削除しました。');
        } else {
            set_flash('error', $result['error'] ?? '削除に失敗しました。');
        }
    }
    redirect_to($baseUrl . '/creative/portal_accounts.php');
}

$accounts = creative_portal_accounts_list($pdo);
$creators = $pdo->query('SELECT id, name, type FROM cre_creators WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

start_page('Creativeポータルアカウント', '専属デザイナー向けポータルのログインアカウントを管理します。');
?>
<main class="page-container">
  <?php if (!creative_portal_ready($pdo)): ?>
    <div class="card alert-box alert-error">Creativeポータル用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block with-actions">
    <div>
      <h1>Creativeポータルアカウント</h1>
      <p>ログインID、パスワード再設定、有効・無効を管理します。</p>
    </div>
    <button class="primary-btn" type="button" onclick="showCreateModal()">新規アカウント作成</button>
  </section>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>クリエイター</th>
            <th>ログインID</th>
            <th>状態</th>
            <th>最終ログイン</th>
            <th>パスワード</th>
            <th>作成日</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$accounts): ?>
            <tr><td colspan="7" class="empty-state">まだアカウントがありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($accounts as $acc): ?>
            <tr>
              <td><?= h($acc['creator_name'] ?: $acc['creator_id']) ?></td>
              <td><?= h($acc['login_id']) ?></td>
              <td>
                <span class="status-badge <?= $acc['is_active'] ? 'success' : 'danger' ?>"><?= $acc['is_active'] ? '有効' : '無効' ?></span>
              </td>
              <td style="font-size:12px;"><?= $acc['last_login_at'] ? h(substr($acc['last_login_at'], 0, 16)) : '-' ?></td>
              <td style="font-size:12px;">
                <?= !empty($acc['password_changed_at']) ? '最終変更 ' . h(substr($acc['password_changed_at'], 0, 16)) : '設定済み' ?><br>
                <span class="muted">表示不可 / 再設定可</span>
              </td>
              <td style="font-size:12px;"><?= h(substr($acc['created_at'], 0, 10)) ?></td>
              <td class="actions-inline">
                <button class="ghost-btn" type="button"
                        onclick='showEditModal(<?= (int)$acc['id'] ?>, <?= h(json_encode($acc['login_id'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>, <?= (int)$acc['is_active'] ?>)'>編集</button>
                <form method="post" data-confirm="このアカウントを削除しますか？">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$acc['id'] ?>">
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

<div id="createModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>新規アカウント作成</h3>
      <button type="button" onclick="hideModal('createModal')">&times;</button>
    </div>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="create">
      <div class="form-grid two">
        <label>
          <span>クリエイター</span>
          <select name="creator_id" required>
            <option value="">選択してください</option>
            <?php foreach ($creators as $creator): ?>
              <option value="<?= h($creator['id']) ?>"><?= h($creator['name']) ?> (<?= $creator['type'] === 'inhouse' ? '社内' : '外部' ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>ログインID</span>
          <input type="text" name="login_id" required>
        </label>
        <label>
          <span>初期パスワード</span>
          <input type="password" name="password" required>
          <span class="help-text">8文字以上</span>
        </label>
      </div>
      <div class="actions-inline">
        <button class="primary-btn" type="submit">作成する</button>
        <button class="ghost-btn" type="button" onclick="hideModal('createModal')">キャンセル</button>
      </div>
    </form>
  </div>
</div>

<div id="editModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>アカウント編集</h3>
      <button type="button" onclick="hideModal('editModal')">&times;</button>
    </div>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <div class="form-grid two">
        <label>
          <span>ログインID</span>
          <input type="text" name="login_id" id="editLoginId" required>
        </label>
        <label>
          <span>新しいパスワード（変更する場合のみ）</span>
          <input type="password" name="password">
        </label>
        <label>
          <span>状態</span>
          <select name="is_active" id="editIsActive">
            <option value="1">有効</option>
            <option value="0">無効</option>
          </select>
        </label>
      </div>
      <div class="actions-inline">
        <button class="primary-btn" type="submit">更新する</button>
        <button class="ghost-btn" type="button" onclick="hideModal('editModal')">キャンセル</button>
      </div>
    </form>
  </div>
</div>

<script>
function showCreateModal() {
  document.getElementById('createModal').style.display = 'flex';
}
function showEditModal(id, loginId, isActive) {
  document.getElementById('editId').value = id;
  document.getElementById('editLoginId').value = loginId;
  document.getElementById('editIsActive').value = isActive;
  document.getElementById('editModal').style.display = 'flex';
}
function hideModal(id) {
  document.getElementById(id).style.display = 'none';
}
document.querySelectorAll('.modal').forEach(function (modal) {
  modal.addEventListener('click', function (event) {
    if (event.target === modal) hideModal(modal.id);
  });
});
document.addEventListener('keydown', function (event) {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('.modal').forEach(function (modal) {
    if (modal.style.display !== 'none') hideModal(modal.id);
  });
});
</script>
<?php end_page(); ?>
