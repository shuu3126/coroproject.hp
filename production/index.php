<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/html/_asset.php';

/**
 * HTML繧ｨ繧ｹ繧ｱ繝ｼ繝・
 */
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$topNews    = [];
$topTalents = [];

try {
    // ===== News・・OP陦ｨ遉ｺ逕ｨ・壽怙譁ｰ3莉ｶ・・=====
    $TOP_NEWS_LIMIT = 3;

    $sql = "
        SELECT *
        FROM news
        WHERE is_published = 1
        ORDER BY sort_order ASC, date DESC, id DESC
        LIMIT :limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $TOP_NEWS_LIMIT, PDO::PARAM_INT);
    $stmt->execute();
    $topNews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== Talents・・OP陦ｨ遉ｺ逕ｨ・壽怙譁ｰ3蜷搾ｼ・=====
    $sql = "
        SELECT *
        FROM talents
        ORDER BY sort_order ASC, debut ASC, name ASC
        LIMIT 3
    ";
    $stmt = $pdo->query($sql);
    $topTalents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TOP逕ｨ avatar 縺ｮ繝代せ陬懈ｭ｣・・./ 繧・./ 繧貞炎繧具ｼ・
    foreach ($topTalents as &$t) {
        $avatar = $t['avatar'] ?? '';
        $t['avatar_for_top'] = public_html_asset_url($avatar);
    }
    unset($t);

} catch (PDOException $e) {
    $topNews    = [];
    $topTalents = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CORO PROJECT Production | VTuber莠句漁謇繝ｻ謇螻槭ち繝ｬ繝ｳ繝域ュ蝣ｱ</title>
  <meta name="description" content="CORO PROJECT Production縺ｯ縲∵園螻槭ち繝ｬ繝ｳ繝医・豢ｻ蜍墓髪謠ｴ繝ｻ繝槭ロ繧ｸ繝｡繝ｳ繝医ｒ陦後≧VTuber莠句漁謇縺ｧ縺吶よ園螻槭Γ繝ｳ繝舌・諠・ｱ繧・怙譁ｰ繝九Η繝ｼ繧ｹ縲√が繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ諠・ｱ繧呈軸霈峨＠縺ｦ縺・∪縺吶・>

  <link rel="canonical" href="https://coroproject.jp/production/">
  <meta name="robots" content="index, follow">

  <!-- OGP -->
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="CORO PROJECT Production | VTuber莠句漁謇繝ｻ謇螻槭ち繝ｬ繝ｳ繝域ュ蝣ｱ">
  <meta property="og:description" content="謇螻槭ち繝ｬ繝ｳ繝医・豢ｻ蜍墓髪謠ｴ繝ｻ繝槭ロ繧ｸ繝｡繝ｳ繝医ｒ陦後≧CORO PROJECT Production縺ｮ蜈ｬ蠑上・繝ｼ繧ｸ縲よ怙譁ｰ繝九Η繝ｼ繧ｹ繧・が繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ諠・ｱ繧よ軸霈峨＠縺ｦ縺・∪縺吶・>
  <meta property="og:url" content="https://coroproject.jp/production/">
  <meta property="og:image" content="https://coroproject.jp/production/images/ogp.png">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="CORO PROJECT Production | VTuber莠句漁謇繝ｻ謇螻槭ち繝ｬ繝ｳ繝域ュ蝣ｱ">
  <meta name="twitter:description" content="謇螻槭ち繝ｬ繝ｳ繝医・豢ｻ蜍墓髪謠ｴ繝ｻ繝槭ロ繧ｸ繝｡繝ｳ繝医ｒ陦後≧CORO PROJECT Production縺ｮ蜈ｬ蠑上・繝ｼ繧ｸ縺ｧ縺吶・>
  <meta name="twitter:image" content="https://coroproject.jp/production/images/ogp.png">
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "name": "CORO PROJECT Production",
          "url": "https://coroproject.jp/production/",
          "parentOrganization": {
            "@type": "Organization",
            "name": "CORO PROJECT",
            "url": "https://coroproject.jp/"
          },
          "logo": "https://coroproject.jp/images/logo.png"
        },
        {
          "@type": "Service",
          "name": "CORO PROJECT Production",
          "serviceType": "VTuber莠句漁謇繝ｻ繧ｿ繝ｬ繝ｳ繝医・繝阪ず繝｡繝ｳ繝・,
          "provider": {
            "@type": "Organization",
            "name": "CORO PROJECT Production"
          },
          "areaServed": "JP",
          "url": "https://coroproject.jp/production/",
          "description": "謇螻槭ち繝ｬ繝ｳ繝医・豢ｻ蜍墓髪謠ｴ縲√・繝阪ず繝｡繝ｳ繝医√・繝ｭ繝｢繝ｼ繧ｷ繝ｧ繝ｳ繧定｡後≧VTuber莠句漁謇縲・
        },
        {
          "@type": "WebPage",
          "name": "CORO PROJECT Production | VTuber莠句漁謇繝ｻ謇螻槭ち繝ｬ繝ｳ繝域ュ蝣ｱ",
          "url": "https://coroproject.jp/production/",
          "description": "謇螻槭ち繝ｬ繝ｳ繝医・豢ｻ蜍墓髪謠ｴ繝ｻ繝槭ロ繧ｸ繝｡繝ｳ繝医ｒ陦後≧CORO PROJECT Production縺ｮ蜈ｬ蠑上・繝ｼ繧ｸ縲よ怙譁ｰ繝九Η繝ｼ繧ｹ繧・が繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ諠・ｱ繧よ軸霈峨＠縺ｦ縺・∪縺吶・
        }
      ]
    }
  </script>

  <link rel="icon" type="image/png" href="images/logo.png">
  <link rel="apple-touch-icon" href="images/logo.png">

  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/top.css">
