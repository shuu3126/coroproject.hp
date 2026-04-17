<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function normalizeFileStem(string $value): string {
    $value = preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', trim($value));
    $value = trim((string)$value, '-_');
    $value = strtolower(substr((string)$value, 0, 50));
    return $value !== '' ? $value : 'news';
}

function normalizeNewsIdBase(string $value): string {
    $value = preg_replace('/\s+/u', '-', trim($value));
    $value = preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', (string)$value);
    $value = trim((string)$value, '-_');
    $value = strtolower(substr((string)$value, 0, 80));
    return $value !== '' ? $value : 'news';
}

function newsIdExists(PDO $pdo, string $id, ?string $excludeId = null): bool {
    if ($excludeId !== null && $excludeId !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE id = :id AND id <> :exclude_id');
        $stmt->execute([':id' => $id, ':exclude_id' => $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
    return (int)$stmt->fetchColumn() > 0;
}

function generateSafeNewsId(PDO $pdo, string $title, string $date = ''): string {
    $datePart = $date !== '' ? str_replace('-', '', $date) : date('Ymd');
    $titlePart = normalizeNewsIdBase($title);
    $base = normalizeNewsIdBase($datePart . '-' . $titlePart);

    if (!newsIdExists($pdo, $base)) {
        return $base;
    }

    for ($i = 2; $i <= 999; $i++) {
        $candidate = $base . '-' . $i;
        if (!newsIdExists($pdo, $candidate)) {
            return $candidate;
        }
    }

    return $base . '-' . date('His');
}

function ensureNewsImageDir(): array {
    $rootDir = dirname(__DIR__, 2);
    $relativeDir = 'images/news';
    $absoluteDir = $rootDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'news';

    if (!is_dir($absoluteDir)) {
        if (!mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('ニュース画像保存フォルダを作成できませんでした: ' . $absoluteDir);
        }
    }

    if (!is_writable($absoluteDir)) {
        throw new RuntimeException('ニュース画像保存フォルダに書き込みできません: ' . $absoluteDir);
    }

    return [$absoluteDir, $relativeDir];
}

function saveNewsThumbUpload(array $file, string $baseName): ?string {
    if (!isset($file['error'])) {
        return null;
    }

    if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('画像アップロードに失敗しました。（エラーコード: ' . (int)$file['error'] . '）');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('アップロードされた画像ファイルを確認できませんでした。');
    }

    if (!empty($file['size']) && (int)$file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('画像サイズは5MB以下にしてください。');
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $originalName = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($ext === '' || !in_array($ext, $allowedExts, true)) {
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $mimeMap[$mime] ?? '';
    }

    if ($ext === '' || !in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('アップロードできる画像形式は jpg / jpeg / png / gif / webp のみです。');
    }

    [$absoluteDir, $relativeDir] = ensureNewsImageDir();

    $stem = normalizeFileStem($baseName);
    $filename = $stem . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    $destination = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('画像ファイルを保存できませんでした。');
    }

    return $relativeDir . '/' . $filename;
}

$errorMessage = '';

// ------- 削除処理 -------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $stmt = $pdo->prepare('DELETE FROM news WHERE id = :id');
        $stmt->execute([':id' => $id]);
        header('Location: news.php?msg=deleted');
        exit;
    } catch (Throwable $e) {
        $errorMessage = '削除中にエラーが発生しました: ' . $e->getMessage();
    }
}

