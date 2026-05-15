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
    'shell_class' => 'page-shell nexus-home',
]);
render_header('', false);
?>
    <main class="brutal-main">
      <section class="brutal-hero">
        <div class="brutal-bg-word brutal-bg-word-a" aria-hidden="true">CREATIVE</div>
        <div class="brutal-bg-word brutal-bg-word-b" aria-hidden="true">CORO</div>

        <div class="brutal-tape brutal-tape-yellow">
          <div class="brutal-tape-track">CORO PROJECT / VTUBER / BUSINESS / CREATIVE / PRODUCTION / CORO PROJECT / VTUBER / BUSINESS / CREATIVE / PRODUCTION /</div>
        </div>
        <div class="brutal-tape brutal-tape-blue">
          <div class="brutal-tape-track brutal-tape-track-reverse">CONNECT TALENT AND COMPANY / CREATIVE HUB / NEXT GENERATION / CONNECT TALENT AND COMPANY / CREATIVE HUB /</div>
        </div>
        <div class="brutal-tape brutal-tape-pink">
          <div class="brutal-tape-track">VIRTUAL TALENT PRODUCTION / BRAND COLLABORATION / DESIGN SUPPORT / VIRTUAL TALENT PRODUCTION /</div>
        </div>

        <div class="container brutal-hero-grid">
          <div class="brutal-hero-copy reveal">
            <div class="brutal-sticker">[ SYS.01 ] VTUBER TOTAL HUB</div>
            <h1 class="brutal-title">
              <span>VTuberと</span>
              <span class="brutal-title-outline">企業を</span>
              <span>つなぐ。</span>
            </h1>
            <div class="brutal-copy-panel">
              <span class="brutal-mini-badge">NEW WAVE</span>
              <p>
                CORO PROJECTは、VTuber事務所運営、企業案件仲介、クリエイティブ制作をひとつの導線でつなぐ総合支援ブランドです。
                活動者・企業・クリエイターが迷わず動けるよう、相談から実行までを整理します。
              </p>
              <div class="brutal-actions">
                <a class="brutal-button brutal-button-dark" href="contact.php">相談する <span aria-hidden="true">›</span></a>
                <a class="brutal-button brutal-button-light" href="#services">3事業を見る</a>
              </div>
            </div>
          </div>

          <div class="brutal-collage reveal">
            <div class="brutal-image-card">
              <div class="brutal-image-offset" aria-hidden="true"></div>
              <img src="images/ogp.png" alt="CORO PROJECT visual">
              <div class="brutal-quality">QUALITY 100%</div>
              <div class="brutal-approved"><span>APPROVED</span></div>
              <div class="brutal-round-badge" aria-hidden="true">
                <span>DESIGN / CASTING / PRODUCTION</span>
                <b>CP</b>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="brutal-section brutal-section-white" id="services">
        <div class="container brutal-section-head reveal">
          <div>
            <span class="brutal-kicker">02 // DIVISIONS</span>
            <h2>3つの事業部から、必要な入口へ。</h2>
          </div>
          <p>参考デザインの勢いはそのままに、CORO PROJECTの事業内容が直感的に選べる導線へ整理しました。</p>
        </div>

        <div class="container brutal-division-grid reveal">
          <?php foreach ($divisions as $division): ?>
            <a class="brutal-division-card <?= h($division['class']) ?>" href="<?= h($basePath . '/' . $division['slug'] . '/') ?>">
              <div class="brutal-card-head">
                <span class="brutal-card-num"><?= h($division['num']) ?></span>
                <span class="brutal-card-unit"><?= h($division['title_jp']) ?></span>
              </div>
              <span class="brutal-card-en"><?= h($division['title']) ?></span>
              <h3><?= h($division['title_jp']) ?></h3>
              <p><?= h($division['desc']) ?></p>
              <span class="brutal-card-link">
                <span>ENTER</span>
                <span aria-hidden="true">›</span>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="brutal-section brutal-section-grid">
        <div class="container brutal-section-head reveal">
          <div>
            <span class="brutal-kicker">03 // PROMISE</span>
            <h2>分断されやすい仕事を、ひとつの流れにする。</h2>
          </div>
          <p>企画、営業、制作、告知、権利確認、納期管理。VTuber施策に必要な要素を、相談段階からまとめて設計します。</p>
        </div>
        <div class="container brutal-feature-grid reveal">
          <article class="brutal-feature-card brutal-feature-yellow">
            <span>01</span>
            <h3>活動者の魅力を案件で消費しない</h3>
            <p>タレントの人格、世界観、活動ペースを理解したうえで、長く応援される見せ方へ整えます。</p>
          </article>
          <article class="brutal-feature-card brutal-feature-pink">
            <span>02</span>
            <h3>企業の目的を施策に翻訳する</h3>
            <p>商品紹介、認知拡大、来店促進、採用広報など、目的に合わせて出演形式と制作物を設計します。</p>
          </article>
          <article class="brutal-feature-card brutal-feature-blue">
            <span>03</span>
            <h3>制作物まで含めて進行を止めない</h3>
            <p>サムネイル、告知画像、動画、配信画面、ロゴなど、実行に必要な素材まで相談できます。</p>
          </article>
        </div>
      </section>

      <section class="brutal-split-section">
        <div class="brutal-split-panel brutal-split-dark reveal">
          <span class="brutal-kicker">04 // FOR TALENTS</span>
          <h2>次世代のスターを、継続できる活動へ。</h2>
          <p>所属・活動設計・企画・案件対応・制作相談まで、VTuberが安心して活動を続けられる環境を整えます。</p>
          <a class="brutal-row-link" href="production/">VTuber事務所を見る <span aria-hidden="true">›</span></a>
        </div>
        <div class="brutal-split-panel brutal-split-yellow reveal">
          <span class="brutal-kicker">05 // FOR COMPANY</span>
          <h2>企業施策を、VTuberらしい熱量へ。</h2>
          <p>既存タレントとの連携、新規企画、PR施策、イベント出演など、目的に合わせた起用方法をご提案します。</p>
          <a class="brutal-row-link" href="business/">案件仲介を見る <span aria-hidden="true">›</span></a>
        </div>
      </section>

      <section class="brutal-section brutal-section-white">
        <div class="container brutal-section-head reveal">
          <div>
            <span class="brutal-kicker">06 // SERVICE OVERVIEW</span>
            <h2>相談内容から、最適な事業部へつなぎます。</h2>
          </div>
          <a class="brutal-button brutal-button-light" href="service.php">サービス詳細</a>
        </div>
        <div class="container brutal-info-grid reveal">
          <?php foreach ($divisions as $division): ?>
            <article class="brutal-info-card <?= h($division['class']) ?>">
              <span><?= h($division['num']) ?></span>
              <h3><?= h($division['title_jp']) ?></h3>
              <p><?= h($division['summary']) ?></p>
              <a class="brutal-card-link" href="<?= h($division['slug']) ?>/">
                <span>OPEN</span>
                <b><?= h($division['title_jp']) ?></b>
                <span aria-hidden="true">›</span>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="brutal-section brutal-section-news" id="news-preview">
        <div class="container brutal-section-head reveal">
          <div>
            <span class="brutal-kicker">07 // NEWS</span>
            <h2>最新情報</h2>
          </div>
          <a class="brutal-button brutal-button-light" href="news.php">ニュース一覧</a>
        </div>
        <div class="container brutal-news-grid reveal">
          <?php foreach (array_slice($newsItems, 0, 3) as $item): ?>
            <article class="brutal-news-card">
              <div>
                <span><?= h($item['category']) ?></span>
                <time><?= h($item['date']) ?></time>
              </div>
              <h3><?= h($item['title']) ?></h3>
              <p><?= h($item['excerpt']) ?></p>
              <a href="news_detail.php?id=<?= urlencode($item['id']) ?>">READ DETAIL <span aria-hidden="true">›</span></a>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="brutal-cta" id="contact">
        <div class="container brutal-cta-inner reveal">
          <div>
            <span class="brutal-kicker">CONTACT</span>
            <h2>企画の種を、実行できる導線へ。</h2>
            <p>目的、予算感、希望時期、必要な制作物を整理しながら、最初に開くべき窓口へつなぎます。</p>
          </div>
          <div class="brutal-actions">
            <a class="brutal-button brutal-button-light" href="contact.php">お問い合わせへ進む</a>
            <a class="brutal-button brutal-button-ghost" href="about.php">CORO PROJECTとは</a>
          </div>
        </div>
      </section>
    </main>

<?php render_footer(); ?>
