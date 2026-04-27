<?php
require_once __DIR__ . '/data.php';

function h(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render_header(string $currentPage, string $siteTitle, array $seo = []): void {
  global $navItems, $siteRoot, $contactUrl, $bmSite;
  $pageName = $seo['page_name'] ?? $siteTitle;
  $description = $seo['description'] ?? 'CORO PROJECTのBusiness Matching。VTuber起用、PR施策、タイアップ、イベント出演などの案件相談を支える総合窓口です。';
  $canonical = $seo['canonical'] ?? 'https://coroproject.jp/business-matching/';
  $ogType = $seo['og_type'] ?? 'website';
  ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($siteTitle) ?></title>
  <meta name="description" content="<?= h($description) ?>">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <meta name="robots" content="index, follow">
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="<?= h($ogType) ?>">
  <meta property="og:title" content="<?= h($siteTitle) ?>">
  <meta property="og:description" content="<?= h($description) ?>">
  <meta property="og:url" content="<?= h($canonical) ?>">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($siteTitle) ?>">
  <meta name="twitter:description" content="<?= h($description) ?>">
  <meta name="twitter:image" content="https://coroproject.jp/images/ogp.png">
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "name": "CORO PROJECT Business Matching",
          "url": "https://coroproject.jp/business-matching/",
          "parentOrganization": {
            "@type": "Organization",
            "name": "CORO PROJECT",
            "url": "https://coroproject.jp/"
          },
          "logo": "https://coroproject.jp/images/logo.png"
        },
        {
          "@type": "Service",
          "name": "CORO PROJECT Business Matching",
          "serviceType": "VTuber案件仲介・企業コラボ相談",
          "provider": {
            "@type": "Organization",
            "name": "CORO PROJECT Business Matching"
          },
          "areaServed": "JP",
          "url": "https://coroproject.jp/business-matching/",
          "description": "VTuber起用、PR施策、タイアップ、イベント出演などの案件相談を支える企業向け案件仲介サービス。"
        },
        {
          "@type": "WebPage",
          "name": "<?= h($pageName) ?>",
          "url": "<?= h($canonical) ?>",
          "description": "<?= h($description) ?>"
        }
      ]
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,700;0,800;0,900;1,700;1,800;1,900&family=Noto+Sans+JP:wght@400;500;700;900&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<div class="page-shell">
  <div class="page-grid"></div>
  <header class="site-header" id="site-header">
    <div class="header-shell">
      <a class="site-logo" href="<?= h($siteRoot) ?>">
        <img src="assets/icons/brand-mark.png" alt="CORO PROJECT">
        <span><?= h($bmSite['brand']) ?></span>
      </a>
      <div class="header-right">
        <nav class="site-nav">
          <?php foreach ($navItems as $item): ?>
            <a class="nav-link <?= $item['key'] === $currentPage ? 'is-current' : '' ?>" href="<?= h($item['href']) ?>"><?= h($item['label']) ?></a>
          <?php endforeach; ?>
        </nav>
        <a class="nav-cta" href="<?= h($contactUrl) ?>">CONTACT</a>
      </div>
    </div>
  </header>
  <main>
  <?php
}

function render_footer(): void {
  global $navItems, $contactUrl, $siteRoot, $bmSite;
  ?>
  </main>
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-brand">
        <a class="site-logo footer-logo" href="<?= h($siteRoot) ?>">
          <img src="assets/icons/brand-mark.png" alt="CORO PROJECT">
          <span><?= h($bmSite['brand']) ?></span>
        </a>
        <p>VTuber起用、PR施策、タイアップ、イベント出演などの案件相談を支えるCORO PROJECTの案件仲介窓口です。</p>
      </div>
      <div class="footer-links">
        <?php foreach ($navItems as $item): ?>
          <a href="<?= h($item['href']) ?>"><?= h($item['label']) ?></a>
        <?php endforeach; ?>
        <a href="<?= h($contactUrl) ?>">CONTACT</a>
      </div>
    </div>
  </footer>
</div>
<script src="assets/js/site.js"></script>
</body>
</html>
<?php
}
