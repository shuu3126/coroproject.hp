<?php
require_once __DIR__ . '/db.php';

/**
 * HTMLエスケープ
 */
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * 万が一 talents.php?id=xxx で開かれたら、
 * 正しい詳細ページ talent.php?id=xxx へリダイレクトさせる
 */
if (isset($_GET['id']) && $_GET['id'] !== '') {
    header('Location: talent.php?id=' . urlencode($_GET['id']));
    exit;
}

$talents = [];

try {
    // 一覧用：全部取得（公開中だけに絞りたいときは WHERE を足す）
    $sql = "
        SELECT *
        FROM talents
        ORDER BY sort_order ASC, debut ASC, name ASC
    ";
    $stmt = $pdo->query($sql);
    $talents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 画像パス補正（../ や ./ を外す）
    foreach ($talents as &$t) {
        $avatar = $t['avatar'] ?? '';
        if (strpos($avatar, '../') === 0) {
            $avatar = substr($avatar, 3);
        } elseif (strpos($avatar, './') === 0) {
            $avatar = substr($avatar, 2);
        }
        $t['avatar_for_list'] = $avatar;
    }
    unset($t);

} catch (PDOException $e) {
    $talents = [];
    // error_log($e->getMessage());
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CORO PROJECT | Talents</title>
  <meta name="description" content="CORO PROJECT に所属するタレントの一覧とプロフィール。配信リンク、活動状況など。">
  <link rel="canonical" href="https://coroproject.jp/html/talents.php">
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Talents | CORO PROJECT">
  <meta property="og:description" content="所属タレント一覧と詳細プロフィール。">
  <meta property="og:url" content="https://coroproject.jp/html/talents.php">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">
  <meta name="twitter:card" content="summary_large_image">

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
          <a href="./talents.php" aria-current="page">Talents</a>
          <a href="./audition.html">Audition</a>
          <a href="./contact.html">Contact</a>
        </nav>
      </div>
    </header>

    <main id="top">
      <!-- Sub Hero -->
      <section class="sub-hero">
        <div class="container sub-hero-inner">
          <div class="sub-hero-copy">
            <p class="eyebrow">Talents</p>
            <h1>所属タレント一覧</h1>
            <p class="lead">プロフィール・配信リンク・活動状況をまとめて掲載しています。</p>
          </div>
          <div class="sub-hero-art" aria-hidden="true">
            <div class="audition-visual"></div>
          </div>
        </div>
      </section>

      <!-- タレント一覧 -->
      <section class="section" id="list">
        <div class="container">
          <div class="section-head">
            <h2 class="section-title">Talents</h2>
          </div>

          <div class="grid grid-3" aria-live="polite">
            <?php if (empty($talents)): ?>
              <p style="color:#b7b7c8; font-size:.9rem;">
                現在、公開中の所属タレント情報はありません。
              </p>
            <?php else: ?>
              <?php foreach ($talents as $t): ?>
                <a class="card" href="talent.php?id=<?= esc($t['id']) ?>" style="text-decoration:none;">
                  <div class="card-thumb"
                      style="
                        width:100%;
                        height:260px;
                        background-image:url('<?= esc($t['avatar_for_list']) ?>');
                        background-size:cover;
                        background-position:center;
                        border-radius:16px;
                      ">
                  </div>
                  <div class="card-body" style="padding:12px 0;">
                    <h3 class="card-title" style="margin:6px 0 4px;">
                      <?= esc($t['name']) ?>
                    </h3>
                    <p class="card-text" style="color:#b7b7c8; font-size:.9rem;">
                      <?= esc($t['bio']) ?>
                    </p>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="section cta">
        <div class="container cta-inner">
          <div class="cta-copy">
            <h2>Audition</h2>
            <p>"自分だけでは届かなかった場所へ"</p>
          </div>
          <div class="cta-actions">
            <a class="btn btn-primary btn-lg" href="./audition.html">応募する</a>
            <a class="btn btn-ghost btn-lg" href="./audition.html#requirements">要項を読む</a>
          </div>
        </div>
      </section>
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
            <li><a href="./talents.php" aria-current="page">Talents</a></li>
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
      window.addEventListener('keydown', (e)=>{
        if(e.key === 'Escape' && document.body.classList.contains('nav-open')){
          btn.setAttribute('aria-expanded','false');
          document.body.classList.remove('nav-open');
        }
      });
    })();
  </script>
</body>
</html>
