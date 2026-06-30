<?php
require_once __DIR__ . '/includes/layout.php';

$pageTitle       = 'CORO PROJECT | VTuber事務所 × B2Bマッチングプラットフォーム CREDiT';
$pageDescription = 'CORO PROJECTは、VTuber事務所「Production」と業界特化B2BプラットフォームCREDiTを運営。タレント育成から企業案件マッチング・クリエイター連携まで、VTuber業界のインフラを構築します。';
$homeUrl         = 'https://coroproject.jp/';
$ogImage         = 'https://coroproject.jp/images/ogp.png';

$homeJsonLd = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        ['@type' => 'Organization', 'name' => 'CORO PROJECT', 'url' => $homeUrl, 'logo' => 'https://coroproject.jp/images/logo.png'],
        ['@type' => 'WebSite',      'name' => 'CORO PROJECT', 'url' => $homeUrl],
        ['@type' => 'WebPage',      'name' => $pageTitle, 'url' => $homeUrl, 'description' => $pageDescription],
        [
            '@type'           => 'ItemList',
            'name'            => 'CORO PROJECTの事業',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'CREDiT B2Bマッチング',  'url' => 'https://credit.coroproject.jp/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Production VTuber事務所', 'url' => $homeUrl . 'production/'],
            ],
        ],
    ],
];

