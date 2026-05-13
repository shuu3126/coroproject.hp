<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent  = current_portal_talent();
$history = portal_fetch_revenue_history($pdo, $talent['talent_id']);

$portalPageTitle = '提出履歴';
require __DIR__ . '/_header.php';
?>

<h1 class="portal-page-title">提出履歴</h1>
<p class="portal-page-desc">過去に送信したすべての月次収益報告の一覧です。</p>

<div style="margin-bottom:16px;">
  <a class="portal-btn portal-btn-primary portal-btn-sm" href="<?= portal_h($portalBase) ?>/submit.php">
    + 新しく報告する
  </a>
</div>

<div class="portal-card">
  <?php if (!$history): ?>
    <div class="portal-table-empty">まだ提出履歴がありません。</div>
  <?php else: ?>
  <div class="portal-table-wrap">
    <table class="portal-table">
      <thead>
        <tr>
          <th>年月</th>
          <th>通貨</th>
          <th class="text-right">配信</th>
          <th class="text-right">グッズ</th>
          <th class="text-right">スポンサー</th>
          <th class="text-right">合計</th>
          <th>状態</th>
          <th>エビデンス</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $row):
          $st  = portal_revenue_status($row['status']);
          $sum = (float)$row['amount_streaming'] + (float)$row['amount_goods'] + (float)$row['amount_sponsor'];
          $canEdit = !$row['is_invoiced'] && in_array($row['status'], ['pending', 'rejected']);
        ?>
          <tr>
            <td><?= portal_h(sprintf('%04d年%d月', $row['year'], $row['month'])) ?></td>
            <td><?= portal_h($row['currency']) ?></td>
            <td class="text-right"><?= portal_h(portal_format_money($row['amount_streaming'])) ?></td>
            <td class="text-right"><?= portal_h(portal_format_money($row['amount_goods'])) ?></td>
            <td class="text-right"><?= portal_h(portal_format_money($row['amount_sponsor'])) ?></td>
            <td class="text-right"><strong><?= portal_h(portal_format_money($sum)) ?></strong></td>
            <td>
              <span class="badge <?= portal_h($st['class']) ?>"><?= portal_h($st['label']) ?></span>
              <?php if ($row['is_invoiced']): ?>
                <span class="badge badge-info" style="margin-left:4px;">請求済</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['evidence_path']): ?>
                <a class="portal-btn portal-btn-outline portal-btn-sm"
                   href="<?= portal_h($portalBase) ?>/download.php?type=evidence&id=<?= (int)$row['id'] ?>"
                   target="_blank" rel="noopener">確認</a>
              <?php else: ?>
                <span style="color:var(--muted);font-size:12px;">なし</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($canEdit): ?>
                <a class="portal-btn portal-btn-outline portal-btn-sm"
                   href="<?= portal_h($portalBase) ?>/submit.php?year=<?= (int)$row['year'] ?>&month=<?= (int)$row['month'] ?>">
                  修正
                </a>
              <?php elseif (!$row['is_invoiced'] && $row['status'] === 'confirmed'): ?>
                <span style="color:var(--muted);font-size:11px;">確定済</span>
              <?php else: ?>
                <span style="color:var(--muted);font-size:11px;">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="portal-card" style="background:rgba(159,139,255,.05);">
  <div style="font-size:12px;color:var(--sub);">
    <strong style="color:var(--accent-2);">状態の説明</strong><br>
    <span class="badge badge-warning">確認待ち</span> 送信済み、管理者が確認中<br>
    <span class="badge badge-success" style="margin-top:4px;">確定済</span> 管理者が内容を確認・承認<br>
    <span class="badge badge-danger" style="margin-top:4px;">要修正</span> 内容に不備あり、修正して再送信してください
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
