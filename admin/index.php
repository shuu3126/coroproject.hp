<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();

// ---- Production ----
$nowYear  = (int)date('Y');
$nowMonth = (int)date('n');
$talents  = accounting_list_talents($pdo, false);
$fxRate   = (float)load_app_settings($pdo, $config)['fx_default_rate'];

$unsubmitted  = 0;
$invoiceReady = 0;
foreach ($talents as $talent) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM accounting_revenues WHERE talent_id = ? AND year = ? AND month = ?');
    $stmt->execute([$talent['id'], $nowYear, $nowMonth]);
    if ((int)$stmt->fetchColumn() === 0) {
        $unsubmitted++;
    }
    $uninvoicedMonths = accounting_get_uninvoiced_months_upto($pdo, $talent['id'], 9999, 12);
    $sum = 0.0;
    foreach ($uninvoicedMonths as $m) {
        $sum += accounting_calc_office_share_jpy_for_month($pdo, $talent['id'], $m['year'], $m['month'], $fxRate);
    }
    if ($sum >= accounting_threshold_yen()) {
        $invoiceReady++;
    }
}
$unpaid         = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices WHERE status = 'issued'")->fetchColumn();
$receiptPending = (int)$pdo->query("SELECT COUNT(*) FROM accounting_invoices WHERE status = 'paid'")->fetchColumn();
$talentCount    = count($talents);

// ---- Business ----
$bizActive = (int)$pdo->query("SELECT COUNT(*) FROM biz_deals WHERE status NOT IN ('完了','不成立')")->fetchColumn();
$bizTotal  = (int)$pdo->query("SELECT COUNT(*) FROM biz_deals")->fetchColumn();

