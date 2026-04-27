<?php
require __DIR__ . '/includes/layout.php';

$siteTitle = 'Contact | Business Matching | CORO PROJECT';
$contactLink = '../contact.php?source=business-matching';

render_header('contact', $siteTitle, [
  'page_name' => 'Contact | Business Matching | CORO PROJECT',
  'canonical' => 'https://coroproject.jp/business-matching/contact.php',
  'description' => 'Business Matchingへのお問い合わせ窓口です。案件相談、キャスティング、PR施策、タイアップ企画などを受け付けています。',
]);
?>
<section class="hero page-hero">
  <div class="container hero-inner hero-inner--page">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span></span> CONTACT</div>
      <h1 class="hero-title page-title">
        <span class="line solid smaller">Business Matching の相談を</span>
        <span class="line outline smaller">すぐに送れる</span>
        <span class="line accent smaller">専用導線です。</span>
      </h1>
      <p class="hero-lead">案件相談、キャスティング、PR施策、タイアップ企画などのご相談を受け付けています。ここから送った内容は総合窓口と同じ管理画面に入り、流入元が Business Matching として記録されます。</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= h($contactLink) ?>">問い合わせフォームへ</a>
        <a class="btn btn-secondary" href="mailto:info@coroproject.jp">メールで相談</a>
      </div>
    </div>
    <aside class="hero-panel reveal delay-1">
      <div class="panel-frame">
        <div class="panel-label">CONTACT / BUSINESS MATCHING</div>
        <div class="panel-card">
          <span class="panel-kicker">CONTACT</span>
          <h2>相談内容が固まっていなくても大丈夫です。</h2>
          <p>施策の方向性が未確定でも、目的や検討中の内容があれば十分です。担当者が内容を整理し、必要に応じて最適な提案に繋げます。</p>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="content-section contact-band single-contact-band">
  <div class="container contact-band__inner">
    <div>
      <div class="eyebrow"><span></span>CONTACT FLOW</div>
      <h2 class="section-title narrow">Business Matching の内容で<br>そのまま送信できます。</h2>
      <p class="section-text">送信後は管理画面で一覧管理されるため、総合問い合わせと混ざって見失うことはありません。Business Matching 経由として区別しながら確認・返信できます。</p>
    </div>
    <div class="contact-band__actions">
      <a class="btn btn-primary" href="<?= h($contactLink) ?>">総合フォームへ進む</a>
      <a class="btn btn-secondary" href="index.php">TOPへ戻る</a>
    </div>
  </div>
</section>
<?php render_footer(); ?>
