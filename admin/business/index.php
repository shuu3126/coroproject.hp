<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$totalDeals    = (int)$pdo->query("SELECT COUNT(*) FROM biz_deals")->fetchColumn();
$activeDeals   = (int)$pdo->query("SELECT COUNT(*) FROM biz_deals WHERE status NOT IN ('完了','不成立')")->fetchColumn();
$totalClients  = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$extTalents    = (int)$pdo->query("SELECT COUNT(*) FROM biz_ext_talents")->fetchColumn();

$recentDeals = $pdo->query("SELECT d.id, d.title, d.status, d.updated_at, COALESCE(c.name,'—') AS client_name FROM biz_deals d LEFT JOIN clients c ON c.id = d.client_id ORDER BY d.updated_at DESC LIMIT 8")->fetchAll();

start_page('Business ダッシュボード', 'ビジネスマッチング事業部の状況を確認できます。');
?>
<main class="page-container">
  <section class="card-grid two">
    <a class="card stat-card" href="<?= h($baseUrl) ?>/business/deals.php">
      <div class="muted">進行中案件</div><div class="stat-number"><?= h((string)$activeDeals) ?></div><p>完了・不成立を除く案件数</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/business/deals.php">
      <div class="muted">総案件数</div><div class="stat-number"><?= h((string)$totalDeals) ?></div><p>過去分を含む全案件</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/clients.php">
      <div class="muted">クライアント数</div><div class="stat-number"><?= h((string)$totalClients) ?></div><p>登録済みクライアント</p>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/business/ext_talents.php">
      <div class="muted">所属外VTuber</div><div class="stat-number"><?= h((string)$extTalents) ?></div><p>候補リスト登録数</p>
    </a>
  </section>

  <section class="card-grid three mt-24">
    <a class="card menu-card" href="<?= h($baseUrl) ?>/business/deals.php"><h3>案件管理</h3><p>全案件の一覧・ステータス管理・候補VTuber設定を行います。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/business/ext_talents.php"><h3>所属外VTuberリスト</h3><p>案件候補として提案できる外部VTuberを管理します。</p></a>
    <a class="card menu-card" href="<?= h($baseUrl) ?>/clients.php"><h3>クライアント管理</h3><p>企業・個人・団体の連絡先と取引履歴を管理します。</p></a>
  </section>

  <?php if ($recentDeals): ?>
  <section class="card mt-24">
    <h3>最近の案件</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>案件名</th><th>クライアント</th><th>ステータス</th><th>更新日</th></tr></thead>
        <tbody>
        <?php foreach ($recentDeals as $d): ?>
          <tr>
            <td><a href="<?= h($baseUrl) ?>/business/deal_edit.php?id=<?= urlencode($d['id']) ?>"><?= h($d['title']) ?></a></td>
            <td><?= h($d['client_name']) ?></td>
            <td><span class="status-badge <?= h(biz_deal_badge($d['status'])) ?>"><?= h($d['status']) ?></span></td>
            <td class="muted"><?= h(format_datetime($d['updated_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>
</main>
<?php end_page();

function biz_deal_badge($status) {
    switch ($status) {
        case '完了': return 'success';
        case '不成立': return 'danger';
        case '実施中': return 'warning';
        default: return 'muted';
    }
}
