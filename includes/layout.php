<?php
require_once __DIR__ . '/site-data.php';

function render_head(string $pageTitle, string $description): void {
    global $siteName;
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle) ?> | <?= h($siteName) ?></title>
  <meta name="description" content="<?= h($description) ?>">
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
  <div class="page-shell page-shell-sub">
    <div class="scanlines"></div>
    <div class="blob blob-a"></div>
    <div class="blob blob-b"></div>
<?php }

function render_header(string $current = 'about'): void {
    $nav = [
        'about' => 'ABOUT',
        'service' => 'SERVICE',
        'news' => 'NEWS',
    ];
    ?>
    <header class="site-header is-scrolled" id="top">
      <div class="container header-inner">
        <a href="index.php" class="brand" aria-label="CORO PROJECT トップへ戻る">
          <img src="images/toukalogo.png" alt="CORO PROJECT" class="brand-logo">
          <span class="brand-text">CORO PROJECT</span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
          <span class="status-pill"><span class="status-dot"></span>SYS/ONLINE</span>
          <?php foreach ($nav as $slug => $label): ?>
            <a href="<?= h($slug) ?>.php" class="<?= $current === $slug ? 'is-active' : '' ?>"><?= h($label) ?></a>
          <?php endforeach; ?>
          <a href="contact.php" class="nav-cta">CONTACT</a>
        </nav>

        <button class="menu-toggle" type="button" aria-label="メニューを開く" data-menu-toggle>
          <span></span><span></span><span></span>
        </button>
      </div>
      <div class="mobile-nav" data-mobile-nav>
        <?php foreach ($nav as $slug => $label): ?>
          <a href="<?= h($slug) ?>.php" class="<?= $current === $slug ? 'is-active' : '' ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
      </div>
    </header>
<?php }

function render_footer(): void {
    global $divisions;
    ?>
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
<?php }
