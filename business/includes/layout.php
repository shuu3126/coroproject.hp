<?php
require_once __DIR__ . '/data.php';

function h(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render_header(string $currentPage, string $siteTitle, array $seo = []): void {
  global $navItems, $siteRoot, $contactUrl, $bmSite;
  $pageName = $seo['page_name'] ?? $siteTitle;
  $description = $seo['description'] ?? 'ころコネクト！はCORO PROJECTの企業向けVTuber案件総合窓口。VTuber起用相談・候補提案・条件整理・進行支援まで一体で対応します。';
  $canonical = $seo['canonical'] ?? 'https://coroproject.jp/business/';
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
          "name": "ころコネクト！",
          "url": "https://coroproject.jp/business/",
          "parentOrganization": {
            "@type": "Organization",
            "name": "CORO PROJECT",
            "url": "https://coroproject.jp/"
          },
          "logo": "https://coroproject.jp/images/logo.png"
        },
        {
          "@type": "Service",
          "name": "ころコネクト！",
          "serviceType": "VTuberビジネス窓口・企業向けキャスティング相談",
          "provider": {
            "@type": "Organization",
            "name": "ころコネクト！"
          },
          "areaServed": "JP",
          "url": "https://coroproject.jp/business/",
          "description": "企業・店舗・地方自治体・広告代理店向けに、VTuber起用相談から候補提案・条件整理・進行支援までを一体で担う総合窓口。"
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
  <link rel="apple-touch-icon" href="../images/logo.png">
  <link rel="shortcut icon" href="../images/logo.png">
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
      <button class="biz-menu-btn" id="biz-menu-btn" type="button" aria-label="メニューを開く">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <div class="biz-mob-nav" id="biz-mob-nav">
    <button class="biz-mob-close" id="biz-mob-close" type="button" aria-label="閉じる">✕</button>
    <?php foreach ($navItems as $item): ?>
      <a href="<?= h($item['href']) ?>"><?= h($item['label']) ?></a>
    <?php endforeach; ?>
    <a href="<?= h($contactUrl) ?>">CONTACT</a>
  </div>
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
        <p>企業とVTuberをつなぐ、CORO PROJECTの総合窓口。VTuber起用・PR施策・タイアップ・イベント相談をワンストップで整えます。</p>
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
<script>
(function () {
  var btn = document.getElementById('biz-menu-btn');
  var nav = document.getElementById('biz-mob-nav');
  var close = document.getElementById('biz-mob-close');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () { nav.classList.toggle('is-open'); });
  if (close) close.addEventListener('click', function () { nav.classList.remove('is-open'); });
  nav.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { nav.classList.remove('is-open'); });
  });
})();
</script>
</body>
</html>
<?php
}
