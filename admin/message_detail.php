<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();
$settings = load_app_settings($pdo, $config);

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect_to($baseUrl . '/messages.php');
}

$stmt = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$inquiry = $stmt->fetch();
if (!$inquiry) {
    set_flash('error', 'お問い合わせが見つかりません。');
    redirect_to($baseUrl . '/messages.php');
}

// 未読なら既読に更新
if ($inquiry['status'] === 'unread') {
    $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute(['read', $id]);
    $inquiry['status'] = 'read';
}

// 返信履歴取得
$stmt = $pdo->prepare('
    SELECT r.*, a.display_name
    FROM inquiry_replies r
    LEFT JOIN admin_users a ON a.id = r.admin_user_id
    WHERE r.inquiry_id = ?
    ORDER BY r.created_at ASC
');
$stmt->execute([$id]);
$replies = $stmt->fetchAll();

$pageError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'reply') {
        $body = trim($_POST['reply_body'] ?? '');
        if ($body === '') {
            $pageError = '返信内容を入力してください。';
        } else {
            $mailSent = false;
            $mailError = '';

            $smtpHost = (string)($settings['smtp_host'] ?? '');
            try {
                require_once dirname(__DIR__) . '/production/lib/PHPMailer/Exception.php';
                require_once dirname(__DIR__) . '/production/lib/PHPMailer/PHPMailer.php';
                require_once dirname(__DIR__) . '/production/lib/PHPMailer/SMTP.php';

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                if ($smtpHost !== '') {
                    $mail->isSMTP();
                    $mail->Host      = $smtpHost;
                    $mail->SMTPAuth  = true;
                    $mail->Username  = (string)($settings['smtp_user'] ?? '');
                    $mail->Password  = (string)($settings['smtp_pass'] ?? '');
                    $mail->Port      = (int)($settings['smtp_port'] ?? 587);
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
                $fromEmail = (string)($settings['smtp_from_email'] ?? $settings['office_email'] ?? '');
                $fromName  = (string)($settings['smtp_from_name']  ?? $settings['office_name']  ?? 'CORO PROJECT');
                if ($fromEmail !== '') {
                    $mail->setFrom($fromEmail, $fromName);
                }
                $mail->CharSet = 'UTF-8';
                $mail->addAddress($inquiry['email'], $inquiry['name']);
                $mail->Subject = '【CORO PROJECT】Re: ' . $inquiry['topic'];
                $footer = "\n\n────────────────────\n"
                    . $settings['office_name'] . "\n"
                    . 'Mail: ' . ($fromEmail ?: 'info@coroproject.jp') . "\n"
                    . "────────────────────";
                $mail->Body = $body . $footer;
                $mail->send();
                $mailSent = true;
            } catch (\Exception $e) {
                $mailError = $e->getMessage();
            }

            $pdo->prepare('INSERT INTO inquiry_replies (inquiry_id, admin_user_id, body, mail_sent, created_at) VALUES (?, ?, ?, ?, NOW())')
                ->execute([$id, (int)$user['id'], $body, $mailSent ? 1 : 0]);

            $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute(['replied', $id]);

            write_admin_log($pdo, (int)$user['id'], 'reply', 'inquiry', (string)$id, 'お問い合わせに返信しました');

            if ($mailSent) {
                set_flash('success', '返信を送信しました。');
            } else {
                set_flash('error', 'DBへの保存は完了しましたがメール送信に失敗しました: ' . $mailError . ' — 設定画面のSMTP設定を確認してください。');
            }
            redirect_to($baseUrl . '/message_detail.php?id=' . $id);
        }
    } elseif ($action === 'archive') {
        $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute(['archived', $id]);
        write_admin_log($pdo, (int)$user['id'], 'archive', 'inquiry', (string)$id, 'お問い合わせをアーカイブしました');
        set_flash('success', 'アーカイブしました。');
        redirect_to($baseUrl . '/messages.php');
    }
}

$statusLabels  = ['unread' => '未読', 'read' => '読了', 'replied' => '返信済', 'archived' => 'アーカイブ'];
$statusClasses = ['unread' => 'danger', 'read' => 'muted', 'replied' => 'success', 'archived' => 'muted'];
$sl = $statusLabels[$inquiry['status']] ?? $inquiry['status'];
$sc = $statusClasses[$inquiry['status']] ?? 'muted';

