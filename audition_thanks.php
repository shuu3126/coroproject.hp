<?php
session_start();
require_once __DIR__ . '/includes/layout.php';

$submitted  = $_SESSION['audition_submitted'] ?? false;
$vtuberName = $_SESSION['audition_vtuber_name'] ?? '';

unset($_SESSION['audition_submitted'], $_SESSION['audition_vtuber_name']);

if (!$submitted) {
    $homeUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    header('Location: ' . rtrim($homeUrl, '/') . '/audition.php', true, 303);
    exit;
}

render_head('オーディションご応募ありがとうございました', 'CORO PROJECT VTuberオーディションへのご応募を受け付けました。審査後、メールにてご連絡いたします。', [
    'robots' => 'noindex, nofollow',
]);
render_header('');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">THANKS</span>
        </div>
        <h1 class="sub-title">ご応募ありがとう<br><span>ございました</span></h1>
        <p class="sub-lead">VTuberオーディションへのご応募を受け付けました。内容を確認のうえ、1〜2週間を目安にメールにてご連絡いたします。</p>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container contact-thanks-grid reveal is-visible">
      <div class="thanks-content cyber-clip-lg">
        <div class="thanks-message">
          <?php if ($vtuberName): ?>
            <h2><?= h($vtuberName) ?> 様、ご応募ありがとうございます</h2>
          <?php else: ?>
            <h2>ご応募ありがとうございます</h2>
          <?php endif; ?>
          <p>ご応募内容を受け付けいたしました。</p>
          <ul class="info-list" style="margin-top:16px;">
            <li>受付確認メールをご登録のアドレスへ送信しました。</li>
            <li>審査結果は1〜2週間を目安にメールにてお知らせします。</li>
            <li>書類審査後、面談をお願いする場合がございます。</li>
            <li>メールが届かない場合は迷惑メールフォルダをご確認ください。</li>
          </ul>
        </div>

        <div class="thanks-actions" style="display:flex;gap:1rem;margin-top:2.5rem;flex-wrap:wrap;">
          <a href="index.php" class="primary-button cyber-clip">TOPに戻る</a>
          <a href="audition.php" class="secondary-button cyber-clip" style="display:inline-block;padding:.75rem 1.5rem;border:2px solid #fff;color:#fff;background:transparent;text-decoration:none;font-weight:600;border-radius:4px;">別アカウントで応募する</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php render_footer(); ?>
