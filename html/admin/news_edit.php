<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';
require_admin_login();
$user = current_admin_user();

function news_id_exists( $pdo, $id, $excludeId = null) {
    if ($excludeId) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE id = ? AND id <> ?');
        $stmt->execute([$id, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE id = ?');
        $stmt->execute([$id]);
    }
    return (int)$stmt->fetchColumn() > 0;
}
function generate_news_id( $pdo, $title, $date = '') {
    $datePart = $date !== '' ? str_replace('-', '', $date) : date('Ymd');
    $base = normalize_file_stem($datePart . '-' . $title, 'news');
    $candidate = $base; $i = 2;
    while (news_id_exists($pdo, $candidate)) { $candidate = $base . '-' . $i; $i++; }
    return $candidate;
}

$id = (isset($_GET['id']) ? $_GET['id'] : '');
$isEdit = $id !== '';
$row = [
    'id' => '', 'title' => '', 'date' => date('Y-m-d'), 'tag' => '', 'thumb' => '',
    'excerpt' => '', 'content_text' => '', 'url' => '', 'is_published' => 1, 'sort_order' => 0,
];
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $row = array_merge($row, $found);
        $row['content_text'] = lines_from_json((isset($found['content']) ? $found['content'] : '[]'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newsId = trim((isset($_POST['id']) ? $_POST['id'] : ''));
    $title = trim((isset($_POST['title']) ? $_POST['title'] : ''));
    $date = trim((isset($_POST['date']) ? $_POST['date'] : ''));
    $tag = trim((isset($_POST['tag']) ? $_POST['tag'] : ''));
    $thumb = trim((isset($_POST['thumb']) ? $_POST['thumb'] : ''));
    $excerpt = trim((isset($_POST['excerpt']) ? $_POST['excerpt'] : ''));
    $contentText = trim((isset($_POST['content_text']) ? $_POST['content_text'] : ''));
    $url = trim((isset($_POST['url']) ? $_POST['url'] : ''));
    $sortOrder = (int)((isset($_POST['sort_order']) ? $_POST['sort_order'] : 0));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    if ($title === '') {
        set_flash('error', 'タイトルは必須です。');
        redirect_to($baseUrl . '/news_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
    if ($newsId === '') {
        $newsId = $isEdit ? $id : generate_news_id($pdo, $title, $date);
    }
    if (($isEdit && news_id_exists($pdo, $newsId, $id)) || (!$isEdit && news_id_exists($pdo, $newsId))) {
        set_flash('error', 'そのニュースIDはすでに使用されています。');
        redirect_to($baseUrl . '/news_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }

    try {
        $upload = save_uploaded_image($_FILES['thumb_file'] ?? [], $config['uploads']['news_public_dir'], $config['uploads']['news_public_prefix'], $newsId ?: $title);
        if ($upload !== null) {
            $thumb = $upload;
        }
        $contentJson = parse_text_lines_to_json($contentText);
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE news SET id=?, title=?, date=?, tag=?, thumb=?, excerpt=?, content=?, url=?, is_published=?, sort_order=? WHERE id=?');
            $stmt->execute([$newsId, $title, $date, $tag, $thumb, $excerpt, $contentJson, $url, $isPublished, $sortOrder, $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'news', null, 'ニュースを更新しました', ['news_id' => $newsId]);
            set_flash('success', 'ニュースを更新しました。');
        } else {
            $stmt = $pdo->prepare('INSERT INTO news (id, title, date, tag, thumb, excerpt, content, url, is_published, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$newsId, $title, $date, $tag, $thumb, $excerpt, $contentJson, $url, $isPublished, $sortOrder]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'news', null, 'ニュースを作成しました', ['news_id' => $newsId]);
            set_flash('success', 'ニュースを作成しました。');
        }
        redirect_to($baseUrl . '/news.php');
    } catch (Exception $e) {
        set_flash('error', '保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/news_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}
start_page($isEdit ? 'ニュースを編集' : 'ニュースを追加', 'ニュース情報を入力してください。');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? 'ニュースを編集' : 'ニュースを追加') ?></h1><p>公開サイトに表示するニュース情報を管理します。</p></section>
  <form method="post" enctype="multipart/form-data" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>ニュースID</span><input type="text" name="id" value="<?= h($row['id']) ?>" <?= $isEdit ? 'readonly' : '' ?>></label>
      <label><span>日付</span><input type="date" name="date" value="<?= h($row['date']) ?>" required></label>
    </div>
    <label><span>タイトル</span><input type="text" name="title" value="<?= h($row['title']) ?>" required></label>
    <div class="form-grid two">
      <label><span>タグ</span><input type="text" name="tag" value="<?= h($row['tag']) ?>"></label>
      <label><span>並び順</span><input type="number" name="sort_order" value="<?= h((string)$row['sort_order']) ?>"></label>
    </div>
    <label><span>サムネイル画像パス</span><input type="text" name="thumb" value="<?= h($row['thumb']) ?>"></label>
    <label><span>サムネイル画像をアップロード</span><input type="file" name="thumb_file" accept="image/*"></label>
    <?php if (!empty($row['thumb'])): ?><img class="inline-preview" src="/<?= h($row['thumb']) ?>" alt="thumb"><?php endif; ?>
    <label><span>抜粋</span><textarea name="excerpt" rows="3"><?= h($row['excerpt']) ?></textarea></label>
    <label><span>本文（1行=1段落）</span><textarea name="content_text" rows="10"><?= h($row['content_text']) ?></textarea></label>
    <label><span>関連URL</span><input type="text" name="url" value="<?= h($row['url']) ?>"></label>
    <label class="checkbox-row"><input type="checkbox" name="is_published" value="1" <?= checked((bool)$row['is_published']) ?>><span>公開する</span></label>
    <div class="actions-inline">
      <button class="primary-btn" type="submit">この内容で保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/news.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
