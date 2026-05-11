<?php
session_start();
require_once __DIR__ . '/includes/layout.php';

// セッションデータを取得
$submitted = $_SESSION['contact_submitted'] ?? false;
$topic = $_SESSION['contact_topic'] ?? '';
$company = $_SESSION['contact_company'] ?? '';
$name = $_SESSION['contact_name'] ?? '';
$email = $_SESSION['contact_email'] ?? '';
$message = $_SESSION['contact_message'] ?? '';

// セッションをクリア
unset($_SESSION['contact_submitted']);
unset($_SESSION['contact_topic']);
unset($_SESSION['contact_company']);
unset($_SESSION['contact_name']);
unset($_SESSION['contact_email']);
unset($_SESSION['contact_message']);

// 直接アクセスされた場合は contact.php にリダイレクト
if (!$submitted) {
    $homeUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    header('Location: ' . rtrim($homeUrl, '/') . '/contact.php', true, 303);
    exit;
}

render_head('お問い合わせありがとうございました', '問い合わせいただきありがとうございます。内容を確認のうえ、ご連絡させていただきます。', [
    'robots' => 'noindex, nofollow',
]);
render_header('thanks');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">THANKS</span>
        </div>
        <h1 class="sub-title">お問い合わせありがとう<br><span>ございました</span></h1>
        <p class="sub-lead">お送りいただいたお問い合わせを受け付けいたしました。内容を確認のうえ、3営業日以内にご返信差し上げます。</p>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container contact-thanks-grid reveal is-visible">
      <div class="thanks-content cyber-clip-lg">
        <div class="thanks-message">
          <h2>ご送信いただいた内容</h2>
          <div class="thanks-details">
            <div class="detail-block">
              <label class="detail-label">お問い合わせ種別</label>
              <div class="detail-value"><?= h($topic) ?></div>
            </div>

            <?php if ($company): ?>
            <div class="detail-block">
              <label class="detail-label">会社名 / 団体名</label>
              <div class="detail-value"><?= h($company) ?></div>
            </div>
            <?php endif; ?>

            <div class="detail-block">
              <label class="detail-label">お名前</label>
              <div class="detail-value"><?= h($name) ?></div>
            </div>

            <div class="detail-block">
              <label class="detail-label">メールアドレス</label>
              <div class="detail-value"><?= h($email) ?></div>
            </div>

            <div class="detail-block">
              <label class="detail-label">お問い合わせ内容</label>
              <div class="detail-value message-content">
                <?= nl2br(h($message)) ?>
              </div>
            </div>
          </div>

          <div class="thanks-note">
            <p>確認メールを <strong><?= h($email) ?></strong> へ送信いたしました。</p>
            <p>メールが届かない場合は、迷惑メールフォルダをご確認ください。</p>
          </div>
        </div>

        <div class="thanks-actions">
          <a href="/" class="primary-button cyber-clip">TOPに戻る</a>
          <a href="/contact.php" class="secondary-button cyber-clip">別件でお問い合わせ</a>
        </div>
      </div>
    </div>
  </section>
</main>

<style>
.thanks-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin: 2rem 0;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
}

.detail-block {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-label {
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  color: #999;
  letter-spacing: 0.05em;
}

.detail-value {
  font-size: 1rem;
  line-height: 1.6;
  color: #fff;
  word-break: break-word;
}

.detail-value.message-content {
  padding: 1rem;
  background: rgba(0, 0, 0, 0.3);
  border-left: 3px solid var(--accent-color, #ff69b4);
  border-radius: 4px;
  white-space: pre-wrap;
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

.thanks-note {
  margin-top: 2rem;
  padding: 1.5rem;
  background: rgba(255, 182, 193, 0.1);
  border-left: 3px solid #ffb6c1;
  border-radius: 4px;
}

.thanks-note p {
  margin: 0.5rem 0;
  font-size: 0.95rem;
}

.thanks-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2.5rem;
  flex-wrap: wrap;
}

.secondary-button {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  border: 2px solid var(--text-color, #fff);
  color: var(--text-color, #fff);
  background: transparent;
  text-decoration: none;
  font-weight: 600;
  border-radius: 4px;
  transition: all 0.3s;
  cursor: pointer;
}

.secondary-button:hover {
  background: var(--text-color, #fff);
  color: var(--bg-color, #000);
}

@media (max-width: 768px) {
  .thanks-details {
    gap: 1rem;
    padding: 1rem;
  }

  .thanks-actions {
    flex-direction: column;
  }

  .primary-button,
  .secondary-button {
    width: 100%;
    text-align: center;
  }
}
</style>

<?php render_footer(); ?>
