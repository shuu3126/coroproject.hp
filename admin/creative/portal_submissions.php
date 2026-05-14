<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'review') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'submitted'));
        $adminNote = mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 3000);
        if (!in_array($status, ['submitted', 'approved', 'revision_requested', 'rejected'], true)) {
            $status = 'submitted';
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM creative_project_submissions WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $submission = $stmt->fetch();
            if (!$submission) {
                throw new RuntimeException('提出物が見つかりません。');
            }
            $pdo->beginTransaction();
            $pdo->prepare('
                UPDATE creative_project_submissions
                SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ')->execute([$status, $adminNote, (int)$user['id'], $id]);

            if ($status === 'revision_requested') {
                $pdo->prepare('UPDATE cre_projects SET status = "修正依頼" WHERE id = ?')->execute([$submission['project_id']]);
            } elseif ($status === 'approved') {
                $pdo->prepare('UPDATE cre_projects SET status = CASE WHEN status = "修正依頼" THEN "確認中" ELSE status END WHERE id = ?')->execute([$submission['project_id']]);
            }

            if ($adminNote !== '' && admin_table_has_column($pdo, 'creative_project_comments', 'id')) {
                $pdo->prepare('
                    INSERT INTO creative_project_comments
                        (project_id, creator_id, sender_type, admin_user_id, body, is_internal, created_at)
                    VALUES
                        (?, ?, "admin", ?, ?, 0, NOW())
                ')->execute([$submission['project_id'], $submission['creator_id'], (int)$user['id'], $adminNote]);
            }
            $pdo->commit();
            write_admin_log($pdo, (int)$user['id'], 'update', 'creative_project_submission', $id, 'Creative提出物を確認しました: ' . $status);
            set_flash('success', '提出物の確認状態を更新しました。');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash('error', '更新に失敗しました: ' . $e->getMessage());
        }
    }
    redirect_to($baseUrl . '/creative/portal_submissions.php');
}

$ready = admin_table_has_column($pdo, 'creative_project_submissions', 'id');
$status = trim((string)($_GET['status'] ?? ''));
$creatorId = trim((string)($_GET['creator_id'] ?? ''));
$rows = [];
$creators = $pdo->query('SELECT id, name FROM cre_creators ORDER BY name ASC')->fetchAll();

if ($ready) {
    $sql = '
        SELECT s.*, p.title AS project_title, c.name AS creator_name
        FROM creative_project_submissions s
        LEFT JOIN cre_projects p ON p.id = s.project_id
        LEFT JOIN cre_creators c ON c.id = s.creator_id
        WHERE 1=1
    ';
    $params = [];
    if ($status !== '') {
        $sql .= ' AND s.status = ?';
        $params[] = $status;
    }
    if ($creatorId !== '') {
        $sql .= ' AND s.creator_id = ?';
        $params[] = $creatorId;
    }
    $sql .= ' ORDER BY s.created_at DESC, s.id DESC LIMIT 300';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

$statuses = [
    '' => 'すべて',
    'submitted' => '提出済',
    'approved' => '承認済',
    'revision_requested' => '修正依頼',
    'rejected' => '差し戻し',
];

start_page('Creative提出物確認', 'ポータルから提出されたラフ・初稿・納品データを確認します。');
?>
<main class="page-container">
  <?php if (!$ready): ?>
    <div class="card alert-box alert-error">提出物用テーブルがありません。admin/portal_migrate.sql を実行してください。</div>
  <?php endif; ?>

  <section class="page-header-block">
    <h1>Creative提出物確認</h1>
    <p>承認、修正依頼、差し戻しを行うと、デザイナー側の通知に表示されます。</p>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label>
      <span>ステータス</span>
      <select name="status">
        <?php foreach ($statuses as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= selected($status, $value) ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>クリエイター</span>
      <select name="creator_id">
        <option value="">すべて</option>
        <?php foreach ($creators as $creator): ?>
          <option value="<?= h($creator['id']) ?>" <?= selected($creatorId, $creator['id']) ?>><?= h($creator['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="actions-inline" style="align-self:end;">
      <button class="ghost-btn" type="submit">絞り込み</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/portal_submissions.php">リセット</a>
    </div>
  </form>

  <section class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>提出日時</th>
            <th>案件</th>
            <th>クリエイター</th>
            <th>内容</th>
            <th>状態</th>
            <th>ファイル</th>
            <th style="min-width:340px;">確認</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="empty-state">提出物はありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= h(format_datetime($row['created_at'])) ?></td>
              <td>
                <a href="<?= h($baseUrl) ?>/creative/project_edit.php?id=<?= urlencode($row['project_id']) ?>" style="font-weight:700;"><?= h($row['project_title'] ?: $row['project_id']) ?></a>
              </td>
              <td><?= h($row['creator_name'] ?: $row['creator_id']) ?></td>
              <td>
                <strong><?= h($row['title'] ?: creative_submission_type_label($row['submission_type'])) ?></strong>
                <div class="muted" style="font-size:12px;white-space:pre-wrap;"><?= h($row['comment']) ?></div>
                <?php if (!empty($row['admin_note'])): ?>
                  <div class="muted" style="font-size:12px;margin-top:4px;">確認コメント: <?= h($row['admin_note']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="status-badge <?= h(creative_review_badge($row['status'])) ?>"><?= h(creative_portal_review_status_label($row['status'])) ?></span></td>
              <td class="actions-inline">
                <?php if (!empty($row['file_path'])): ?>
                  <a class="ghost-btn" href="<?= h($baseUrl) ?>/download.php?kind=creative_submission&id=<?= (int)$row['id'] ?>">DL</a>
                <?php endif; ?>
                <?php if (!empty($row['external_url'])): ?>
                  <a class="ghost-btn" href="<?= h($row['external_url']) ?>" target="_blank" rel="noopener">URL</a>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" class="form-stack" style="gap:8px;">
                  <input type="hidden" name="action" value="review">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <div class="form-grid two" style="gap:8px;">
                    <label style="margin:0;">
                      <span>状態</span>
                      <select name="status">
                        <option value="submitted" <?= selected($row['status'], 'submitted') ?>>提出済</option>
                        <option value="approved" <?= selected($row['status'], 'approved') ?>>承認済</option>
                        <option value="revision_requested" <?= selected($row['status'], 'revision_requested') ?>>修正依頼</option>
                        <option value="rejected" <?= selected($row['status'], 'rejected') ?>>差し戻し</option>
                      </select>
                    </label>
                    <div class="actions-inline" style="align-self:end;">
                      <button class="primary-btn" type="submit">更新</button>
                    </div>
                  </div>
                  <label style="margin:0;">
                    <span>確認コメント</span>
                    <textarea name="admin_note" rows="2" placeholder="修正点や確認結果。ポータルにも表示されます。"><?= h($row['admin_note']) ?></textarea>
                  </label>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php
end_page();

function creative_submission_type_label($type) {
    switch ((string)$type) {
        case 'rough': return 'ラフ';
        case 'draft': return '初稿';
        case 'revision': return '修正版';
        case 'final': return '納品';
        default: return 'その他';
    }
}

function creative_review_badge($status) {
    switch ((string)$status) {
        case 'approved': return 'success';
        case 'revision_requested': return 'warning';
        case 'rejected': return 'danger';
        case 'submitted':
        default: return 'info';
    }
}
?>
