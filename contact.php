<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/inquiries.php';

$contactSource = inquiry_normalize_source((string)($_GET['source'] ?? 'general'));
$submitted = isset($_GET['sent']) && $_GET['sent'] === '1';
$selectedTopic = trim((string)($_GET['topic'] ?? ''));
$returnTo = '../contact.php?sent=1&source=' . rawurlencode($contactSource);

render_head('CONTACT', 'CORO PROJECTへのお問い合わせ窓口です。事業相談、制作依頼、出演・PR相談などをまとめて受け付けています。');
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
        <h1 class="sub-title">相談したい内容を整理して<br><span>そのまま送れる窓口です。</span></h1>
        <p class="sub-lead">総合ページ、Business Matching、Production など、各ページからの相談をまとめて受け付ける共通窓口です。内容に応じて担当へ振り分け、管理画面から確認・返信できる形で記録します。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">SOURCE: <?= h(inquiry_source_label($contactSource)) ?></span>
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
          <p>事業相談、タイアップ、制作依頼、出演やPRのご相談など、用途が固まっていなくても問題ありません。わかる範囲で記入いただければ、内容を整理したうえで担当から折り返します。</p>
          <ul class="info-list">
            <li>通常は3営業日以内を目安に返信します。</li>
            <li>各事業部ページから来た問い合わせも、この窓口でまとめて管理されます。</li>
            <li>急ぎの場合は本文冒頭に希望時期や締切を書いてください。</li>
          </ul>
        </div>
        <div class="contact-aside-block">
          <h3>対応できる内容</h3>
          <p>企業案件、キャスティング、制作依頼、イベント出演、Productionに関する相談、その他のお問い合わせまで受け付けています。</p>
        </div>
        <div class="contact-aside-block">
          <h3>補足いただけると助かる項目</h3>
          <p>参考URL、希望時期、予算感、想定している施策やアウトプットがあれば、初回返信までがよりスムーズになります。</p>
        </div>
      </div>

      <div class="contact-form-wrap cyber-clip-lg">
        <?php if ($submitted): ?>
          <div class="success-box cyber-clip">
            <strong>お問い合わせを受け付けました。</strong>
            <p>内容は管理画面にも保存され、担当者が確認できる状態になっています。順番に返信しますので、少しだけお待ちください。</p>
          </div>
        <?php endif; ?>

        <form method="post" action="api/contact.php" class="contact-form">
          <input type="hidden" name="source" value="<?= h($contactSource) ?>">
          <input type="hidden" name="return_to" value="<?= h($returnTo) ?>">

          <label>
            <span>お問い合わせ種別</span>
            <select name="topic" required>
              <?php foreach ($contactTopics as $topic): ?>
                <option value="<?= h($topic) ?>" <?= (string)$topic === $selectedTopic ? 'selected' : '' ?>><?= h($topic) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="field-note">近い内容を選んでいただければ十分です。迷う場合は「その他」で問題ありません。</small>
          </label>

          <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;">
            <label>
              <span>Company</span>
              <input type="text" name="company" autocomplete="off" tabindex="-1">
            </label>
          </div>

          <label>
            <span>お名前</span>
            <input type="text" name="name" required>
          </label>

          <label>
            <span>メールアドレス</span>
            <input type="email" name="email" required>
          </label>

          <label>
            <span>参考URL</span>
            <input type="url" name="url" placeholder="https://example.com">
          </label>

          <label>
            <span>お問い合わせ内容</span>
            <textarea name="message" rows="6" required></textarea>
          </label>

          <label class="checkbox-row">
            <input type="checkbox" name="agree" required>
            <span>プライバシーポリシーに同意して送信します。</span>
          </label>

          <button type="submit" class="primary-button cyber-clip">SEND SIGNAL</button>
        </form>
      </div>
    </div>
  </section>
</main>
<?php render_footer(); ?>
