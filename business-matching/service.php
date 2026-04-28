<?php
require __DIR__ . '/includes/layout.php';
$siteTitle = 'Service | Business Matching | CORO PROJECT';
render_header('service', $siteTitle, [
  'page_name' => 'Service | Business Matching | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business-matching/service.php',
  'description' => 'Business Matchingの支援内容一覧。VTuberキャスティング、PR施策設計、進行管理など、企業向け案件仲介のサービス内容を紹介します。'
]);
?>
<section class="hero page-hero">
  <div class="container hero-inner hero-inner--page">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span> SERVICE</div>
      <h1 class="hero-title page-title">
        <span class="line solid">企業とVTuberをつなぐ、</span><span class="line solid">3つの支援領域。</span><span class="line accent">相談から実行まで。</span>
      </h1>
      <p class="hero-lead">Business Matchingでは、キャスティングの提案、PR施策の設計、進行管理の3軸で、案件を進めやすい形に整えます。</p>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">SERVICE MENU / FLEXIBLE SUPPORT</div>
        <div class="panel-card">
          <span class="panel-kicker">SERVICE</span>
          <h2>相談窓口と、実務支援を一本化。</h2>
          <p>企業にとって負担になりやすい「誰に相談するか」「どう進めるか」をまとめ、起用先との接続から実施までを整理します。</p>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section">
  <div class="container case-grid support-cards-grid">
    <?php foreach ($bmSite['supports'] as $support): ?>
      <article class="case-card reveal">
        <span class="case-card__type"><?= h($support['eyebrow']) ?></span>
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
</section>
<section class="content-section contact-band">
  <div class="container contact-band__inner">
    <div>
      <div class="eyebrow"><span></span>SUPPORT MENU</div>
      <h2 class="section-title narrow">必要な支援だけでも、まとめてでも。</h2>
      <p class="section-text">案件の規模や体制に応じて、提案だけ、進行だけ、全体設計から一括、など柔軟に相談できます。</p>
    </div>
    <div class="contact-band__actions">
      <a class="btn btn-primary" href="contact.php">相談する</a>
      <a class="btn btn-secondary" href="flow.php">進行の流れを見る</a>
    </div>
  </div>
</section>
<?php render_footer(); ?>
