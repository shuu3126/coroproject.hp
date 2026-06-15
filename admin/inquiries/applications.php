<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$filterStatus = trim($_GET['status'] ?? '');

$statusLabels = [
    'new'       => '新着',
    'reviewing' => '審査中',
    'passed'    => '合格',
    'rejected'  => '不合格',
    'hold'      => '保留',
];
$statusClasses = [
    'new'       => 'danger',
    'reviewing' => 'warning',
    'passed'    => 'success',
    'rejected'  => 'muted',
    'hold'      => 'info',
];

// タブごとの件数
$counts = [];
foreach (array_keys($statusLabels) as $st) {
    $counts[$st] = (int)$pdo->prepare("SELECT COUNT(*) FROM talent_applications WHERE status = ?")->execute([$st]) ? 0 : 0;
}
// 正確なカウントをまとめて取得
try {
    $cntStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM talent_applications GROUP BY status");
    foreach ($cntStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $counts[$r['status']] = (int)$r['cnt'];
    }
} catch (Exception $_e) {}

// 一覧取得
$where  = ['1=1'];
$params = [];
if ($filterStatus !== '') {
    $where[] = 'status = ?';
    $params[] = $filterStatus;
}
$sql = 'SELECT id, vtuber_name, gender, age, prefecture, email,'
     . ' main_platform, youtube_followers, twitch_followers, twitter_followers, twitcasting_followers,'
     . ' affiliation_type, work_style, status, created_at'
     . ' FROM talent_applications WHERE ' . implode(' AND ', $where)
     . ' ORDER BY created_at DESC';
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_e) {
    $rows = [];
}

$affiliationLabels = ['exclusive' => '専属', 'non_exclusive' => '非専属', 'negotiable' => '相談'];
$genderLabels      = ['female' => '女性', 'male' => '男性', 'other' => 'その他', 'private' => '非公開'];

start_page('オーディション応募管理', 'VTuberオーディション応募一覧');
?>
<main class="page-container">
  <section class="page-header-block" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <h1>オーディション応募管理</h1>
    <?php $newCount = $counts['new'] ?? 0; ?>
    <?php if ($newCount > 0): ?>
      <span class="status-badge danger"><?= $newCount ?>件の新着</span>
    <?php endif; ?>
  </section>

  <!-- ステータスタブ -->
  <div class="tab-bar" style="display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:0;">
    <a href="<?= h($baseUrl) ?>/inquiries/applications.php"
       class="tab-link ghost-btn <?= $filterStatus === '' ? 'active' : '' ?>"
       style="border-bottom:<?= $filterStatus === '' ? '2px solid currentColor' : 'none' ?>;border-radius:4px 4px 0 0;">
      すべて（<?= array_sum($counts) ?>）
    </a>
    <?php foreach ($statusLabels as $st => $label): ?>
      <a href="<?= h($baseUrl) ?>/inquiries/applications.php?status=<?= h($st) ?>"
         class="tab-link ghost-btn <?= $filterStatus === $st ? 'active' : '' ?>"
         style="border-bottom:<?= $filterStatus === $st ? '2px solid currentColor' : 'none' ?>;border-radius:4px 4px 0 0;">
        <?= h($label) ?>（<?= (int)($counts[$st] ?? 0) ?>）
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>VTuber名</th>
          <th style="width:100px;">最大フォロワー</th>
          <th style="width:90px;">年齢・性別</th>
          <th style="width:80px;">所属形態</th>
          <th style="width:130px;">応募日時</th>
          <th style="width:90px;">ステータス</th>
          <th style="width:80px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="muted" style="text-align:center;padding:32px;">応募はありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row):
          // 最大フォロワー数を計算
          $maxFollowers = max(
              (int)($row['youtube_followers'] ?? 0),
              (int)($row['twitch_followers'] ?? 0),
              (int)($row['twitter_followers'] ?? 0),
              (int)($row['twitcasting_followers'] ?? 0)
          );
          $sl = $statusLabels[$row['status']] ?? $row['status'];
          $sc = $statusClasses[$row['status']] ?? 'muted';
        ?>
          <tr <?= $row['status'] === 'new' ? 'style="font-weight:600;"' : '' ?>>
            <td class="muted">#<?= (int)$row['id'] ?></td>
            <td><?= h($row['vtuber_name']) ?></td>
            <td class="muted" style="font-size:0.9em;">
              <?= $maxFollowers > 0 ? number_format($maxFollowers) : '—' ?>
            </td>
            <td class="muted" style="font-size:0.85em;">
              <?= h($row['age']) ?>歳 / <?= h($genderLabels[$row['gender']] ?? $row['gender']) ?>
            </td>
            <td class="muted" style="font-size:0.85em;">
              <?= h($affiliationLabels[$row['affiliation_type']] ?? $row['affiliation_type']) ?>
            </td>
            <td class="muted" style="font-size:0.85em;"><?= h(format_datetime($row['created_at'])) ?></td>
            <td><span class="status-badge <?= h($sc) ?>"><?= h($sl) ?></span></td>
            <td>
              <a class="ghost-btn" style="font-size:0.8em;padding:4px 10px;"
                 href="<?= h($baseUrl) ?>/inquiries/application_detail.php?id=<?= (int)$row['id'] ?>">詳細</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php end_page(); ?>
