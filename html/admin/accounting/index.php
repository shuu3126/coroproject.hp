<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_admin_login();
start_page('会計システム', '収益入力、請求管理、会計一覧をこの画面群で操作します。');
?>
<main class="page-container">
  <section class="card-grid three">
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/revenues.php"><h3>収益入力</h3><p>月次収益の登録・編集・削除を行います。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/invoices.php"><h3>請求管理</h3><p>請求書作成、入金確認、領収書発行を行います。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/accounting/journals.php"><h3>会計一覧</h3><p>記帳の確認、手入力、証憑の管理を行います。</p></a>
  </section>
</main>
<?php end_page(); ?>
