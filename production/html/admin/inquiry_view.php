<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();

ensure_inquiries_schema($pdo);

$user = current_admin_user();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'Invalid inquiry ID.');
    redirect_to($baseUrl . '/inquiries.php');
}

$loadInquiry = function () use ($pdo, $id) {
    $stmt = $pdo->prepare(
        'SELECT i.*, u.display_name AS replied_by_name
         FROM inquiries i
         LEFT JOIN admin_users u ON u.id = i.replied_by
         WHERE i.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $status = trim((string)($_POST['status'] ?? ''));
    $adminMemo = trim((string)($_POST['admin_memo'] ?? ''));

    if (!isset(inquiry_status_options()[$status])) {
        $status = 'new';
    }

    $inquiry = $loadInquiry();
    if (!$inquiry) {
        set_flash('error', 'Inquiry not found.');
        redirect_to($baseUrl . '/inquiries.php');
    }

    if ($action === 'save') {
        $stmt = $pdo->prepare('UPDATE inquiries SET status = ?, admin_memo = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $adminMemo, $id]);
        write_admin_log($pdo, (int)$user['id'], 'update', 'inquiry', $id, 'Updated inquiry status', [
            'status' => $status,
        ]);
        set_flash('success', 'Inquiry updated.');
        redirect_to($baseUrl . '/inquiry_view.php?id=' . urlencode((string)$id));
    }

    if ($action === 'reply') {
        $replySubject = trim((string)($_POST['reply_subject'] ?? ''));
        $replyBody = trim((string)($_POST['reply_body'] ?? ''));

        if ($replySubject === '' || $replyBody === '') {
            set_flash('error', 'Reply subject and body are required.');
            redirect_to($baseUrl . '/inquiry_view.php?id=' . urlencode((string)$id));
        }

        try {
            send_inquiry_admin_reply($inquiry, $replySubject, $replyBody);
            $stmt = $pdo->prepare(
                'UPDATE inquiries
                 SET status = ?, admin_memo = ?, reply_subject = ?, reply_body = ?, replied_at = NOW(), replied_by = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute(['replied', $adminMemo, $replySubject, $replyBody, (int)$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'reply', 'inquiry', $id, 'Sent inquiry reply');
            set_flash('success', 'Reply sent.');
        } catch (Throwable $e) {
            inquiry_log_line('MAIL admin reply ERROR: ' . $e->getMessage());
            set_flash('error', 'Reply send failed.');
        }

        redirect_to($baseUrl . '/inquiry_view.php?id=' . urlencode((string)$id));
    }
}

$row = $loadInquiry();
if (!$row) {
    set_flash('error', 'Inquiry not found.');
    redirect_to($baseUrl . '/inquiries.php');
}

$statusOptions = inquiry_status_options();
$replySubjectDefault = trim((string)($row['reply_subject'] ?? '')) !== ''
    ? (string)$row['reply_subject']
    : 'Re: ' . (string)$row['topic'] . ' / CORO PROJECT';
$replyBodyDefault = trim((string)($row['reply_body'] ?? '')) !== ''
    ? (string)$row['reply_body']
    : (string)$row['name'] . "\n\nThank you for your inquiry.\n\n";

start_page('Inquiry Detail', 'Review the message, update status, and send a reply.');
?>
<main class="page-container narrow">
  <section class="page-header-block with-actions">
    <div>
      <h1>Inquiry #<?= h((string)$row['id']) ?></h1>
      <p>Source: <?= h(inquiry_source_label((string)$row['source'])) ?></p>
    </div>
    <a class="ghost-btn" href="<?= h($baseUrl) ?>/inquiries.php">Back to list</a>
  </section>

  <section class="card">
    <div class="summary-list">
      <div class="summary-row"><span>Received</span><strong><?= h(format_datetime($row['created_at'])) ?></strong></div>
      <div class="summary-row"><span>Name</span><strong><?= h((string)$row['name']) ?></strong></div>
      <div class="summary-row"><span>Email</span><strong><a href="mailto:<?= h((string)$row['email']) ?>"><?= h((string)$row['email']) ?></a></strong></div>
      <div class="summary-row"><span>Topic</span><strong><?= h((string)$row['topic']) ?></strong></div>
      <div class="summary-row"><span>URL</span><strong><?= h((string)($row['url'] ?: 'N/A')) ?></strong></div>
      <div class="summary-row"><span>Status</span><strong><span class="status-badge <?= h(status_badge_class((string)$row['status'])) ?>"><?= h(inquiry_status_label((string)$row['status'])) ?></span></strong></div>
      <div class="summary-row"><span>Last reply</span><strong><?= h(format_datetime($row['replied_at'])) ?><?= !empty($row['replied_by_name']) ? ' / ' . h((string)$row['replied_by_name']) : '' ?></strong></div>
    </div>
  </section>

  <section class="card mt-24">
    <h3>Message</h3>
    <p style="white-space:pre-wrap;"><?= h((string)$row['message']) ?></p>
  </section>

  <form method="post" class="card form-card form-stack mt-24">
    <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
    <input type="hidden" name="action" value="save">

    <label>
      <span>Status</span>
      <select name="status">
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= selected((string)$row['status'], $value) ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      <span>Admin memo</span>
      <textarea name="admin_memo" rows="5"><?= h((string)($row['admin_memo'] ?? '')) ?></textarea>
    </label>

    <div class="actions-inline">
      <button class="ghost-btn" type="submit">Save</button>
    </div>
  </form>

  <form method="post" class="card form-card form-stack mt-24">
    <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
    <input type="hidden" name="action" value="reply">
    <input type="hidden" name="status" value="replied">
    <input type="hidden" name="admin_memo" value="<?= h((string)($row['admin_memo'] ?? '')) ?>">

    <h3>Reply by email</h3>

    <label>
      <span>Subject</span>
      <input type="text" name="reply_subject" value="<?= h($replySubjectDefault) ?>" required>
    </label>

    <label>
      <span>Body</span>
      <textarea name="reply_body" rows="12" required><?= h($replyBodyDefault) ?></textarea>
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">Send reply</button>
      <a class="ghost-btn" href="mailto:<?= h((string)$row['email']) ?>">Open mail app</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
