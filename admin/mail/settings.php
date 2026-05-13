<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$user = current_admin_user();
admin_mail_ensure_schema($pdo);
$settings = load_app_settings($pdo, $config);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim($_POST['action'] ?? 'save');

    if ($action === 'test_receive') {
        try {
            admin_mail_test_receive_connection($settings);
            set_flash('success', '受信サーバーへの接続に成功しました。');
        } catch (Exception $e) {
            set_flash('error', '受信サーバー接続に失敗しました: ' . $e->getMessage());
        }
        redirect_to($baseUrl . '/mail/settings.php');
    }

    // テンプレート設定の保存
    if ($action === 'save_template') {
        $templateMap = [
            'contact_reply_subject' => trim($_POST['contact_reply_subject'] ?? ''),
            'contact_reply_body' => trim($_POST['contact_reply_body'] ?? ''),
        ];

        if (!$templateMap['contact_reply_subject'] || !$templateMap['contact_reply_body']) {
            set_flash('error', '件名と本文の両方を入力してください。');
            redirect_to($baseUrl . '/mail/settings.php');
        }

        save_app_settings_map($pdo, (int)$user['id'], $templateMap);
        write_admin_log($pdo, (int)$user['id'], 'edit', 'mail_settings', null, '自動返信メールテンプレートを更新しました');
        set_flash('success', '自動返信メールテンプレートを保存しました。');
        redirect_to($baseUrl . '/mail/settings.php');
    }

    // SMTP/受信設定の保存
    $fields = [
        'smtp_host',
        'smtp_port',
        'smtp_secure',
        'smtp_user',
        'smtp_pass',
        'smtp_from_email',
        'smtp_from_name',
        'mail_receive_protocol',
        'mail_pop_host',
        'mail_pop_port',
        'mail_pop_encryption',
        'mail_pop_user',
        'mail_pop_pass',
        'mail_sync_limit',
    ];

    $map = [];
    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        if (in_array($field, ['smtp_pass', 'mail_pop_pass'], true) && $value === '' && !empty($settings[$field])) {
            $value = (string)$settings[$field];
        }
        $map[$field] = $value;
    }

    if (!filter_var($map['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
        set_flash('error', '送信元メールアドレスを正しく入力してください。');
        redirect_to($baseUrl . '/mail/settings.php');
    }

    if ((int)$map['smtp_port'] <= 0 || (int)$map['mail_pop_port'] <= 0) {
        set_flash('error', 'ポート番号を正しく入力してください。');
        redirect_to($baseUrl . '/mail/settings.php');
    }

    if ((int)$map['mail_sync_limit'] <= 0) {
        $map['mail_sync_limit'] = '50';
    }
    if (!in_array($map['mail_receive_protocol'], ['imap', 'pop3'], true)) {
        $map['mail_receive_protocol'] = 'imap';
    }

    save_app_settings_map($pdo, (int)$user['id'], $map);
    write_admin_log($pdo, (int)$user['id'], 'edit', 'mail_settings', null, 'メール設定を更新しました');
    set_flash('success', 'メール設定を保存しました。');
    redirect_to($baseUrl . '/mail/settings.php');
}

$settings = load_app_settings($pdo, $config);

$receiveProtocol = admin_mail_receive_protocol($settings);

start_page('メール設定', 'ミニムのSMTP/IMAP情報を設定します');
?>
<main class="page-container narrow">
  <section class="page-header-block with-actions">
    <div>
      <h1>メール設定</h1>
      <p>送信は現在動いている localhost SMTP を維持し、受信方式はIMAP/POP3から選べます。</p>
    </div>
    <div class="actions-inline">
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail.php">メール管理</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail_compose.php">新規作成</a>
    </div>
  </section>

  <section class="card form-card">
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="save">

      <h2 class="section-heading">送信設定 SMTP</h2>
      <div class="form-grid two">
        <label><span>SMTPホスト</span><input type="text" name="smtp_host" value="<?= h($settings['smtp_host'] ?? 'localhost') ?>" placeholder="localhost" required></label>
        <label><span>SMTPポート</span><input type="number" name="smtp_port" value="<?= h($settings['smtp_port'] ?? '25') ?>" placeholder="25" required></label>
      </div>
      <div class="form-grid two">
        <label><span>SMTP暗号化</span>
          <select name="smtp_secure">
            <option value="ssl" <?= selected($settings['smtp_secure'] ?? 'ssl', 'ssl') ?>>SSL / SMTPS</option>
            <option value="tls" <?= selected($settings['smtp_secure'] ?? 'ssl', 'tls') ?>>STARTTLS</option>
            <option value="none" <?= selected($settings['smtp_secure'] ?? 'ssl', 'none') ?>>なし</option>
          </select>
        </label>
        <label><span>SMTPユーザー名</span><input type="text" name="smtp_user" value="<?= h($settings['smtp_user']) ?>" placeholder="m13017-info" required></label>
      </div>
      <label>
        <span>SMTPパスワード<?= !empty($settings['smtp_pass']) ? '（保存済み。変更時だけ入力）' : '' ?></span>
        <input type="password" name="smtp_pass" value="" autocomplete="new-password" <?= empty($settings['smtp_pass']) ? 'required' : '' ?>>
      </label>
      <div class="form-grid two">
        <label><span>送信元メールアドレス</span><input type="email" name="smtp_from_email" value="<?= h($settings['smtp_from_email']) ?>" placeholder="info@coroproject.jp" required></label>
        <label><span>送信者名</span><input type="text" name="smtp_from_name" value="<?= h($settings['smtp_from_name']) ?>" placeholder="CORO PROJECT"></label>
      </div>

      <h2 class="section-heading mt-24">受信設定</h2>
      <label><span>受信方式</span>
        <select name="mail_receive_protocol">
          <option value="imap" <?= selected($receiveProtocol, 'imap') ?>>IMAP（推奨）</option>
          <option value="pop3" <?= selected($receiveProtocol, 'pop3') ?>>POP3（従来方式）</option>
        </select>
      </label>
      <div class="form-grid two">
        <label><span>受信ホスト</span><input type="text" name="mail_pop_host" value="<?= h($settings['mail_pop_host'] ?? 's221.myssl.jp') ?>" placeholder="s221.myssl.jp" required></label>
        <label><span>受信ポート</span><input type="number" name="mail_pop_port" value="<?= h($settings['mail_pop_port'] ?? ($receiveProtocol === 'imap' ? '993' : '995')) ?>" placeholder="<?= $receiveProtocol === 'imap' ? '993' : '995' ?>" required></label>
      </div>
      <div class="form-grid two">
        <label><span>受信暗号化</span>
          <select name="mail_pop_encryption">
            <option value="ssl" <?= selected($settings['mail_pop_encryption'] ?? 'ssl', 'ssl') ?>>SSL</option>
            <option value="tls" <?= selected($settings['mail_pop_encryption'] ?? 'ssl', 'tls') ?>>STARTTLS</option>
            <option value="none" <?= selected($settings['mail_pop_encryption'] ?? 'ssl', 'none') ?>>なし</option>
          </select>
        </label>
        <label><span>受信ユーザー名</span><input type="text" name="mail_pop_user" value="<?= h($settings['mail_pop_user'] ?? 'm13017-info') ?>" placeholder="m13017-info" required></label>
      </div>
      <label>
        <span>受信パスワード<?= !empty($settings['mail_pop_pass']) ? '（保存済み。変更時だけ入力）' : '' ?></span>
        <input type="password" name="mail_pop_pass" value="" autocomplete="new-password" <?= empty($settings['mail_pop_pass']) ? 'required' : '' ?>>
      </label>
      <label><span>1回の受信同期件数</span><input type="number" name="mail_sync_limit" value="<?= h($settings['mail_sync_limit']) ?>" min="1" max="200"></label>

      <div class="alert-box alert-success" style="margin:0;">
        送信は動作確認済みの <strong>localhost:25</strong> を使えます。IMAP受信は通常 <strong>s221.myssl.jp:993 SSL</strong>、POP3受信は <strong>s221.myssl.jp:995 SSL</strong> です。
      </div>

      <div class="actions-inline">
        <button class="primary-btn" type="submit">設定を保存</button>
      </div>
    </form>
  </section>

  <section class="card form-card mt-24">
    <h2 class="section-heading">お問い合わせフォーム 自動返信メール</h2>
    <p class="muted">コンタクトフォームから送信されたお問い合わせに対する自動返信メールのテンプレートを設定します。</p>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="save_template">

      <label>
        <span>自動返信メール件名</span>
        <input type="text" name="contact_reply_subject" value="<?= h($settings['contact_reply_subject'] ?? 'お問い合わせありがとうございます | CORO PROJECT') ?>" placeholder="お問い合わせありがとうございます | CORO PROJECT" required>
      </label>

      <label>
        <span>自動返信メール本文</span>
        <textarea name="contact_reply_body" rows="12" placeholder="お疲れ様です。&#10;&#10;{name}様&#10;&#10;この度は、CORO PROJECTへのお問い合わせをいただき、ありがとうございます。&#10;&#10;お送りいただいたお問い合わせを受け付けいたしました。&#10;内容を確認のうえ、このメールアドレス宛にご返信差し上げます。&#10;&#10;【お問い合わせ内容】&#10;種別: {topic}&#10;{company_line}&#10;&#10;---&#10;{message}&#10;---&#10;&#10;ご不明な点がございましたら、お気軽にお問い合わせください。&#10;&#10;よろしくお願いいたします。&#10;&#10;CORO PROJECT&#10;https://coroproject.jp/" required><?= h($settings['contact_reply_body'] ?? '') ?></textarea>
        <small class="field-note">
          以下の変数を使用できます：<br>
          <strong>{name}</strong> = お名前<br>
          <strong>{company}</strong> = 会社名（入力された場合のみ表示）<br>
          <strong>{company_line}</strong> = 会社名（入力時: 「会社名: [会社名]」、未入力時: 空）<br>
          <strong>{topic}</strong> = お問い合わせ種別<br>
          <strong>{message}</strong> = お問い合わせ内容
        </small>
      </label>

      <div class="actions-inline">
        <button class="primary-btn" type="submit">テンプレートを保存</button>
      </div>
    </form>
  </section>

  <section class="card form-card mt-24">
    <h2 class="section-heading">接続確認</h2>
    <p class="muted">保存済みの受信設定でサーバーにログインできるか確認します。</p>
    <form method="post" class="actions-inline">
      <input type="hidden" name="action" value="test_receive">
      <button class="ghost-btn" type="submit">受信接続を確認</button>
      <a class="primary-btn" href="<?= h($baseUrl) ?>/mail.php?mailbox=inbox">受信トレイで同期</a>
    </form>
  </section>
</main>
<?php end_page(); ?>
