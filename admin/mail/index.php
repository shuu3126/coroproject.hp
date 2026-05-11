<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$user     = current_admin_user();
$settings = load_app_settings($pdo, $config);
admin_mail_ensure_schema($pdo);

$mailbox = trim($_GET['mailbox'] ?? 'inbox');
$allowedMailboxes = [
    'inbox'       => '受信トレイ',
    'sent'        => '送信済み',
    'archive'     => 'アーカイブ',
    'trash'       => 'ゴミ箱',
    'inquiries'   => 'お問い合わせ',
];
if (!isset($allowedMailboxes[$mailbox])) $mailbox = 'inbox';

$isInquiryMode = ($mailbox === 'inquiries');
$selectedId    = (int)($_GET['id'] ?? 0);

// ──────────────────────────────────────────────
// POST actions
// ──────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim($_POST['action'] ?? '');

    // ── 通常メール: 受信同期 ──
    if ($action === 'sync') {
        try {
            $result = admin_mail_sync_pop3($pdo, $settings, (int)$user['id']);
            write_admin_log($pdo, (int)$user['id'], 'sync', 'mail', null, 'メールを受信同期しました');
            set_flash('success', '受信完了 — 新着 ' . (int)$result['inserted'] . ' 件');
        } catch (Exception $e) {
            set_flash('error', '受信失敗: ' . $e->getMessage());
        }
        redirect_to($baseUrl . '/mail/index.php?mailbox=inbox');
    }

    // ── 通常メール: 送信 ──
    if ($action === 'send') {
        $toText    = trim($_POST['to']       ?? '');
        $ccText    = trim($_POST['cc']       ?? '');
        $bccText   = trim($_POST['bcc']      ?? '');
        $subject   = trim($_POST['subject']  ?? '');
        $body      = trim($_POST['body']     ?? '');
        $replyToId = (int)($_POST['reply_to'] ?? 0);
        try {
            $sentId = admin_mail_send_message($pdo, $settings, (int)$user['id'], $toText, $subject, $body, $ccText, $bccText, $replyToId ?: null);
            write_admin_log($pdo, (int)$user['id'], 'send', 'mail', (string)$sentId, 'メールを送信しました');
            set_flash('success', '送信しました。');
            redirect_to($baseUrl . '/mail/index.php?mailbox=' . ($replyToId > 0 ? $mailbox . '&id=' . $replyToId : 'sent&id=' . $sentId));
        } catch (Exception $e) {
            set_flash('error', '送信失敗: ' . $e->getMessage());
            redirect_to($baseUrl . '/mail/index.php?mailbox=' . $mailbox . ($selectedId ? '&id=' . $selectedId : ''));
        }
    }

    // ── お問い合わせ: 返信 ──
    if ($action === 'inquiry_reply') {
        $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
        $body      = trim($_POST['body'] ?? '');
        if ($inquiryId > 0 && $body !== '') {
            $inqStmt = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
            $inqStmt->execute([$inquiryId]);
            $inq = $inqStmt->fetch();
            if ($inq) {
                $mailSent = false;
                try {
                    admin_mail_require_phpmailer();
                    $fromEmail = admin_mail_setting($settings, 'smtp_from_email', 'info@coroproject.jp');
                    $fromName  = admin_mail_setting($settings, 'smtp_from_name',  'CORO PROJECT');
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet  = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->isSMTP();
                    $mail->Host       = admin_mail_setting($settings, 'smtp_host');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = admin_mail_setting($settings, 'smtp_user');
                    $mail->Password   = admin_mail_setting($settings, 'smtp_pass');
                    $mail->Port       = (int)admin_mail_setting($settings, 'smtp_port', '465');
                    $smtpSecure = admin_mail_setting($settings, 'smtp_secure', 'ssl');
                    if ($smtpSecure === 'ssl') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } elseif ($smtpSecure === 'tls') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($inq['email'], $inq['name']);
                    $mail->Subject = '【CORO PROJECT】Re: ' . $inq['topic'];
                    $mail->Body    = $body . "\n\n────────────────────\n{$fromName}\nMail: {$fromEmail}\n────────────────────";
                    $mail->send();
                    $mailSent = true;
                } catch (Exception $e) {
                    set_flash('error', 'メール送信失敗: ' . $e->getMessage());
                }
                $pdo->prepare('INSERT INTO inquiry_replies (inquiry_id, admin_user_id, body, mail_sent, created_at) VALUES (?, ?, ?, ?, NOW())')
                    ->execute([$inquiryId, (int)$user['id'], $body, $mailSent ? 1 : 0]);
                $pdo->prepare("UPDATE inquiries SET status = 'replied' WHERE id = ?")->execute([$inquiryId]);
                write_admin_log($pdo, (int)$user['id'], 'reply', 'inquiry', (string)$inquiryId, 'お問い合わせに返信しました');
                if ($mailSent) set_flash('success', '返信を送信しました。');
            }
        }
        redirect_to($baseUrl . '/mail/index.php?mailbox=inquiries&id=' . $inquiryId);
    }

    // ── お問い合わせ: ステータス変更 ──
    if ($action === 'inquiry_status') {
        $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        if ($inquiryId > 0 && in_array($newStatus, ['unread', 'read', 'archived'], true)) {
            $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$newStatus, $inquiryId]);
            write_admin_log($pdo, (int)$user['id'], 'update', 'inquiry', (string)$inquiryId, 'お問い合わせのステータスを変更しました');
        }
        redirect_to($baseUrl . '/mail/index.php?mailbox=inquiries' . ($newStatus !== 'archived' ? '&id=' . $inquiryId : ''));
    }

    // ── 通常メール: メッセージ操作 ──
    $msgId = (int)($_POST['id'] ?? $selectedId);
    if (!$isInquiryMode && $msgId > 0) {
        if ($action === 'toggle_star') {
            $pdo->prepare('UPDATE mail_messages SET is_starred = 1 - is_starred, updated_at = NOW() WHERE id = ?')->execute([$msgId]);
            redirect_to($baseUrl . '/mail/index.php?mailbox=' . $mailbox . '&id=' . $msgId);
        }
        if ($action === 'mark_unread') {
            $pdo->prepare("UPDATE mail_messages SET status = 'unread', updated_at = NOW() WHERE id = ?")->execute([$msgId]);
            redirect_to($baseUrl . '/mail/index.php?mailbox=' . $mailbox);
        }
        if ($action === 'archive') {
            $pdo->prepare("UPDATE mail_messages SET mailbox = 'archive', status = 'read', updated_at = NOW() WHERE id = ?")->execute([$msgId]);
            redirect_to($baseUrl . '/mail/index.php?mailbox=archive');
        }
        if ($action === 'trash') {
            $pdo->prepare("UPDATE mail_messages SET mailbox = 'trash', status = 'read', updated_at = NOW() WHERE id = ?")->execute([$msgId]);
            redirect_to($baseUrl . '/mail/index.php?mailbox=' . $mailbox);
        }
        if ($action === 'restore') {
            $s2 = $pdo->prepare('SELECT direction FROM mail_messages WHERE id = ? LIMIT 1');
            $s2->execute([$msgId]);
            $r2 = $s2->fetch();
            $tb = ($r2 && $r2['direction'] === 'outbound') ? 'sent' : 'inbox';
            $pdo->prepare('UPDATE mail_messages SET mailbox = ?, updated_at = NOW() WHERE id = ?')->execute([$tb, $msgId]);
            redirect_to($baseUrl . '/mail/index.php?mailbox=' . $tb);
        }
        if ($action === 'delete_permanent') {
            $pdo->prepare('DELETE FROM mail_messages WHERE id = ?')->execute([$msgId]);
            write_admin_log($pdo, (int)$user['id'], 'delete', 'mail', (string)$msgId, 'メールを完全削除しました');
            set_flash('success', '完全削除しました。');
            redirect_to($baseUrl . '/mail/index.php?mailbox=trash');
        }
    }
}

