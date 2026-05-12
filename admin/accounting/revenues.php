<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) ? $_POST['action'] : '') === 'delete') {
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    if ($id > 0) {
        accounting_delete_revenue($pdo, $id);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_revenue', $id, '収益データを削除しました');
        set_flash('success', '収益データを削除しました。');
    }
    redirect_to($baseUrl . '/accounting/revenues.php');
}

$q         = trim(isset($_GET['q']) ? $_GET['q'] : '');
$rows      = accounting_fetch_all_revenues_with_status($pdo, $q);
$settings  = load_app_settings($pdo, $config);
$fxRate    = (float)$settings['fx_default_rate'];
$threshold = accounting_threshold_yen();
$pendingSummaries = ($q === '') ? accounting_get_pending_summaries($pdo, $fxRate) : [];

start_page('収益入力', '収益の登録・請求状況を管理します。');
?>
<main class="page-container">

  <section class="page-header-block with-actions">
    <div>
      <h1>収益入力</h1>
      <p>登録した収益は請求書の計算に使われます。請求待ちの状況は下のサマリーで確認できます。</p>
    </div>
    <a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/revenue_edit.php">新しく収益を登録する</a>
  </section>

  <?php if ($pendingSummaries): ?>

  <div class="card form-card pending-summary">
    <div class="pending-summary-head">
      <span class="pending-summary-title">請求待ちサマリー</span>
      <span class="pending-summary-note">概算（<?= h(number_format($fxRate, 0)) ?> 円/USD・各タレントの取り分率で計算）</span>
    </div>
    <div class="pending-cards">
      <?php foreach ($pendingSummaries as $s):
        $canInvoice  = $s['estimated_jpy'] >= $threshold;
        $monthLabels = implode('・', array_map(function ($m) {
          list($y, $mo) = explode('-', $m);
          return $y . '年' . (int)$mo . '月';
        }, $s['months']));
      ?>
        <div class="pending-card <?= $canInvoice ? 'pending-card--ready' : 'pending-card--carry' ?>">
          <div class="pending-card-name"><?= h($s['invoice_name']) ?></div>
          <div class="pending-card-amount">¥<?= h(format_money($s['estimated_jpy'])) ?></div>
          <div class="pending-card-months">
            <?= h(count($s['months'])) ?>ヶ月分未請求
            <span style="margin-left:4px;font-size:10px;"><?= h($monthLabels) ?></span>
          </div>
          <?php if ($canInvoice): ?>
            <span class="pending-card-badge">請求可能</span>
            <a class="primary-btn"
               href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=revenue&division=production&talent_id=<?= urlencode($s['talent_id']) ?>">
              請求書を作成する →
            </a>
          <?php else: ?>
            <span class="pending-card-badge">次月繰越</span>
            <span style="font-size:11px;color:var(--sub);">
              合計 ¥<?= h(format_money($threshold)) ?> 未満のため発行できません
            </span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php elseif ($q === '' && !$rows): ?>

  <div class="card form-card" style="color:var(--sub);font-size:.88em;">
    請求待ちの収益はありません。
  </div>

  <?php endif; ?>

  <form method="get" class="card form-card form-grid two mt-24">
    <label>
      <span>タレント名・メモで検索</span>
      <input type="text" name="q" value="<?= h($q) ?>">
    </label>
    <div class="actions-inline" style="align-self:end;">
      <button class="ghost-btn" type="submit">検索する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/revenues.php">条件をリセット</a>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>タレント</th>
            <th>年月</th>
            <th>通貨</th>
            <th class="text-right">配信</th>
            <th class="text-right">グッズ</th>
            <th class="text-right">スポンサー</th>
            <th class="text-right">合計</th>
            <th>状態</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="9" class="empty-state">まだ収益データがありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row):
            $sum = (float)$row['amount_streaming'] + (float)$row['amount_goods'] + (float)$row['amount_sponsor'];
          ?>
            <tr>
              <td><?= h($row['invoice_name']) ?></td>
              <td><?= h(sprintf('%04d-%02d', $row['year'], $row['month'])) ?></td>
              <td><?= h($row['currency']) ?></td>
              <td class="text-right"><?= h(format_money($row['amount_streaming'], 2)) ?></td>
              <td class="text-right"><?= h(format_money($row['amount_goods'], 2)) ?></td>
              <td class="text-right"><?= h(format_money($row['amount_sponsor'], 2)) ?></td>
              <td class="text-right"><?= h(format_money($sum, 2)) ?></td>
              <td>
                <?php if ($row['is_invoiced']): ?>
                  <span class="status-badge success">請求済</span>
                <?php else: ?>
                  <span class="status-badge warning">未請求</span>
                <?php endif; ?>
              </td>
              <td class="actions-inline">
                <a class="ghost-btn"
                   href="<?= h($baseUrl) ?>/accounting/revenue_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a>
                <form method="post" data-confirm="この収益データを削除しますか？">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
                  <button class="danger-btn" type="submit">削除</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
<?php end_page(); ?>
