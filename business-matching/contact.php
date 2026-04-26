<?php
require __DIR__ . '/includes/layout.php';
$siteTitle = 'Contact | Business Matching | CORO PROJECT';
render_header('contact', $siteTitle, [
  'page_name' => 'Contact | Business Matching | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business-matching/contact.php',
  'description' => 'Business Matchingのお問い合わせページ。VTuber起用、PR施策、イベント出演、タイアップなどの企業向け案件相談を受け付けています。'
]);
?>
<section class="hero page-hero">
  <div class="container hero-inner hero-inner--page">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span> CONTACT</div>
      <h1 class="hero-title page-title">
        <span class="line solid smaller">案件相談・起用相談を、</span><span class="line outline smaller">受け付けています。</span><span class="line accent smaller">まずは目的段階からでも。</span>
      </h1>
      <p class="hero-lead">商品PR、イベント出演、SNS施策、タイアップ企画など、まずは目的やイメージ段階からご相談ください。小規模案件や初回相談も歓迎しています。</p>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">CONTACT / GENERAL DESK</div>
        <div class="panel-card">
          <span class="panel-kicker">CONTACT</span>
          <h2>相談前の整理から伴走します。</h2>
          <p>VTuber業界に不慣れな企業でも進めやすいよう、施策の目的整理や相談内容の切り分けからサポートします。</p>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section contact-band single-contact-band">
  <div class="container contact-band__inner">
    <div>
      <div class="eyebrow"><span></span>CONTACT FORM</div>
      <h2 class="section-title narrow">総合窓口から、案件相談へ。</h2>
      <p class="section-text">案件の規模や進め方によって最適な提案内容が変わるため、料金や実施フローはお問い合わせベースでご案内しています。まずは相談内容や目的を共有ください。</p>
    </div>
    <div class="contact-band__actions">
      <a class="btn btn-primary" href="../contact.php">総合窓口へ進む</a>
      <a class="btn btn-secondary" href="index.php">TOPへ戻る</a>
    </div>
  </div>
</section>
<?php render_footer(); ?>