// 受信トレイを開いたとき自動受信
if ($mailbox === 'inbox' && $popReady && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $autoResult = admin_mail_sync_pop3($pdo, $settings, (int)$user['id']);
        if ((int)($autoResult['inserted'] ?? 0) > 0) {
            set_flash('success', '新着メール ' . (int)$autoResult['inserted'] . ' 件を受信しました。');
        }
    } catch (Exception $e) {
        // silent
    }
}

// ──────────────────────────────────────────────
// データ取得
// ──────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');

// 選択中のメッセージ
$selectedMessage  = null;
$selectedInquiry  = null;
$inquiryReplies   = [];
$replyTo = $replySubject = $replyBody = '';

if ($selectedId > 0) {
    if ($isInquiryMode) {
        $st = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
        $st->execute([$selectedId]);
        $selectedInquiry = $st->fetch();
        if ($selectedInquiry) {
            if ($selectedInquiry['status'] === 'unread') {
                $pdo->prepare("UPDATE inquiries SET status = 'read' WHERE id = ?")->execute([$selectedId]);
                $selectedInquiry['status'] = 'read';
            }
            $rSt = $pdo->prepare('SELECT r.*, a.display_name FROM inquiry_replies r LEFT JOIN admin_users a ON a.id = r.admin_user_id WHERE r.inquiry_id = ? ORDER BY r.created_at ASC');
            $rSt->execute([$selectedId]);
            $inquiryReplies = $rSt->fetchAll();
        } else {
            $selectedId = 0;
        }
    } else {
        $st = $pdo->prepare('SELECT * FROM mail_messages WHERE id = ? LIMIT 1');
        $st->execute([$selectedId]);
        $selectedMessage = $st->fetch();
        if ($selectedMessage) {
            if ($selectedMessage['mailbox'] === 'inbox' && $selectedMessage['status'] === 'unread') {
                $pdo->prepare("UPDATE mail_messages SET status = 'read', updated_at = NOW() WHERE id = ?")->execute([$selectedId]);
                $selectedMessage['status'] = 'read';
            }
            $replyTo = $selectedMessage['direction'] === 'outbound'
                ? (string)$selectedMessage['to_text']
                : trim((string)($selectedMessage['from_name'] && $selectedMessage['from_email']
                    ? $selectedMessage['from_name'] . ' <' . $selectedMessage['from_email'] . '>'
                    : $selectedMessage['from_email']));
            $replySubject = preg_match('/^\s*re\s*:/i', (string)$selectedMessage['subject'])
                ? (string)$selectedMessage['subject']
                : 'Re: ' . (string)$selectedMessage['subject'];
            $quoted = trim((string)$selectedMessage['body_text']);
            if ($quoted !== '') {
                $replyBody = "\n\n--- 元のメール ---\n" . preg_replace('/^/m', '> ', $quoted);
            }
        } else {
            $selectedId = 0;
        }
    }
}

