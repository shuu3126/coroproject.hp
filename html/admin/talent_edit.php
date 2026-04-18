<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';
require_admin_login();
$user = current_admin_user();

function talent_id_exists( $pdo, $id, $excludeId = '') {
    if ($excludeId !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = ? AND id <> ?');
        $stmt->execute([$id, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = ?');
        $stmt->execute([$id]);
    }
    return (int)$stmt->fetchColumn() > 0;
}
function generate_talent_id( $pdo, $name, $excludeId = '') {
    $base = normalize_file_stem($name, 'talent');
    $candidate = $base; $i = 2;
    while (talent_id_exists($pdo, $candidate, $excludeId)) { $candidate = $base . '-' . $i; $i++; }
    return $candidate;
}
function fetch_platform_rows( $pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT name, url FROM talent_platforms WHERE talent_id = ? ORDER BY id ASC');
    $stmt->execute([$talentId]);
    return $stmt->fetchAll() ?: [];
}
function fetch_link_rows( $pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT label, url FROM talent_links WHERE talent_id = ? ORDER BY id ASC');
    $stmt->execute([$talentId]);
    return $stmt->fetchAll() ?: [];
}
function sync_talent_relations( $pdo, $oldId, $newId, $platforms, $links) {
    $targets = array_values(array_unique(array_filter([$oldId, $newId])));
    if ($targets) {
        $ph = implode(',', array_fill(0, count($targets), '?'));
        $pdo->prepare("DELETE FROM talent_platforms WHERE talent_id IN ($ph)")->execute($targets);
        $pdo->prepare("DELETE FROM talent_links WHERE talent_id IN ($ph)")->execute($targets);
    }
    if ($platforms) {
        $stmt = $pdo->prepare('INSERT INTO talent_platforms (talent_id, name, url) VALUES (?, ?, ?)');
        foreach ($platforms as $row) {
            $stmt->execute([$newId, $row['name'], $row['url']]);
        }
    }
    if ($links) {
        $stmt = $pdo->prepare('INSERT INTO talent_links (talent_id, label, url) VALUES (?, ?, ?)');
        foreach ($links as $row) {
            $stmt->execute([$newId, $row['label'], $row['url']]);
        }
    }
}

$id = (isset($_GET['id']) ? $_GET['id'] : '');
$isEdit = $id !== '';
$row = [
    'id' => '', 'name' => '', 'kana' => '', 'talent_group' => '', 'status' => 'active',
    'debut' => '', 'last_active' => '', 'avatar' => '', 'bio' => '', 'long_bio_json' => '[]',
    'tags_json' => '[]', 'sort_order' => 0, 'is_published' => 1, 'platforms_text' => '', 'links_text' => '', 'long_bio_text' => '', 'tags_text' => ''
];
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM talents WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $row = array_merge($row, $found);
        $row['long_bio_text'] = lines_from_json((isset($found['long_bio_json']) ? $found['long_bio_json'] : '[]'));
        $tags = json_decode((string)((isset($found['tags_json']) ? $found['tags_json'] : '[]')), true);
        $row['tags_text'] = is_array($tags) ? implode(', ', array_filter(array_map('strval', $tags))) : '';
        $row['platforms_text'] = pipe_lines_from_rows(fetch_platform_rows($pdo, $id), 'name', 'url');
        $row['links_text'] = pipe_lines_from_rows(fetch_link_rows($pdo, $id), 'label', 'url');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $talentId = trim((isset($_POST['id']) ? $_POST['id'] : ''));
    $name = trim((isset($_POST['name']) ? $_POST['name'] : ''));
    $kana = trim((isset($_POST['kana']) ? $_POST['kana'] : ''));
    $talentGroup = trim((isset($_POST['talent_group']) ? $_POST['talent_group'] : ''));
    $status = trim((isset($_POST['status']) ? $_POST['status'] : 'active'));
    $debut = trim((isset($_POST['debut']) ? $_POST['debut'] : ''));
    $lastActive = trim((isset($_POST['last_active']) ? $_POST['last_active'] : ''));
    $avatar = trim((isset($_POST['avatar']) ? $_POST['avatar'] : ''));
    $bio = trim((isset($_POST['bio']) ? $_POST['bio'] : ''));
    $longBioText = trim((isset($_POST['long_bio_text']) ? $_POST['long_bio_text'] : ''));
    $platformsText = trim((isset($_POST['platforms_text']) ? $_POST['platforms_text'] : ''));
    $linksText = trim((isset($_POST['links_text']) ? $_POST['links_text'] : ''));
    $tagsText = trim((isset($_POST['tags_text']) ? $_POST['tags_text'] : ''));
    $sortOrder = (int)((isset($_POST['sort_order']) ? $_POST['sort_order'] : 0));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    if ($name === '') {
        set_flash('error', '名前は必須です。');
        redirect_to($baseUrl . '/talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
    if ($talentId === '') {
        $talentId = $isEdit ? $id : generate_talent_id($pdo, $name);
    }
    if (($isEdit && talent_id_exists($pdo, $talentId, $id)) || (!$isEdit && talent_id_exists($pdo, $talentId))) {
        set_flash('error', 'そのタレントIDはすでに使用されています。');
        redirect_to($baseUrl . '/talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
    try {
        $upload = save_uploaded_image($_FILES['avatar_file'] ?? [], $config['uploads']['talent_public_dir'], $config['uploads']['talent_public_prefix'], $talentId ?: $name);
        if ($upload !== null) {
            $avatar = $upload;
        }
        $platforms = parse_pipe_lines($platformsText, 'name', 'url');
        $links = parse_pipe_lines($linksText, 'label', 'url');
        $longBioJson = parse_text_lines_to_json($longBioText);
        $tags = array_values(array_filter(array_map('trim', preg_split('/[,\r\n]+/u', $tagsText))));
        $tagsJson = json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $platformsJson = json_encode($platforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $linksJson = json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $pdo->beginTransaction();
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE talents SET id=?, name=?, kana=?, talent_group=?, status=?, debut=?, last_active=?, avatar=?, bio=?, long_bio_json=?, platforms_json=?, links_json=?, tags_json=?, sort_order=?, is_published=? WHERE id=?');
            $stmt->execute([$talentId, $name, $kana, $talentGroup, $status, $debut ?: null, $lastActive ?: null, $avatar, $bio, $longBioJson, $platformsJson, $linksJson, $tagsJson, $sortOrder, $isPublished, $id]);
            sync_talent_relations($pdo, $id, $talentId, $platforms, $links);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'talent', null, 'タレントを更新しました', ['talent_id' => $talentId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO talents (id, name, kana, talent_group, status, debut, last_active, avatar, bio, long_bio_json, platforms_json, links_json, tags_json, sort_order, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$talentId, $name, $kana, $talentGroup, $status, $debut ?: null, $lastActive ?: null, $avatar, $bio, $longBioJson, $platformsJson, $linksJson, $tagsJson, $sortOrder, $isPublished]);
            sync_talent_relations($pdo, $talentId, $talentId, $platforms, $links);
            write_admin_log($pdo, (int)$user['id'], 'create', 'talent', null, 'タレントを作成しました', ['talent_id' => $talentId]);
        }
        $pdo->commit();
        set_flash('success', 'タレント情報を保存しました。');
        redirect_to($baseUrl . '/talents.php');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', '保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}
start_page($isEdit ? 'タレントを編集' : 'タレントを追加', '公開サイトのタレント情報を入力してください。');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? 'タレントを編集' : 'タレントを追加') ?></h1><p>公開ページに表示するプロフィールやリンクを管理します。</p></section>
  <form method="post" enctype="multipart/form-data" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>タレントID</span><input type="text" name="id" value="<?= h($row['id']) ?>" <?= $isEdit ? 'readonly' : '' ?>></label>
      <label><span>名前</span><input type="text" name="name" value="<?= h($row['name']) ?>" required></label>
    </div>
    <div class="form-grid two">
      <label><span>かな</span><input type="text" name="kana" value="<?= h($row['kana']) ?>"></label>
      <label><span>グループ</span><input type="text" name="talent_group" value="<?= h($row['talent_group']) ?>"></label>
    </div>
    <div class="form-grid two">
      <label><span>ステータス</span><input type="text" name="status" value="<?= h($row['status']) ?>"></label>
      <label><span>並び順</span><input type="number" name="sort_order" value="<?= h((string)$row['sort_order']) ?>"></label>
    </div>
    <div class="form-grid two">
      <label><span>デビュー日</span><input type="date" name="debut" value="<?= h($row['debut']) ?>"></label>
      <label><span>最近の活動日</span><input type="date" name="last_active" value="<?= h($row['last_active']) ?>"></label>
    </div>
    <label><span>短い紹介文</span><textarea name="bio" rows="3"><?= h($row['bio']) ?></textarea></label>
    <label><span>ロングプロフィール（1行=1段落）</span><textarea name="long_bio_text" rows="8"><?= h($row['long_bio_text']) ?></textarea></label>
    <label><span>アバター画像パス</span><input type="text" name="avatar" value="<?= h($row['avatar']) ?>"></label>
    <label><span>アバター画像をアップロード</span><input type="file" name="avatar_file" accept="image/*"></label>
    <?php if (!empty($row['avatar'])): ?><img class="inline-preview" src="/<?= h($row['avatar']) ?>" alt="avatar"><?php endif; ?>
    <label><span>プラットフォーム一覧（1行ごとに 名前|URL）</span><textarea name="platforms_text" rows="6"><?= h($row['platforms_text']) ?></textarea></label>
    <label><span>その他リンク（1行ごとに ラベル|URL）</span><textarea name="links_text" rows="6"><?= h($row['links_text']) ?></textarea></label>
    <label><span>タグ（カンマ区切り）</span><input type="text" name="tags_text" value="<?= h($row['tags_text']) ?>"></label>
    <label class="checkbox-row"><input type="checkbox" name="is_published" value="1" <?= checked((bool)$row['is_published']) ?>><span>公開する</span></label>
    <div class="actions-inline"><button class="primary-btn" type="submit">この内容で保存する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/talents.php">一覧へ戻る</a></div>
  </form>
</main>
<?php end_page(); ?>
