<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();

ensure_inquiries_schema($pdo);

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$source = trim((string)($_GET['source'] ?? ''));

$statusOptions = inquiry_status_options();
$sourceOptions = inquiry_source_options();
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR topic LIKE ? OR message LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($status !== '' && isset($statusOptions[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}

if ($source !== '' && isset($sourceOptions[$source])) {
    $where[] = 'source = ?';
    $params[] = $source;
}

$sql = 'SELECT id, source, status, name, email, topic, created_at, replied_at FROM inquiries';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$summary = $pdo->query(
    "SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) AS replied_count
     FROM inquiries"
)->fetch() ?: ['total_count' => 0, 'new_count' => 0, 'in_progress_count' => 0, 'replied_count' => 0];

start_page('Inquiries', 'View and manage all inquiries from public pages.');
?>
<main class="page-container">
  <section class="card-grid two">
    <div class="card stat-card">
      <div class="muted">Total</div>
      <div class="stat-number"><?= h((string)$summary['total_count']) ?></div>
      <p>Total inquiries saved in the system.</p>
    </div>
    <div class="card stat-card">
      <div class="muted">New</div>
      <div class="stat-number"><?= h((string)$summary['new_count']) ?></div>
      <p>New inquiries not handled yet.</p>
    </div>
  </section>

  <section class="card-grid two mt-24">
    <div class="card stat-card">
      <div class="muted">In Progress</div>
      <div class="stat-number"><?= h((string)$summary['in_progress_count']) ?></div>
      <p>Inquiries currently under review.</p>
    </div>
    <div class="card stat-card">
      <div class="muted">Replied</div>
      <div class="stat-number"><?= h((string)$summary['replied_count']) ?></div>
      <p>Inquiries replied from the admin panel.</p>
    </div>
  </section>

  <form method="get" class="card form-card form-grid two mt-24">
    <label>
      <span>Keyword</span>
      <input type="text" name="q" value="<?= h($q) ?>">
    </label>
    <label>
      <span>Status</span>
      <select name="status">
        <option value="">All</option>
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= selected($status, $value) ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>Source</span>
      <select name="source">
        <option value="">All</option>
        <?php foreach ($sourceOptions as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= selected($source, $value) ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="actions-inline" style="align-self:end;">
      <button class="ghost-btn" type="submit">Search</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/inquiries.php">Reset</a>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Received</th>
            <th>Source</th>
            <th>Topic</th>
            <th>Sender</th>
            <th>Status</th>
            <th>Replied</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="8" class="empty-state">No inquiries found.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td>#<?= h((string)$row['id']) ?></td>
              <td><?= h(format_datetime($row['created_at'])) ?></td>
              <td><?= h(inquiry_source_label((string)$row['source'])) ?></td>
              <td><?= h((string)$row['topic']) ?></td>
              <td><?= h((string)$row['name']) ?><br><span class="muted"><?= h((string)$row['email']) ?></span></td>
              <td><span class="status-badge <?= h(status_badge_class((string)$row['status'])) ?>"><?= h(inquiry_status_label((string)$row['status'])) ?></span></td>
              <td><?= h(format_datetime($row['replied_at'])) ?></td>
              <td><a class="ghost-btn" href="<?= h($baseUrl) ?>/inquiry_view.php?id=<?= urlencode((string)$row['id']) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php end_page(); ?>