// メール一覧 or お問い合わせ一覧
$messages = [];
if ($isInquiryMode) {
    $where  = '1=1';
    $params = [];
    if ($q !== '') {
        $where  = '(name LIKE ? OR email LIKE ? OR topic LIKE ? OR message LIKE ?)';
        $like   = '%' . $q . '%';
        $params = [$like, $like, $like, $like];
    }
    $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE {$where} ORDER BY created_at DESC LIMIT 300");
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
} else {
    $params = [$mailbox];
    $where  = 'WHERE mailbox = ?';
    if ($q !== '') {
        $where .= ' AND (from_name LIKE ? OR from_email LIKE ? OR to_text LIKE ? OR subject LIKE ? OR body_text LIKE ?)';
        $like   = '%' . $q . '%';
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
    }
    $stmt = $pdo->prepare("SELECT * FROM mail_messages {$where} ORDER BY COALESCE(received_at, sent_at, created_at) DESC, id DESC LIMIT 300");
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
}

// フォルダカウント
$counts = [];
foreach (['inbox', 'sent', 'archive', 'trash'] as $box) {
    $s = $pdo->prepare('SELECT COUNT(*) FROM mail_messages WHERE mailbox = ?');
    $s->execute([$box]);
    $counts[$box] = (int)$s->fetchColumn();
}
$counts['inquiries'] = 0;
$unreadInquiries = 0;
if (admin_table_has_column($pdo, 'inquiries', 'status')) {
    try {
        $counts['inquiries'] = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status != 'archived'")->fetchColumn();
        $unreadInquiries     = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
    } catch (Exception $e) {}
} else {
    try {
        $counts['inquiries'] = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
    } catch (Exception $e) {}
}

$unreadMail = admin_mail_unread_count($pdo);

$popReady  = admin_mail_setting($settings, 'mail_pop_host') !== ''
    && admin_mail_setting($settings, 'mail_pop_user', admin_mail_setting($settings, 'smtp_user')) !== ''
    && admin_mail_setting($settings, 'mail_pop_pass', admin_mail_setting($settings, 'smtp_pass')) !== '';
$smtpReady = admin_mail_setting($settings, 'smtp_host') !== ''
    && admin_mail_setting($settings, 'smtp_user') !== ''
    && admin_mail_setting($settings, 'smtp_pass') !== '';

