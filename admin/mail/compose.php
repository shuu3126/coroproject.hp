<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$user = current_admin_user();
$settings = load_app_settings($pdo, $config);
admin_mail_ensure_schema($pdo);

$replyToId = (int)($_GET['reply_to'] ?? ($_POST['reply_to'] ?? 0));
$replyMessage = null;
if ($replyToId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM mail_messages WHERE id = ? LIMIT 1');
    $stmt->execute([$replyToId]);
    $replyMessage = $stmt->fetch();
}

$to = trim($_GET['to'] ?? '');
$cc = '';
$bcc = '';
$subject = '';
$body = '';

if ($replyMessage) {
    $to = $replyMessage['direction'] === 'outbound'
        ? (string)$replyMessage['to_text']
        : trim((string)($replyMessage['from_name'] && $replyMessage['from_email']
            ? $replyMessage['from_name'] . ' <' . $replyMessage['from_email'] . '>'
            : $replyMessage['from_email']));
    $subject = preg_match('/^\s*re\s*:/i', (string)$replyMessage['subject'])
        ? (string)$replyMessage['subject']
        : 'Re: ' . (string)$replyMessage['subject'];
    $quoted = trim((string)$replyMessage['body_text']);
    if ($quoted !== '') {
        $body = "\n\n--- 元のメール ---\n" . preg_replace('/^/m', '> ', $quoted);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $to      = trim($_POST['to']      ?? '');
    $cc      = trim($_POST['cc']      ?? '');
    $bcc     = trim($_POST['bcc']     ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body']    ?? '');

    try {
        $sentId = admin_mail_send_message($pdo, $settings, (int)$user['id'], $to, $subject, $body, $cc, $bcc, $replyToId ?: null);
        write_admin_log($pdo, (int)$user['id'], 'send', 'mail', (string)$sentId, 'メールを送信しました');
        set_flash('success', 'メールを送信しました。');
        redirect_to($baseUrl . '/mail/detail.php?id=' . $sentId . '&back=sent');
    } catch (Exception $e) {
        set_flash('error', 'メール送信に失敗しました: ' . $e->getMessage());
    }
}

// ── 名簿データ構築（mail_contacts + cre_creators のメールアドレス） ──
$pickerContacts = [];
$seenEmails = [];

$mcRows = $pdo->query(
    'SELECT name, email, company FROM mail_contacts ORDER BY COALESCE(last_contacted_at, created_at) DESC, name ASC LIMIT 500'
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($mcRows as $c) {
    $em = strtolower(trim($c['email'] ?? ''));
    if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$em])) continue;
    $seenEmails[$em] = true;
    $pickerContacts[] = ['name' => $c['name'] ?? '', 'email' => $em, 'company' => $c['company'] ?? '', 'source_label' => ''];
}

try {
    $crRows = $pdo->query(
        "SELECT name, contact FROM cre_creators WHERE is_active = 1 AND contact LIKE '%@%' ORDER BY name ASC LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($crRows as $c) {
        $em = strtolower(trim($c['contact'] ?? ''));
        if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$em])) continue;
        $seenEmails[$em] = true;
        $pickerContacts[] = ['name' => $c['name'] ?? '', 'email' => $em, 'company' => '', 'source_label' => 'クリエイター'];
    }
} catch (Exception $e) {}

$smtpReady = admin_mail_setting($settings, 'smtp_host') !== ''
    && admin_mail_setting($settings, 'smtp_user') !== ''
    && admin_mail_setting($settings, 'smtp_pass') !== '';

start_page('メール作成', '管理画面からメールを送信します');
?>
<script>
window._MAIL_CONTACTS = <?= json_encode($pickerContacts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<main class="page-container narrow">
  <section class="page-header-block with-actions">
    <div>
      <h1>メール作成</h1>
      <p>送信履歴はメール管理の「送信済み」に保存されます。</p>
    </div>
    <div class="actions-inline">
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail/index.php?mailbox=inbox">受信トレイ</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail/contacts.php">宛先管理</a>
    </div>
  </section>

  <?php if (!$smtpReady): ?>
    <div class="alert-box alert-error" style="margin:0 0 16px;">
      送信設定が未完了です。<a href="<?= h($baseUrl) ?>/mail/settings.php" style="text-decoration:underline;">メール設定</a>でSMTP情報とパスワードを保存してください。
    </div>
  <?php endif; ?>

  <section class="card form-card">
    <form method="post" class="form-stack" id="compose-form">
      <input type="hidden" name="reply_to" value="<?= (int)$replyToId ?>">

      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <span class="field-label" style="margin:0;">宛先</span>
          <button type="button" class="ghost-btn" style="font-size:.78em;padding:2px 9px;"
            onclick="window._openContactsPicker(document.getElementById('to-wrap'))">名簿から選ぶ</button>
        </div>
        <div class="recipient-wrap" id="to-wrap">
          <input type="hidden" name="to" value="<?= h($to) ?>">
          <input type="text" class="recipient-text-input" placeholder="名前またはメールアドレスで検索..." autocomplete="off">
          <div class="recipient-ac-dropdown"></div>
        </div>
      </div>

      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <span class="field-label" style="margin:0;">Cc</span>
          <button type="button" class="ghost-btn" style="font-size:.78em;padding:2px 9px;"
            onclick="window._openContactsPicker(document.getElementById('cc-wrap'))">名簿から選ぶ</button>
        </div>
        <div class="recipient-wrap" id="cc-wrap">
          <input type="hidden" name="cc" value="<?= h($cc) ?>">
          <input type="text" class="recipient-text-input" placeholder="Cc の宛先..." autocomplete="off">
          <div class="recipient-ac-dropdown"></div>
        </div>
      </div>

      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <span class="field-label" style="margin:0;">Bcc</span>
          <button type="button" class="ghost-btn" style="font-size:.78em;padding:2px 9px;"
            onclick="window._openContactsPicker(document.getElementById('bcc-wrap'))">名簿から選ぶ</button>
        </div>
        <div class="recipient-wrap" id="bcc-wrap">
          <input type="hidden" name="bcc" value="<?= h($bcc) ?>">
          <input type="text" class="recipient-text-input" placeholder="Bcc の宛先..." autocomplete="off">
          <div class="recipient-ac-dropdown"></div>
        </div>
      </div>

      <label><span>件名</span><input type="text" name="subject" value="<?= h($subject) ?>" required></label>
      <label><span>本文</span><textarea name="body" rows="14" required><?= h($body) ?></textarea></label>

      <div class="actions-inline">
        <button class="primary-btn" type="submit">送信</button>
        <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail/index.php?mailbox=inbox">キャンセル</a>
      </div>
    </form>
  </section>
</main>

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
<?php end_page(); ?>
