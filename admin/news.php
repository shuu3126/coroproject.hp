<?php
// html/admin/news.php

// ルート直下の db.php を読む
require_once __DIR__ . '/../../db.php';

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// ------- 削除処理 -------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: news.php?msg=deleted');
    exit;
}

// ------- 保存処理（新規＆更新） -------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = trim($_POST['id'] ?? '');
    $mode       = $_POST['mode'] ?? 'create'; // create or update
    $title      = trim($_POST['title'] ?? '');
    $date       = trim($_POST['date'] ?? '');
    $tag        = trim($_POST['tag'] ?? '');
    $thumb      = trim($_POST['thumb'] ?? '');
    $excerpt    = trim($_POST['excerpt'] ?? '');
    $url        = trim($_POST['url'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_pub     = isset($_POST['is_published']) ? 1 : 0;

    // 本文：1行＝1段落 → JSON配列に変換
    $contentText = trim($_POST['content'] ?? '');
    if ($contentText === '') {
        $contentJson = json_encode([], JSON_UNESCAPED_UNICODE);
    } else {
        $lines = preg_split('/\R/u', $contentText);
        $lines = array_values(array_filter($lines, 'strlen'));
        $contentJson = json_encode($lines, JSON_UNESCAPED_UNICODE);
    }

    // id 未入力なら、自動で生成（date-titleスラッグ）
    if ($id === '') {
        $base = ($date ?: date('Y-m-d')) . '-' . mb_substr(preg_replace('/\s+/', '-', $title), 0, 20);
        $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $base);
        $id = strtolower($base);
    }

    if ($mode === 'update') {
        $sql = "UPDATE news SET
                    title = :title,
                    date = :date,
                    tag = :tag,
                    thumb = :thumb,
                    excerpt = :excerpt,
                    content = :content,
                    url = :url,
                    is_published = :is_published,
                    sort_order = :sort_order
                WHERE id = :id";
    } else {
        $sql = "INSERT INTO news
                    (id, title, date, tag, thumb, excerpt, content, url, is_published, sort_order)
                VALUES
                    (:id, :title, :date, :tag, :thumb, :excerpt, :content, :url, :is_published, :sort_order)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'           => $id,
        ':title'        => $title,
        ':date'         => $date ?: date('Y-m-d'),
        ':tag'          => $tag ?: 'お知らせ',
        ':thumb'        => $thumb,
        ':excerpt'      => $excerpt,
        ':content'      => $contentJson,
        ':url'          => $url,
        ':is_published' => $is_pub,
        ':sort_order'   => $sort_order,
    ]);

    header('Location: news.php?msg=saved');
    exit;
}

// ------- 編集用データの取得 -------
$editNews = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    $editNews = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editNews) {
        // JSONをテキストに戻す（1行＝1段落）
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
$stmt = $pdo->query("SELECT * FROM news ORDER BY date DESC, sort_order ASC, id DESC");
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
    .layout{display:flex;gap:24px;align-items:flex-start;}
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
    input[type="text"],input[type="date"],input[type="number"],textarea{
      width:100%;padding:6px 8px;border-radius:6px;border:1px solid #4b5563;background:#020617;color:#e5e7eb;font-size:13px;
    }
    textarea{min-height:120px;resize:vertical;}
    label{display:block;font-size:12px;margin-top:6px;margin-bottom:2px;color:#9ca3af;}
    .row{display:flex;gap:8px;}
    .row>div{flex:1;}
    .msg{margin-bottom:8px;font-size:12px;color:#a5b4fc;}
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

  <div class="layout">
    <!-- 一覧 -->
    <div class="panel" style="flex:1.4;max-height:80vh;overflow:auto;">
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

    <!-- 編集フォーム -->
    <div class="panel" style="flex:1;">
      <h2><?= $editNews ? 'ニュース編集' : '新規追加' ?></h2>
      <form method="post" action="news.php">
        <input type="hidden" name="mode" value="<?= $editNews ? 'update' : 'create' ?>">

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

        <label>サムネイル画像パス（例：../images/news/audition-1.jpg）</label>
        <input type="text" name="thumb" value="<?= esc($editNews['thumb'] ?? '') ?>">

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

        <div style="margin-top:12px; display:flex; gap:8px;">
          <button type="submit" class="btn btn-primary">保存する</button>
          <a href="news.php" class="btn btn-secondary">新規作成に戻る</a>
        </div>
      </form>

      <p style="font-size:11px;color:#6b7280;margin-top:12px;">
        ※「本文」は1行＝1段落として、サイト側では自動で&lt;p&gt;に分割して表示されます。<br>
        ※ 「並び順」は小さい数字が上に来ます（0,1,2...）。同じなら日付の新しいものが上。
      </p>
    </div>
  </div>
</body>
</html>
