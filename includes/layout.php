<?php
require_once __DIR__ . '/site-data.php';
require_once __DIR__ . '/public-settings.php';

function render_head(string $pageTitle, string $description, array $options = []): void {
    global $siteName;
    $title = $options['title'] ?? ($pageTitle . ' | ' . $siteName);
    $canonical = $options['canonical'] ?? null;
    $robots = $options['robots'] ?? null;
    $ogType = $options['og_type'] ?? null;
    $ogTitle = $options['og_title'] ?? $title;
    $ogDescription = $options['og_description'] ?? $description;
    $ogUrl = $options['og_url'] ?? $canonical;
    $ogImage = $options['og_image'] ?? null;
    $jsonLd = $options['json_ld'] ?? null;
    $shellClass = $options['shell_class'] ?? 'page-shell page-shell-sub';
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($title) ?></title>
  <meta name="description" content="<?= h($description) ?>">
  <?php if ($canonical): ?>
  <link rel="canonical" href="<?= h($canonical) ?>">
  <?php endif; ?>
  <?php if ($robots): ?>
  <meta name="robots" content="<?= h($robots) ?>">
  <?php endif; ?>
  <?php if ($ogType): ?>
  <meta property="og:site_name" content="<?= h($siteName) ?>">
  <meta property="og:type" content="<?= h($ogType) ?>">
  <meta property="og:title" content="<?= h($ogTitle) ?>">
  <meta property="og:description" content="<?= h($ogDescription) ?>">
  <?php if ($ogUrl): ?>
  <meta property="og:url" content="<?= h($ogUrl) ?>">
  <?php endif; ?>
  <?php if ($ogImage): ?>
  <meta property="og:image" content="<?= h($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($ogTitle) ?>">
  <meta name="twitter:description" content="<?= h($ogDescription) ?>">
  <meta name="twitter:image" content="<?= h($ogImage) ?>">
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($jsonLd): ?>
  <script type="application/ld+json">
<?php echo json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n"; ?>
  </script>
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" sizes="32x32" href="images/logo.png">
  <link rel="icon" type="image/png" sizes="192x192" href="images/logo.png">
  <link rel="apple-touch-icon" href="images/logo.png">
  <link rel="shortcut icon" href="images/logo.png">
  <link rel="stylesheet" href="assets/css/portal.css?v=20260429-content-depth">
</head>
<body>
  <div class="<?= h($shellClass) ?>">
    <div class="scanlines"></div>
    <div class="blob blob-a"></div>
    <div class="blob blob-b"></div>
<?php }

function render_header(string $current = 'about', bool $isScrolled = true): void {
    $nav = [
        'about' => 'ABOUT',
        'service' => 'SERVICE',
        'news' => 'NEWS',
    ];
    $headerClass = trim('site-header ' . ($isScrolled ? 'is-scrolled' : ''));
    ?>
    <header class="<?= h($headerClass) ?>" id="top">
      <div class="container header-inner">
        <a href="index.php" class="brand" aria-label="CORO PROJECT トップへ戻る">
          <img src="images/toukalogo.png" alt="CORO PROJECT" class="brand-logo">
          <span class="brand-text">CORO PROJECT</span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
          <span class="status-pill" data-admin-gate data-admin-url="admin/login.php"><span class="status-dot"></span>SYS/ONLINE</span>
          <?php foreach ($nav as $slug => $label): ?>
            <a href="<?= h($slug) ?>.php" class="<?= $current === $slug ? 'is-active' : '' ?>"><?= h($label) ?></a>
          <?php endforeach; ?>
          <a href="contact.php" class="nav-cta <?= $current === 'contact' ? 'is-active' : '' ?>">CONTACT</a>
        </nav>

        <button class="menu-toggle" type="button" aria-label="メニューを開く" data-menu-toggle>
          <span></span><span></span><span></span>
        </button>
      </div>
      <div class="mobile-nav" data-mobile-nav>
        <?php foreach ($nav as $slug => $label): ?>
          <a href="<?= h($slug) ?>.php" class="<?= $current === $slug ? 'is-active' : '' ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
        <a href="contact.php" class="<?= $current === 'contact' ? 'is-active' : '' ?>">CONTACT</a>
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
          <p>Productionを軸に、Business MatchingとCreative Supportを展開する総合支援ブランドです。</p>
          <div class="footer-socials">
            <?php coro_public_render_social_icons(); ?>
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
  <script src="assets/js/social-links.js?v=20260429-1" data-social-endpoint="production/html/api/site-links.php"></script>
  <script src="assets/js/portal.js?v=20260428-admin-gate-status"></script>
</body>
</html>
<?php }
