<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

function admin_profile_requests_ready($pdo) {
    return admin_table_has_column($pdo, 'talent_profile_change_requests', 'id');
}

function admin_profile_request_payload($request) {
    $payload = json_decode((string)($request['payload_json'] ?? ''), true);
    return is_array($payload) ? $payload : [];
}

function admin_profile_request_label($status) {
    if ($status === 'pending') return ['確認待ち', 'warning'];
    if ($status === 'approved') return ['承認済', 'success'];
    if ($status === 'rejected') return ['差し戻し', 'danger'];
    return [(string)$status, 'muted'];
}

function admin_profile_sync_relations($pdo, $talentId, $platforms, $links) {
    $pdo->prepare('DELETE FROM talent_platforms WHERE talent_id = ?')->execute([(string)$talentId]);
    $pdo->prepare('DELETE FROM talent_links WHERE talent_id = ?')->execute([(string)$talentId]);

    if ($platforms) {
        $stmt = $pdo->prepare('INSERT INTO talent_platforms (talent_id, name, url) VALUES (?, ?, ?)');
        foreach ($platforms as $row) {
            $stmt->execute([(string)$talentId, $row['name'], $row['url']]);
        }
    }

    if ($links) {
        $stmt = $pdo->prepare('INSERT INTO talent_links (talent_id, label, url) VALUES (?, ?, ?)');
        foreach ($links as $row) {
            $stmt->execute([(string)$talentId, $row['label'], $row['url']]);
        }
    }
}

