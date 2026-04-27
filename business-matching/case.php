<?php
require __DIR__ . '/includes/layout.php';
$siteTitle = 'Case | Business Matching | CORO PROJECT';
render_header('case', $siteTitle, [
  'page_name' => 'Case | Business Matching | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business-matching/case.php',
  'description' => 'Business Matchingの案件事例ページ。企業やブランド向けに、VTuber起用やPR施策をどのように設計・実施しているかを紹介します。'
]);
?>
<section class="hero page-hero">
  <div class="container hero-inner hero-inner--page">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span> CASE</div>
      <h1 class="hero-title page-title">
        <span class="line solid">どんな施策に向いているか、</span><span class="line solid">対応イメージで</span><span class="line accent">整理する。</span>
      </h1>
      <p class="hero-lead">PR配信、イベント出演、継続コラボなど、目的に応じて施策の組み方を調整します。まずは「こういうことは相談できるのか」の目安としてご覧ください。</p>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">CASE BOARD</div>
        <div class="panel-card">
          <span class="panel-kicker">CASE</span>
          <h2>単発施策から継続企画まで。</h2>
          <p>ブランドの認知拡大、イベント動員、SNS拡散、継続的な接点づくりなど、目的に応じて設計します。</p>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section">
  <div class="container case-grid">
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
      <article class="case-card reveal">
        <span class="case-card__type">SMALL START</span>
        <h3>まずは小さく始める相談</h3>
        <p class="case-card__client">企業 / 店舗 / 自治体</p>
        <p>いきなり大規模にせず、1本の投稿や1回の配信からテスト的に始める相談にも対応しています。</p>
        <ul>
          <li>スモールスタート設計</li>
          <li>実施後の振り返り支援</li>
          <li>次回施策への接続</li>
        </ul>
      </article>
  </div>
</section>
<section class="content-section">
  <div class="container section-grid">
    <div class="info-card reveal">
      <span class="info-card__eyebrow">向いている相談</span>
      <ul>
        <li>新商品の認知を広げたい</li>
        <li>イベントや店舗の接点を増やしたい</li>
        <li>SNSだけでなく配信も絡めたい</li>
        <li>長期的な起用の相性を探りたい</li>
      </ul>
    </div>
    <div class="info-card reveal delay-1">
      <span class="info-card__eyebrow">相談時にあると進めやすいもの</span>
      <ul>
        <li>施策の目的や背景</li>
        <li>想定している時期や期間</li>
        <li>予算感の目安</li>
        <li>ブランドやサービスの概要資料</li>
      </ul>
    </div>
  </div>
</section>
<?php render_footer(); ?>