start_page('お問い合わせ #' . $id, $inquiry['topic']);
?>
<main class="page-container narrow">

  <?php if ($pageError !== ''): ?>
    <div class="alert-box alert-error"><?= h($pageError) ?></div>
  <?php endif; ?>

  <!-- 問い合わせ本文 -->
  <div class="card form-card form-stack">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
      <div>
        <h2 style="margin:0 0 4px;"><?= h($inquiry['topic']) ?></h2>
        <span class="status-badge <?= h($sc) ?>"><?= h($sl) ?></span>
      </div>
      <div class="actions-inline">
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/messages.php">← 一覧へ戻る</a>
        <?php if ($inquiry['status'] !== 'archived'): ?>
          <form method="post" style="display:inline;" onsubmit="return confirm('アーカイブしますか？');">
            <input type="hidden" name="action" value="archive">
            <button class="ghost-btn" type="submit">アーカイブ</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <table class="data-table" style="margin-top:16px;">
      <tbody>
        <tr><th style="width:120px;">お名前</th><td><?= h($inquiry['name']) ?></td></tr>
        <tr><th>メールアドレス</th><td><a href="mailto:<?= h($inquiry['email']) ?>"><?= h($inquiry['email']) ?></a></td></tr>
        <tr><th>ご用件</th><td><?= h($inquiry['topic']) ?></td></tr>
        <?php if (!empty($inquiry['url'])): ?>
          <tr><th>関連URL</th><td><a href="<?= h($inquiry['url']) ?>" target="_blank" rel="noopener"><?= h($inquiry['url']) ?></a></td></tr>
        <?php endif; ?>
        <tr><th>受信日時</th><td><?= h(format_datetime($inquiry['created_at'])) ?></td></tr>
      </tbody>
    </table>

    <div style="background:var(--bg,#f9f9f9);border:1px solid var(--border,#e5e5e5);border-radius:6px;padding:16px;white-space:pre-wrap;line-height:1.7;"><?= h($inquiry['message']) ?></div>
  </div>

  <!-- 返信履歴 -->
  <?php if ($replies): ?>
    <div class="card form-card form-stack">
      <h3 style="margin:0 0 12px;">返信履歴（<?= count($replies) ?>件）</h3>
      <?php foreach ($replies as $reply): ?>
        <div style="border:1px solid var(--border,#e5e5e5);border-radius:6px;padding:12px 16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.85em;" class="muted">
            <span><?= h($reply['display_name'] ?? '管理者') ?></span>
            <span>
              <?= h(format_datetime($reply['created_at'])) ?>
              <?php if ($reply['mail_sent']): ?>
                <span class="status-badge success" style="font-size:0.8em;margin-left:6px;">メール送信済</span>
              <?php else: ?>
                <span class="status-badge warning" style="font-size:0.8em;margin-left:6px;">メール未送信</span>
              <?php endif; ?>
            </span>
          </div>
          <div style="white-space:pre-wrap;line-height:1.7;"><?= h($reply['body']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- 返信フォーム -->
  <?php if ($inquiry['status'] !== 'archived'): ?>
    <div class="card form-card form-stack">
      <h3 style="margin:0 0 12px;">返信する</h3>
      <p class="muted" style="font-size:0.85em;">
        送信先: <strong><?= h($inquiry['name']) ?></strong> &lt;<?= h($inquiry['email']) ?>&gt;<br>
        件名: 【CORO PROJECT】Re: <?= h($inquiry['topic']) ?>
      </p>
      <?php if (empty($settings['smtp_host'])): ?>
        <div class="alert-box alert-error" style="font-size:0.85em;">
          SMTP設定が未設定です。<a href="<?= h($baseUrl) ?>/settings.php">設定画面</a>でSMTP情報を入力するとメールが送信されます。返信内容はDBに保存されます。
        </div>
      <?php endif; ?>
      <form method="post" class="form-stack">
        <input type="hidden" name="action" value="reply">
        <label><span>返信内容</span><textarea name="reply_body" rows="8" required placeholder="返信メッセージを入力してください…"></textarea></label>
        <div class="actions-inline">
          <button class="primary-btn" type="submit">返信を送信する</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

</main>
<?php end_page(); ?>
