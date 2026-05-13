<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title      = trim($_POST['title'] ?? '');
        $body       = trim($_POST['body'] ?? '');
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        if ($title && $body) {
            $result = accounting_portal_notice_create($pdo, $title, $body, $isPublished, (int)$user['id']);
            if (isset($result['success'])) {
                write_admin_log($pdo, (int)$user['id'], 'create', 'talent_portal_notice', 0, 'お知らせを作成しました: ' . $title);
                set_flash('success', 'お知らせを作成しました。');
            } else {
                set_flash('error', $result['error']);
            }
        } else {
            set_flash('error', 'タイトルと本文を入力してください。');
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [];
        if (isset($_POST['title'])) $data['title'] = trim($_POST['title']);
        if (isset($_POST['body'])) $data['body'] = trim($_POST['body']);
        $data['is_published'] = isset($_POST['is_published']) ? 1 : 0;
        if ($id > 0 && $data) {
            $result = accounting_portal_notice_update($pdo, $id, $data, (int)$user['id']);
            if (isset($result['success'])) {
                write_admin_log($pdo, (int)$user['id'], 'update', 'talent_portal_notice', $id, 'お知らせを更新しました');
                set_flash('success', 'お知らせを更新しました。');
            } else {
                set_flash('error', $result['error']);
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $result = accounting_portal_notice_delete($pdo, $id);
            if (isset($result['success'])) {
                write_admin_log($pdo, (int)$user['id'], 'delete', 'talent_portal_notice', $id, 'お知らせを削除しました');
                set_flash('success', 'お知らせを削除しました。');
            } else {
                set_flash('error', $result['error']);
            }
        }
    }
    redirect_to($baseUrl . '/notices.php');
}

$notices = accounting_portal_notices_list($pdo);

start_page('お知らせ管理', 'タレントポータルのお知らせを管理します。');
?>
<main class="page-container">

  <section class="page-header-block with-actions">
    <div>
      <h1>お知らせ管理</h1>
      <p>タレントポータルに表示するお知らせを作成・管理します。</p>
    </div>
    <button class="primary-btn" type="button" onclick="showCreateModal()">新規お知らせ作成</button>
  </section>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>タイトル</th>
            <th>状態</th>
            <th>公開日</th>
            <th>作成日</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$notices): ?>
            <tr><td colspan="5" class="empty-state">まだお知らせがありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($notices as $n): ?>
            <tr>
              <td><?= h($n['title']) ?></td>
              <td>
                <?php if ($n['is_published']): ?>
                  <span class="status-badge success">公開中</span>
                <?php else: ?>
                  <span class="status-badge muted">下書き</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;"><?= $n['published_at'] ? h(substr($n['published_at'], 0, 10)) : '-' ?></td>
              <td style="font-size:12px;"><?= h(substr($n['created_at'], 0, 10)) ?></td>
              <td class="actions-inline">
                <button class="ghost-btn" type="button"
                        onclick='showEditModal(<?= (int)$n['id'] ?>, <?= h(json_encode($n['title'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>, <?= h(json_encode($n['body'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>, <?= (int)$n['is_published'] ?>)'>編集</button>
                <form method="post" data-confirm="このお知らせを削除しますか？">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= h((string)$n['id']) ?>">
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
      <h3>新規お知らせ作成</h3>
      <button type="button" onclick="hideModal('createModal')">&times;</button>
    </div>
    <form method="post">
      <input type="hidden" name="action" value="create">
      <div class="form-group">
        <label>
          <span>タイトル</span>
          <input type="text" name="title" required>
        </label>
      </div>
      <div class="form-group">
        <label>
          <span>本文</span>
          <textarea name="body" rows="6" required></textarea>
        </label>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="is_published" value="1">
          <span>すぐに公開する</span>
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
      <h3>お知らせ編集</h3>
      <button type="button" onclick="hideModal('editModal')">&times;</button>
    </div>
    <form method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <div class="form-group">
        <label>
          <span>タイトル</span>
          <input type="text" name="title" id="editTitle" required>
        </label>
      </div>
      <div class="form-group">
        <label>
          <span>本文</span>
          <textarea name="body" id="editBody" rows="6" required></textarea>
        </label>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="is_published" id="editIsPublished" value="1">
          <span>公開する</span>
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

function showEditModal(id, title, body, isPublished) {
    document.getElementById('editId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editBody').value = body;
    document.getElementById('editIsPublished').checked = isPublished;
    document.getElementById('editModal').style.display = 'flex';
}

function hideModal(id) {
    document.getElementById(id).style.display = 'none';
}
</script>

<?php end_page(); ?>
