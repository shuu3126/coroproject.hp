<?php
require __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/includes/public-settings.php';

$_biz_news = [];
$_biz_pdo = coro_public_settings_db();
if ($_biz_pdo) {
    try {
        $s = $_biz_pdo->query("SELECT id, title, date, tag, url FROM news WHERE is_published = 1 AND (targets IS NULL OR targets = '' OR FIND_IN_SET('business', targets)) ORDER BY date DESC, id DESC LIMIT 3");
        $_biz_news = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

$siteTitle = 'ころコネクト！ | VTuber案件窓口・企業コラボ相談 | CORO PROJECT';
render_header('', $siteTitle, [
  'page_name' => 'ころコネクト！ | VTuber案件窓口・企業コラボ相談 | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business/',
  'description' => 'ころコネクト！はCORO PROJECTの企業向けVTuber案件総合窓口。VTuber起用相談・候補提案・条件整理・進行支援まで一体で対応します。'
]);
global $bmSite, $contactUrl;
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span><?= h($bmSite['hero']['kicker']) ?></div>
      <h1 class="hero-title">
        <?php foreach ($bmSite['hero']['lines'] as $line): ?>
          <span class="line <?= h($line['class']) ?>"><?= h($line['text']) ?></span>
        <?php endforeach; ?>
      </h1>
      <p class="hero-lead"><?= h($bmSite['hero']['lead']) ?></p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="contact.php">案件相談をする</a>
        <a class="btn btn-secondary" href="service.php">支援内容を見る</a>
      </div>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">CONSULT / CASTING / PRODUCTION</div>
        <div class="panel-card">
          <span class="panel-kicker">SUPPORTED FOR</span>
          <h2>企業側の“分からない”を、進めやすい設計に。</h2>
          <p>企業・店舗・地方自治体・広告代理店に向けて、所属外VTuberも含めた候補提案、条件整理、確認導線の整理まで一体で支援します。</p>
          <ul class="panel-points">
            <?php foreach ($bmSite['supports'][0]['points'] as $point): ?>
              <li><?= h($point) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="panel-stats">
            <div class="stat-box"><span>相談対象</span><strong>企業 / 店舗 / 自治体</strong></div>
            <div class="stat-box"><span>提案範囲</span><strong>所属内外の候補提案</strong></div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section">
  <div class="container section-grid">
    <div class="section-copy reveal">
      <div class="eyebrow"><span></span>CORE SUPPORT</div>
      <h2 class="section-title title-lines">
        <span>企業とVTuberをつなぐ、</span>
        <span>相談窓口と</span>
        <span class="nowrap-inline">実行支援。</span>
      </h2>
      <p class="section-text">ころコネクト！は、企業・ブランド・イベントとVTuberをつなぎ、起用の相談受付から企画整理、候補提案、条件調整、実施進行までを一体で支える事業部です。案件をつなぐだけで終わらせず、実施しやすい形へ整えることを役割にしています。</p>
      <p class="section-text">VTuber業界に不慣れな企業でも相談しやすいように、何を目的に起用するべきか、どの規模の施策から始めるべきか、配信・SNS・イベントのどこが適しているか、といった入口から伴走します。</p>
    </div>
    <div class="info-stack reveal delay-1">
      <?php foreach ($bmSite['supports'] as $support): ?>
        <article class="info-card">
          <span class="info-card__eyebrow"><?= h($support['eyebrow']) ?></span>
          <h3><?= h($support['title']) ?></h3>
          <p><?= h($support['text']) ?></p>
          <ul>
            <?php foreach ($support['points'] as $point): ?>
              <li><?= h($point) ?></li>
            <?php endforeach; ?>
          </ul>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-section target-block support-statement">
  <div class="container support-statement__inner">
    <div class="eyebrow center"><span></span>WHO WE SUPPORT</div>
    <h2 class="center-title support-title">
      <span class="support-title__accent">相談しやすさ</span><span class="support-title__join">と、</span>
      <span class="support-title__accent support-title__accent--pink">実行しやすさ。</span>
      <span class="support-title__body">その両方を整えるための窓口です。</span>
    </h2>
    <p class="support-statement__lead">目的の整理、候補提案、条件調整、実施前後の確認まで。企業側がつまずきやすいポイントを、相談しやすい形に整えます。</p>
    <div class="pill-list support-pill-list">
      <?php foreach ($bmSite['targetPills'] as $index => $target): ?>
        <span><small><?= h(str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)) ?></small><?= h($target) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-section mini-flow">
  <div class="container">
    <div class="eyebrow"><span></span>FLOW SNAPSHOT</div>
    <div class="flow-grid">
      <?php foreach ($bmSite['flow'] as $item): ?>
        <article class="flow-card reveal">
          <div class="flow-card__no"><?= h($item['no']) ?></div>
          <h3><?= h($item['title']) ?></h3>
          <p><?= h($item['text']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-section case-preview">
  <div class="container">
    <div class="section-head-row">
      <div>
        <div class="eyebrow"><span></span>CASE IMAGE</div>
        <h2 class="section-title">企業の目的に合わせて、施策の設計から実行まで整理します。</h2>
      </div>
      <a class="btn btn-secondary" href="case.php">事例を見る</a>
    </div>
    <div class="case-grid">
      <?php foreach ($bmSite['cases'] as $case): ?>
        <article class="case-card reveal">
          <span class="case-card__type"><?= h($case['type']) ?></span>
          <h3><?= h($case['title']) ?></h3>
          <p class="case-card__client"><?= h($case['client']) ?></p>
          <p><?= h($case['summary']) ?></p>
          <ul>
            <?php foreach ($case['bullets'] as $bullet): ?>
              <li><?= h($bullet) ?></li>
            <?php endforeach; ?>
          </ul>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-section faq-preview">
  <div class="container">
    <div class="section-head-row">
      <div>
        <div class="eyebrow"><span></span>FAQ</div>
        <h2 class="section-title">はじめての相談でも、進め方が見える状態に。</h2>
      </div>
      <a class="btn btn-secondary" href="contact.php">問い合わせる</a>
    </div>
    <div class="faq-list">
      <?php foreach ($bmSite['faqs'] as $faq): ?>
        <article class="faq-item reveal">
          <h3><?= h($faq['q']) ?></h3>
          <p><?= h($faq['a']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="content-section news-preview">
  <div class="container">
    <div class="section-head-row">
      <div>
        <div class="eyebrow"><span></span>NEWS</div>
        <h2 class="section-title">お知らせ・最新情報</h2>
      </div>
      <a class="btn btn-secondary" href="news.php">すべて見る</a>
    </div>
    <div class="news-list">
      <?php if ($_biz_news): ?>
        <?php foreach ($_biz_news as $item): ?>
          <?php
            $dateStr = !empty($item['date']) ? date('Y.m.d', strtotime($item['date'])) : '';
            $link    = !empty($item['url']) ? $item['url'] : 'news.php';
            $target  = !empty($item['url']) ? ' target="_blank" rel="noopener"' : '';
          ?>
          <article class="news-item">
            <div class="news-item-meta">
              <?php if ($dateStr): ?><time class="news-date"><?= h($dateStr) ?></time><?php endif; ?>
              <?php if (!empty($item['tag'])): ?><span class="news-tag"><?= h($item['tag']) ?></span><?php endif; ?>
            </div>
            <a class="news-item-title" href="<?= h($link) ?>"<?= $target ?>><?= h($item['title']) ?></a>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="news-empty">現在お知らせはありません。<a href="news.php">ニュース一覧へ</a></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="content-section contact-band">
  <div class="container contact-band__inner">
    <div>
      <div class="eyebrow"><span></span>CONTACT</div>
      <h2 class="section-title narrow">案件相談・起用相談を、まずは目的段階から。</h2>
      <p class="section-text">料金や進め方は案件規模や温度感によって変わるため、お問い合わせベースでご案内しています。まずは目的や課題感からご相談ください。</p>
    </div>
    <div class="contact-band__actions">
      <a class="btn btn-primary" href="contact.php">総合窓口へ進む</a>
      <a class="btn btn-secondary" href="../index.php">総合TOPへ</a>
    </div>
  </div>
</section>
<?php render_footer(); ?>
