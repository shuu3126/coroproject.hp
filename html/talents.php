<?php
require_once __DIR__ . '/../db.php';

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// 全タレント取得（とりあえず status 絞らず）
$sql = "
    SELECT *
    FROM talents
    ORDER BY sort_order ASC, debut ASC, name ASC
";
$stmt = $pdo->query($sql);
$talents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON を配列に
foreach ($talents as &$t) {
    $t['tags']      = $t['tags_json']       ? json_decode($t['tags_json'], true)       : [];
    $t['platforms'] = $t['platforms_json']  ? json_decode($t['platforms_json'], true)  : [];
}
unset($t);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CORO PROJECT | Talents</title>
  <meta name="description" content="CORO PROJECT に所属するタレントの一覧とプロフィール。">

  <link rel="canonical" href="https://coroproject.jp/html/talents.php">
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Talents | CORO PROJECT">
  <meta property="og:description" content="所属タレント一覧と詳細プロフィール。">
  <meta property="og:url" content="https://coroproject.jp/html/talents.php">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <div id="app" class="app">
    <header class="site-header">
      <div class="container header-inner">
        <a class="brand" href="../index.php">
          <img src="../images/toukalogo.png" alt="CORO PROJECT ロゴ" class="brand-logo">
          <span class="brand-text">CORO PROJECT</span>
        </a>
        <nav class="nav" id="siteNav">
          <a href="../index.php#about">About</a>
          <a href="./news.php">News</a>
          <a href="./talents.php" aria-current="page">Talents</a>
          <a href="./audition.html">Audition</a>
          <a href="./contact.html">Contact</a>
        </nav>
      </div>
    </header>

    <main id="top">
      <section class="sub-hero">
        <div class="container sub-hero-inner">
          <div class="sub-hero-copy">
            <p class="eyebrow">Talents</p>
            <h1>所属タレント一覧</h1>
            <p class="lead">カードをクリックすると詳細プロフィールページへ移動します。</p>
          </div>
          <div class="sub-hero-art" aria-hidden="true">
            <div class="audition-visual"></div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <div class="section-head">
            <h2 class="section-title">Talents</h2>
          </div>

          <div class="grid grid-3">
            <?php foreach ($talents as $t): ?>
              <article class="card">
                <a href="talent.php?id=<?= esc($t['id']) ?>" class="card-body">
                  <div class="card-thumb"
                       style="background-image:url('<?= esc($t['avatar']) ?>');"></div>
                  <div class="card-meta">
                    <span class="tag"><?= esc($t['group'] ?: 'Member') ?></span>
                  </div>
                  <h3 class="card-title"><?= esc($t['name']) ?></h3>
                  <p class="card-text"><?= esc($t['bio']) ?></p>
                </a>
              </article>
            <?php endforeach; ?>
          </div>

        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-inner">
        <!-- 省略：既存のフッターをそのままコピペでOK -->
      </div>
    </footer>
  </div>
</body>
</html>
