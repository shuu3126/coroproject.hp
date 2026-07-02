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
    'extra_css'      => ['assets/css/portal-v3.css'],
]);
render_header('', false);
?>
    <main class="v3">

      <!-- ═══ HERO — White Hall, Purple Stage ═══ -->
      <section class="hero3">
        <div class="hero3-orb hero3-orb-a" aria-hidden="true"></div>
        <div class="hero3-orb hero3-orb-b" aria-hidden="true"></div>
        <div class="container hero3-inner">
          <div class="hero3-copy reveal">
            <p class="hero3-eyebrow">VTUBER AGENCY <span>×</span> B2B PLATFORM</p>
            <h1 class="hero3-title">VTuber業界を、<br><em>インフラ</em>から変える。</h1>
            <p class="hero3-lead">
              CORO PROJECTは、VTuber事務所「Production」と業界特化B2Bマッチング「CREDiT」の2つの事業で、タレントの育成から企業の案件接続までを支えます。
            </p>
            <div class="hero3-stats">
              <div class="hero3-stat">
                <span class="hero3-stat-num">127</span>
                <span class="hero3-stat-label">審査通過クリエイター</span>
              </div>
              <div class="hero3-stat">
                <span class="hero3-stat-num">340</span>
                <span class="hero3-stat-label">完了案件</span>
              </div>
              <div class="hero3-stat">
                <span class="hero3-stat-num">98<small>%</small></span>
                <span class="hero3-stat-label">満足度スコア</span>
              </div>
            </div>
            <div class="hero3-actions">
              <a class="btn3-primary" href="#gate">入口を選ぶ <span aria-hidden="true">↓</span></a>
              <a class="btn3-ghost" href="contact.php">相談する</a>
            </div>
          </div>

          <div class="hero3-stage reveal">
            <div class="stage3-window stage3-mock" aria-label="CORO PROJECTの事業構造: タレントと企業をプラットフォームがつなぐ">
              <div class="mock3-grid" aria-hidden="true"></div>
              <div class="mock3-glow" aria-hidden="true"></div>
              <div class="mock3-line" aria-hidden="true"><span class="mock3-pulse"></span></div>
              <div class="mock3-planet" aria-hidden="true">
                <img src="images/toukalogo.png" alt="" width="110" height="110">
              </div>
              <div class="mock3-card mock3-talent" aria-hidden="true">
                <div class="mock3-talent-row">
                  <div class="mock3-avatar"></div>
                  <div class="mock3-lines">
                    <i style="width:72%"></i>
                    <i style="width:46%"></i>
                  </div>
                </div>
                <div class="mock3-meta">
                  <span class="mock3-tag">PRODUCTION</span>
                  <span class="mock3-live"><span class="mock3-live-dot"></span>LIVE</span>
                </div>
              </div>
              <div class="mock3-card mock3-deal" aria-hidden="true">
                <div class="mock3-deal-head">
                  <span class="mock3-badge">案件成立</span>
                  <span class="mock3-amount">¥120,000</span>
                </div>
                <div class="mock3-lines">
                  <i style="width:84%"></i>
                  <i style="width:58%"></i>
                </div>
                <div class="mock3-meta">
                  <span class="mock3-tag mock3-tag-credit">CREDiT</span>
                </div>
              </div>
              <div class="stage3-caption">
                <span>CORO PROJECT</span>
                TALENT × BUSINESS
              </div>
            </div>
            <div class="stage3-blob" aria-hidden="true"></div>
            <div class="stage3-chip stage3-chip-1" aria-hidden="true">
              <span class="chip3-dot"></span>審査通過クリエイター 127名
            </div>
            <div class="stage3-chip stage3-chip-2" aria-hidden="true">
              完了案件 340件
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ GATE — 2事業への分岐（主役） ═══ -->
      <section class="gate3" id="gate">
        <div class="container">
          <div class="sec3-head reveal">
            <span class="sec3-label">GATE</span>
            <h2 class="sec3-title">どちらの入口へ？</h2>
            <p class="sec3-lead">目的に合わせて、2つの事業サイトへご案内します。</p>
          </div>

          <div class="gate3-grid reveal">
            <?php foreach ($divisions as $division):
              $url   = $division['url'] ?? ($basePath . '/' . $division['slug'] . '/');
              $isExt = !empty($division['url']);
            ?>
              <a class="gate3-card gate3-<?= h($division['slug']) ?>"
                 href="<?= h($url) ?>"
                 <?= $isExt ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                <div class="gate3-card-head">
                  <span class="gate3-num"><?= h($division['num']) ?></span>
                  <span class="gate3-tag"><?= h($division['title_jp']) ?></span>
                </div>
                <h3 class="gate3-brand"><?= h($division['title']) ?></h3>
                <p class="gate3-desc"><?= h($division['desc']) ?></p>
                <span class="gate3-cta">
                  <?= $isExt ? 'OPEN PLATFORM' : 'ENTER AGENCY' ?>
                  <span class="gate3-arrow" aria-hidden="true">→</span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="gate3-routes reveal">
            <a href="https://credit.coroproject.jp/offer/" target="_blank" rel="noopener noreferrer">
              <span class="route3-who">企業・広告主の方</span>
              <span class="route3-to">CREDiTで案件を相談する <span aria-hidden="true">→</span></span>
            </a>
            <a href="https://credit.coroproject.jp/register/" target="_blank" rel="noopener noreferrer">
              <span class="route3-who">クリエイターの方</span>
              <span class="route3-to">CREDiTに登録する <span aria-hidden="true">→</span></span>
            </a>
            <a href="<?= $basePath ?>/production/">
              <span class="route3-who">VTuber・ファンの方</span>
              <span class="route3-to">Productionへ進む <span aria-hidden="true">→</span></span>
            </a>
          </div>
        </div>
      </section>

      <!-- ═══ MARQUEE（セクション間のリズム） ═══ -->
      <div class="marquee3" aria-hidden="true">
        <div class="marquee3-track">
          <span>CORO PROJECT&nbsp;&nbsp;—&nbsp;&nbsp;VTUBER AGENCY&nbsp;&nbsp;×&nbsp;&nbsp;B2B PLATFORM&nbsp;&nbsp;—&nbsp;&nbsp;</span><span>CORO PROJECT&nbsp;&nbsp;—&nbsp;&nbsp;VTUBER AGENCY&nbsp;&nbsp;×&nbsp;&nbsp;B2B PLATFORM&nbsp;&nbsp;—&nbsp;&nbsp;</span>
        </div>
      </div>

      <!-- ═══ MISSION ═══ -->
      <section class="mission3" id="about">
        <div class="container mission3-inner reveal">
          <span class="sec3-label">MISSION</span>
          <h2 class="mission3-title">
            VTuberという才能を、<br>
            持続可能な<em>産業</em>にする。
          </h2>
          <p class="mission3-text">
            CORO PROJECTは、VTuber事務所の現場から生まれた会社です。タレント運営を通じて見えてきた業界課題——不透明な取引・契約トラブル・制作パートナーとのミスマッチ——をテクノロジーで解決するためにCREDiTを立ち上げました。事務所とプラットフォームの両輪で、VTuber・クリエイター・企業の三者が安心して関わり続けられる業界インフラを構築します。
          </p>
          <a class="text3-link" href="about.php">ABOUT CORO PROJECT <span aria-hidden="true">→</span></a>
        </div>
      </section>

      <!-- ═══ NEWS（admin管理・DB連携） ═══ -->
      <section class="news3" id="news-preview">
        <div class="container">
          <div class="sec3-head-row reveal">
            <div>
              <span class="sec3-label">NEWS</span>
              <h2 class="sec3-title">お知らせ</h2>
            </div>
            <a class="text3-link" href="news.php">VIEW ALL <span aria-hidden="true">→</span></a>
          </div>
          <div class="news3-list reveal">
            <?php foreach (array_slice($newsItems, 0, 4) as $item): ?>
              <a class="news3-row" href="news_detail.php?id=<?= urlencode($item['id']) ?>">
                <span class="news3-date"><?= h($item['date']) ?></span>
                <span class="news3-cat"><?= h($item['category']) ?></span>
                <span class="news3-title"><?= h($item['title']) ?></span>
                <span class="news3-arrow" aria-hidden="true">→</span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- ═══ CTA ═══ -->
      <section class="cta3" id="contact">
        <div class="container cta3-inner reveal">
          <span class="cta3-label">CONTACT</span>
          <h2 class="cta3-title">まず、話してみてください。</h2>
          <p class="cta3-lead">
            企業案件のご相談、VTuber登録、クリエイター参加、事業提携——どんな入口でも大丈夫です。目的を整理するところから、一緒に始めましょう。
          </p>
          <div class="cta3-actions">
            <a class="btn3-white" href="contact.php">CONTACT US</a>
            <a class="btn3-outline" href="https://credit.coroproject.jp/" target="_blank" rel="noopener noreferrer">CREDiT を見る</a>
          </div>
        </div>
      </section>

    </main>

<?php render_footer(); ?>