// 名簿データ構築（mail_contacts + cre_creators のメールアドレス）
$_contactsRaw = $pdo->query(
    'SELECT name, email, company FROM mail_contacts ORDER BY COALESCE(last_contacted_at, created_at) DESC, name ASC LIMIT 500'
)->fetchAll(PDO::FETCH_ASSOC);
$contacts = [];  // 後方互換 (reply-contact-list 等に使用)
$_pickerContacts = [];
$_seenEmails = [];
foreach ($_contactsRaw as $_c) {
    $_em = strtolower(trim($_c['email'] ?? ''));
    if ($_em === '' || !filter_var($_em, FILTER_VALIDATE_EMAIL) || isset($_seenEmails[$_em])) continue;
    $_seenEmails[$_em] = true;
    $contacts[] = $_c;
    $_pickerContacts[] = ['name' => $_c['name'] ?? '', 'email' => $_em, 'company' => $_c['company'] ?? '', 'source_label' => ''];
}
try {
    $_crRows = $pdo->query(
        "SELECT name, contact FROM cre_creators WHERE is_active = 1 AND contact LIKE '%@%' ORDER BY name ASC LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($_crRows as $_c) {
        $_em = strtolower(trim($_c['contact'] ?? ''));
        if ($_em === '' || !filter_var($_em, FILTER_VALIDATE_EMAIL) || isset($_seenEmails[$_em])) continue;
        $_seenEmails[$_em] = true;
        $_pickerContacts[] = ['name' => $_c['name'] ?? '', 'email' => $_em, 'company' => '', 'source_label' => 'クリエイター'];
    }
} catch (Exception $_e) {}

$hasDetail = ($selectedMessage !== null || $selectedInquiry !== null);

// ステータスラベル (お問い合わせ)
$inqStatusLabels  = ['unread' => '未読', 'read' => '読了', 'replied' => '返信済', 'archived' => 'アーカイブ'];
$inqStatusClasses = ['unread' => 'danger', 'read' => 'muted', 'replied' => 'success', 'archived' => 'muted'];

