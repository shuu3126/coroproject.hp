<?php
require_once __DIR__ . '/includes/layout.php';

$pageTitle = $siteName . ' | VTuber・案件仲介・クリエイティブ支援の総合ポータル';
$pageDescription = 'CORO PROJECTの総合ポータル。VTuber事務所、企業向け案件仲介、クリエイティブ支援を一体で案内し、活動設計・案件提案・制作進行の相談窓口へつなぎます。';
$ogDescription = 'VTuber事務所、企業向け案件仲介、クリエイティブ支援を一体で案内するCORO PROJECTの総合ポータル。';
$homeUrl = 'https://coroproject.jp/';
$ogImage = 'https://coroproject.jp/images/ogp.png';
$homeJsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            'name' => 'CORO PROJECT',
            'url' => $homeUrl,
            'logo' => 'https://coroproject.jp/images/logo.png',
        ],
        [
            '@type' => 'WebSite',
            'name' => 'CORO PROJECT',
            'url' => $homeUrl,
        ],
        [
            '@type' => 'WebPage',
            'name' => $pageTitle,
            'url' => $homeUrl,
            'description' => $ogDescription,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'CORO PROJECT',
                'url' => $homeUrl,
            ],
        ],
        [
            '@type' => 'ItemList',
            'name' => 'CORO PROJECTの事業一覧',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Business Matching',
                    'url' => 'https://coroproject.jp/business/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Creative Support',
                    'url' => 'https://coroproject.jp/creative/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => 'Production',
                    'url' => 'https://coroproject.jp/production/',
                ],
            ],
        ],
    ],
];

render_head($siteName, $pageDescription, [
    'title' => $pageTitle,
    'canonical' => $homeUrl,
    'robots' => 'index, follow',
    'og_type' => 'website',
    'og_title' => $pageTitle,
    'og_description' => $ogDescription,
    'og_url' => $homeUrl,
    'og_image' => $ogImage,
    'json_ld' => $homeJsonLd,
    'shell_class' => 'page-shell',
]);
render_header('', false);
?>
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
            活動設計、企業案件の接続、制作進行までを一つの導線で支える総合支援ブランドです。
          </p>
        </div>

        <div class="container cards-grid reveal" id="services">
          <?php foreach ($divisions as $division): ?>
            <a class="division-card cyber-clip-lg <?= h($division['class']) ?>" href="<?= h($basePath . '/' . $division['slug'] . '/') ?>">
              <div class="corner corner-tl"></div>
              <div class="card-top">
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

      <section class="content-section content-section-alt">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">WHY INTEGRATED</span>
              <h2 class="section-title">分断されやすい仕事を、ひとつの流れにする。</h2>
            </div>
          </div>
          <p class="section-note">VTuber活動は、配信だけで完結しません。企画、営業、制作、告知、権利確認、納期管理が同時に動くからこそ、相談窓口を分けすぎない設計が必要です。</p>
        </div>
        <div class="container insight-grid reveal">
          <article class="insight-card cyber-clip-lg">
            <span class="insight-index">01</span>
            <h3>活動者の魅力を、案件で消費しない</h3>
            <p>タレントの人格、世界観、活動ペースを理解したうえで、無理に案件へ当てはめず、長く応援される見せ方へ整えます。</p>
          </article>
          <article class="insight-card cyber-clip-lg">
            <span class="insight-index">02</span>
            <h3>企業の目的を、施策に翻訳する</h3>
            <p>商品紹介、認知拡大、来店促進、採用広報など、目的によって必要な出演形式や制作物は変わります。最初の相談段階から整理します。</p>
          </article>
          <article class="insight-card cyber-clip-lg">
            <span class="insight-index">03</span>
            <h3>制作物まで含めて進行を止めない</h3>
            <p>サムネイル、告知画像、動画、配信画面、ロゴなど、案件や活動に必要な素材を制作相談へつなげ、実行までの抜け漏れを減らします。</p>
          </article>
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
                CORO PROJECTは、単なるタレント事務所に留まりません。企業とタレントが互いの価値を高め合える案件の創出、
                クリエイターのスキルを最適な形で接続する仕組み、そしてそれらを支える確かなマネジメントを構築します。
              </p>
              <p>
                大切にしているのは、短期的な話題づくりだけではなく、活動者・企業・制作パートナーが安心して関わり続けられる状態をつくること。
                「三方よし」の精神で、VTuber業界のさらなる成熟と、そこに集うすべての人々の可能性を最大化することを使命としています。
              </p>
            </div>
            <a href="about.php" class="outline-button cyber-clip">ABOUT CORO PROJECT <span aria-hidden="true">›</span></a>
          </div>

          <div class="about-visual reveal">
            <div class="visual-frame cyber-clip-lg">
              <div class="visual-gradient"></div>
              <video class="visual-video" src="images/short/short1.mp4" autoplay muted loop playsinline preload="metadata" aria-label="青海しび ゲームプレイ動画"></video>
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

      <section class="content-section service-overview">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">SERVICE OVERVIEW</span>
              <h2 class="section-title">3つの窓口で、活動と案件と制作を止めない。</h2>
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

      <section class="content-section">
        <div class="container section-head reveal">
          <div class="section-title-wrap">
            <span class="pink-bar"></span>
            <div>
              <span class="section-sub">CONSULTATION ROUTES</span>
              <h2 class="section-title">相談内容から、最適な入口を選べます。</h2>
            </div>
          </div>
          <a href="contact.php" class="ghost-link cyber-clip">START CONSULTATION <span aria-hidden="true">→</span></a>
        </div>
        <div class="container route-grid reveal">
          <article class="route-card cyber-clip-lg">
            <span>FOR COMPANY</span>
            <h3>企業案件・PR施策</h3>
            <p>商品紹介、サービス認知、イベント出演、SNS施策など、目的に合わせてVTuber起用の形を整理します。</p>
          </article>
          <article class="route-card cyber-clip-lg">
            <span>FOR CREATOR</span>
            <h3>制作・デザイン相談</h3>
            <p>イラスト、動画、配信素材、ロゴなど、活動や案件に必要な制作物を依頼しやすい粒度に整えます。</p>
          </article>
          <article class="route-card cyber-clip-lg">
            <span>FOR TALENT</span>
            <h3>所属・活動相談</h3>
            <p>活動方針、企画、案件対応、制作の悩みまで、継続して活動するために必要な支援を一緒に確認します。</p>
          </article>
          <article class="route-card cyber-clip-lg">
            <span>FOR PARTNER</span>
            <h3>提携・取材・協業</h3>
            <p>メディア掲載、共同企画、業務提携など、CORO PROJECTとの接点を広げる相談を受け付けています。</p>
          </article>
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
        <video class="cta-bg-video" src="images/short/short1.mp4" autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video>
        <div class="cta-beam"></div>
        <div class="container cta-inner reveal">
          <div class="mini-tag">SYSTEM_PROMPT: NEXT_STEP</div>
          <h2>企画の種を、<br>実行できる導線へ。</h2>
          <p class="cta-lead">相談内容がまだ曖昧でも大丈夫です。目的、予算感、希望時期、必要な制作物を整理しながら、最初に開くべき窓口へつなぎます。</p>
          <div class="cta-actions">
            <a href="contact.php" class="primary-button cyber-clip">CONTACT US</a>
            <a href="service.php" class="secondary-button cyber-clip">SERVICE / SYSTEM DETAIL</a>
          </div>
        </div>
      </section>
    </main>

<?php render_footer(); ?>
