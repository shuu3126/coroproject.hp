<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$totalProjects  = (int)$pdo->query("SELECT COUNT(*) FROM cre_projects")->fetchColumn();
$activeProjects = (int)$pdo->query("SELECT COUNT(*) FROM cre_projects WHERE status NOT IN ('完了')")->fetchColumn();
$totalCreators  = (int)$pdo->query("SELECT COUNT(*) FROM cre_creators WHERE is_active = 1")->fetchColumn();
$doneThisMonth  = (int)$pdo->query("SELECT COUNT(*) FROM cre_projects WHERE status = '完了' AND updated_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetchColumn();

$nearDeadline = $pdo->query("
    SELECT p.id, p.title, p.status, p.deadline,
           COALESCE(c.name,'—') AS client_name,
           DATEDIFF(p.deadline, CURDATE()) AS days_left
    FROM cre_projects p
    LEFT JOIN clients c ON c.id = p.client_id
    WHERE p.status NOT IN ('完了') AND p.deadline IS NOT NULL
    ORDER BY p.deadline ASC LIMIT 8
")->fetchAll();

$recentProjects = $pdo->query("
    SELECT p.id, p.title, p.status, p.category, p.updated_at,
           COALESCE(c.name,'—') AS client_name,
           COALESCE(cr.name,'—') AS creator_name
    FROM cre_projects p
    LEFT JOIN clients c ON c.id = p.client_id
    LEFT JOIN cre_creators cr ON cr.id = p.creator_id
    ORDER BY p.updated_at DESC LIMIT 8
")->fetchAll();

$categories = ['illustration' => 'イラスト', 'live2d' => 'Live2D', 'single_art' => '一枚絵', 'music' => '音楽', 'video' => '動画', 'other' => 'その他'];

start_page('Creative', '');
?>
<main class="page-container">

  <section class="card-grid four">
    <a class="card stat-card" href="<?= h($baseUrl) ?>/creative/projects.php?status=">
      <div class="muted">進行中</div>
      <div class="stat-number"><?= $activeProjects ?></div>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/creative/projects.php">
      <div class="muted">総案件数</div>
      <div class="stat-number"><?= $totalProjects ?></div>
    </a>
    <a class="card stat-card" href="<?= h($baseUrl) ?>/creative/creators.php">
      <div class="muted">クリエイター</div>
      <div class="stat-number"><?= $totalCreators ?></div>
    </a>
    <div class="card stat-card">
      <div class="muted">今月完了</div>
      <div class="stat-number"><?= $doneThisMonth ?></div>
    </div>
  </section>

  <div class="card-grid two mt-24">

    <?php if ($nearDeadline): ?>
    <section class="card table-card">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 10px;">
        <h3 style="margin:0;font-size:.92em;">納期が近い案件</h3>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/projects.php" style="font-size:.78em;padding:2px 10px;">すべて</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>案件名</th><th>ステータス</th><th style="width:80px;text-align:right;">残り</th></tr></thead>
          <tbody>
          <?php foreach ($nearDeadline as $p):
            $d = (int)$p['days_left'];
            $dColor = $d < 0 ? 'var(--danger)' : ($d <= 3 ? 'var(--warning)' : 'var(--sub)');
            $dLabel = $d < 0 ? '期限超過' : ($d === 0 ? '今日' : $d . '日');
          ?>
            <tr>
              <td>
                <a href="<?= h($baseUrl) ?>/creative/project_edit.php?id=<?= urlencode($p['id']) ?>"><?= h($p['title']) ?></a>
                <div class="muted" style="font-size:.78em;"><?= h($p['client_name']) ?> · <?= h($p['deadline']) ?></div>
              </td>
              <td><span class="status-badge <?= h(cre_project_badge($p['status'])) ?>"><?= h($p['status']) ?></span></td>
              <td style="text-align:right;font-weight:600;font-size:.85em;color:<?= $dColor ?>;"><?= h($dLabel) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($recentProjects): ?>
    <section class="card table-card">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 10px;">
        <h3 style="margin:0;font-size:.92em;">最近の更新</h3>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/projects.php" style="font-size:.78em;padding:2px 10px;">すべて</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>案件名</th><th>カテゴリ</th><th>ステータス</th></tr></thead>
          <tbody>
          <?php foreach ($recentProjects as $p): ?>
            <tr>
              <td>
                <a href="<?= h($baseUrl) ?>/creative/project_edit.php?id=<?= urlencode($p['id']) ?>"><?= h($p['title']) ?></a>
                <div class="muted" style="font-size:.78em;"><?= h($p['creator_name']) ?></div>
              </td>
              <td class="muted" style="font-size:.82em;"><?= h($categories[$p['category']] ?? $p['category']) ?></td>
              <td><span class="status-badge <?= h(cre_project_badge($p['status'])) ?>"><?= h($p['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

  </div>
</main>
<?php end_page();

function cre_project_badge($status) {
    switch ($status) {
        case '完了':   return 'success';
        case '納品':
        case '確認中': return 'warning';
        case '制作中': return 'info';
        default:       return 'muted';
    }
}
