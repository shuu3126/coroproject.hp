<?php
require_once __DIR__ . '/db.php';

function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$id = $_GET['id'] ?? '';
$id = trim($id);

if ($id === '') {
    // id がないときは一覧へ
    header('Location: talents.php');
    exit;
}

$talent = null;

try {
    $sql = "SELECT * FROM talents WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $talent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($talent) {
        // 画像パス補正
        $avatar = $talent['avatar'] ?? '';
        if (strpos($avatar, '../') === 0) {
            $avatar = substr($avatar, 3);
        } elseif (strpos($avatar, './') === 0) {
            $avatar = substr($avatar, 2);
        }
        $talent['avatar_for_detail'] = $avatar;
    }

} catch (PDOException $e) {
    $talent = null;
    // error_log($e->getMessage());
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>
    <?php if ($talent): ?>
      <?= esc($talent['name']) ?> | Talents | CORO PROJECT
    <?php else: ?>
      Talents | CORO PROJECT
    <?php endif; ?>
  </title>
  <meta name="description" content="<?= $talent ? esc($talent['bio']) : 'CORO PROJECT所属タレントのプロフィールページ。' ?>">
  <link rel="canonical" href="https://coroproject.jp/html/talent.php?id=<?= esc($id) ?>">
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= $talent ? esc($talent['name']) . ' | Talents' : 'Talents | CORO PROJECT' ?>">
  <meta property="og:description" content="<?= $talent ? esc($talent['bio']) : 'CORO PROJECT所属タレントのプロフィールページ。' ?>">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">

  <link rel="stylesheet" href="../css/styles.css">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="apple-touch-icon" href="../images/logo.png">
</head>
<body>
  <div id="app" class="app">
    <!-- Header -->
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
      <?php if (!$talent): ?>
        <section class="section section-alt">
          <div class="container">
            <h1 class="section-title">Talents</h1>
            <p style="color:#b7b7c8;">指定されたIDのタレント情報が見つかりませんでした。</p>
            <p><a class="btn btn-primary" href="talents.php">一覧に戻る</a></p>
          </div>
        </section>
      <?php else: ?>
        <!-- Hero-ish detail -->
        <section class="section section-talents">
          <div class="container">
            <div class="talent-detail">
              <div class="talent-detail-media">
                <div class="talent-detail-media-inner">
                  <img src="<?= esc($talent['avatar_for_detail']) ?>"
                       alt="<?= esc($talent['name']) ?>のキービジュアル">
                </div>
              </div>
              <div class="talent-detail-info">
                <p class="talent-label">Coro Project Talent</p>
                <h1 class="talent-name-main"><?= esc($talent['name']) ?></h1>
                <?php if (!empty($talent['kana'])): ?>
                  <p class="talent-kana"><?= esc($talent['kana']) ?></p>
                <?php endif; ?>

                <p class="talent-desc">
                  <?= nl2br(esc($talent['bio'])) ?>
                </p>

                <?php if (!empty($talent['debut'])): ?>
                  <p class="talent-meta-line">
                    デビュー日：<?= esc($talent['debut']) ?>
                  </p>
                <?php endif; ?>

                <!-- ここにあとでSNSやタグを足していける -->
                <p style="margin-top:16px;">
                  <a class="btn btn-outline" href="talents.php">一覧に戻る</a>
                </p>
              </div>
            </div>
          </div>
        </section>
      <?php endif; ?>
    </main>

    <!-- Footer -->
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
            <li><a href="https://x.com/CoroProject0111" target="_blank" rel="noopener">X</a></li>
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