render_head($siteName, $pageDescription, [
    'title'          => $pageTitle,
    'canonical'      => $homeUrl,
    'robots'         => 'index, follow',
    'og_type'        => 'website',
    'og_title'       => $pageTitle,
    'og_description' => $pageDescription,
    'og_url'         => $homeUrl,
    'og_image'       => $ogImage,
    'json_ld'        => $homeJsonLd,
    'shell_class'    => 'page-shell',
]);
render_header('', false);
?>
    <main>

      <!-- ═══ HERO ═══ -->
      <section class="hero-section">
        <div class="hero-accent-line"></div>

        <!-- Marquee background -->
        <div class="hero-marquee hero-marquee-top" aria-hidden="true">
          <div class="marquee-track">VTUBER &nbsp;&nbsp; B2B PLATFORM &nbsp;&nbsp; CREATOR &nbsp;&nbsp; COMPANY &nbsp;&nbsp; CREDIT &nbsp;&nbsp; PRODUCTION &nbsp;&nbsp;</div>
          <div class="marquee-track">VTUBER &nbsp;&nbsp; B2B PLATFORM &nbsp;&nbsp; CREATOR &nbsp;&nbsp; COMPANY &nbsp;&nbsp; CREDIT &nbsp;&nbsp; PRODUCTION &nbsp;&nbsp;</div>
        </div>
        <div class="hero-marquee hero-marquee-bottom" aria-hidden="true">
          <div class="marquee-track reverse">INFRASTRUCTURE &nbsp;&nbsp; MATCHING &nbsp;&nbsp; CONTRACT &nbsp;&nbsp; INVOICE &nbsp;&nbsp; TALENT &nbsp;&nbsp; PLATFORM &nbsp;&nbsp;</div>
          <div class="marquee-track reverse">INFRASTRUCTURE &nbsp;&nbsp; MATCHING &nbsp;&nbsp; CONTRACT &nbsp;&nbsp; INVOICE &nbsp;&nbsp; TALENT &nbsp;&nbsp; PLATFORM &nbsp;&nbsp;</div>
        </div>

        <!-- HUD overlays -->
        <div class="hero-hud hero-hud-left" aria-hidden="true">SYS.STATUS &gt; ONLINE<br>PROTOCOL &gt; v2.0.0<br>NETWORK &gt; SECURE<br>NODES &gt; 3 ACTIVE</div>
        <div class="hero-hud hero-hud-right" aria-hidden="true">CREATORS &gt; 127<br>COMPANIES &gt; 48+<br>DEALS &gt; 340<br>SAT.RATE &gt; 98%</div>

        <div class="container">
          <div class="hero-inner">

            <!-- LEFT: copy + stats + CTA -->
            <div class="hero-left reveal">
              <div class="hero-label cyber-clip">VTuber事務所 × B2B Platform</div>
              <h1 class="hero-title">
                VTuber業界を、<br>
                <span>インフラから変える。</span>
              </h1>
              <p class="hero-lead">
                CORO PROJECTは<strong>VTuber事務所「Production」</strong>と<strong>業界特化B2Bマッチング「CREDiT」</strong>の2事業を展開。タレントの育成から企業の案件接続まで、VTuber業界の基盤を担います。
              </p>
              <div class="hero-stats">
                <div class="hero-stat-item">
                  <span class="hero-stat-num">127</span>
                  <span class="hero-stat-label">CREATORS<br>審査通過</span>
                </div>
                <div class="hero-stat-item">
                  <span class="hero-stat-num">340</span>
                  <span class="hero-stat-label">DEALS<br>完了案件</span>
                </div>
                <div class="hero-stat-item">
                  <span class="hero-stat-num">98%</span>
                  <span class="hero-stat-label">SATISFACTION<br>満足度スコア</span>
                </div>
              </div>
              <div class="hero-cta-row">
                <a class="primary-button cyber-clip" href="https://credit.coroproject.jp/" target="_blank" rel="noopener noreferrer">CREDiT を開く</a>
                <a class="outline-button cyber-clip" href="contact.php" style="margin-top:0">相談する</a>
              </div>
            </div>

            <!-- RIGHT: division portal cards -->
            <div class="hero-right reveal">
              <?php foreach ($divisions as $division):
                $url   = $division['url'] ?? ($basePath . '/' . $division['slug'] . '/');
                $isExt = !empty($division['url']);
              ?>
                <a class="division-card cyber-clip-lg <?= h($division['class']) ?>"
                   href="<?= h($url) ?>"
                   <?= $isExt ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                  <div class="corner corner-tl"></div>
                  <div class="card-top">
                    <span class="card-num"><?= h($division['num']) ?></span>
                    <span class="card-num-name"><?= h($division['title_jp']) ?></span>
                  </div>
                  <span class="card-en"><?= h($division['title']) ?></span>
                  <h2 class="card-jp"><?= h($division['title_jp']) ?></h2>
                  <p class="card-desc"><?= h($division['desc']) ?></p>
                  <span class="card-link">
                    <span class="card-link-main"><?= $isExt ? 'OPEN PLATFORM' : 'ENTER' ?></span>
                    <span aria-hidden="true">›</span>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>

          </div>
        </div>

        <div class="hero-scroll reveal">
          <div class="hero-scroll-line"></div>
          <span>SCROLL DOWN</span>
        </div>
      </section>

      <!-- ═══ WHAT WE DO ═══ -->
      <section class="content-section content-section-alt">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">TWO PILLARS</span>
              <h2 class="section-title">2つの事業が、業界を動かす。</h2>
            </div>
          </div>
          <p class="section-note">
            事務所としてタレントを育て、プラットフォームで業界取引を効率化する。<br class="hide-mobile">
            この2事業の連携が、VTuber業界に新しい価値軸をつくります。
          </p>
        </div>
        <div class="container info-grid reveal" style="grid-template-columns: repeat(2, minmax(0,1fr));">
          <?php foreach ($divisions as $division):
            $url   = $division['url'] ?? ($basePath . '/' . $division['slug'] . '/');
            $isExt = !empty($division['url']);
          ?>
            <article class="info-card cyber-clip-lg <?= h($division['class']) ?>">
              <div class="corner corner-tl"></div>
              <span class="info-num"><?= h($division['num']) ?></span>
              <span class="info-eyebrow"><?= h($division['title']) ?></span>
              <h3><?= h($division['title_jp']) ?></h3>
              <p><?= h($division['summary']) ?></p>
              <a href="<?= h($url) ?>" class="card-link" <?= $isExt ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                <span class="card-link-main">OPEN</span>
                <span class="card-link-name"><?= h($division['title_jp']) ?></span>
                <span aria-hidden="true">›</span>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- ═══ WHY INTEGRATED ═══ -->
      <section class="content-section">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">WHY CORO PROJECT</span>
              <h2 class="section-title">事務所とプラットフォームを、一社で持つ理由。</h2>
            </div>
          </div>
        </div>
        <div class="container insight-grid reveal">
          <article class="insight-card cyber-clip-lg">
            <span class="insight-index">01</span>
            <h3>現場感覚で作るプラットフォーム</h3>
            <p>実際にVTuberを運営しているからこそ、業界の商慣行・問題点・ニーズを肌感覚で理解しています。CREDiTは「使う側」が作ったB2Bインフラです。</p>
          </article>
          <article class="insight-card cyber-clip-lg">
            <span class="insight-index">02</span>
            <h3>三者全員に価値を届ける構造</h3>
            <p>VTuber・クリエイター・企業の三者が安心して取引できる仕組みを設計。クレジットツリー・契約書自動生成・インボイス対応で、業界の商慣行を近代化します。</p>
          </article>
          <article class="insight-card cyber-clip-lg">
            <span class="insight-index">03</span>
            <h3>タレントの価値を最大化する一貫支援</h3>
            <p>事務所で培ったマネジメントノウハウと、プラットフォームの案件データを組み合わせ、所属タレントが最適な案件・制作パートナーと出会える環境を整えます。</p>
          </article>
        </div>
      </section>

      <!-- ═══ ABOUT ═══ -->
      <section class="about-section" id="about">
        <div class="container about-grid">
          <div class="about-copy reveal">
            <div class="section-marker">
              <span class="marker-bar"></span>
              <span class="marker-text">MISSION</span>
            </div>
            <h2>
              VTuberという才能を、<br>
              一過性のブームではなく、<br>
              持続可能な<span>産業</span>にする。
            </h2>
            <div class="about-text">
              <p>
                CORO PROJECTは、VTuber事務所の現場から生まれた会社です。タレント運営を通じて見えてきた業界課題——不透明な取引・契約トラブル・制作パートナーとのミスマッチ——をテクノロジーで解決するためにCREDiTを立ち上げました。
              </p>
              <p>
                事務所とプラットフォームを同時に運営することで、理論ではなく実体験に基づいたサービス改善を継続。VTuber・クリエイター・企業の三者が安心して関わり続けられる業界インフラの構築が、私たちの使命です。
              </p>
            </div>
            <a href="about.php" class="outline-button cyber-clip">ABOUT CORO PROJECT <span aria-hidden="true">›</span></a>
          </div>

          <div class="about-visual reveal">
            <div class="visual-frame cyber-clip-lg">
              <div class="visual-gradient"></div>
              <video class="visual-video" src="images/short/short1.mp4" autoplay muted loop playsinline preload="metadata" aria-label="CORO PROJECT 所属タレント"></video>
              <div class="frame-corner frame-corner-tl"></div>
              <div class="frame-corner frame-corner-br"></div>
              <div class="visual-caption">
                <span>FEATURED TALENT</span>
                <strong class="talent-name">青海しび</strong>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ FOR WHOM ═══ -->
      <section class="content-section content-section-alt">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">CONTACT ROUTES</span>
              <h2 class="section-title">あなたの立場から、入口を選んでください。</h2>
            </div>
          </div>
          <a href="contact.php" class="ghost-link cyber-clip">お問い合わせ <span aria-hidden="true">→</span></a>
        </div>
        <div class="container route-grid reveal">
          <article class="route-card cyber-clip-lg">
            <span>FOR COMPANY</span>
            <h3>企業・ブランド担当者</h3>
            <p>VTuber起用のPR案件、タイアップ、イベント出演など。CREDiTで条件を入力するだけで、審査済みのVTuber・クリエイターに一括打診できます。</p>
          </article>
          <article class="route-card cyber-clip-lg">
            <span>FOR CREATOR</span>
            <h3>クリエイター・制作者</h3>
            <p>Live2D・イラスト・動画編集・楽曲制作など、VTuber向けスキルをお持ちの方。CREDiTクリエイター登録で、審査済みの案件に出会えます。</p>
          </article>
          <article class="route-card cyber-clip-lg">
            <span>FOR VTUBER</span>
            <h3>VTuber・配信者</h3>
            <p>活動支援・マネジメント・案件紹介。事務所所属のご相談、またはCREDiTへのVTuber登録（審査制）はこちらから。</p>
          </article>
          <article class="route-card cyber-clip-lg">
            <span>FOR PARTNER</span>
            <h3>メディア・提携・協業</h3>
            <p>取材・掲載・業務提携・共同企画のご相談はお気軽に。CORO PROJECTの事業に共鳴していただける方をお待ちしています。</p>
          </article>
        </div>
      </section>

      <!-- ═══ NEWS ═══ -->
      <section class="content-section" id="news-preview">
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

      <!-- ═══ CTA ═══ -->
      <section class="cta-section" id="contact">
        <video class="cta-bg-video" src="images/short/short1.mp4" autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video>
        <div class="cta-beam"></div>
        <div class="container cta-inner reveal">
          <div class="mini-tag">NEXT_STEP</div>
          <h2>まず、話してみてください。</h2>
          <p class="cta-lead">企業案件のご相談、VTuber登録、クリエイター参加、事業提携——どんな入口でも大丈夫です。<br class="hide-mobile">目的を整理するところから、一緒に始めましょう。</p>
          <div class="cta-actions">
            <a href="contact.php" class="primary-button cyber-clip">CONTACT US</a>
            <a href="https://credit.coroproject.jp/" class="secondary-button cyber-clip" target="_blank" rel="noopener noreferrer">CREDiT を見る</a>
          </div>
        </div>
      </section>

    </main>

<?php render_footer(); ?>
