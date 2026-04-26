<?php
require __DIR__ . '/includes/layout.php';
$siteTitle = 'About | Business Matching | CORO PROJECT';
render_header('about', $siteTitle, [
  'page_name' => 'About | Business Matching | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business-matching/about.php',
  'description' => 'Business Matchingの考え方と支援方針。企業がVTuber施策を安心して進めるための相談窓口として、整理と実行支援の全体像を紹介します。'
]);
?>
<section class="hero page-hero">
  <div class="container hero-inner hero-inner--page">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span> ABOUT</div>
      <h1 class="hero-title page-title">
        <span class="line solid">企業がVTuber施策を、</span><span class="line solid">安心して進める</span><span class="line accent">ための窓口。</span>
      </h1>
      <p class="hero-lead">企業・ブランド・イベントとVTuberをつなぎ、起用の相談から企画整理、進行支援までを一体で支える事業部です。</p>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">ABOUT BUSINESS MATCHING</div>
        <div class="panel-card">
          <span class="panel-kicker">ABOUT</span>
          <h2>つなぐだけで終わらせない。</h2>
          <p>相談の入口設計、候補整理、条件調整、進行管理までを一体で支えることで、企業側もVTuber側も進めやすい施策をつくります。</p>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section">
  <div class="container section-grid">
    <div class="section-copy reveal">
      <div class="eyebrow"><span></span>WHY IT WORKS</div>
      <h2 class="section-title title-lines">
        <span>つなぐだけで終わらせない。</span>
        <span>相談しやすさと</span>
        <span class="nowrap-inline">実行のしやすさ。</span>
      </h2>
      <p class="section-text">Business Matchingは、企業・ブランド・イベントとVTuberをつなぎ、起用の相談受付から企画整理、進行実務までを支える事業部です。施策を成立させるだけでなく、相談しやすく進めやすい状態まで整えることを大切にしています。</p>
      <p class="section-text">最初から大きな企画でなくても問題ありません。まずは「何ができるか知りたい」「小さく始めたい」という段階から対応できます。</p>
    </div>
    <div class="info-stack reveal delay-1">
      <article class="info-card">
        <span class="info-card__eyebrow">ROLE</span>
        <h3>企業が不慣れでも進めやすい設計</h3>
        <p>VTuber業界に詳しくない企業でも、相談先・進行フロー・確認ポイントが見える状態をつくります。</p>
      </article>
      <article class="info-card">
        <span class="info-card__eyebrow">EXAMPLE</span>
        <h3>対応できる相談例</h3>
        <ul>
          <li>商品やサービスのPR配信相談</li>
          <li>SNSを絡めたキャンペーン施策</li>
          <li>イベント出演や告知協力の調整</li>
          <li>継続コラボ・アンバサダー運用</li>
        </ul>
      </article>
    </div>
  </div>
</section>
<section class="content-section contact-band">
  <div class="container contact-band__inner">
    <div>
      <div class="eyebrow"><span></span>NEXT STEP</div>
      <h2 class="section-title narrow">まずは目的や課題感から、ご相談ください。</h2>
      <p class="section-text">案件の規模や施策の温度感が決まり切っていない段階でも大丈夫です。相談しやすい入口を用意しています。</p>
    </div>
    <div class="contact-band__actions">
      <a class="btn btn-primary" href="contact.php">総合窓口へ進む</a>
      <a class="btn btn-secondary" href="service.php">支援内容を見る</a>
    </div>
  </div>
</section>
<?php render_footer(); ?>