</head>

<body class="home is-loading">
  <!-- Simple Loader -->
  <div id="coro-loader" class="coro-loader" aria-label="Loading">
    <div class="coro-loader__simple-inner">
      <img src="images/logo.png" alt="CORO PROJECT" class="coro-loader__simple-logo">
      <div class="coro-loader__simple-title">縺薙ｍ縺ｷ繧阪§縺・￥縺ｨ・・/div>
    </div>
  </div>

  <!-- App -->
  <div id="app">
    <!-- ===== Header ===== -->
    <header class="site-header">
      <div class="container header-inner">
        <a href="#top" class="brand">
          <img src="images/toukalogo.png" alt="CORO PROJECT 繝ｭ繧ｴ" class="brand-logo">
          <span class="brand-text">CORO PROJECT</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="繝｡繝九Η繝ｼ繧帝幕縺・>
          <span></span><span></span><span></span>
        </button>

        <nav class="nav" id="siteNav" aria-label="繝｡繧､繝ｳ繝翫ン繧ｲ繝ｼ繧ｷ繝ｧ繝ｳ">
          <a href="#about">About</a>
          <a href="#news">News</a>
          <a href="#talents">Talents</a>
          <a href="html/audition.html">Audition</a>
          <a href="html/contact.html">Contact</a>
        </nav>
      </div>
    </header>

    <main id="top">
      <!-- ===== Hero ===== -->
      <section class="hero">
        <div class="hero-bg" aria-hidden="true"></div>

        <div class="container hero-inner">
          <div class="hero-copy">
            <p class="hero-eyebrow">VTUBER PRODUCTION</p>
            <h1 class="hero-title">縺薙ｍ縺ｷ繧阪§縺・￥縺ｨ・・/h1>
            <p class="hero-lead">窶懆・蛻・□縺代〒縺ｯ螻翫°縺ｪ縺九▲縺溷ｴ謇縺ｸ窶・/p>
            <p class="hero-sub">
              邏ｫ縺ｨ繝斐Φ繧ｯ繧偵ユ繝ｼ繝槭↓縲・・菫｡繝ｻ蜑ｵ菴懊・莨∫判縺ｮ縺吶∋縺ｦ繧剃ｸ邱偵↓讌ｽ縺励・VTuber繝励Ο繝繧ｯ繧ｷ繝ｧ繝ｳ縲・
              縺ゅ↑縺溘・縲悟･ｽ縺阪阪ｒ縲√ｂ縺｣縺ｨ驕縺上∪縺ｧ螻翫￠縺ｾ縺吶・
            </p>

            <div class="hero-actions">
              <a class="btn btn-primary" href="html/audition.html">繧ｪ繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ</a>
              <a class="btn btn-outline" href="html/talents.php">繧ｿ繝ｬ繝ｳ繝医ｒ隕九ｋ</a>
            </div>
          </div>

          <div class="hero-visual">
            <div class="hero-visual-inner">
              <div class="hero-aurora" aria-hidden="true"></div>

              <div class="shorts-phone">
                <div class="shorts-phone-inner">
                  <div class="shorts-track" id="shortsTrack">
                    <section class="shorts-item"><video playsinline muted preload="metadata" src="shorts/short1.mp4"></video></section>
                    <section class="shorts-item"><video playsinline muted preload="metadata" src="shorts/short2.mp4"></video></section>
                    <section class="shorts-item"><video playsinline muted preload="metadata" src="shorts/short3.mp4"></video></section>
                    <section class="shorts-item"><video playsinline muted preload="metadata" src="shorts/short4.mp4"></video></section>
                  </div>
                </div>
                <div class="shorts-phone-bar"></div>
              </div>

              <div class="hero-badge">
                <span class="badge-label">Coro Project Shorts</span>
                <span class="badge-dot"></span>
              </div>

              <div class="hero-tags">
                <span>#蛻・ｊ謚懊″</span>
                <span>#VTuber</span>
                <span>#CoroProject</span>
              </div>

              <div class="shorts-dots" aria-label="繧ｷ繝ｧ繝ｼ繝亥虚逕ｻ縺ｮ繧､繝ｳ繧ｸ繧ｱ繝ｼ繧ｿ繝ｼ">
                <button type="button" data-index="0" class="is-active"></button>
                <button type="button" data-index="1"></button>
                <button type="button" data-index="2"></button>
                <button type="button" data-index="3"></button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== About ===== -->
      <section id="about" class="section section-about reveal">
        <div class="decor-line-left"></div>
        <div class="decor-line-right"></div>
        <div class="container about-inner">
          <div class="section-head">
            <h2 class="section-title">About</h2>
            <p class="section-kicker">CORO PROJECT縺ｨ縺ｯ・・/p>
          </div>

          <div class="about-grid">
            <div class="about-main">
              <h3 class="about-title">縲悟･ｽ縺阪阪→縲檎ｶ壹￠繧峨ｌ繧九阪ｒ縲√■繧・ｓ縺ｨ荳｡遶九＆縺帙ｋ繝励Ο繝繧ｯ繧ｷ繝ｧ繝ｳ縲・/h3>
              <p>
                CORO PROJECT縺ｯ縲∫ｴｫ縺ｨ繝斐Φ繧ｯ繧偵ユ繝ｼ繝槭↓縺励◆蟆上＆縺ｪVTuber繝励Ο繝繧ｯ繧ｷ繝ｧ繝ｳ縺ｧ縺吶・br>
                逶ｮ謖・＠縺ｦ縺・ｋ縺ｮ縺ｯ縲∝､ｧ縺阪↑逵区攸縺ｧ縺ｯ縺ｪ縺上後■繧・ｓ縺ｨ髫｣縺ｧ荳邱偵↓襍ｰ縺｣縺ｦ縺上ｌ繧矩°蝟ｶ縲阪・
              </p>
              <p>
                驟堺ｿ｡繧ｹ繧ｱ繧ｸ繝･繝ｼ繝ｫ縲∽ｼ∫判縲√さ繝ｩ繝懊∵焚蟄励・莨ｸ縺ｳ譁ｹ縲・br>
                縺ｲ縺ｨ縺､縺ｲ縺ｨ縺､縺ｮ謔ｩ縺ｿ縺ｫ蟇・ｊ豺ｻ縺・↑縺後ｉ縲√ち繝ｬ繝ｳ繝医→荳邱偵↓
                <span class="about-highlight">窶懊◎縺ｮ莠ｺ繧峨＠縺・ｴｻ蜍輔せ繧ｿ繧､繝ｫ窶・/span>繧堤ｵ・∩遶九※縺ｦ縺・″縺ｾ縺吶・
              </p>
              <p>
                縲後ｂ縺｣縺ｨ譛ｬ豌励〒繧・ｊ縺溘＞縺代←縲√・縺ｨ繧翫□縺ｨ髯千阜繧呈─縺倥※縺・ｋ縲阪後〒繧ゅ√ぎ繝√ぎ繝√・邂ｱ縺ｫ蜈･繧翫◆縺・ｏ縺代§繧・↑縺・阪・br>
                縺昴ｓ縺ｪ莠ｺ縺ｮ 窶懊■繧・≧縺ｩ縺・＞螻・ｴ謇窶・縺ｫ縺ｪ繧後◆繧峨√→閠・∴縺ｦ縺・∪縺吶・
              </p>
            </div>

            <div class="about-side">
              <div class="about-pill">Support &amp; Production</div>
              <ul class="about-points">
                <li><strong>驟堺ｿ｡縺ｾ繧上ｊ縺ｮ莨ｴ襍ｰ繧ｵ繝昴・繝・/strong><span>莨∫判逶ｸ隲・/ 騾ｱ谺｡縺ｮ謖ｯ繧願ｿ斐ｊ / 譁ｹ蜷第ｧ縺ｮ縺吶ｊ蜷医ｏ縺・縺ｪ縺ｩ</span></li>
                <li><strong>繧ｯ繝ｪ繧ｨ繧､繝・ぅ繝門宛菴懊・遯灘哨</strong><span>繧ｭ繝｣繝ｩ繝・じ繝ｻ繝ｭ繧ｴ繝ｻOPED繝ｻBGM縺ｪ縺ｩ縲∝宛菴懊ヱ繝ｼ繝医リ繝ｼ縺ｮ邏ｹ莉九→騾ｲ陦後し繝昴・繝・/span></li>
                <li><strong>謨ｰ蟄励→逕滓ｴｻ縺ｮ繝舌Λ繝ｳ繧ｹ險ｭ險・/strong><span>辟｡逅・・縺ｪ縺・ｴｻ蜍輔・繝ｼ繧ｹ縺ｮ險ｭ險・/ 蜿守寢蛹悶∪縺ｧ縺ｮ繝ｭ繝ｼ繝峨・繝・・菴懈・</span></li>
                <li><strong>繝輔ぃ繝ｳ縺ｨ荳邱偵↓閧ｲ縺ｦ繧倶ｼ∫判</strong><span>蜻ｨ蟷ｴ莨∫判 / 繧ｰ繝・ぜ / 繧､繝吶Φ繝磯°蝟ｶ 縺ｪ縺ｩ縺ｮ蜈ｱ蜷後・繝ｩ繝ｳ繝九Φ繧ｰ</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== News ===== -->
      <section id="news" class="section section-news reveal">
        <div class="container">
          <div class="section-head">
            <h2 class="section-title">News</h2>
            <a class="section-link" href="html/news.php">縺吶∋縺ｦ隕九ｋ</a>
          </div>

          <div id="top-news-list" class="news-grid">
            <?php if (empty($topNews)): ?>
              <p class="news-empty" style="color:#9ca3c3; font-size:.9rem;">
                迴ｾ蝨ｨ陦ｨ遉ｺ縺ｧ縺阪ｋ繝九Η繝ｼ繧ｹ縺ｯ縺ゅｊ縺ｾ縺帙ｓ縲りｩｳ邏ｰ縺ｯ <a href="html/news.php">News繝壹・繧ｸ</a> 繧偵＃遒ｺ隱阪￥縺縺輔＞縲・
              </p>
            <?php else: ?>
              <?php foreach ($topNews as $n): ?>
                <article class="news-card">
                  <a href="<?= $n['url'] ? esc($n['url']) : 'html/news.php' ?>">
                    <div class="card-thumb" aria-hidden="true" style="<?= $n['thumb'] ? "background-image:url('".esc(public_html_asset_url($n['thumb']))."')" : '' ?>"></div>
                    <span class="news-label"><?= esc($n['tag'] ?: 'News') ?></span>
                    <span class="news-date"><?= esc($n['date']) ?></span>
                    <h3 class="news-title"><?= esc($n['title']) ?></h3>
                    <p class="news-text"><?= esc($n['excerpt'] ?? '') ?></p>
                  </a>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <noscript>
            <p style="font-size:.85rem; color:#9ca3c3;">
              JavaScript縺檎┌蜉ｹ縺ｫ縺ｪ縺｣縺ｦ縺・∪縺吶よ怙譁ｰ諠・ｱ縺ｯ<a href="html/news.php">News繝壹・繧ｸ</a>縺九ｉ縺皮｢ｺ隱阪￥縺縺輔＞縲・
            </p>
          </noscript>
        </div>
      </section>

      <!-- ===== Talents ===== -->
      <section id="talents" class="section section-talents reveal">
        <div class="container">
          <div class="section-head">
            <h2 class="section-title">Talents</h2>
            <a class="section-link" href="html/talents.php">荳隕ｧ繧定ｦ九ｋ</a>
          </div>

          <div class="talents-top-grid">
            <?php foreach ($topTalents as $t): ?>
              <a class="talent-top-card" href="html/talent.php?id=<?= esc($t['id']) ?>">
                <div class="talent-top-thumb" style="background-image:url('<?= esc($t['avatar_for_top']) ?>');"></div>
                <div class="talent-top-info">
                  <p class="talent-top-label">Coro Project Talent</p>
                  <h3 class="talent-top-name"><?= esc($t['name']) ?></h3>
                </div>
              </a>
            <?php endforeach; ?>

            <?php for ($i = count($topTalents); $i < 3; $i++): ?>
              <div class="talent-top-card talent-top-card--empty">
                <div class="talent-top-thumb"></div>
                <div class="talent-top-info">
                  <p class="talent-top-coming">COMING SOON</p>
                </div>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </section>

      <!-- ===== Audition CTA ===== -->
      <section class="section section-cta section-cta--audition reveal">
        <div class="container cta-audition">
          <div class="cta-audition-copy">
            <p class="cta-label">Audition</p>
            <h2 class="cta-title">縲御ｸ蠎ｦ縺｡繧・ｓ縺ｨ縲∵悽豌励〒繧・▲縺ｦ縺ｿ縺溘＞縲堺ｺｺ縺ｸ縲・/h2>
            <p class="cta-lead">
              CORO PROJECT縺ｮ繧ｪ繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ縺ｧ縺ｯ縲∫匳骭ｲ閠・焚繧・・菫｡豁ｴ縺縺代〒蛻､譁ｭ縺励∪縺帙ｓ縲・br>
              縺・∪縺ｮ謨ｰ蟄励ｈ繧翫ｂ縲√％繧後°繧我ｸ邱偵↓菴懊▲縺ｦ縺・￠繧九檎・驥上阪→縲檎ｶ壹￠繧区э諤昴阪ｒ螟ｧ蛻・↓縺励※縺・∪縺吶・
            </p>
            <ul class="cta-points">
              <li><span>笨・/span> 驟堺ｿ｡邨碁ｨ薙′蟆代↑縺上※繧０K・域悴邨碁ｨ薙〒繧よэ谺ｲ縺後≠繧後・豁楢ｿ趣ｼ・/li>
              <li><span>笨・/span> 蟄ｦ讌ｭ繝ｻ莉穂ｺ九→縺ｮ荳｡遶九ｒ蜑肴署縺ｫ縲∵ｴｻ蜍輔・繝ｼ繧ｹ繧剃ｸ邱偵↓險ｭ險・/li>
              <li><span>笨・/span> 繧ｭ繝｣繝ｩ繧ｯ繧ｿ繝ｼ繧・ｸ也阜隕ｳ縺･縺上ｊ縺九ｉ逶ｸ隲・庄閭ｽ</li>
            </ul>
            <div class="cta-actions">
              <a class="btn btn-primary" href="html/audition.html">繧ｪ繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ縺ｮ隧ｳ邏ｰ繧定ｦ九ｋ</a>
              <a class="btn btn-outline" href="html/contact.html">縺ｾ縺壹・逶ｸ隲・＠縺ｦ縺ｿ繧・/a>
            </div>
            <p class="cta-note">縲瑚・蛻・↓蜷医▲縺ｦ縺・ｋ縺ｮ縺九ｏ縺九ｉ縺ｪ縺・阪悟ｰ代＠縺縺題ｩｱ繧定◇縺阪◆縺・阪↑縺ｩ縺ｮ縺皮嶌隲・ｂ縺頑ｰ苓ｻｽ縺ｫ縺ｩ縺・◇縲・/p>
          </div>

          <div class="cta-audition-side">
            <div class="cta-card">
              <h3>蜍滄寔縺励※縺・ｋ繧､繝｡繝ｼ繧ｸ</h3>
              <ul>
                <li>髟ｷ譛溽噪縺ｫ豢ｻ蜍輔ｒ邯壹￠縺溘＞諢丞ｿ励′縺ゅｋ譁ｹ</li>
                <li>繝ｪ繧ｹ繝翫・縺ｨ繧ｳ繝溘Η繝九こ繝ｼ繧ｷ繝ｧ繝ｳ繧偵→繧九・縺悟･ｽ縺阪↑譁ｹ</li>
                <li>譁ｰ縺励＞縺薙→縺ｫ謖第姶縺励※縺ｿ縺溘＞譁ｹ</li>
              </ul>
            </div>
            <div class="cta-card cta-card--soft">
              <h3>驕ｸ閠・ヵ繝ｭ繝ｼ・井ｾ具ｼ・/h3>
              <ol>
                <li>Web繝輔か繝ｼ繝縺九ｉ蠢懷供</li>
                <li>譖ｸ鬘槭・驟堺ｿ｡繧｢繝ｼ繧ｫ繧､繝悶・遒ｺ隱・/li>
                <li>繧ｪ繝ｳ繝ｩ繧､繝ｳ髱｢隲・ｼ・縲・蝗橸ｼ・/li>
              </ol>
              <p class="cta-small">隧ｳ邏ｰ縺ｯ<a href="html/audition.html">繧ｪ繝ｼ繝・ぅ繧ｷ繝ｧ繝ｳ繝壹・繧ｸ</a>縺ｫ縺ｦ縺皮｢ｺ隱阪￥縺縺輔＞縲・/p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ===== Footer ===== -->
    <footer class="site-footer">
      <div class="container footer-inner">
        <div class="footer-col">
          <div class="footer-brand">
            <img src="images/logo.png" alt="CORO PROJECT 繝ｭ繧ｴ" class="footer-logo">
            <span class="footer-name">CORO PROJECT</span>
          </div>
          <p class="footer-text">VTuber縺ｮ繝励Ο繝・Η繝ｼ繧ｹ繝ｻ驟堺ｿ｡繧ｵ繝昴・繝医ｒ陦後≧繝励Ο繝繧ｯ繧ｷ繝ｧ繝ｳ縺ｧ縺吶・/p>
        </div>
        <div class="footer-col">
          <h4>Links</h4>
          <ul>
            <li><a href="html/news.php">News</a></li>
            <li><a href="html/talents.php">Talents</a></li>
            <li><a href="html/audition.html">Audition</a></li>
            <li><a href="html/privacy.html">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Social</h4>
          <ul>
            <li><a href="https://x.com/CoroProjectJP" target="_blank" rel="noopener">X・・witter・・/a></li>
            <li><a href="#">YouTube</a></li>
            <li><a href="#">Twitch</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <small>ﾂｩ <span id="year"></span> CORO PROJECT</small>
      </div>
    </footer>
  </div><!-- /#app -->

  <!-- ===== Scripts ===== -->
  <script>
    // 蟷ｴ蜿ｷ
    document.getElementById('year').textContent = new Date().getFullYear();

    // 繝｢繝舌う繝ｫ繝翫ン
    (function(){
      const btn = document.getElementById('navToggle');
      const nav = document.getElementById('siteNav');
      if(!btn || !nav) return;

      btn.addEventListener('click', () => {
        const open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));
        document.body.classList.toggle('nav-open', !open);
      });

      nav.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          document.body.classList.remove('nav-open');
          btn.setAttribute('aria-expanded', 'false');
        });
      });
    })();

    // Hero 繧ｷ繝ｧ繝ｼ繝亥虚逕ｻ・育ｸｦ繧ｹ繝ｯ繧､繝鈴｢ｨ・・
    (function(){
      const track = document.getElementById('shortsTrack');
      if (!track) return;

      const items  = Array.from(track.querySelectorAll('.shorts-item'));
      const videos = items.map(it => it.querySelector('video'));
      const dots   = Array.from(document.querySelectorAll('.shorts-dots button'));
      const DURATION = 8000;

      let index = 0;
      let timer = null;

      function go(to){
        index = (to + items.length) % items.length;
        track.style.transform = `translateY(-${index * 100}%)`;

        videos.forEach((v,i)=>{
          if (i === index){
            try{ v.currentTime = 0; v.muted = true; v.play(); }catch(e){}
          }else{
            try{ v.pause(); }catch(e){}
          }
        });

        dots.forEach((d,i)=>d.classList.toggle('is-active', i === index));
        restart();
      }

      function restart(){
        clearTimeout(timer);
        timer = setTimeout(()=>go(index + 1), DURATION);
      }

      dots.forEach((btn,i)=>btn.addEventListener('click', ()=>go(i)));

      videos.forEach(v=>{
        v.setAttribute('playsinline','');
        v.muted = true;
      });

      go(0);
    })();

    // Loader・域怙蟆上・遒ｺ螳溘↓豸医∴繧具ｼ・
    (function () {
      const MIN_SHOW_MS = 1800;
      const FADE_MS     = 800;
      const FAILSAFE_MS = 6000;

      const start = performance.now();

      function finish() {
        const loader = document.getElementById("coro-loader");
        if (loader) loader.classList.add("coro-loader--hide");

        document.body.classList.remove("is-loading");
        document.body.classList.add("is-loaded");

        setTimeout(() => { if (loader) loader.remove(); }, FADE_MS);
      }

      window.addEventListener("load", () => {
        const elapsed = performance.now() - start;
        setTimeout(finish, Math.max(0, MIN_SHOW_MS - elapsed));
      });

      setTimeout(finish, FAILSAFE_MS);
    })();

    // reveal
    (function(){
      const reveals = Array.from(document.querySelectorAll('.reveal'));
      if (!('IntersectionObserver' in window) || !reveals.length) {
        reveals.forEach(el => el.classList.add('is-visible'));
        return;
      }
      const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting){
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          }
        });
      }, { threshold:0.15 });
      reveals.forEach(el => io.observe(el));
    })();
  </script>
</body>
</html>