start_page('メール', '');
?>
<script>
window._MAIL_CONTACTS = <?= json_encode($_pickerContacts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<div class="mail-page-outer">

  <?php if (!$popReady && !$isInquiryMode): ?>
    <div class="alert-box alert-error mail-alert">
      受信設定が未完了です。<a href="<?= h($baseUrl) ?>/mail_settings.php" style="text-decoration:underline;font-weight:700;">メール設定</a>でサーバー情報を保存してください。
    </div>
  <?php endif; ?>

  <div class="mail-app <?= $hasDetail ? 'has-detail' : '' ?>">

    <!-- ── Left nav ─────────────────────── -->
    <nav class="mail-left-nav">
      <button class="mail-compose-btn" type="button" onclick="openCompose()">✏ 新規作成</button>

      <div class="mail-left-nav-label">メール</div>
      <?php foreach (['inbox' => '受信トレイ', 'sent' => '送信済み', 'archive' => 'アーカイブ', 'trash' => 'ゴミ箱'] as $box => $label):
        $isActive = $mailbox === $box;
        $unread   = ($box === 'inbox') ? $unreadMail : 0;
        $count    = $counts[$box] ?? 0;
      ?>
        <a class="mail-folder-item <?= $isActive ? 'active' : '' ?>"
           href="<?= h($baseUrl) ?>/mail.php?mailbox=<?= h($box) ?>">
          <?= h($label) ?>
          <?php if ($unread > 0): ?>
            <span class="mail-folder-badge"><?= h((string)$unread) ?></span>
          <?php elseif ($count > 0): ?>
            <span class="mail-folder-count"><?= h((string)$count) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <div class="mail-nav-divider"></div>
      <div class="mail-left-nav-label">フォーム</div>
      <?php
        $isActive = $mailbox === 'inquiries';
        $count    = $counts['inquiries'] ?? 0;
      ?>
      <a class="mail-folder-item <?= $isActive ? 'active' : '' ?>"
         href="<?= h($baseUrl) ?>/mail.php?mailbox=inquiries">
        お問い合わせ
        <?php if ($unreadInquiries > 0): ?>
          <span class="mail-folder-badge"><?= h((string)$unreadInquiries) ?></span>
        <?php elseif ($count > 0): ?>
          <span class="mail-folder-count"><?= h((string)$count) ?></span>
        <?php endif; ?>
      </a>

      <div class="mail-nav-divider"></div>
      <div class="mail-nav-footer">
        <a class="mail-nav-footer-link" href="<?= h($baseUrl) ?>/mail_contacts.php">宛先管理</a>
        <a class="mail-nav-footer-link" href="<?= h($baseUrl) ?>/mail_settings.php">メール設定</a>
      </div>
    </nav>

    <!-- ── List pane ────────────────────── -->
    <div class="mail-list-pane">
      <div class="mail-list-toolbar">
        <form method="get" style="display:contents;">
          <input type="hidden" name="mailbox" value="<?= h($mailbox) ?>">
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="検索…">
          <button class="ghost-btn" type="submit">検索</button>
          <?php if ($q !== ''): ?>
            <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail.php?mailbox=<?= h($mailbox) ?>">✕</a>
          <?php endif; ?>
        </form>
        <?php if ($mailbox === 'inbox'): ?>
          <form method="post" style="margin:0;">
            <input type="hidden" name="action" value="sync">
            <button class="primary-btn" type="submit">受信</button>
          </form>
        <?php endif; ?>
      </div>

      <div class="mail-list-scroll">
        <?php if (!$messages): ?>
          <div class="empty-state">
            <?= $isInquiryMode ? 'お問い合わせはまだありません。' : 'メールはまだありません。' ?>
          </div>
        <?php endif; ?>

        <?php foreach ($messages as $msg):
          if ($isInquiryMode) {
              $isUnread   = $msg['status'] === 'unread';
              $isSelected = $selectedId === (int)$msg['id'];
              $party      = (string)$msg['name'];
              $subject    = (string)$msg['topic'];
              $excerpt    = (string)$msg['message'];
              $rawTime    = $msg['created_at'];
              $statusLabel = $inqStatusLabels[$msg['status']] ?? '';
              $statusClass = $inqStatusClasses[$msg['status']] ?? 'muted';
          } else {
              $isUnread   = $msg['mailbox'] === 'inbox' && $msg['status'] === 'unread';
              $isSelected = $selectedId === (int)$msg['id'];
              $party      = $msg['mailbox'] === 'sent'
                  ? ('→ ' . mb_strimwidth((string)$msg['to_text'], 0, 24, '…'))
                  : trim((string)($msg['from_name'] ?: $msg['from_email']));
              $subject    = (string)$msg['subject'];
              $excerpt    = trim((string)$msg['body_text']);
              $rawTime    = $msg['received_at'] ?: ($msg['sent_at'] ?: $msg['created_at']);
              $statusLabel = $statusClass = '';
          }
          $when = $rawTime ? date('m/d', strtotime($rawTime)) : '';
        ?>
          <a class="mail-row<?= $isSelected ? ' is-selected' : '' ?><?= $isUnread ? ' is-unread' : '' ?>"
             href="<?= h($baseUrl) ?>/mail.php?mailbox=<?= h($mailbox) ?>&id=<?= (int)$msg['id'] ?>">
            <?php if (!$isInquiryMode): ?>
              <span class="mail-row-star<?= !empty($msg['is_starred']) ? ' is-starred' : '' ?>">★</span>
            <?php else: ?>
              <span class="mail-row-star" style="color:transparent;">★</span>
            <?php endif; ?>
            <div class="mail-row-content">
              <div class="mail-row-from">
                <?php if ($isUnread): ?><span class="mail-unread-dot"></span><?php endif; ?>
                <?= h(mb_strimwidth($party !== '' ? $party : '(不明)', 0, 28, '…')) ?>
                <?php if ($isInquiryMode && $statusLabel !== ''): ?>
                  <span class="status-badge <?= h($statusClass) ?>" style="margin-left:6px;font-size:10px;"><?= h($statusLabel) ?></span>
                <?php endif; ?>
              </div>
              <div class="mail-row-subject">
                <?= h(mb_strimwidth($subject, 0, 44, '…')) ?>
                <span class="mail-row-excerpt"> — <?= h(mb_strimwidth($excerpt, 0, 36, '…')) ?></span>
              </div>
            </div>
            <div class="mail-row-meta">
              <div class="mail-row-time"><?= h($when) ?></div>
              <?php if (!$isInquiryMode && !empty($msg['has_attachments'])): ?><div class="mail-row-time">📎</div><?php endif; ?>
              <?php if (!$isInquiryMode && ($msg['status'] ?? '') === 'failed'): ?><div class="mail-row-time" style="color:var(--danger);">✕</div><?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── Detail pane ──────────────────── -->
    <?php if ($isInquiryMode && $selectedInquiry): ?>
      <?php
        $inq = $selectedInquiry;
        $sl  = $inqStatusLabels[$inq['status']] ?? $inq['status'];
        $sc  = $inqStatusClasses[$inq['status']] ?? 'muted';
        $isArchived = ($inq['status'] === 'archived');
      ?>
      <div class="mail-detail-pane">
        <div class="mail-detail-header">
          <h2 class="mail-detail-subject"><?= h($inq['topic']) ?></h2>
          <div class="mail-detail-meta">
            <div>
              <strong>差出人:</strong>
              <?= h($inq['name']) ?>
              &lt;<a href="mailto:<?= h($inq['email']) ?>" style="color:var(--primary);"><?= h($inq['email']) ?></a>&gt;
            </div>
            <div><strong>日時:</strong> <?= h(format_datetime($inq['created_at'])) ?></div>
            <?php if (!empty($inq['url'])): ?>
              <div><strong>URL:</strong> <a href="<?= h($inq['url']) ?>" target="_blank" rel="noopener" style="color:var(--primary);"><?= h($inq['url']) ?></a></div>
            <?php endif; ?>
          </div>
          <div class="mail-detail-actions">
            <span class="status-badge <?= h($sc) ?>"><?= h($sl) ?></span>
            <?php if (!$isArchived): ?>
              <form method="post">
                <input type="hidden" name="action" value="inquiry_status">
                <input type="hidden" name="inquiry_id" value="<?= (int)$selectedId ?>">
                <input type="hidden" name="status" value="unread">
                <button class="ghost-btn" type="submit">未読に戻す</button>
              </form>
              <form method="post" data-confirm="このお問い合わせをアーカイブしますか？">
                <input type="hidden" name="action" value="inquiry_status">
                <input type="hidden" name="inquiry_id" value="<?= (int)$selectedId ?>">
                <input type="hidden" name="status" value="archived">
                <button class="ghost-btn" type="submit">アーカイブ</button>
              </form>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="action" value="inquiry_status">
                <input type="hidden" name="inquiry_id" value="<?= (int)$selectedId ?>">
                <input type="hidden" name="status" value="read">
                <button class="ghost-btn" type="submit">元に戻す</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- スレッド表示 -->
        <div class="mail-detail-body mail-thread-wrap">
          <div class="mail-thread-bubble from-visitor">
            <div class="mail-thread-bubble-body"><?= nl2br(h($inq['message'])) ?></div>
            <div class="mail-thread-bubble-meta"><?= h($inq['name']) ?> · <?= h(format_datetime($inq['created_at'])) ?></div>
          </div>
          <?php foreach ($inquiryReplies as $reply): ?>
            <div class="mail-thread-bubble from-admin">
              <div class="mail-thread-bubble-body"><?= nl2br(h($reply['body'])) ?></div>
              <div class="mail-thread-bubble-meta">
                <?= h($reply['display_name'] ?? '管理者') ?> · <?= h(format_datetime($reply['created_at'])) ?>
                <?php if ($reply['mail_sent']): ?>
                  <span class="status-badge success" style="font-size:10px;margin-left:4px;">送信済</span>
                <?php else: ?>
                  <span class="status-badge warning" style="font-size:10px;margin-left:4px;">未送信</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!$isArchived): ?>
          <div class="mail-detail-reply">
            <button class="mail-reply-toggle" type="button" onclick="toggleReply(this)">
              ↩ 返信する
            </button>
            <div class="mail-reply-form-wrap">
              <form method="post" class="form-stack">
                <input type="hidden" name="action" value="inquiry_reply">
                <input type="hidden" name="inquiry_id" value="<?= (int)$selectedId ?>">
                <div style="font-size:12px;color:var(--sub);margin-bottom:4px;">
                  送信先: <strong><?= h($inq['name']) ?></strong> &lt;<?= h($inq['email']) ?>&gt;
                </div>
                <label><span>本文</span><textarea name="body" required placeholder="返信内容を入力してください…"></textarea></label>
                <div class="actions-inline">
                  <button class="primary-btn" type="submit">返信を送信</button>
                  <button class="ghost-btn" type="button" onclick="toggleReply(this.closest('.mail-detail-reply').querySelector('.mail-reply-toggle'))">キャンセル</button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>

    <?php elseif (!$isInquiryMode && $selectedMessage): ?>
      <?php $bodyHtml = trim((string)$selectedMessage['body_html']); ?>
      <div class="mail-detail-pane">
        <div class="mail-detail-header">
          <h2 class="mail-detail-subject"><?= h($selectedMessage['subject'] ?: '(件名なし)') ?></h2>
          <div class="mail-detail-meta">
            <div>
              <strong>差出人:</strong>
              <?= h(trim((string)($selectedMessage['from_name'] ?: $selectedMessage['from_email']))) ?>
              <?php if ($selectedMessage['from_email']): ?>
                &lt;<?= h($selectedMessage['from_email']) ?>&gt;
              <?php endif; ?>
            </div>
            <div><strong>宛先:</strong> <?= h($selectedMessage['to_text']) ?></div>
            <?php if (!empty($selectedMessage['cc_text'])): ?>
              <div><strong>Cc:</strong> <?= h($selectedMessage['cc_text']) ?></div>
            <?php endif; ?>
            <div><strong>日時:</strong> <?= h(format_datetime($selectedMessage['received_at'] ?: ($selectedMessage['sent_at'] ?: $selectedMessage['created_at']))) ?></div>
          </div>
          <div class="mail-detail-actions">
            <form method="post">
              <input type="hidden" name="action" value="toggle_star">
              <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
              <button class="ghost-btn" type="submit"><?= !empty($selectedMessage['is_starred']) ? '★ スター解除' : '☆ スター' ?></button>
            </form>
            <?php if ($selectedMessage['mailbox'] === 'inbox'): ?>
              <form method="post">
                <input type="hidden" name="action" value="mark_unread">
                <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
                <button class="ghost-btn" type="submit">未読に戻す</button>
              </form>
              <form method="post">
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
                <button class="ghost-btn" type="submit">アーカイブ</button>
              </form>
            <?php endif; ?>
            <?php if ($selectedMessage['mailbox'] !== 'trash'): ?>
              <form method="post">
                <input type="hidden" name="action" value="trash">
                <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
                <button class="danger-btn" type="submit">ゴミ箱へ</button>
              </form>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
                <button class="ghost-btn" type="submit">元に戻す</button>
              </form>
              <form method="post" data-confirm="完全に削除します。よろしいですか？">
                <input type="hidden" name="action" value="delete_permanent">
                <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
                <button class="danger-btn" type="submit">完全削除</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="mail-detail-body">
          <?php if ($bodyHtml !== ''): ?>
            <iframe class="mail-html-frame" sandbox srcdoc="<?= h($bodyHtml) ?>"></iframe>
          <?php else: ?>
            <?= nl2br(h((string)$selectedMessage['body_text'])) ?>
          <?php endif; ?>
          <?php if ($selectedMessage['status'] === 'failed' && !empty($selectedMessage['error_message'])): ?>
            <div class="alert-box alert-error" style="margin:12px 0 0;">
              送信エラー: <?= h($selectedMessage['error_message']) ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="mail-detail-reply">
          <button class="mail-reply-toggle" type="button" onclick="toggleReply(this)">↩ 返信する</button>
          <div class="mail-reply-form-wrap">
            <form method="post" class="form-stack">
              <input type="hidden" name="action" value="send">
              <input type="hidden" name="reply_to" value="<?= (int)$selectedId ?>">
              <label><span>宛先</span><input type="text" name="to" value="<?= h($replyTo) ?>" list="reply-contact-list" required></label>
              <label><span>件名</span><input type="text" name="subject" value="<?= h($replySubject) ?>" required></label>
              <label><span>本文</span><textarea name="body" required><?= h($replyBody) ?></textarea></label>
              <div class="actions-inline">
                <button class="primary-btn" type="submit">返信を送信</button>
                <button class="ghost-btn" type="button" onclick="toggleReply(this.closest('.mail-detail-reply').querySelector('.mail-reply-toggle'))">キャンセル</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="mail-detail-pane" style="background:#fafbfc;">
        <div class="mail-empty-placeholder">
          <div class="mail-empty-placeholder-icon">✉</div>
          <div>メールを選択してください</div>
        </div>
      </div>
    <?php endif; ?>

  </div><!-- /mail-app -->
