<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();
$logs = cp_fetch_activity($pdo, $creator['creator_id'], 120);

cp_start_page('操作ログ', 'ログイン、提出、ダウンロードなどの履歴です。');
?>
<section class="cp-card">
  <div class="cp-card-head">
    <div>
      <h2>履歴</h2>
      <p>身に覚えのない操作がある場合はCORO PROJECTへ連絡してください。</p>
    </div>
  </div>
  <div class="cp-table-wrap">
    <table class="cp-table">
      <thead>
        <tr>
          <th>日時</th>
          <th>操作</th>
          <th>詳細</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="4" class="cp-empty">操作ログはありません。</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td data-label="日時"><?= cp_h(cp_format_datetime($log['created_at'])) ?></td>
          <td data-label="操作"><span class="cp-chip"><?= cp_h($log['action']) ?></span></td>
          <td data-label="詳細"><?= cp_h($log['detail'] ?? '') ?></td>
          <td data-label="IP" class="cp-muted"><?= cp_h($log['ip'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php cp_end_page(); ?>
