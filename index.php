<?php
require_once __DIR__ . '/includes/site-data.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($siteName) ?> | VTuber・案件仲介・クリエイティブ支援の総合ポータル</title>
  <meta name="description" content="CORO PROJECTの総合ポータル。VTuber事務所、企業向け案件仲介、クリエイティブ支援の3事業をまとめて案内し、活動・施策・制作の相談窓口へつなぎます。">
  <link rel="canonical" href="https://coroproject.jp/">
  <meta name="robots" content="index, follow">
  <meta property="og:site_name" content="<?= h($siteName) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($siteName) ?> | VTuber・案件仲介・クリエイティブ支援の総合ポータル">
  <meta property="og:description" content="VTuber事務所、企業向け案件仲介、クリエイティブ支援の3事業をまとめて案内するCORO PROJECTの総合ポータル。">
  <meta property="og:url" content="https://coroproject.jp/">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($siteName) ?> | VTuber・案件仲介・クリエイティブ支援の総合ポータル">
  <meta name="twitter:description" content="VTuber事務所、企業向け案件仲介、クリエイティブ支援の3事業をまとめて案内するCORO PROJECTの総合ポータル。">
  <meta name="twitter:image" content="https://coroproject.jp/images/ogp.png">
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "name": "CORO PROJECT",
          "url": "https://coroproject.jp/",
          "logo": "https://coroproject.jp/images/logo.png"
        },
        {
          "@type": "WebSite",
          "name": "CORO PROJECT",
          "url": "https://coroproject.jp/"
        },
        {
          "@type": "WebPage",
          "name": "CORO PROJECT | VTuber・案件仲介・クリエイティブ支援の総合ポータル",
          "url": "https://coroproject.jp/",
          "description": "VTuber事務所、企業向け案件仲介、クリエイティブ支援の3事業をまとめて案内するCORO PROJECTの総合ポータル。",
          "isPartOf": {
            "@type": "WebSite",
            "name": "CORO PROJECT",
            "url": "https://coroproject.jp/"
          }
        },
        {
          "@type": "ItemList",
          "name": "CORO PROJECTの事業一覧",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Business Matching",
              "url": "https://coroproject.jp/business-matching/"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Creative Support",
              "url": "https://coroproject.jp/creative-support/"
            },
            {
              "@type": "ListItem",
              "position": 3,
              "name": "Production",
              "url": "https://coroproject.jp/production/"
            }
          ]
        }
      ]
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" sizes="32x32" href="images/logo.png">
  <link rel="icon" type="image/png" sizes="192x192" href="images/logo.png">
  <link rel="apple-touch-icon" href="images/logo.png">
  <link rel="shortcut icon" href="images/logo.png">
  <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
  <div class="page-shell">
    <div class="scanlines"></div>
    <div class="blob blob-a"></div>
    <div class="blob blob-b"></div>

    <header class="site-header" id="top">
      <div class="container header-inner">
        <a href="index.php" class="brand" aria-label="CORO PROJECT トップへ戻る">
          <img src="images/toukalogo.png" alt="CORO PROJECT" class="brand-logo">
          <span class="brand-text">CORO PROJECT</span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
          <span class="status-pill"><span class="status-dot"></span>SYS/ONLINE</span>
          <a href="about.php">ABOUT</a>
          <a href="service.php">SERVICE</a>
          <a href="news.php">NEWS</a>
          <a href="contact.php" class="nav-cta">CONTACT</a>
        </nav>

        <button class="menu-toggle" type="button" aria-label="メニューを開く" data-menu-toggle>
          <span></span><span></span><span></span>
        </button>
      </div>
      <div class="mobile-nav" data-mobile-nav>
        <a href="about.php">ABOUT</a>
        <a href="service.php">SERVICE</a>
        <a href="news.php">NEWS</a>
        <a href="contact.php">CONTACT</a>
      </div>
    </header>

    <main>
      <section class="hero-section">
        <div class="hero-marquee hero-marquee-top">
          <div class="marquee-track">CORO PROJECT // VTUBER PRODUCTION // NEXT GENERATION // CORO PROJECT // VTUBER PRODUCTION //</div>
        </div>
        <div class="hero-marquee hero-marquee-bottom">
          <div class="marquee-track reverse">CREATIVE SUPPORT // BUSINESS MATCHING // CREATIVE SUPPORT // BUSINESS MATCHING //</div>
        </div>

        <div class="hero-hud hero-hud-left">SYS.VER.1.0.4<br>STATUS: ONLINE</div>
        <div class="hero-hud hero-hud-right">TARGET: ACQUIRED<br>COORD: 35.6895° N, 139.6917° E</div>

        <div class="container hero-content reveal">
          <div class="hero-label cyber-clip">VTuber Total Solutions</div>
          <h1 class="hero-title">
            VTuberと企業・クリエイターを<br>
            <span>つなぎ、可能性を拡張する。</span>
          </h1>
          <p class="hero-lead">
            CORO PROJECTは、VTuber事務所運営を軸に、<br class="hide-mobile">
            企業案件仲介とクリエイティブサポートを展開する総合支援ブランドです。
          </p>
        </div>

        <div class="container cards-grid reveal" id="services">
          <?php foreach ($divisions as $division): ?>
            <a class="division-card cyber-clip-lg <?= h($division['class']) ?>" href="<?= h($basePath . '/' . $division['slug'] . '/') ?>">
              <div class="corner corner-tl"></div>
              <div class="card-top">
                <div class="card-icon"></div>
                <span class="card-num"><?= h($division['num']) ?></span>
              </div>
              <span class="card-en"><?= h($division['title']) ?></span>
              <h2 class="card-jp"><?= h($division['title_jp']) ?></h2>
              <p class="card-desc"><?= h($division['desc']) ?></p>
              <span class="card-link">ENTER SYNC <span aria-hidden="true">›</span></span>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="hero-scroll reveal">
          <div class="hero-scroll-line"></div>
          <span>SCROLL DOWN</span>
        </div>
      </section>

      <section class="about-section" id="about">
        <div class="container about-grid">
          <div class="about-copy reveal">
            <div class="section-marker">
              <span class="marker-bar"></span>
              <span class="marker-text">MISSION OBJECTIVE</span>
            </div>
            <h2>
              VTuberという新時代の才能を、<br>
              一過性のブームではなく、<br>
              多角的な価値へと<span>変革</span>していく。
            </h2>
            <div class="about-text">
              <p>
                CORO PROJECTは、単なるタレント事務所に留まりません。企業とタレントがWin-Winになる案件の創出、
                クリエイターのスキルを最適な形でマッチングする仕組み、そしてそれらを支える確かなマネジメントを構築します。
              </p>
              <p>
                「三方よし」の精神で、VTuber業界のさらなる成熟と、そこに集うすべての人々の可能性を最大化することを使命としています。
              </p>
            </div>
            <a href="about.php" class="outline-button cyber-clip">ABOUT CORO PROJECT <span aria-hidden="true">›</span></a>
          </div>

          <div class="about-visual reveal">
            <div class="visual-frame cyber-clip-lg">
              <div class="visual-gradient"></div>
              <div class="visual-placeholder"></div>
              <div class="frame-corner frame-corner-tl"></div>
              <div class="frame-corner frame-corner-br"></div>
              <div class="visual-caption">
                <span>AUTHORIZATION: CORE</span>
                <strong>Coro</strong>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content-section service-overview">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">SERVICE OVERVIEW</span>
              <h2 class="section-title">事業の紹介</h2>
            </div>
          </div>
          <a href="service.php" class="ghost-link cyber-clip">VIEW SERVICE PAGE <span aria-hidden="true">→</span></a>
        </div>
        <div class="container info-grid reveal">
          <?php foreach ($divisions as $division): ?>
            <article class="info-card cyber-clip-lg <?= h($division['class']) ?>">
              <div class="corner corner-tl"></div>
              <span class="info-num"><?= h($division['num']) ?></span>
              <span class="info-eyebrow"><?= h($division['title']) ?></span>
              <h3><?= h($division['title_jp']) ?></h3>
              <p><?= h($division['summary']) ?></p>
              <a href="<?= h($division['slug']) ?>/" class="card-link">OPEN DIVISION <span aria-hidden="true">›</span></a>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="content-section content-section-alt" id="news-preview">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">SIGNAL ARCHIVE</span>
              <h2 class="section-title">NEWS</h2>
            </div>
          </div>
          <a href="news.php" class="ghost-link cyber-clip">OPEN NEWS PAGE <span aria-hidden="true">→</span></a>
        </div>
        <div class="container news-list news-list-preview reveal">
          <?php foreach (array_slice($newsItems, 0, 3) as $item): ?>
            <article class="news-card cyber-clip-lg">
              <div class="news-meta">
                <span class="news-category"><?= h($item['category']) ?></span>
                <span class="news-date"><?= h($item['date']) ?></span>
              </div>
              <h2><?= h($item['title']) ?></h2>
              <p><?= h($item['excerpt']) ?></p>
              <a href="news_detail.php?id=<?= urlencode($item['id']) ?>" class="card-link">READ DETAIL <span aria-hidden="true">›</span></a>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="cta-section" id="contact">
        <div class="cta-beam"></div>
        <div class="container cta-inner reveal">
          <div class="mini-tag">SYSTEM_PROMPT: NEXT_STEP</div>
          <h2>新しい活動の扉を、<br>共に開きましょう。</h2>
          <div class="cta-actions">
            <a href="contact.php" class="primary-button cyber-clip">CONTACT US</a>
            <a href="service.php" class="secondary-button cyber-clip">SERVICE / SYSTEM DETAIL</a>
          </div>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-grid">
        <div class="footer-brand">
          <a href="index.php" class="brand" aria-label="CORO PROJECT トップへ戻る">
            <img src="images/toukalogo.png" alt="CORO PROJECT" class="brand-logo">
            <span class="brand-text">CORO PROJECT</span>
          </a>
          <p>VTuber事務所運営を軸に、企業案件仲介とクリエイティブサポートを展開する総合支援ブランドです。</p>
          <div class="footer-socials">
            <a href="#" aria-label="X">𝕏</a>
            <a href="#" aria-label="YouTube">▶</a>
          </div>
        </div>

        <div class="footer-links">
          <h3>SERVICES</h3>
          <?php foreach ($divisions as $division): ?>
            <a href="<?= h($division['slug']) ?>/"><?= h($division['title']) ?></a>
          <?php endforeach; ?>
        </div>

        <div class="footer-links">
          <h3>INFO</h3>
          <a href="about.php">About</a>
          <a href="service.php">Service</a>
          <a href="news.php">News</a>
          <a href="contact.php">Contact</a>
        </div>
      </div>

      <div class="footer-meta">
        <div>SERVER_01</div>
        <div>LATENCY: 12ms</div>
        <div>SECURE_CONNECTION</div>
      </div>
      <p class="footer-copy">© CORO PROJECT 2026 // ALL SYSTEM OPERATIONAL</p>
    </footer>
  </div>

  <script src="assets/js/portal.js"></script>
</body>
</html>