function admin_profile_apply_request($pdo, $request, $userId, $note = '') {
    $payload = admin_profile_request_payload($request);
    $talentId = (string)$request['talent_id'];
    $name = trim((string)($payload['name'] ?? ''));
    if ($name === '') {
        throw new RuntimeException('活動名が空のため承認できません。');
    }

    $longBioJson = json_encode(parse_lines_to_array($payload['long_bio_text'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $platforms = parse_pipe_lines($payload['platforms_text'] ?? '', 'name', 'url');
    $links = parse_pipe_lines($payload['links_text'] ?? '', 'label', 'url');
    $platformsJson = json_encode($platforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $linksJson = json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tags = array_values(array_filter(array_map('trim', preg_split('/[,、]/u', (string)($payload['tags_text'] ?? '')))));
    $tagsJson = json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            UPDATE talents
            SET name = ?, kana = ?, talent_group = ?, debut = ?, avatar = ?, bio = ?,
                long_bio_json = ?, platforms_json = ?, links_json = ?, tags_json = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $name,
            trim((string)($payload['kana'] ?? '')),
            trim((string)($payload['talent_group'] ?? '')),
            trim((string)($payload['debut'] ?? '')) ?: null,
            trim((string)($payload['avatar'] ?? '')),
            trim((string)($payload['bio'] ?? '')),
            $longBioJson,
            $platformsJson,
            $linksJson,
            $tagsJson,
            $talentId,
        ]);

        if ($stmt->rowCount() < 1) {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = ?');
            $exists->execute([$talentId]);
            if ((int)$exists->fetchColumn() < 1) {
                throw new RuntimeException('対象タレントが見つかりません。');
            }
        }

        admin_profile_sync_relations($pdo, $talentId, $platforms, $links);

        $pdo->prepare('
            UPDATE talent_profile_change_requests
            SET status = "approved", admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ')->execute([trim($note), $userId ?: null, (int)$request['id']]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_profile_requests_ready($pdo)) {
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $note = trim((string)($_POST['admin_note'] ?? ''));

    $stmt = $pdo->prepare('SELECT * FROM talent_profile_change_requests WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $request = $stmt->fetch();

    if (!$request) {
        set_flash('error', '申請が見つかりません。');
        redirect_to($baseUrl . '/production/profile_requests.php');
    }

    try {
        if ($action === 'approve') {
            admin_profile_apply_request($pdo, $request, (int)$user['id'], $note);
            write_admin_log($pdo, (int)$user['id'], 'approve', 'talent_profile_request', $id, 'HP掲載情報申請を承認しました');
            set_flash('success', 'HP掲載情報を承認し、公開プロフィールへ反映しました。');
        } elseif ($action === 'reject') {
            $pdo->prepare('
                UPDATE talent_profile_change_requests
                SET status = "rejected", admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ')->execute([$note, (int)$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'reject', 'talent_profile_request', $id, 'HP掲載情報申請を差し戻しました');
            set_flash('success', '申請を差し戻しました。');
        }
    } catch (Exception $e) {
        set_flash('error', '処理に失敗しました: ' . $e->getMessage());
    }

    redirect_to($baseUrl . '/production/profile_requests.php');
}

$filter = trim((string)($_GET['status'] ?? 'pending'));
$rows = [];
if (admin_profile_requests_ready($pdo)) {
    $sql = '
        SELECT r.*, t.name AS current_name
        FROM talent_profile_change_requests r
        LEFT JOIN talents t ON t.id = r.talent_id
    ';
    $params = [];
    if ($filter !== '') {
        $sql .= ' WHERE r.status = ?';
        $params[] = $filter;
    }
    $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

start_page('HP掲載情報申請', 'タレント本人から送信された公開プロフィール変更申請を確認・承認します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div>
      <h1>HP掲載情報申請</h1>
      <p>承認した内容だけがHP側のタレントプロフィールに反映されます。</p>
    </div>
    <div class="actions-inline">
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/production/profile_requests.php?status=pending">確認待ち</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/production/profile_requests.php?status=">すべて</a>
    </div>
  </section>

  <?php if (!admin_profile_requests_ready($pdo)): ?>
    <div class="card">
      <p style="color:var(--danger);margin:0;">申請用テーブルがありません。admin/portal_migrate.sql を再実行してください。</p>
    </div>
  <?php endif; ?>

  <?php if (!$rows && admin_profile_requests_ready($pdo)): ?>
    <div class="card empty-state">対象の申請はありません。</div>
  <?php endif; ?>

  <?php foreach ($rows as $row): ?>
    <?php
      $payload = admin_profile_request_payload($row);
      $st = admin_profile_request_label($row['status']);
    ?>
    <section class="card mt-24">
      <div class="page-header-block with-actions" style="margin-bottom:12px;">
        <div>
          <h2 style="margin:0;font-size:18px;"><?= h($payload['name'] ?? $row['current_name'] ?? $row['talent_id']) ?></h2>
          <p style="margin:4px 0 0;color:var(--sub);font-size:12px;">
            <?= h($row['talent_id']) ?> / 申請日時 <?= h(substr($row['created_at'], 0, 16)) ?>
          </p>
        </div>
        <span class="status-badge <?= h($st[1]) ?>"><?= h($st[0]) ?></span>
      </div>

      <div class="table-wrap">
        <table>
          <tbody>
            <tr><th>活動名</th><td><?= h($payload['name'] ?? '') ?></td></tr>
            <tr><th>かな</th><td><?= h($payload['kana'] ?? '') ?></td></tr>
            <tr><th>グループ</th><td><?= h($payload['talent_group'] ?? '') ?></td></tr>
            <tr><th>デビュー日</th><td><?= h($payload['debut'] ?? '') ?></td></tr>
            <tr><th>プロフィール画像</th><td><?= h($payload['avatar'] ?? '') ?></td></tr>
            <tr><th>短い紹介文</th><td style="white-space:pre-wrap;"><?= h($payload['bio'] ?? '') ?></td></tr>
            <tr><th>ロングプロフィール</th><td style="white-space:pre-wrap;"><?= h($payload['long_bio_text'] ?? '') ?></td></tr>
            <tr><th>配信プラットフォーム</th><td style="white-space:pre-wrap;"><?= h($payload['platforms_text'] ?? '') ?></td></tr>
            <tr><th>その他リンク</th><td style="white-space:pre-wrap;"><?= h($payload['links_text'] ?? '') ?></td></tr>
            <tr><th>タグ</th><td><?= h($payload['tags_text'] ?? '') ?></td></tr>
            <?php if (!empty($row['admin_note'])): ?>
              <tr><th>管理者メモ</th><td style="white-space:pre-wrap;"><?= h($row['admin_note']) ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($row['status'] === 'pending'): ?>
        <form method="post" class="form-stack" style="margin-top:16px;">
          <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
          <label>
            <span>管理者メモ（差し戻し理由など）</span>
            <textarea name="admin_note" rows="3"></textarea>
          </label>
          <div class="actions-inline">
            <button class="primary-btn" type="submit" name="action" value="approve">承認してHPへ反映</button>
            <button class="danger-btn" type="submit" name="action" value="reject">差し戻し</button>
          </div>
        </form>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</main>
<?php end_page(); ?>
