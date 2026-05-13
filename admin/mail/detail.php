<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$user = current_admin_user();
$settings = load_app_settings($pdo, $config);
admin_mail_ensure_schema($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect_to($baseUrl . '/mail/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM mail_messages WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$message = $stmt->fetch();
if (!$message) {
    set_flash('error', 'メールが見つかりません。');
    redirect_to($baseUrl . '/mail/index.php');
}

if ($message['mailbox'] === 'inbox' && $message['status'] === 'unread') {
    $pdo->prepare("UPDATE mail_messages SET status = 'read', updated_at = NOW() WHERE id = ?")->execute([$id]);
    $message['status'] = 'read';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'reply') {
        $body = trim($_POST['body'] ?? '');
        $to = trim($_POST['to'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        try {
            $sentId = admin_mail_send_message($pdo, $settings, (int)$user['id'], $to, $subject, $body, '', '', $id);
            write_admin_log($pdo, (int)$user['id'], 'send', 'mail', (string)$sentId, 'メールに返信しました');
            set_flash('success', '返信を送信しました。');
            redirect_to($baseUrl . '/mail/detail.php?id=' . $id);
        } catch (Exception $e) {
            set_flash('error', '返信送信に失敗しました: ' . $e->getMessage());
            redirect_to($baseUrl . '/mail/detail.php?id=' . $id);
        }
    }

    if ($action === 'toggle_star') {
        $pdo->prepare('UPDATE mail_messages SET is_starred = 1 - is_starred, updated_at = NOW() WHERE id = ?')->execute([$id]);
        redirect_to($baseUrl . '/mail/detail.php?id=' . $id);
    }

    if ($action === 'mark_unread') {
        $pdo->prepare("UPDATE mail_messages SET status = 'unread', updated_at = NOW() WHERE id = ?")->execute([$id]);
        redirect_to($baseUrl . '/mail/index.php?mailbox=inbox');
    }

    if ($action === 'archive') {
        $pdo->prepare("UPDATE mail_messages SET mailbox = 'archive', status = 'read', updated_at = NOW() WHERE id = ?")->execute([$id]);
        redirect_to($baseUrl . '/mail/index.php?mailbox=archive');
    }

    if ($action === 'trash') {
        $pdo->prepare("UPDATE mail_messages SET mailbox = 'trash', status = 'read', updated_at = NOW() WHERE id = ?")->execute([$id]);
        redirect_to($baseUrl . '/mail/index.php?mailbox=trash');
    }

    if ($action === 'restore') {
        $targetMailbox = $message['direction'] === 'outbound' ? 'sent' : 'inbox';
        $targetStatus = $message['direction'] === 'outbound' ? 'sent' : 'read';
        $pdo->prepare('UPDATE mail_messages SET mailbox = ?, status = ?, updated_at = NOW() WHERE id = ?')->execute([$targetMailbox, $targetStatus, $id]);
        redirect_to($baseUrl . '/mail/index.php?mailbox=' . $targetMailbox);
    }

    if ($action === 'delete_permanent') {
        $pdo->prepare('DELETE FROM mail_messages WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'mail', (string)$id, 'メールを完全削除しました');
        set_flash('success', 'メールを完全削除しました。');
        redirect_to($baseUrl . '/mail/index.php?mailbox=trash');
    }
}

$backMailbox = isset($_GET['back']) ? trim($_GET['back']) : (string)$message['mailbox'];
if (!in_array($backMailbox, ['inbox', 'sent', 'archive', 'trash'], true)) {
    $backMailbox = 'inbox';
}

$replyTo = $message['direction'] === 'outbound'
    ? (string)$message['to_text']
    : trim((string)($message['from_name'] && $message['from_email'] ? $message['from_name'] . ' <' . $message['from_email'] . '>' : $message['from_email']));
$replySubject = preg_match('/^\s*re\s*:/i', (string)$message['subject']) ? (string)$message['subject'] : 'Re: ' . (string)$message['subject'];
$quoted = trim((string)$message['body_text']);
if ($quoted !== '') {
    $quoted = "\n\n--- 元のメール ---\n" . preg_replace('/^/m', '> ', $quoted);
}
$bodyHtml = trim((string)$message['body_html']);

start_page('メール詳細', (string)$message['subject']);
?>
<main class="page-container narrow">
  <section class="page-header-block with-actions">
    <div>
      <h1><?= h($message['subject'] ?: '(no subject)') ?></h1>
      <div class="muted">
        <?= h($message['direction'] === 'outbound' ? '送信済み' : '受信メール') ?>
        <?php if (!empty($message['has_attachments'])): ?> / 添付あり<?php endif; ?>
      </div>
    </div>
    <div class="actions-inline">
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail.php?mailbox=<?= h($backMailbox) ?>">一覧へ戻る</a>
      <form method="post"><input type="hidden" name="action" value="toggle_star"><button class="ghost-btn" type="submit"><?= !empty($message['is_starred']) ? 'スター解除' : 'スター' ?></button></form>
      <?php if ($message['mailbox'] === 'inbox'): ?>
        <form method="post"><input type="hidden" name="action" value="mark_unread"><button class="ghost-btn" type="submit">未読に戻す</button></form>
        <form method="post"><input type="hidden" name="action" value="archive"><button class="ghost-btn" type="submit">アーカイブ</button></form>
      <?php endif; ?>
      <?php if ($message['mailbox'] !== 'trash'): ?>
        <form method="post"><input type="hidden" name="action" value="trash"><button class="danger-btn" type="submit">ゴミ箱へ</button></form>
      <?php else: ?>
        <form method="post"><input type="hidden" name="action" value="restore"><button class="ghost-btn" type="submit">戻す</button></form>
        <form method="post" data-confirm="このメールを完全に削除します。よろしいですか？"><input type="hidden" name="action" value="delete_permanent"><button class="danger-btn" type="submit">完全削除</button></form>
      <?php endif; ?>
    </div>
  </section>

  <section class="card form-card form-stack">
    <table class="data-table">
      <tbody>
        <tr><th style="width:110px;">差出人</th><td><?= h(trim((string)($message['from_name'] ?: $message['from_email']))) ?> <?php if ($message['from_email']): ?>&lt;<?= h($message['from_email']) ?>&gt;<?php endif; ?></td></tr>
        <tr><th>宛先</th><td><?= h($message['to_text']) ?></td></tr>
        <?php if (!empty($message['cc_text'])): ?><tr><th>Cc</th><td><?= h($message['cc_text']) ?></td></tr><?php endif; ?>
        <?php if (!empty($message['bcc_text'])): ?><tr><th>Bcc</th><td><?= h($message['bcc_text']) ?></td></tr><?php endif; ?>
        <?php if (!empty($message['account_email'])): ?><tr><th>管理アカウント</th><td><?= h($message['account_email']) ?></td></tr><?php endif; ?>
        <tr><th>日時</th><td><?= h(format_datetime($message['received_at'] ?: ($message['sent_at'] ?: $message['created_at']))) ?></td></tr>
      </tbody>
    </table>

    <div class="mail-body">
      <?php if ($bodyHtml !== ''): ?>
        <iframe class="mail-html-frame" sandbox srcdoc="<?= h($bodyHtml) ?>"></iframe>
      <?php else: ?>
        <?= nl2br(h((string)$message['body_text'])) ?>
      <?php endif; ?>
    </div>

    <?php if ($message['status'] === 'failed' && !empty($message['error_message'])): ?>
      <div class="alert-box alert-error" style="margin:0;">
        送信エラー: <?= h($message['error_message']) ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card form-card form-stack mt-24">
    <h2 class="section-heading">返信</h2>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="reply">
      <?php if (!empty($message['account_email'])): ?>
        <div class="alert-box alert-success" style="margin:0;">返信元: <strong><?= h($message['account_email']) ?></strong></div>
      <?php endif; ?>
      <label><span>送信先</span><input type="text" name="to" value="<?= h($replyTo) ?>" required></label>
      <label><span>件名</span><input type="text" name="subject" value="<?= h($replySubject) ?>" required></label>
      <label><span>本文</span><textarea name="body" rows="10" required><?= h($quoted) ?></textarea></label>
      <div class="actions-inline">
        <button class="primary-btn" type="submit">返信を送信</button>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail_compose.php?reply_to=<?= (int)$message['id'] ?>">作成画面で開く</a>
      </div>
    </form>
  </section>
</main>
<?php end_page(); ?>