// ---- Creative ----
$creActive    = (int)$pdo->query("SELECT COUNT(*) FROM cre_projects WHERE status NOT IN ('完了')")->fetchColumn();
$creNearDead  = (int)$pdo->query("SELECT COUNT(*) FROM cre_projects WHERE status NOT IN ('完了') AND deadline IS NOT NULL AND deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// ---- Messages ----
$unreadMsg = 0;
if (admin_table_has_column($pdo, 'inquiries', 'status')) {
    $unreadMsg = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
}

// ---- 要対応リスト ----
$nearDeadlineProjects = $pdo->query("
    SELECT p.id, p.title, p.status, p.deadline,
           COALESCE(c.name,'—') AS client_name
    FROM cre_projects p
    LEFT JOIN clients c ON c.id = p.client_id
    WHERE p.status NOT IN ('完了') AND p.deadline IS NOT NULL
    ORDER BY p.deadline ASC LIMIT 5
")->fetchAll();

$unpaidInvoices = $pdo->query("
    SELECT i.id, i.invoice_no, i.subject, i.amount_jpy, i.division,
           COALESCE(c.name, t.name, '—') AS party_name
    FROM accounting_invoices i
    LEFT JOIN talents t ON t.id = i.talent_id
    LEFT JOIN clients c ON c.id = i.client_id
    WHERE i.status = 'issued'
    ORDER BY i.created_at ASC LIMIT 5
")->fetchAll();

// ---- 操作ログ ----
$recentLogs = $pdo->query("
    SELECT l.created_at, l.summary, l.target_type, l.action_type, l.target_id,
           COALESCE(u.display_name, 'system') AS user_name
    FROM admin_logs l
    LEFT JOIN admin_users u ON u.id = l.user_id
    ORDER BY l.created_at DESC LIMIT 10
")->fetchAll();

start_page('ダッシュボード', '全部門の状況をまとめて確認できます。');
?>
<main class="page-container">

  <!-- ===== 全部門ステータス ===== -->
  <section class="card-grid four" style="grid-template-columns:repeat(4,1fr);">
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/revenues.php">
      <div class="muted">未提出収益</div>
      <div class="stat-number"><?= h((string)$unsubmitted) ?></div>
      <p>Production / 今月未登録タレント</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php">
      <div class="muted">未入金請求</div>
      <div class="stat-number <?= $unpaid > 0 ? 'text-warning' : '' ?>"><?= h((string)$unpaid) ?></div>
      <p>全部門 / 請求済みで入金待ち</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/business/deals.php">
      <div class="muted">Business 進行中</div>
      <div class="stat-number"><?= h((string)$bizActive) ?></div>
      <p>完了・不成立を除く案件数</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/creative/projects.php">
      <div class="muted">Creative 進行中</div>
      <div class="stat-number <?= $creNearDead > 0 ? 'text-warning' : '' ?>"><?= h((string)$creActive) ?></div>
      <p>完了除く<?php if ($creNearDead > 0): ?> / <span style="color:#e53e3e;">7日以内 <?= h((string)$creNearDead) ?>件</span><?php endif; ?></p>
    </a>
  </section>

  <!-- ===== サブ指標 ===== -->
  <section class="card-grid four" style="grid-template-columns:repeat(4,1fr);margin-top:12px;">
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php">
      <div class="muted">請求可能タレント</div>
      <div class="stat-number"><?= h((string)$invoiceReady) ?></div>
      <p>Production / 5,000円以上の対象</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/accounting/invoices.php?status=paid">
      <div class="muted">領収書未発行</div>
      <div class="stat-number"><?= h((string)$receiptPending) ?></div>
      <p>入金済みで発行待ち</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/business/deals.php">
      <div class="muted">Business 累計案件</div>
      <div class="stat-number"><?= h((string)$bizTotal) ?></div>
      <p>過去分を含む全案件数</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/messages.php">
      <div class="muted">未読メッセージ</div>
      <div class="stat-number <?= $unreadMsg > 0 ? 'text-warning' : '' ?>"><?= h((string)$unreadMsg) ?></div>
      <p>サイトからのお問い合わせ</p>
    </a>
  </section>

  <!-- ===== 部門クイックリンク ===== -->
  <section class="card-grid three mt-24">
    <div class="card" style="padding:20px;">
      <h3 style="margin:0 0 12px;font-size:1em;color:var(--muted,#888);">Production</h3>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/talents.php">タレント管理（<?= h((string)$talentCount) ?>名）</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/news.php">お知らせ管理</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/accounting/revenues.php">収益入力</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/accounting/invoices.php">請求管理</a>
      </div>
    </div>
    <div class="card" style="padding:20px;">
      <h3 style="margin:0 0 12px;font-size:1em;color:var(--muted,#888);">Business</h3>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/business/deals.php">案件管理</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/business/ext_talents.php">所属外VTuberリスト</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/clients.php">クライアント管理</a>
      </div>
    </div>
    <div class="card" style="padding:20px;">
      <h3 style="margin:0 0 12px;font-size:1em;color:var(--muted,#888);">Creative</h3>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/creative/projects.php">制作案件管理</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/creative/creators.php">クリエイターリスト</a>
        <a class="ghost-btn" style="text-align:left;" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=manual&division=creative">Creative 請求書作成</a>
      </div>
    </div>
  </section>

  <!-- ===== 要対応 ===== -->
  <?php if ($nearDeadlineProjects || $unpaidInvoices): ?>
  <section class="card mt-24">
    <h3>要対応</h3>
    <div class="card-grid two" style="margin-top:12px;">

      <?php if ($nearDeadlineProjects): ?>
      <div>
        <h4 style="margin:0 0 8px;font-size:0.9em;" class="muted">Creative — 納期が近い案件</h4>
        <table class="data-table">
          <thead><tr><th>案件名</th><th>依頼者</th><th>納期</th></tr></thead>
          <tbody>
          <?php foreach ($nearDeadlineProjects as $p): ?>
            <?php $isUrgent = strtotime($p['deadline']) <= strtotime('+3 days'); ?>
            <tr>
              <td><a href="<?= h($baseUrl) ?>/creative/project_edit.php?id=<?= urlencode($p['id']) ?>"><?= h(mb_strimwidth($p['title'], 0, 25, '…')) ?></a></td>
              <td class="muted"><?= h($p['client_name']) ?></td>
              <td style="<?= $isUrgent ? 'color:#e53e3e;font-weight:600;' : '' ?>"><?= h($p['deadline']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php if ($unpaidInvoices): ?>
      <div>
        <h4 style="margin:0 0 8px;font-size:0.9em;" class="muted">未入金請求書（古い順）</h4>
        <table class="data-table">
          <thead><tr><th>請求書番号</th><th>宛先</th><th>金額</th></tr></thead>
          <tbody>
          <?php foreach ($unpaidInvoices as $inv): ?>
            <tr>
              <td><a href="<?= h($baseUrl) ?>/accounting/invoice_detail.php?id=<?= urlencode((string)$inv['id']) ?>"><?= h($inv['invoice_no']) ?></a></td>
              <td class="muted"><?= h(mb_strimwidth($inv['party_name'], 0, 18, '…')) ?></td>
              <td class="text-right">¥<?= h(format_money($inv['amount_jpy'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

  <!-- ===== 操作ログ ===== -->
  <section class="card table-card mt-24">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--line);">
      <h3 style="margin:0;font-size:.95em;">最近の操作</h3>
      <a class="ghost-btn" style="font-size:0.8em;padding:4px 10px;" href="<?= h($baseUrl) ?>/logs.php">すべて見る</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>日時</th>
            <th>ユーザー</th>
            <th>操作</th>
            <th>対象</th>
            <th>概要</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$recentLogs): ?>
            <tr><td colspan="5" class="empty-state">まだ操作ログがありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($recentLogs as $log): ?>
            <tr>
              <td class="muted" style="white-space:nowrap;"><?= h(format_datetime($log['created_at'])) ?></td>
              <td><?= h($log['user_name']) ?></td>
              <td class="muted"><?= h($log['action_type'] ?? '') ?></td>
              <td class="muted"><?= h($log['target_type'] ?? '') ?><?= !empty($log['target_id']) ? ' #' . h((string)$log['target_id']) : '' ?></td>
              <td><?= h($log['summary']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>
<?php require __DIR__ . '/_footer.php'; ?>
