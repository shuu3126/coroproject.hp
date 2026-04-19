<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$isEdit = $id > 0;
$row = [
    'id' => 0,
    'title' => '',
    'date' => date('Y-m-d'),
    'tag' => '',
    'thumb' => '',
    'excerpt' => '',
    'content' => '',
    'content_json' => '[]',
    'url' => '',
    'is_published' => 1,
    'sort_order' => 0,
    'content_text' => '',
];
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $row = array_merge($row, $found);
        $row['content_text'] = news_content_text_from_json(isset($found['content_json']) && $found['content_json'] !== '' ? $found['content_json'] : $found['content']);
    } else {
        set_flash('error', 'ニュースが見つかりません。');
        redirect_to($baseUrl . '/news.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $date = trim(isset($_POST['date']) ? $_POST['date'] : '');
    $tag = trim(isset($_POST['tag']) ? $_POST['tag'] : '');
    $thumb = trim(isset($_POST['thumb']) ? $_POST['thumb'] : '');
    $excerpt = trim(isset($_POST['excerpt']) ? $_POST['excerpt'] : '');
    $contentText = trim(isset($_POST['content_text']) ? $_POST['content_text'] : '');
    $url = trim(isset($_POST['url']) ? $_POST['url'] : '');
    $sortOrder = (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '') {
        set_flash('error', 'タイトルは必須です。');
        redirect_to($baseUrl . '/news_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : ''));
    }

    try {
        $upload = save_uploaded_image($_FILES['thumb_file'] ?? [], $config['uploads']['news_public_dir'], $config['uploads']['news_public_prefix'], $title . '-' . $date);
        if ($upload !== null) {
            $thumb = $upload;
        }
        $contentJson = news_content_json_from_text($contentText);
        $contentPlain = implode("\n", parse_lines_to_array($contentText));
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE news SET title=?, date=?, tag=?, thumb=?, excerpt=?, content=?, content_json=?, url=?, is_published=?, sort_order=? WHERE id=?');
            $stmt->execute([$title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder, $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'news', $id, 'ニュースを更新しました');
        } else {
            $stmt = $pdo->prepare('INSERT INTO news (title, date, tag, thumb, excerpt, content, content_json, url, is_published, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder]);
            $id = (int)$pdo->lastInsertId();
            write_admin_log($pdo, (int)$user['id'], 'create', 'news', $id, 'ニュースを作成しました');
        }
        set_flash('success', 'ニュースを保存しました。');
        redirect_to($baseUrl . '/news.php');
    } catch (Exception $e) {
        set_flash('error', '保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/news_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : ''));
    }
}
start_page($isEdit ? 'ニュースを編集' : 'ニュースを追加', '公開側の content_json 形式で本文を保存します。');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? 'ニュースを編集' : 'ニュースを追加') ?></h1><p>公開 news.php に合わせて保存されます。</p></section>
  <form method="post" enctype="multipart/form-data" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>日付</span><input type="date" name="date" value="<?= h($row['date']) ?>" required></label>
      <label><span>並び順</span><input type="number" name="sort_order" value="<?= h((string)$row['sort_order']) ?>"></label>
    </div>
    <label><span>タイトル</span><input type="text" name="title" value="<?= h($row['title']) ?>" required></label>
    <div class="form-grid two">
      <label><span>タグ</span><input type="text" name="tag" value="<?= h($row['tag']) ?>"></label>
      <label><span>関連URL</span><input type="text" name="url" value="<?= h($row['url']) ?>"></label>
    </div>
    <label><span>サムネイル画像パス</span><input type="text" name="thumb" value="<?= h($row['thumb']) ?>"></label>
    <label><span>サムネイル画像をアップロード</span><input type="file" name="thumb_file" accept="image/*"></label>
    <?php if (!empty($row['thumb'])): ?><img class="inline-preview" src="/<?= h($row['thumb']) ?>" alt="thumb"><?php endif; ?>
    <label><span>抜粋</span><textarea name="excerpt" rows="3"><?= h($row['excerpt']) ?></textarea></label>
    <label><span>本文（1行=1段落）</span><textarea name="content_text" rows="10"><?= h($row['content_text']) ?></textarea></label>
    <label class="checkbox-row"><input type="checkbox" name="is_published" value="1" <?= checked((bool)$row['is_published']) ?>><span>公開する</span></label>
    <div class="actions-inline">
      <button class="primary-btn" type="submit">この内容で保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/news.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
