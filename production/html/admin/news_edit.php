<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

function news_id_exists($pdo, $id, $excludeId = null) {
    if ($excludeId !== null && $excludeId !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE id = ? AND id <> ?');
        $stmt->execute([$id, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE id = ?');
        $stmt->execute([$id]);
    }
    return (int)$stmt->fetchColumn() > 0;
}

function generate_news_id($pdo, $title, $date = '') {
    $datePart = $date !== '' ? str_replace('-', '', $date) : date('Ymd');
    $base = normalize_file_stem($datePart . '-' . $title, 'news');
    if ($base === '') {
        $base = 'news-' . $datePart;
    }

    $candidate = $base;
    $i = 2;

    while (news_id_exists($pdo, $candidate)) {
        $candidate = $base . '-' . $i;
        $i++;
    }

    return $candidate;
}

function news_lines_from_any_content($jsonOrText) {
    $raw = (string)$jsonOrText;
    $decoded = json_decode($raw, true);

    if (is_array($decoded)) {
        return implode("\n", array_map('strval', $decoded));
    }

    return $raw;
}

$id = (isset($_GET['id']) ? trim((string)$_GET['id']) : '');
$lookupTitle = (isset($_GET['lookup_title']) ? trim((string)$_GET['lookup_title']) : '');
$lookupDate = (isset($_GET['lookup_date']) ? trim((string)$_GET['lookup_date']) : '');
$lookupTag = (isset($_GET['lookup_tag']) ? trim((string)$_GET['lookup_tag']) : '');

$isEdit = false;
$originalId = '';
$originalTitle = '';
$originalDate = '';
$originalTag = '';

$row = [
    'id' => '',
    'title' => '',
    'date' => date('Y-m-d'),
    'tag' => '',
    'thumb' => '',
    'excerpt' => '',
    'content_text' => '',
    'url' => '',
    'is_published' => 1,
    'sort_order' => 0,
];

if ($id !== '') {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();

    if ($found) {
        $isEdit = true;
        $originalId = (string)$found['id'];
        $originalTitle = (string)$found['title'];
        $originalDate = (string)$found['date'];
        $originalTag = (string)$found['tag'];

        $row = array_merge($row, $found);
        $row['content_text'] = news_lines_from_any_content(
            (isset($found['content_json']) && $found['content_json'] !== '') ? $found['content_json'] : (isset($found['content']) ? $found['content'] : '')
        );
    }
} elseif ($lookupTitle !== '' && $lookupDate !== '') {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE (id IS NULL OR id = "") AND title = ? AND date = ? AND tag = ? LIMIT 1');
    $stmt->execute([$lookupTitle, $lookupDate, $lookupTag]);
    $found = $stmt->fetch();

    if ($found) {
        $isEdit = true;
        $originalId = (string)$found['id'];
        $originalTitle = (string)$found['title'];
        $originalDate = (string)$found['date'];
        $originalTag = (string)$found['tag'];

        $row = array_merge($row, $found);
        $row['content_text'] = news_lines_from_any_content(
            (isset($found['content_json']) && $found['content_json'] !== '') ? $found['content_json'] : (isset($found['content']) ? $found['content'] : '')
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedOriginalId = trim((isset($_POST['original_id']) ? $_POST['original_id'] : ''));
    $postedOriginalTitle = trim((isset($_POST['original_title']) ? $_POST['original_title'] : ''));
    $postedOriginalDate = trim((isset($_POST['original_date']) ? $_POST['original_date'] : ''));
    $postedOriginalTag = trim((isset($_POST['original_tag']) ? $_POST['original_tag'] : ''));

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
        redirect_to($baseUrl . '/news_edit.php' . ($id !== '' ? '?id=' . urlencode($id) : ''));
    }

    if ($newsId === '') {
        $newsId = generate_news_id($pdo, $title, $date);
    }

    $excludeIdForCheck = ($postedOriginalId !== '') ? $postedOriginalId : null;
    if (news_id_exists($pdo, $newsId, $excludeIdForCheck)) {
        set_flash('error', 'そのニュースIDはすでに使用されています。');
        redirect_to($baseUrl . '/news_edit.php' . ($postedOriginalId !== '' ? '?id=' . urlencode($postedOriginalId) : ''));
    }

    try {
        $upload = save_uploaded_image(
            $_FILES['thumb_file'] ?? [],
            $config['uploads']['news_public_dir'],
            $config['uploads']['news_public_prefix'],
            $newsId ?: $title
        );
        if ($upload !== null) {
            $thumb = $upload;
        }

        $contentJson = parse_text_lines_to_json($contentText);
        $contentPlain = $contentText;

        if ($postedOriginalId !== '' || ($postedOriginalTitle !== '' && $postedOriginalDate !== '')) {
            if ($postedOriginalId !== '') {
                $stmt = $pdo->prepare('
                    UPDATE news
                    SET id = ?, title = ?, date = ?, tag = ?, thumb = ?, excerpt = ?, content = ?, content_json = ?, url = ?, is_published = ?, sort_order = ?
                    WHERE id = ?
                ');
                $stmt->execute([
                    $newsId, $title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder,
                    $postedOriginalId
                ]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE news
                    SET id = ?, title = ?, date = ?, tag = ?, thumb = ?, excerpt = ?, content = ?, content_json = ?, url = ?, is_published = ?, sort_order = ?
                    WHERE (id IS NULL OR id = "") AND title = ? AND date = ? AND tag = ?
                    LIMIT 1
                ');
                $stmt->execute([
                    $newsId, $title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder,
                    $postedOriginalTitle, $postedOriginalDate, $postedOriginalTag
                ]);
            }

            write_admin_log($pdo, (int)$user['id'], 'edit', 'news', null, 'ニュースを更新しました', ['news_id' => $newsId]);
            set_flash('success', 'ニュースを更新しました。');
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO news (id, title, date, tag, thumb, excerpt, content, content_json, url, is_published, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $newsId, $title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder
            ]);

            write_admin_log($pdo, (int)$user['id'], 'create', 'news', null, 'ニュースを作成しました', ['news_id' => $newsId]);
            set_flash('success', 'ニュースを作成しました。');
        }

        redirect_to($baseUrl . '/news.php');
    } catch (Exception $e) {
        set_flash('error', '保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/news_edit.php' . ($postedOriginalId !== '' ? '?id=' . urlencode($postedOriginalId) : ''));
    }
}

start_page($isEdit ? 'ニュースを編集' : 'ニュースを追加', 'ニュース情報を入力してください。');
?>
<main class="page-container narrow">
  <section class="page-header-block">
    <h1><?= h($isEdit ? 'ニュースを編集' : 'ニュースを追加') ?></h1>
    <p>公開サイトに表示するニュース情報を管理します。</p>
  </section>

  <form method="post" enctype="multipart/form-data" class="card form-card form-stack">
    <input type="hidden" name="original_id" value="<?= h($originalId) ?>">
    <input type="hidden" name="original_title" value="<?= h($originalTitle) ?>">
    <input type="hidden" name="original_date" value="<?= h($originalDate) ?>">
    <input type="hidden" name="original_tag" value="<?= h($originalTag) ?>">

    <div class="form-grid two">
      <label>
        <span>ニュースID</span>
        <input type="text" name="id" value="<?= h($row['id']) ?>" placeholder="空欄なら保存時に自動生成されます">
      </label>

      <label>
        <span>日付</span>
        <input type="date" name="date" value="<?= h($row['date']) ?>" required>
      </label>
    </div>

    <label>
      <span>タイトル</span>
      <input type="text" name="title" value="<?= h($row['title']) ?>" required>
    </label>

    <div class="form-grid two">
      <label>
        <span>タグ</span>
        <input type="text" name="tag" value="<?= h($row['tag']) ?>">
      </label>

      <label>
        <span>並び順</span>
        <input type="number" name="sort_order" value="<?= h((string)$row['sort_order']) ?>">
      </label>
    </div>

    <label>
      <span>サムネイル画像パス</span>
      <input type="text" name="thumb" value="<?= h($row['thumb']) ?>">
    </label>

    <label>
      <span>サムネイル画像をアップロード</span>
      <input type="file" name="thumb_file" accept="image/*">
    </label>

    <?php if (!empty($row['thumb'])): ?>
      <img class="inline-preview" src="<?= h(public_asset_url($row['thumb'])) ?>" alt="thumb">
    <?php endif; ?>

    <label>
      <span>抜粋</span>
      <textarea name="excerpt" rows="3"><?= h($row['excerpt']) ?></textarea>
    </label>

    <label>
      <span>本文（1行=1段落）</span>
      <textarea name="content_text" rows="10"><?= h($row['content_text']) ?></textarea>
    </label>

    <label>
      <span>関連URL</span>
      <input type="text" name="url" value="<?= h($row['url']) ?>">
    </label>

    <label class="checkbox-row">
      <input type="checkbox" name="is_published" value="1" <?= checked((bool)$row['is_published']) ?>>
      <span>公開する</span>
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">この内容で保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/news.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
