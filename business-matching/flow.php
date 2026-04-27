<?php
require __DIR__ . '/includes/layout.php';
$siteTitle = 'Flow | Business Matching | CORO PROJECT';
render_header('flow', $siteTitle, [
  'page_name' => 'Flow | Business Matching | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business-matching/flow.php',
  'description' => 'Business Matchingの案件相談フロー。目的整理、候補提案、条件調整、進行管理まで、企業向けVTuber案件の進め方をわかりやすく案内します。'
]);
?>
<section class="hero page-hero">
  <div class="container hero-inner hero-inner--page">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span> FLOW</div>
      <h1 class="hero-title page-title">
        <span class="line solid">ご相談から実施まで、</span><span class="line solid">整理しながら</span><span class="line accent">進めます。</span>
      </h1>
      <p class="hero-lead">初めてのVTuber施策でも進めやすいように、段階ごとに必要な確認事項を整理しながら伴走します。</p>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">HOW WE MOVE</div>
        <div class="panel-card">
          <span class="panel-kicker">FLOW</span>
          <h2>確認の抜けを減らし、動きやすい進行をつくる。</h2>
          <p>相談 → 提案 → 条件調整 → 実施という流れの中で、役割と確認ポイントを分かりやすく整えます。</p>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section">
  <div class="container">
    <div class="flow-grid large-flow-grid">
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
<section class="content-section">
  <div class="container section-grid">
    <div class="info-card reveal">
      <span class="info-card__eyebrow">THINKING</span>
      <h3>進め方の考え方</h3>
      <p>相談の段階では、やりたいことが完全に固まっていなくても問題ありません。目的、相性、予算感、実施時期などを一緒に整理しながら、必要な形へ落とし込みます。</p>
    </div>
    <div class="info-card reveal delay-1">
      <span class="info-card__eyebrow">EXAMPLE</span>
      <h3>よくある相談例</h3>
      <ul>
        <li>何を依頼できるのかから知りたい</li>
        <li>小規模でも相談できるか確認したい</li>
        <li>投稿内容や配信の見せ方も相談したい</li>
        <li>継続案件として進められるか聞きたい</li>
      </ul>
    </div>
  </div>
</section>
<?php render_footer(); ?>
