<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $result = creative_portal_notice_create($pdo, $_POST['title'] ?? '', $_POST['body'] ?? '', isset($_POST['is_published']) ? 1 : 0, (int)$user['id']);
        if (!empty($result['success'])) {
            write_admin_log($pdo, (int)$user['id'], 'create', 'creative_portal_notice', 0, 'Creativeポータルお知らせを作成しました');
            set_flash('success', 'お知らせを作成しました。');
        } else {
            set_flash('error', $result['error'] ?? '作成に失敗しました。');
        }
    } elseif ($action === 'update') {
        $result = creative_portal_notice_update($pdo, (int)($_POST['id'] ?? 0), [
            'title' => $_POST['title'] ?? '',
            'body' => $_POST['body'] ?? '',
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ], (int)$user['id']);
        if (!empty($result['success'])) {
            write_admin_log($pdo, (int)$user['id'], 'update', 'creative_portal_notice', (int)($_POST['id'] ?? 0), 'Creativeポータルお知らせを更新しました');
            set_flash('success', 'お知らせを更新しました。');
        } else {
            set_flash('error', $result['error'] ?? '更新に失敗しました。');
        }
    } elseif ($action === 'delete') {
        $result = creative_portal_notice_delete($pdo, (int)($_POST['id'] ?? 0));
        if (!empty($result['success'])) {
            write_admin_log($pdo, (int)$user['id'], 'delete', 'creative_portal_notice', (int)($_POST['id'] ?? 0), 'Creativeポータルお知らせを削除しました');
            set_flash('success', 'お知らせを削除しました。');
        } else {
            set_flash('error', $result['error'] ?? '削除に失敗しました。');
        }
    }
    redirect_to($baseUrl . '/creative/portal_notices.php');
}

$notices = creative_portal_notices_list($pdo);

start_page('Creativeポータルお知らせ', 'Creativeポータルに表示するお知らせを管理します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div>
      <h1>Creativeポータルお知らせ</h1>
      <p>専属デザイナー向けのお知らせを作成・編集します。</p>
    </div>
    <button class="primary-btn" type="button" onclick="showCreateModal()">新規お知らせ作成</button>
  </section>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>タイトル</th><th>状態</th><th>公開日</th><th>作成日</th><th>操作</th></tr>
        </thead>
        <tbody>
          <?php if (!$notices): ?>
            <tr><td colspan="5" class="empty-state">お知らせはありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($notices as $notice): ?>
            <tr>
              <td><?= h($notice['title']) ?></td>
              <td><span class="status-badge <?= $notice['is_published'] ? 'success' : 'muted' ?>"><?= $notice['is_published'] ? '公開中' : '下書き' ?></span></td>
              <td><?= $notice['published_at'] ? h(substr($notice['published_at'], 0, 10)) : '-' ?></td>
              <td><?= h(substr($notice['created_at'], 0, 10)) ?></td>
              <td class="actions-inline">
                <button class="ghost-btn" type="button"
                        onclick='showEditModal(<?= (int)$notice['id'] ?>, <?= h(json_encode($notice['title'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>, <?= h(json_encode($notice['body'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>, <?= (int)$notice['is_published'] ?>)'>編集</button>
                <form method="post" data-confirm="このお知らせを削除しますか？">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$notice['id'] ?>">
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
      <h3>新規お知らせ作成</h3>
      <button type="button" onclick="hideModal('createModal')">&times;</button>
    </div>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="create">
      <label><span>タイトル</span><input type="text" name="title" required></label>
      <label><span>本文</span><textarea name="body" rows="6" required></textarea></label>
      <label class="checkbox-row"><input type="checkbox" name="is_published" value="1" checked><span>公開する</span></label>
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
      <h3>お知らせ編集</h3>
      <button type="button" onclick="hideModal('editModal')">&times;</button>
    </div>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <label><span>タイトル</span><input type="text" name="title" id="editTitle" required></label>
      <label><span>本文</span><textarea name="body" id="editBody" rows="6" required></textarea></label>
      <label class="checkbox-row"><input type="checkbox" name="is_published" id="editIsPublished" value="1"><span>公開する</span></label>
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
function showEditModal(id, title, body, isPublished) {
  document.getElementById('editId').value = id;
  document.getElementById('editTitle').value = title;
  document.getElementById('editBody').value = body;
  document.getElementById('editIsPublished').checked = !!isPublished;
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