</div><!-- /mail-page-outer -->

<!-- ── Compose modal ──────────────────── -->
<div class="mail-compose-overlay" id="compose-overlay" onclick="closeCompose()"></div>
<div class="mail-compose-modal" id="compose-modal">
  <div class="mail-compose-modal-header">
    <span>新規作成</span>
    <button class="mail-compose-close" type="button" onclick="closeCompose()">✕</button>
  </div>
  <form method="post" class="mail-compose-modal-body" id="compose-form">
    <input type="hidden" name="action" value="send">

    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
        <span style="font-size:11px;font-weight:700;">宛先</span>
        <button type="button" class="ghost-btn" style="font-size:.75em;padding:1px 7px;"
          onclick="window._openContactsPicker(document.getElementById('modal-to-wrap'))">名簿</button>
      </div>
      <div class="recipient-wrap" id="modal-to-wrap">
        <input type="hidden" name="to">
        <input type="text" class="recipient-text-input" placeholder="名前またはメールで検索..." autocomplete="off">
        <div class="recipient-ac-dropdown"></div>
      </div>
    </div>

    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
        <span style="font-size:11px;font-weight:700;">Cc</span>
        <button type="button" class="ghost-btn" style="font-size:.75em;padding:1px 7px;"
          onclick="window._openContactsPicker(document.getElementById('modal-cc-wrap'))">名簿</button>
      </div>
      <div class="recipient-wrap" id="modal-cc-wrap">
        <input type="hidden" name="cc">
        <input type="text" class="recipient-text-input" placeholder="Cc..." autocomplete="off">
        <div class="recipient-ac-dropdown"></div>
      </div>
    </div>

    <label><span>件名</span><input type="text" name="subject" required></label>
    <label><span>本文</span><textarea name="body" required></textarea></label>
  </form>
  <div class="mail-compose-modal-footer">
    <button class="primary-btn" type="button" onclick="document.getElementById('compose-form').submit()">送信</button>
    <button class="ghost-btn" type="button" onclick="closeCompose()">キャンセル</button>
  </div>
