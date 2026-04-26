<?php
require_once __DIR__ . '/includes/layout.php';
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
render_head('CONTACT', '企業案件、制作相談、所属相談などの問い合わせ窓口です。');
render_header('contact');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">CONTACT NODE</span>
        </div>
        <h1 class="sub-title">相談内容を整理し、<br><span>必要な窓口へ接続します。</span></h1>
        <p class="sub-lead">企業案件、PR・タイアップ、イベント出演、制作依頼、所属相談、活動相談、クリエイター提携、取材、業務提携など、内容に応じて適切な窓口へご案内します。まずは総合フォームからご相談ください。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">REPLY: 3 BUSINESS DAYS</span>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container contact-grid reveal is-visible">
      <div class="contact-info cyber-clip-lg">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">GUIDE</span>
        </div>
        <h2 class="content-title small">お問い合わせ前のご案内</h2>
        <div class="stack-md contact-copy">
          <p>ご相談内容が固まっていない段階でも問題ありません。目的や困りごとが曖昧でも、整理の段階からサポートします。</p>
          <ul class="info-list">
            <li>返信目安は通常3営業日以内です。</li>
            <li>企業案件、制作相談、所属相談、活動相談、取材、業務提携なども含め、すべて総合フォームから受付します。</li>
            <li>具体的な予算や納期が未定でもご相談可能です。</li>
          </ul>
        </div>
        <div class="contact-aside-block">
          <h3>対応可能な内容</h3>
          <p>案件相談、キャスティング、PR施策、イベント出演、制作依頼、動画・MV関連、所属相談、活動相談、クリエイター提携、取材、業務提携、その他CORO PROJECTに関するお問い合わせ。</p>
        </div>
        <div class="contact-aside-block">
          <h3>注意事項</h3>
          <p>内容によっては確認にお時間をいただく場合があります。営業・売り込みのみを目的としたご連絡には返信できない場合があります。</p>
        </div>
      </div>

      <div class="contact-form-wrap cyber-clip-lg">
        <?php if ($submitted): ?>
          <div class="success-box cyber-clip">
            <strong>送信を受け付けました。</strong>
            <p>このデモ版では実送信は行っていませんが、フォームUIと導線の確認ができます。</p>
          </div>
        <?php endif; ?>
        <form method="post" class="contact-form">
          <label>
            <span>お問い合わせ種別</span>
            <select name="topic" required>
              <?php foreach ($contactTopics as $topic): ?>
                <option value="<?= h($topic) ?>"><?= h($topic) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="field-note">該当する項目が近いものを選んでください。迷う場合は「その他」で問題ありません。</small>
          </label>
          <label>
            <span>会社名 / 団体名</span>
            <input type="text" name="company" placeholder="任意">
          </label>
          <label>
            <span>お名前</span>
            <input type="text" name="name" required>
          </label>
          <label>
            <span>メールアドレス</span>
            <input type="email" name="email" required>
          </label>
          <label>
            <span>お問い合わせ内容</span>
            <textarea name="message" rows="6" required></textarea>
          </label>
          <button type="submit" class="primary-button cyber-clip">SEND SIGNAL</button>
        </form>
      </div>
    </div>
  </section>
</main>
<?php render_footer(); ?>
