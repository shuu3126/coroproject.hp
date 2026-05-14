<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
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

function news_talent_stream_url(array $talent): string {
    $platforms = json_decode((string)($talent['platforms_json'] ?? '[]'), true);
    $links = json_decode((string)($talent['links_json'] ?? '[]'), true);
    $candidates = [];

    foreach (is_array($platforms) ? $platforms : [] as $row) {
        $label = mb_strtolower((string)($row['name'] ?? ''));
        $url = trim((string)($row['url'] ?? ''));
        if ($url === '') continue;
        $score = (strpos($label, 'youtube') !== false || strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) ? 100 : 10;
        if (strpos($label, 'twitch') !== false || strpos($url, 'twitch.tv') !== false) $score = max($score, 90);
        $candidates[] = ['score' => $score, 'url' => $url];
    }
    foreach (is_array($links) ? $links : [] as $row) {
        $url = trim((string)($row['url'] ?? ''));
        if ($url !== '') $candidates[] = ['score' => 1, 'url' => $url];
    }

    usort($candidates, static function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    return $candidates[0]['url'] ?? '';
}

// Ensure targets column exists
try { $pdo->exec("ALTER TABLE news ADD COLUMN targets VARCHAR(120) NOT NULL DEFAULT 'main,production'"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE news ADD COLUMN talent_id VARCHAR(191) NULL AFTER targets"); } catch (Exception $e) {}

$talentRows = $pdo->query('SELECT id, name, platforms_json, links_json FROM talents WHERE is_published = 1 ORDER BY sort_order ASC, name ASC')->fetchAll(PDO::FETCH_ASSOC);
$talentOptions = [];
foreach ($talentRows as $talentRow) {
    $talentOptions[] = [
        'id' => (string)$talentRow['id'],
        'name' => (string)$talentRow['name'],
        'stream_url' => news_talent_stream_url($talentRow),
    ];
}

// OGP画像取得AJAXエンドポイント
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['action']) ? $_GET['action'] : '') === 'fetch_ogp') {
    header('Content-Type: application/json; charset=utf-8');
    $fetchUrl = trim((string)(isset($_GET['url']) ? $_GET['url'] : ''));
    if ($fetchUrl === '') {
        echo json_encode(['ok' => false, 'error' => 'URLが未入力です']);
        exit;
    }
    $imgUrl = fetch_ogp_image_url($fetchUrl);
    if ($imgUrl === null) {
        echo json_encode(['ok' => false, 'error' => 'OGP画像が見つかりませんでした']);
        exit;
    }
    echo json_encode(['ok' => true, 'url' => $imgUrl]);
    exit;
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
    'targets' => 'main,production',
    'talent_id' => '',
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

// Parse current targets for display
$currentTargets = array_filter(array_map('trim', explode(',', $row['targets'] ?? 'main,production')));
if (empty($currentTargets)) $currentTargets = ['main', 'production'];

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
    $talentId = trim((isset($_POST['talent_id']) ? $_POST['talent_id'] : ''));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $allowedTargets = ['main', 'production', 'business', 'creative'];
    $postedTargets = array_values(array_intersect((array)($_POST['target'] ?? []), $allowedTargets));
    $targets = $postedTargets ? implode(',', $postedTargets) : 'main';

    if ($title === '') {
        set_flash('error', 'タイトルは必須です。');
        redirect_to($baseUrl . '/content/news_edit.php' . ($id !== '' ? '?id=' . urlencode($id) : ''));
    }

    if ($newsId === '') {
        $newsId = generate_news_id($pdo, $title, $date);
    }

    $excludeIdForCheck = ($postedOriginalId !== '') ? $postedOriginalId : null;
    if (news_id_exists($pdo, $newsId, $excludeIdForCheck)) {
        set_flash('error', 'そのニュースIDはすでに使用されています。');
        redirect_to($baseUrl . '/content/news_edit.php' . ($postedOriginalId !== '' ? '?id=' . urlencode($postedOriginalId) : ''));
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

        // 画像未設定かつURLがある場合、OGP画像を自動取得
        if ($thumb === '' && $url !== '') {
            $ogpImg = fetch_ogp_image_url($url);
            if ($ogpImg !== null) {
                $thumb = $ogpImg;
            }
        }
        if ($thumb === '') {
            $thumb = 'images/ogp.png';
        }

        $contentJson = parse_text_lines_to_json($contentText);
        $contentPlain = $contentText;

        if ($postedOriginalId !== '' || ($postedOriginalTitle !== '' && $postedOriginalDate !== '')) {
            if ($postedOriginalId !== '') {
                $stmt = $pdo->prepare('
                    UPDATE news
                    SET id = ?, title = ?, date = ?, tag = ?, thumb = ?, excerpt = ?, content = ?, content_json = ?, url = ?, is_published = ?, sort_order = ?, targets = ?, talent_id = ?
                    WHERE id = ?
                ');
                $stmt->execute([
                    $newsId, $title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder, $targets, $talentId !== '' ? $talentId : null,
                    $postedOriginalId
                ]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE news
                    SET id = ?, title = ?, date = ?, tag = ?, thumb = ?, excerpt = ?, content = ?, content_json = ?, url = ?, is_published = ?, sort_order = ?, targets = ?, talent_id = ?
                    WHERE (id IS NULL OR id = "") AND title = ? AND date = ? AND tag = ?
                    LIMIT 1
                ');
                $stmt->execute([
                    $newsId, $title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder, $targets, $talentId !== '' ? $talentId : null,
                    $postedOriginalTitle, $postedOriginalDate, $postedOriginalTag
                ]);
            }

            write_admin_log($pdo, (int)$user['id'], 'edit', 'news', null, 'ニュースを更新しました', ['news_id' => $newsId]);
            set_flash('success', 'ニュースを更新しました。');
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO news (id, title, date, tag, thumb, excerpt, content, content_json, url, is_published, sort_order, targets, talent_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $newsId, $title, $date, $tag, $thumb, $excerpt, $contentPlain, $contentJson, $url, $isPublished, $sortOrder, $targets, $talentId !== '' ? $talentId : null
            ]);

            write_admin_log($pdo, (int)$user['id'], 'create', 'news', null, 'ニュースを作成しました', ['news_id' => $newsId]);
            set_flash('success', 'ニュースを作成しました。');
        }

        redirect_to($baseUrl . '/content/news.php');
    } catch (Exception $e) {
        set_flash('error', '保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/content/news_edit.php' . ($postedOriginalId !== '' ? '?id=' . urlencode($postedOriginalId) : ''));
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
      <img class="inline-preview" src="<?= h(admin_public_url($row['thumb'])) ?>" alt="thumb">
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
      <input type="text" name="url" id="news-url" value="<?= h($row['url']) ?>">
    </label>

    <label>
      <span>関連タレント</span>
      <select name="talent_id" id="news-talent-id">
        <option value="">指定なし</option>
        <?php foreach ($talentOptions as $talent): ?>
          <option value="<?= h($talent['id']) ?>" data-stream-url="<?= h($talent['stream_url']) ?>" <?= selected((string)($row['talent_id'] ?? ''), $talent['id']) ?>>
            <?= h($talent['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div id="ogp-fetch-wrap" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <button type="button" id="ogp-fetch-btn" class="ghost-btn" style="font-size:.82em;">URLからOGP画像を取得</button>
      <span id="ogp-fetch-status" style="font-size:.8em;color:#666;"></span>
    </div>

    <div>
      <span style="font-size:.82em;font-weight:700;display:block;margin-bottom:8px;">掲載先（複数選択可）</span>
      <div style="display:flex;flex-wrap:wrap;gap:16px;">
        <?php foreach (['main' => '総合サイト', 'production' => 'Production', 'business' => 'Business', 'creative' => 'Creative'] as $tVal => $tLabel): ?>
          <label class="checkbox-row">
            <input type="checkbox" name="target[]" value="<?= h($tVal) ?>" <?= in_array($tVal, $currentTargets) ? 'checked' : '' ?>>
            <span><?= h($tLabel) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <label class="checkbox-row">
      <input type="checkbox" name="is_published" value="1" <?= checked((bool)$row['is_published']) ?>>
      <span>公開する</span>
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">この内容で保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/content/news.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<script>
(function () {
  var btn = document.getElementById('ogp-fetch-btn');
  var status = document.getElementById('ogp-fetch-status');
  var thumbInput = document.querySelector('input[name="thumb"]');
  var thumbPreview = document.querySelector('.inline-preview');
  var urlInput = document.getElementById('news-url');
  var talentSelect = document.getElementById('news-talent-id');

  if (!btn || !thumbInput || !urlInput) return;

  if (talentSelect) {
    var applyTalentUrl = function (force) {
      var option = talentSelect.options[talentSelect.selectedIndex];
      var streamUrl = option ? (option.getAttribute('data-stream-url') || '') : '';
      if (streamUrl && (force || !urlInput.value.trim())) {
        urlInput.value = streamUrl;
        status.textContent = '関連URLにタレントの配信URLを入力しました。';
        status.style.color = '#27ae60';
      }
    };
    talentSelect.addEventListener('change', function () {
      applyTalentUrl(true);
    });
    var form = talentSelect.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        applyTalentUrl(false);
      });
    }
  }

  btn.addEventListener('click', function () {
    var url = urlInput.value.trim();
    if (!url) {
      status.textContent = 'URLを入力してください。';
      status.style.color = '#c0392b';
      return;
    }

    btn.disabled = true;
    status.textContent = '取得中...';
    status.style.color = '#666';

    var endpoint = '<?= h($baseUrl) ?>/content/news_edit.php?action=fetch_ogp&url=' + encodeURIComponent(url);
    fetch(endpoint)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          thumbInput.value = data.url;
          status.textContent = 'OGP画像を取得しました。';
          status.style.color = '#27ae60';
          // プレビュー更新
          if (thumbPreview) {
            thumbPreview.src = data.url;
            thumbPreview.style.display = '';
          } else {
            var img = document.createElement('img');
            img.className = 'inline-preview';
            img.src = data.url;
            img.alt = 'thumb';
            thumbInput.closest('form').insertBefore(img, document.getElementById('ogp-fetch-wrap'));
          }
        } else {
          status.textContent = data.error || 'OGP画像が見つかりませんでした。';
          status.style.color = '#c0392b';
        }
      })
      .catch(function () {
        status.textContent = '取得に失敗しました。';
        status.style.color = '#c0392b';
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
})();
</script>
<?php end_page(); ?>