</div>

<!-- 名簿ピッカーモーダル -->
<div class="contacts-picker-modal" id="contacts-picker-modal">
  <div class="contacts-picker-inner">
    <div class="contacts-picker-head">
      <h3>名簿から宛先を選ぶ</h3>
      <button type="button" class="contacts-picker-close-btn ghost-btn" style="padding:2px 9px;font-size:.82em;">閉じる</button>
    </div>
    <div class="contacts-picker-search">
      <input type="text" placeholder="名前・メールアドレス・会社名で検索...">
    </div>
    <div class="contacts-picker-list"></div>
    <div class="contacts-picker-foot">
      <span class="contacts-picker-selected-count"></span>
      <div class="actions-inline">
        <button type="button" class="ghost-btn contacts-picker-close-btn">キャンセル</button>
        <button type="button" class="primary-btn contacts-picker-add-btn">選択した宛先を追加</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= h(rtrim($baseUrl, '/')) ?>/assets/js/recipient.js?v=20260508-1"></script>
<script>
(function () {
  var POLL_INTERVAL = 90 * 1000;
  var pollUrl = <?= json_encode(rtrim($baseUrl, '/') . '/mail/poll.php') ?>;

  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  function updateInboxBadge(unread) {
    var badge = document.querySelector('.mail-folder-item[href*="mailbox=inbox"] .mail-folder-badge');
    var countEl = document.querySelector('.mail-folder-item[href*="mailbox=inbox"] .mail-folder-count');
    if (unread > 0) {
      if (badge) { badge.textContent = unread; }
      else if (countEl) { countEl.className = 'mail-folder-badge'; countEl.textContent = unread; }
    }
  }

  function showDesktopNotification(count) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
      new Notification('CORO PROJECT — 新着メール', {
        body: '新しいメールが ' + count + ' 件届きました。',
        tag: 'coro-mail-new',
      });
    } catch (e) {}
  }

  function poll() {
    fetch(pollUrl, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.new > 0) {
          showDesktopNotification(data.new);
          updateInboxBadge(data.unread);
        }
      })
      .catch(function () {});
  }

  setTimeout(function () {
    poll();
    setInterval(poll, POLL_INTERVAL);
  }, POLL_INTERVAL);
})();

