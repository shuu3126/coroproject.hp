<?php
// html/admin/index.php
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>管理トップ | CORO PROJECT</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:14px;line-height:1.5;margin:16px;background:#111827;color:#e5e7eb;}
    a{color:#60a5fa;text-decoration:none;}
    a:hover{text-decoration:underline;}
    h1{font-size:22px;margin-bottom:8px;}
    .nav{margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid #1f2937;}
    .nav a{margin-right:12px;font-size:13px;}
    .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:12px;}
    .card{background:#020617;border-radius:12px;padding:16px;border:1px solid #1f2937;box-shadow:0 8px 20px rgba(0,0,0,.5);}
    .card h2{margin:0 0 6px;font-size:16px;}
    .card p{margin:0 0 8px;font-size:13px;color:#9ca3af;}
    .btn{display:inline-block;padding:6px 10px;border-radius:6px;background:#6366f1;color:#fff;font-size:13px;}
  </style>
</head>
<body>
  <h1>CORO PROJECT 管理画面</h1>

  <div class="nav">
    <a href="index.php">🏠 トップ</a>
    <a href="news.php">📰 News管理</a>
    <a href="talents.php">👤 Talents管理</a>
    <a href="https://coroproject.jp/index.php" target="_blank">🌐 サイトTOPを開く</a>
  </div>

  <div class="cards">
    <div class="card">
      <h2>News管理</h2>
      <p>お知らせ / リリース / イベント情報の新規追加・編集・削除。</p>
      <a href="news.php" class="btn">News管理へ</a>
    </div>
    <div class="card">
      <h2>Talents管理</h2>
      <p>所属タレントのプロフィール・リンク・ステータスを編集。</p>
      <a href="talents.php" class="btn">Talents管理へ</a>
    </div>
  </div>
</body>
</html>
