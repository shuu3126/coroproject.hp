<?php
require_once __DIR__ . '/../db.php';

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

$id = $_GET['id'] ?? '';
if ($id === '') {
    http_response_code(404);
    echo 'ID not found';
    exit;
}

$sql = "SELECT * FROM talents WHERE id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$talent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$talent) {
    http_response_code(404);
    echo 'Talent not found';
    exit;
}

// JSON 系のカラムがあればデコード（存在しなければ空配列に）
$tags = [];
if (!empty($talent['tags_json'])) {
    $tags = json_decode($talent['tags_json'], true) ?: [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= esc($talent['name']) ?> | Talents | CORO PROJECT</title>
  <meta name="description" content="<?= esc($talent['bio'] ?? 'CORO PROJECT所属VTuber。') ?>">
  <link rel="canonical" href="https://coroproject.jp/html/talent.php?id=<?= esc($talent['id']) ?>">
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= esc($talent['name']) ?> | CORO PROJECT">
  <meta property="og:description" content="<?= esc($talent['bio'] ?? '') ?>">
  <meta property="og:url" content="https://coroproject.jp/html/talent.php?id=<?= esc($talent['id']) ?>">
  <meta property="og:image" content="https://coroproject.jp/<?= esc($talent['avatar'] ?? 'images/ogp.png') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="apple-touch-icon" href="../images/logo.png">
</head>
<body>
  <div id="app" class="app visible">
    <header class="site-header">
      <div class="container header-inner">
        <a class="brand" href="../index.php">
          <img src="../images/toukalogo.png" alt="CORO PROJECT ロゴ" class="brand-logo">
          <span class="brand-text">CORO PROJECT</span>
        </a>
        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="メニューを開く">
          <span></span><span></span><span></span>
        </button>
        <nav class="nav" id="siteNav" aria-label="メインナビゲーション">
          <a href="../index.php#about">About</a>
          <a href="./news.php">News</a>
          <a href="./talents.php">Talents</a>
          <a href="./audition.html">Audition</a>
          <a href="./contact.html">Contact</a>
        </nav>
      </div>
    </header>

    <main id="top">
      <section class="sub-hero">
        <div class="container sub-hero-inner">
          <div class="sub-hero-copy">
            <p class="eyebrow">Talent</p>
            <h1><?= esc($talent['name']) ?></h1>
            <p class="lead"><?= esc($talent['bio'] ?? '') ?></p>
          </div>
          <div class="sub-hero-art" aria-hidden="true">
            <div class="audition-visual"></div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="container">
          <div class="talent-detail">
            <div class="talent-detail-main">
              <?php
                $avatar = $talent['avatar'] ?? '';
                if (strpos($avatar, '../') === 0) $avatar = substr($avatar, 3);
                elseif (strpos($avatar, './') === 0) $avatar = substr($avatar, 2);
              ?>
              <div class="talent-detail-visual">
                <?php if ($avatar): ?>
                  <img src="../<?= esc($avatar) ?>" alt="<?= esc($talent['name']) ?>" style="max-width:100%; border-radius:16px;">
                <?php endif; ?>
              </div>

              <div class="talent-detail-body">
                <h2>Profile</h2>
                <p><?= nl2br(esc($talent['long_bio'] ?? $talent['bio'] ?? '')) ?></p>

                <?php if (!empty($tags)): ?>
                  <div style="margin-top:12px; display:flex; gap:6px; flex-wrap:wrap;">
                    <?php foreach ($tags as $tag): ?>
                      <span class="tag"><?= esc($tag) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-inner">
        <div class="footer-col">
          <div class="brand brand--footer">
            <img src="../images/logo.png" alt="CORO PROJECT" class="footer-logo">
            <span class="brand-text">CORO PROJECT</span>
          </div>
          <p class="footer-desc">CORO PROJECTはVTuberのプロデュース・配信支援・クリエイティブ制作を行うプロダクションです。</p>
        </div>
        <div class="footer-col">
          <h4>Links</h4>
          <ul class="footer-links">
            <li><a href="./news.php">News</a></li>
            <li><a href="./talents.php">Talents</a></li>
            <li><a href="./audition.html">Audition</a></li>
            <li><a href="./privacy.html">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Social</h4>
          <ul class="footer-links">
            <li><a href="#" target="_blank">YouTube</a></li>
            <li><a href="#" target="_blank">X</a></li>
            <li><a href="mailto:info@coroproject.jp">Mail</a></li>
          </ul>
        </div>
      </div>
      <div class="container footer-copy">
        <small>© <span id="year"></span> CORO PROJECT</small>
      </div>
    </footer>
  </div>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
    (function(){
      const btn = document.getElementById('navToggle');
      const nav = document.getElementById('siteNav');
      if(!btn || !nav) return;
      btn.addEventListener('click', ()=>{
        const opened = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!opened));
        document.body.classList.toggle('nav-open', !opened);
      });
    })();
  </script>
</body>
</html>