// ------- 保存処理（新規＆更新） -------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mode       = $_POST['mode'] ?? 'create';
        $originalId = trim($_POST['original_id'] ?? '');
        $id         = trim($_POST['id'] ?? '');
        $title      = trim($_POST['title'] ?? '');
        $date       = trim($_POST['date'] ?? '');
        $tag        = trim($_POST['tag'] ?? '');
        $thumb      = trim($_POST['thumb'] ?? '');
        $excerpt    = trim($_POST['excerpt'] ?? '');
        $url        = trim($_POST['url'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_pub     = isset($_POST['is_published']) ? 1 : 0;

        if ($title === '') {
            throw new RuntimeException('タイトルは必須です。');
        }

        if ($mode === 'update' && $originalId === '') {
            throw new RuntimeException('更新対象のニュースIDが見つかりません。');
        }

        // 本文：1行＝1段落 → JSON配列に変換
        $contentText = trim($_POST['content'] ?? '');
        if ($contentText === '') {
            $contentJson = json_encode([], JSON_UNESCAPED_UNICODE);
        } else {
            $lines = preg_split('/\R/u', $contentText);
            $lines = array_values(array_filter(array_map('trim', $lines), 'strlen'));
            $contentJson = json_encode($lines, JSON_UNESCAPED_UNICODE);
        }

        // id 未入力なら安全なIDを自動生成
        if ($id === '') {
            if ($mode === 'update' && $originalId !== '') {
                $id = $originalId;
            } else {
                $id = generateSafeNewsId($pdo, $title, $date);
            }
        }

        if ($mode === 'update') {
            if (newsIdExists($pdo, $id, $originalId)) {
                throw new RuntimeException('そのニュースIDはすでに使われています。別のIDにしてください。');
            }
        } else {
            if (newsIdExists($pdo, $id)) {
                throw new RuntimeException('そのニュースIDはすでに使われています。別のIDにしてください。');
            }
        }

        // 画像アップロードがあれば thumb より優先
        $uploadThumb = saveNewsThumbUpload($_FILES['thumb_file'] ?? [], $id !== '' ? $id : $title);
        if ($uploadThumb !== null) {
            $thumb = $uploadThumb;
        }

        if ($mode === 'update') {
            $sql = "UPDATE news SET
                        id = :new_id,
                        title = :title,
                        date = :date,
                        tag = :tag,
                        thumb = :thumb,
                        excerpt = :excerpt,
                        content = :content,
                        url = :url,
                        is_published = :is_published,
                        sort_order = :sort_order
                    WHERE id = :original_id";
        } else {
            $sql = "INSERT INTO news
                        (id, title, date, tag, thumb, excerpt, content, url, is_published, sort_order)
                    VALUES
                        (:new_id, :title, :date, :tag, :thumb, :excerpt, :content, :url, :is_published, :sort_order)";
        }

        $stmt = $pdo->prepare($sql);
        $params = [
            ':new_id'       => $id,
            ':title'        => $title,
            ':date'         => $date ?: date('Y-m-d'),
            ':tag'          => $tag ?: 'お知らせ',
            ':thumb'        => $thumb,
            ':excerpt'      => $excerpt,
            ':content'      => $contentJson,
            ':url'          => $url,
            ':is_published' => $is_pub,
            ':sort_order'   => $sort_order,
        ];
        if ($mode === 'update') {
            $params[':original_id'] = $originalId;
        }
        $stmt->execute($params);

        header('Location: news.php?msg=saved');
        exit;
    } catch (Throwable $e) {
        $errorMessage = '保存中にエラーが発生しました: ' . $e->getMessage();
        $editNews = [
            'id'           => $_POST['id'] ?? '',
            'title'        => $_POST['title'] ?? '',
            'date'         => $_POST['date'] ?? date('Y-m-d'),
            'tag'          => $_POST['tag'] ?? '',
            'thumb'        => $_POST['thumb'] ?? '',
            'excerpt'      => $_POST['excerpt'] ?? '',
            'content'      => json_encode([], JSON_UNESCAPED_UNICODE),
            '_content_text'=> $_POST['content'] ?? '',
            'url'          => $_POST['url'] ?? '',
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order'   => $_POST['sort_order'] ?? 0,
        ];
    }
}

// ------- 編集用データの取得 -------
if (!isset($editNews)) {
    $editNews = null;
}
if ($editNews === null && isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id');
    $stmt->execute([':id' => $_GET['id']]);
    $editNews = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editNews) {
        $contentText = '';
        $decoded = json_decode($editNews['content'], true);
        if (is_array($decoded)) {
            $contentText = implode("\n", $decoded);
        } else {
            $contentText = (string)$editNews['content'];
        }
        $editNews['_content_text'] = $contentText;
    }
}

