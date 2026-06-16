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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($action === 'uninvoice') {
            $rev = accounting_fetch_revenue($pdo, $id);
            if ($rev) {
                $pdo->prepare('DELETE FROM accounting_invoiced_months WHERE talent_id = ? AND year = ? AND month = ?')
                    ->execute([(string)$rev['talent_id'], (int)$rev['year'], (int)$rev['month']]);
                write_admin_log($pdo, (int)$user['id'], 'update', 'accounting_revenue', $id,
                    sprintf('収益を未請求に戻しました（%d年%d月）', $rev['year'], $rev['month']));
                set_flash('success', sprintf('%d年%d月の収益を未請求に戻しました。請求待ちサマリーから新しい請求書を作成してください。', $rev['year'], $rev['month']));
            }
        } elseif ($action === 'confirm') {
            if (accounting_portal_confirm_revenue($pdo, $id, (int)$user['id'])) {
                write_admin_log($pdo, (int)$user['id'], 'update', 'accounting_revenue', $id, '収益データを承認しました');
                set_flash('success', '収益データを承認しました。');
            } else {
                set_flash('error', '承認に失敗しました。');
            }
        } elseif ($action === 'reject') {
            $note = trim($_POST['reject_note'] ?? '');
            if (accounting_portal_reject_revenue($pdo, $id, (int)$user['id'], $note)) {
                write_admin_log($pdo, (int)$user['id'], 'update', 'accounting_revenue', $id, '収益データを却下しました');
                set_flash('success', '収益データを却下しました。');
            } else {
                set_flash('error', '却下に失敗しました。');
            }
        }
    }
    redirect_to($baseUrl . '/accounting/revenues.php');
}

$q         = trim(isset($_GET['q']) ? $_GET['q'] : '');
$rows      = accounting_fetch_all_revenues_with_status($pdo, $q);
$settings  = load_app_settings($pdo, $config);
$fxRate    = accounting_get_live_fx_rate($pdo, $config, $settings);
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
    <div class="actions-inline">
      <a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/revenue_edit.php">新しく収益を登録する</a>
    </div>
  </section>

  <?php if ($pendingSummaries): ?>

  <div class="card form-card pending-summary">
    <div class="pending-summary-head">
      <span class="pending-summary-title">請求待ちサマリー</span>
      <span class="pending-summary-note">
        <?php
          $cachedAt = $settings['fx_cached_at'] ?? '';
          $isLive = $cachedAt !== '' && (time() - strtotime($cachedAt)) < 21600;
        ?>
        概算（<?= h(number_format($fxRate, 2)) ?> 円/USD<?= $isLive ? '・最新レート' : '・デフォルトレート' ?>・各タレントの取り分率で計算）
      </span>
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
            <th>ポータル</th>
            <th>提出情報</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="11" class="empty-state">まだ収益データがありません。</td></tr>
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
              <td>
                <?php
                $portalStatus = $row['status'] ?? 'confirmed';
                if ($portalStatus === 'pending') {
                    echo '<span class="status-badge warning">確認待ち</span>';
                } elseif ($portalStatus === 'confirmed') {
                    echo '<span class="status-badge success">確定済</span>';
                } elseif ($portalStatus === 'rejected') {
                    echo '<span class="status-badge danger">要修正</span>';
                } else {
                    echo '<span class="status-badge muted">' . h($portalStatus) . '</span>';
                }
                ?>
              </td>
              <td>
                <?php if (!empty($row['evidence_path'])): ?>
                  <a class="ghost-btn"
                     href="<?= h($baseUrl) ?>/download.php?kind=revenue_evidence&id=<?= (int)$row['id'] ?>"
                     target="_blank" rel="noopener"
                     style="font-size:11px;padding:4px 8px;">証拠</a>
                <?php endif; ?>
                <?php if (!empty($row['portal_note'])): ?>
                  <span class="status-badge muted" title="<?= h($row['portal_note']) ?>">コメントあり</span>
                <?php endif; ?>
                <?php if (empty($row['evidence_path']) && empty($row['portal_note'])): ?>
                  <span style="color:var(--text-dim);font-size:12px;">-</span>
                <?php endif; ?>
              </td>
              <td class="actions-inline">
                <?php if (($row['status'] ?? 'confirmed') === 'pending'): ?>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
                    <button class="primary-btn" type="submit" style="font-size:11px;padding:4px 8px;">承認</button>
                  </form>
                  <button class="danger-btn" type="button" style="font-size:11px;padding:4px 8px;"
                          onclick='rejectRevenue(<?= (int)$row['id'] ?>, <?= h(json_encode($row['invoice_name'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)'>却下</button>
                <?php endif; ?>
                <a class="ghost-btn"
                   href="<?= h($baseUrl) ?>/accounting/revenue_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a>
                <?php if ($row['is_invoiced']): ?>
                  <form method="post" data-confirm="<?= h(sprintf('%d年%d月の請求済みフラグを解除しますか？複数月まとめて再請求する場合は対象月すべてで実行してください。', $row['year'], $row['month'])) ?>">
                    <input type="hidden" name="action" value="uninvoice">
                    <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
                    <button class="warning-btn" type="submit" style="font-size:11px;padding:4px 8px;">未請求に戻す</button>
                  </form>
                <?php endif; ?>
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

<script>
function rejectRevenue(id, name) {
    const note = prompt('却下理由を入力してください（任意）：\n' + name + ' の収益データを却下します。');
    if (note !== null) {
        const form = document.createElement('form');
        form.method = 'post';
        const addHidden = (fieldName, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = fieldName;
            input.value = value;
            form.appendChild(input);
        };
        addHidden('action', 'reject');
        addHidden('id', id);
        addHidden('reject_note', note);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php end_page(); ?>
