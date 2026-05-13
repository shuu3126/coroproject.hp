<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$q = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = 'SELECT * FROM inquiries';
$conds = [];
$params = [];
if ($q !== '') {
    $conds[] = '(name LIKE ? OR email LIKE ? OR topic LIKE ? OR message LIKE ?)';
    $lq = "%{$q}%";
    $params = array_merge($params, [$lq, $lq, $lq, $lq]);
}
if ($filterStatus !== '') {
    $conds[] = 'status = ?';
    $params[] = $filterStatus;
}
if ($conds) {
    $sql .= ' WHERE ' . implode(' AND ', $conds);
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();

$statusLabels = ['unread' => '未読', 'read' => '読了', 'replied' => '返信済', 'archived' => 'アーカイブ'];
$statusClasses = ['unread' => 'danger', 'read' => 'muted', 'replied' => 'success', 'archived' => 'muted'];

start_page('メッセージ管理', 'サイトからのお問い合わせ一覧');
?>
<main class="page-container">
  <section class="page-header-block">
    <h1>メッセージ管理</h1>
    <?php if ($unreadCount > 0): ?>
      <span class="status-badge danger"><?= $unreadCount ?>件の未読</span>
    <?php endif; ?>
  </section>

  <form method="get" class="search-bar" style="display:flex;gap:8px;align-items:center;margin-bottom:16px;">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="名前・メール・件名・内容で検索" style="flex:1;">
    <select name="status" style="min-width:120px;">
      <option value="">すべて</option>
      <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= h($val) ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="ghost-btn" type="submit">検索</button>
    <?php if ($q !== '' || $filterStatus !== ''): ?>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/inquiries/messages.php">リセット</a>
    <?php endif; ?>
  </form>

  <div class="card">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>お名前</th>
          <th>メールアドレス</th>
          <th>ご用件</th>
          <th style="width:140px;">受信日時</th>
          <th style="width:90px;">状態</th>
          <th style="width:100px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="muted" style="text-align:center;padding:32px;">お問い合わせはありません。</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <?php
            $sl = $statusLabels[$row['status']] ?? $row['status'];
            $sc = $statusClasses[$row['status']] ?? 'muted';
          ?>
          <tr <?= $row['status'] === 'unread' ? 'style="font-weight:600;"' : '' ?>>
            <td class="muted">#<?= (int)$row['id'] ?></td>
            <td><?= h($row['name']) ?></td>
            <td class="muted" style="font-size:0.85em;"><?= h($row['email']) ?></td>
            <td><?= h(mb_strimwidth($row['topic'], 0, 40, '…')) ?></td>
            <td class="muted" style="font-size:0.85em;"><?= h(format_datetime($row['created_at'])) ?></td>
            <td><span class="status-badge <?= h($sc) ?>"><?= h($sl) ?></span></td>
            <td>
              <a class="ghost-btn" style="font-size:0.8em;padding:4px 10px;" href="<?= h($baseUrl) ?>/inquiries/message_detail.php?id=<?= (int)$row['id'] ?>">詳細・返信</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php end_page(); ?>
