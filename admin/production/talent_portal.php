<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $talentId = trim($_POST['talent_id'] ?? '');
        $loginId  = trim($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($talentId && $loginId && $password) {
            $result = accounting_portal_account_create($pdo, $talentId, $loginId, $password, (int)$user['id']);
            if (isset($result['success'])) {
                write_admin_log($pdo, (int)$user['id'], 'create', 'talent_portal_account', 0, 'ポータルアカウントを作成しました: ' . $loginId);
                set_flash('success', 'ポータルアカウントを作成しました。');
            } else {
                set_flash('error', $result['error']);
            }
        } else {
            set_flash('error', '必須項目を入力してください。');
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [];
        if (isset($_POST['login_id'])) $data['login_id'] = trim($_POST['login_id']);
        if (isset($_POST['password']) && $_POST['password'] !== '') $data['password'] = $_POST['password'];
        if (isset($_POST['is_active'])) $data['is_active'] = (int)$_POST['is_active'];
        if ($id > 0 && $data) {
            $result = accounting_portal_account_update($pdo, $id, $data, (int)$user['id']);
            if (isset($result['success'])) {
                write_admin_log($pdo, (int)$user['id'], 'update', 'talent_portal_account', $id, 'ポータルアカウントを更新しました');
                set_flash('success', 'ポータルアカウントを更新しました。');
            } else {
                set_flash('error', $result['error']);
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $result = accounting_portal_account_delete($pdo, $id);
            if (isset($result['success'])) {
                write_admin_log($pdo, (int)$user['id'], 'delete', 'talent_portal_account', $id, 'ポータルアカウントを削除しました');
                set_flash('success', 'ポータルアカウントを削除しました。');
            } else {
                set_flash('error', $result['error']);
            }
        }
    }
    redirect_to($baseUrl . '/production/talent_portal.php');
}

$accounts = accounting_portal_accounts_list($pdo);
$talents  = accounting_list_talents($pdo, false);

start_page('ポータルアカウント管理', 'タレント向けポータルのアカウントを管理します。');
?>
<main class="page-container">

  <section class="page-header-block with-actions">
    <div>
      <h1>ポータルアカウント管理</h1>
      <p>タレントが収益を報告するためのポータルアカウントを発行・管理します。</p>
    </div>
    <button class="primary-btn" type="button" onclick="showCreateModal()">新規アカウント作成</button>
  </section>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>タレント</th>
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
            <tr><td colspan="7" class="empty-state">まだポータルアカウントがありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($accounts as $acc): ?>
            <tr>
              <td><?= h($acc['talent_name']) ?></td>
              <td><?= h($acc['login_id']) ?></td>
              <td>
                <?php if ($acc['is_active']): ?>
                  <span class="status-badge success">有効</span>
                <?php else: ?>
                  <span class="status-badge danger">無効</span>
                <?php endif; ?>
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
                  <input type="hidden" name="id" value="<?= h((string)$acc['id']) ?>">
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

<!-- Create Modal -->
<div id="createModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>新規アカウント作成</h3>
      <button type="button" onclick="hideModal('createModal')">&times;</button>
    </div>
    <form method="post">
      <input type="hidden" name="action" value="create">
      <div class="form-grid two">
        <label>
          <span>タレント</span>
          <select name="talent_id" required>
            <option value="">選択してください</option>
            <?php foreach ($talents as $t): ?>
              <option value="<?= h($t['id']) ?>"><?= h($t['name']) ?></option>
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
        </label>
      </div>
      <div class="actions-inline">
        <button class="primary-btn" type="submit">作成する</button>
        <button type="button" onclick="hideModal('createModal')">キャンセル</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>アカウント編集</h3>
      <button type="button" onclick="hideModal('editModal')">&times;</button>
    </div>
    <form method="post">
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
        <button type="button" onclick="hideModal('editModal')">キャンセル</button>
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
        if (event.target === modal) {
            hideModal(modal.id);
        }
    });
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal').forEach(function (modal) {
        if (modal.style.display !== 'none') {
            hideModal(modal.id);
        }
    });
});
</script>

<?php end_page(); ?>