// ------- 一覧を取得 -------
$stmt = $pdo->query('SELECT * FROM news ORDER BY date DESC, sort_order ASC, id DESC');
$allNews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>News管理 | CORO PROJECT</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:14px;line-height:1.5;margin:16px;background:#111827;color:#e5e7eb;}
    a{color:#60a5fa;text-decoration:none;}
    a:hover{text-decoration:underline;}
    h1{font-size:20px;margin-bottom:12px;}
    .layout{display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;}
    .panel{background:#020617;border-radius:12px;padding:16px;border:1px solid #1f2937;box-shadow:0 8px 20px rgba(0,0,0,.5);}
    .panel h2{margin-top:0;font-size:16px;margin-bottom:8px;}
    table{width:100%;border-collapse:collapse;font-size:12px;}
    th,td{border-bottom:1px solid #1f2937;padding:6px 8px;text-align:left;vertical-align:top;}
    th{background:#020617;position:sticky;top:0;z-index:1;}
    tr:nth-child(even){background:#020617;}
    .tag{display:inline-block;padding:2px 6px;border-radius:999px;font-size:11px;background:#1f2937;}
    .btn{display:inline-block;padding:6px 10px;border-radius:6px;border:none;cursor:pointer;font-size:13px;}
    .btn-primary{background:#6366f1;color:#fff;}
    .btn-danger{background:#b91c1c;color:#fff;}
    .btn-secondary{background:#374151;color:#e5e7eb;}
    input[type="text"],input[type="date"],input[type="number"],input[type="file"],textarea{
      width:100%;padding:6px 8px;border-radius:6px;border:1px solid #4b5563;background:#020617;color:#e5e7eb;font-size:13px;box-sizing:border-box;
    }
    input[type="file"]{padding:5px;}
    textarea{min-height:120px;resize:vertical;}
    label{display:block;font-size:12px;margin-top:6px;margin-bottom:2px;color:#9ca3af;}
    .row{display:flex;gap:8px;flex-wrap:wrap;}
    .row>div{flex:1;}
    .msg{margin-bottom:8px;font-size:12px;color:#a5b4fc;}
    .error{margin-bottom:8px;font-size:12px;color:#fca5a5;white-space:pre-wrap;}
    .help{font-size:11px;color:#94a3b8;margin:4px 0 0;}
    .current-thumb{margin-top:8px;}
    .current-thumb img{display:block;max-width:220px;max-height:140px;border-radius:8px;border:1px solid #1f2937;background:#0b1120;object-fit:cover;}

    .preview-wrap{margin-top:16px;border-top:1px dashed #1f2937;padding-top:12px;}
    .preview-title{font-size:13px;color:#9ca3af;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
    .preview-title span{font-size:10px;padding:2px 6px;border-radius:999px;background:#1f2937;}
    .preview-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1.2fr);gap:12px;}
    .preview-card,.preview-detail{background:#020617;border-radius:10px;border:1px solid #1f2937;padding:10px;}
    .preview-card-thumb{width:100%;padding-top:56%;border-radius:8px;background:#0b1120 center/cover no-repeat;margin-bottom:6px;position:relative;overflow:hidden;}
    .preview-card-thumb::after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,transparent,rgba(15,23,42,.7));opacity:.4;}
    .preview-card-meta{display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;margin-bottom:4px;}
    .preview-card-title{font-size:14px;font-weight:600;margin:0 0 4px;}
    .preview-card-text{font-size:12px;color:#cbd5f5;margin:0;}
    .preview-detail h3{font-size:14px;margin:0 0 4px;}
    .preview-detail-meta{font-size:11px;color:#9ca3af;margin-bottom:6px;}
    .preview-detail-body p{font-size:12px;margin:0 0 4px;}
    .preview-link{font-size:11px;margin-top:4px;}
    @media (max-width:1024px){
      .layout{flex-direction:column;}
      .preview-grid{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>
  <h1>News管理（CORO PROJECT）</h1>
  <?php if (!empty($_GET['msg'])): ?>
    <p class="msg">
      <?php if ($_GET['msg']==='saved') echo '保存しました。'; ?>
      <?php if ($_GET['msg']==='deleted') echo '削除しました。'; ?>
    </p>
  <?php endif; ?>
  <?php if ($errorMessage !== ''): ?>
    <p class="error"><?= esc($errorMessage) ?></p>
  <?php endif; ?>

  <div class="nav" style="margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #1f2937;font-size:13px;">
    <a href="index.php">🏠 トップ</a>
    <a href="news.php">📰 News管理</a>
    <a href="talents.php">👤 Talents管理</a>
    <a href="https://coroproject.jp/index.php" target="_blank">🌐 サイトTOP</a>
  </div>

  <div class="layout">
    <div class="panel" style="flex:1.4;max-height:80vh;overflow:auto;min-width:320px;">
      <h2>一覧</h2>
      <table>
        <thead>
          <tr>
            <th>日付</th>
            <th>タグ</th>
            <th>タイトル</th>
            <th>公開</th>
            <th>並び順</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allNews as $n): ?>
            <tr>
              <td><?= esc($n['date']) ?></td>
              <td><span class="tag"><?= esc($n['tag']) ?></span></td>
              <td><?= esc($n['title']) ?></td>
              <td><?= $n['is_published'] ? '公開' : '非公開' ?></td>
              <td><?= (int)$n['sort_order'] ?></td>
              <td>
                <a href="news.php?action=edit&id=<?= esc($n['id']) ?>">編集</a> /
                <a href="news.php?action=delete&id=<?= esc($n['id']) ?>" onclick="return confirm('本当に削除しますか？');" style="color:#f97373;">削除</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel" style="flex:1;min-width:320px;">
      <h2><?= $editNews ? 'ニュース編集' : '新規追加' ?></h2>
      <form method="post" action="news.php" id="newsForm" enctype="multipart/form-data">
        <input type="hidden" name="mode" value="<?= $editNews ? 'update' : 'create' ?>">
        <?php if ($editNews): ?>
          <input type="hidden" name="original_id" value="<?= esc($editNews['id']) ?>">
        <?php endif; ?>

        <label>ニュースID（URLなどに使うID。空なら自動生成）</label>
        <input type="text" name="id" value="<?= esc($editNews['id'] ?? '') ?>">

        <div class="row">
          <div>
            <label>タイトル</label>
            <input type="text" name="title" required value="<?= esc($editNews['title'] ?? '') ?>">
          </div>
          <div>
            <label>日付</label>
            <input type="date" name="date" value="<?= esc($editNews['date'] ?? date('Y-m-d')) ?>">
          </div>
        </div>

        <div class="row">
          <div>
            <label>タグ</label>
            <input type="text" name="tag" placeholder="お知らせ / リリース / イベント など" value="<?= esc($editNews['tag'] ?? '') ?>">
          </div>
          <div>
            <label>並び順（小さいほど上）</label>
            <input type="number" name="sort_order" value="<?= esc($editNews['sort_order'] ?? 0) ?>">
          </div>
        </div>

        <label>サムネイル画像アップロード</label>
        <input type="file" name="thumb_file" id="thumb_file" accept="image/jpeg,image/png,image/gif,image/webp">
        <p class="help">アップロードした画像は <span style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">images/news/</span> に保存され、画像パスより優先して使われます。対応形式: jpg / jpeg / png / gif / webp、5MBまで。</p>

        <label>サムネイル画像パス（アップロードしない場合のみ使用）</label>
        <input type="text" name="thumb" id="thumb_input" value="<?= esc($editNews['thumb'] ?? '') ?>" placeholder="images/news/example.jpg または ../images/news/example.jpg">

        <?php if (!empty($editNews['thumb'])): ?>
          <div class="current-thumb">
            <div class="help" style="margin-bottom:6px;">現在のサムネイル</div>
            <img src="<?= esc($editNews['thumb']) ?>" alt="現在のサムネイル" onerror="this.style.display='none'">
          </div>
        <?php endif; ?>

        <label>抜粋（一覧カードに表示される短い説明）</label>
        <textarea name="excerpt"><?= esc($editNews['excerpt'] ?? '') ?></textarea>

        <label>本文（1行＝1段落になります）</label>
        <textarea name="content" placeholder="1行につき1段落で入力してください"><?= esc($editNews['_content_text'] ?? '') ?></textarea>

        <label>関連URL（詳細ページや外部リンク。不要なら空でOK）</label>
        <input type="text" name="url" value="<?= esc($editNews['url'] ?? '') ?>">

        <label>
          <input type="checkbox" name="is_published" value="1" <?= (!isset($editNews['is_published']) || $editNews['is_published']) ? 'checked' : '' ?>>
          公開する
        </label>

        <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary">保存する</button>
          <a href="news.php" class="btn btn-secondary">新規作成に戻る</a>
        </div>
      </form>

      <div class="preview-wrap">
        <div class="preview-title">
          プレビュー
          <span>Newsページのイメージ表示（保存前に確認用）</span>
        </div>
        <div class="preview-grid">
          <div class="preview-card">
            <div class="preview-card-thumb" id="pv-thumb"></div>
            <div class="preview-card-meta">
              <span id="pv-date"></span>
              <span id="pv-tag" class="tag"></span>
            </div>
            <h3 class="preview-card-title" id="pv-title"></h3>
            <p class="preview-card-text" id="pv-excerpt"></p>
          </div>
          <div class="preview-detail">
            <div class="preview-detail-meta">
              <span id="pv-date-detail"></span>
              <span id="pv-tag-detail" class="tag"></span>
            </div>
            <h3 id="pv-title-detail"></h3>
            <div class="preview-detail-body" id="pv-body"></div>
            <div class="preview-link" id="pv-link"></div>
          </div>
        </div>
      </div>

      <p style="font-size:11px;color:#6b7280;margin-top:12px;">
        ※「本文」は1行＝1段落として、サイト側では自動で&lt;p&gt;に分割して表示されます。<br>
        ※「並び順」は小さい数字が上に来ます（0,1,2...）。同じなら日付の新しいものが上。<br>
        ※削除時、アップロード済み画像ファイル自体は自動削除しません。
      </p>
    </div>
  </div>

  <script>
    (function(){
      const form = document.getElementById('newsForm');
      if (!form) return;

      const $ = name => form.querySelector('[name="'+name+'"]');

      const elTitle   = $('title');
      const elDate    = $('date');
      const elTag     = $('tag');
      const elThumb   = $('thumb');
      const elThumbF  = $('thumb_file');
      const elExcerpt = $('excerpt');
      const elContent = $('content');
      const elUrl     = $('url');

      const pvThumb   = document.getElementById('pv-thumb');
      const pvDate    = document.getElementById('pv-date');
      const pvTag     = document.getElementById('pv-tag');
      const pvTitle   = document.getElementById('pv-title');
      const pvExcerpt = document.getElementById('pv-excerpt');

      const pvDateD   = document.getElementById('pv-date-detail');
      const pvTagD    = document.getElementById('pv-tag-detail');
      const pvTitleD  = document.getElementById('pv-title-detail');
      const pvBody    = document.getElementById('pv-body');
      const pvLink    = document.getElementById('pv-link');

      function escHtml(str){
        return String(str || '').replace(/[&<>"']/g, s => ({
          '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[s]));
      }

      function fmtDateInput(v){
        if(!v) return '';
        const d = new Date(v);
        if (isNaN(d)) return v;
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const dd= String(d.getDate()).padStart(2,'0');
        return `${y}.${m}.${dd}`;
      }

      let uploadedPreviewUrl = '';
      function refreshUploadPreview(){
        if (uploadedPreviewUrl) {
          URL.revokeObjectURL(uploadedPreviewUrl);
          uploadedPreviewUrl = '';
        }
        const file = elThumbF && elThumbF.files && elThumbF.files[0] ? elThumbF.files[0] : null;
        if (file) {
          uploadedPreviewUrl = URL.createObjectURL(file);
        }
      }

      function render(){
        const title   = elTitle.value.trim();
        const date    = elDate.value.trim();
        const tag     = elTag.value.trim() || 'お知らせ';
        const thumb   = elThumb.value.trim();
        const excerpt = elExcerpt.value.trim();
        const url     = elUrl.value.trim();
        const content = elContent.value;

        pvTitle.textContent   = title || 'タイトル';
        pvExcerpt.textContent = excerpt || 'ここに抜粋が表示されます。';
        pvTag.textContent     = tag;
        pvDate.textContent    = fmtDateInput(date) || '----.--.--';

        const previewThumb = uploadedPreviewUrl || thumb;
        if (previewThumb){
          pvThumb.style.backgroundImage = `url('${previewThumb.replace(/'/g, "\\'")}')`;
        } else {
          pvThumb.style.backgroundImage = 'linear-gradient(135deg,#4f46e5,#ec4899)';
        }

        pvTitleD.textContent = title || 'タイトル';
        pvTagD.textContent   = tag;
        pvDateD.textContent  = fmtDateInput(date) || '----.--.--';

        pvBody.innerHTML = '';
        const lines = content.split(/\r?\n/).filter(l => l.trim().length);
        if (!lines.length){
          const p = document.createElement('p');
          p.textContent = 'ここに本文が表示されます。';
          pvBody.appendChild(p);
        } else {
          lines.forEach(line=>{
            const p = document.createElement('p');
            p.textContent = line;
            pvBody.appendChild(p);
          });
        }

        if (url){
          pvLink.innerHTML = `<a href="${escHtml(url)}" target="_blank" style="color:#60a5fa;">関連リンク（クリックで新しいタブ）</a>`;
        } else {
          pvLink.textContent = '';
        }
      }

      ['input','change'].forEach(ev=>{
        form.addEventListener(ev, e=>{
          if (e.target.matches('input,textarea')) {
            if (e.target === elThumbF) {
              refreshUploadPreview();
            }
            render();
          }
        });
      });

      refreshUploadPreview();
      render();
    })();
  </script>
</body>
</html>