function openCompose(opts) {
  var modal = document.getElementById('compose-modal');
  modal.classList.add('open');
  document.getElementById('compose-overlay').classList.add('open');
  var toWrap = document.getElementById('modal-to-wrap');
  if (opts && opts.to && toWrap && toWrap._setOne) {
    toWrap._setOne(opts.to);
  }
  if (opts && opts.subject) modal.querySelector('[name="subject"]').value = opts.subject;
  if (opts && opts.body)    modal.querySelector('[name="body"]').value = opts.body;
  var toFilled = toWrap && toWrap.querySelector('input[type="hidden"]').value !== '';
  if (toFilled) {
    var sub = modal.querySelector('[name="subject"]');
    if (sub) sub.focus();
  } else if (toWrap) {
    var ti = toWrap.querySelector('.recipient-text-input');
    if (ti) ti.focus();
  }
}
function closeCompose() {
  document.getElementById('compose-modal').classList.remove('open');
  document.getElementById('compose-overlay').classList.remove('open');
}
function toggleReply(btn) {
  var wrap = btn.parentElement.querySelector('.mail-reply-form-wrap');
  if (!wrap) return;
  var isOpen = wrap.classList.toggle('open');
  if (isOpen) {
    var ta = wrap.querySelector('textarea');
    if (ta) ta.focus();
  }
}
</script>
<?php end_page(); ?>
