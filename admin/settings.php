<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();
$canManageUsers = admin_user_can_manage_users($user);

$defaults = app_settings_defaults($config);
$currentSettings = load_app_settings($pdo, $config);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim($_POST['action'] ?? 'save_settings');

    if ($action === 'export_admin_data') {
        if (!$canManageUsers) {
            set_flash('error', 'データのエクスポート権限がありません。');
            redirect_to($baseUrl . '/settings.php');
        }
        admin_data_send_export($pdo);
    }

    if ($action === 'import_admin_data') {
        if (!$canManageUsers) {
            set_flash('error', 'データのインポート権限がありません。');
            redirect_to($baseUrl . '/settings.php');
        }

        $mode = trim($_POST['import_mode'] ?? 'merge');
        if ($mode === 'replace' && empty($_POST['confirm_replace'])) {
            set_flash('error', '全置き換えを行う場合は確認チェックを入れてください。');
            redirect_to($baseUrl . '/settings.php');
        }

        try {
            $stats = admin_data_import_file($pdo, $_FILES['import_file'] ?? null, $mode);
            $total = 0;
            foreach ($stats as $row) {
                $total += (int)$row['count'];
            }
            write_admin_log($pdo, (int)$user['id'], 'import', 'admin_data', null, '管理データをインポートしました: ' . $total . '件');
            set_flash('success', '管理データをインポートしました（' . $total . '件）。');
        } catch (Exception $e) {
            set_flash('error', 'インポートに失敗しました: ' . $e->getMessage());
        }
        redirect_to($baseUrl . '/settings.php');
    }

    if ($action === 'create_admin_user') {
        if (!$canManageUsers) {
            set_flash('error', 'ログインユーザーの追加権限がありません。');
            redirect_to($baseUrl . '/settings.php');
        }

        $loginId = trim($_POST['new_login_id'] ?? '');
        $displayName = trim($_POST['new_display_name'] ?? '');
        $email = trim($_POST['new_email'] ?? '');
        $password = (string)($_POST['new_password'] ?? '');
        $passwordConfirm = (string)($_POST['new_password_confirm'] ?? '');
        $isActive = isset($_POST['new_is_active']) ? 1 : 0;

        if ($loginId === '' || $displayName === '' || $password === '') {
            set_flash('error', 'ログインID、表示名、パスワードを入力してください。');
            redirect_to($baseUrl . '/settings.php');
        }
        if (mb_strlen($loginId) > 100 || mb_strlen($displayName) > 100) {
            set_flash('error', 'ログインIDと表示名は100文字以内で入力してください。');
            redirect_to($baseUrl . '/settings.php');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'メールアドレスの形式が正しくありません。');
            redirect_to($baseUrl . '/settings.php');
        }
        if (strlen($password) < 8) {
            set_flash('error', 'パスワードは8文字以上で入力してください。');
            redirect_to($baseUrl . '/settings.php');
        }
        if ($password !== $passwordConfirm) {
            set_flash('error', '確認用パスワードが一致しません。');
            redirect_to($baseUrl . '/settings.php');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO admin_users (login_id, password_hash, display_name, email, is_active)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $loginId,
                password_hash($password, PASSWORD_DEFAULT),
                $displayName,
                $email !== '' ? $email : null,
                $isActive,
            ]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'admin_user', (string)$pdo->lastInsertId(), 'ログインユーザーを追加しました: ' . $loginId);
            set_flash('success', 'ログインユーザーを追加しました。');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                set_flash('error', '同じログインIDのユーザーが既に存在します。');
            } else {
                set_flash('error', 'ログインユーザーの追加に失敗しました。');
            }
        }
        redirect_to($baseUrl . '/settings.php#settings-admin-users');
    }

    if ($action === 'update_admin_user') {
        if (!$canManageUsers) {
            set_flash('error', 'ログインユーザーの編集権限がありません。');
            redirect_to($baseUrl . '/settings.php');
        }

        $targetId    = (int)($_POST['edit_user_id'] ?? 0);
        $displayName = trim($_POST['edit_display_name'] ?? '');
        $email       = trim($_POST['edit_email'] ?? '');
        $password    = (string)($_POST['edit_password'] ?? '');
        $passwordConfirm = (string)($_POST['edit_password_confirm'] ?? '');
        $isActive    = isset($_POST['edit_is_active']) ? 1 : 0;

        if ($targetId <= 0 || $displayName === '') {
            set_flash('error', '表示名を入力してください。');
            redirect_to($baseUrl . '/settings.php#settings-admin-users');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'メールアドレスの形式が正しくありません。');
            redirect_to($baseUrl . '/settings.php?edit_user_id=' . $targetId . '#settings-admin-users');
        }
        if ($targetId === (int)$user['id'] && !$isActive) {
            set_flash('error', '自分自身を無効にすることはできません。');
            redirect_to($baseUrl . '/settings.php?edit_user_id=' . $targetId . '#settings-admin-users');
        }
        if ($password !== '') {
            if (strlen($password) < 8) {
                set_flash('error', 'パスワードは8文字以上で入力してください。');
                redirect_to($baseUrl . '/settings.php?edit_user_id=' . $targetId . '#settings-admin-users');
            }
            if ($password !== $passwordConfirm) {
                set_flash('error', '確認用パスワードが一致しません。');
                redirect_to($baseUrl . '/settings.php?edit_user_id=' . $targetId . '#settings-admin-users');
            }
        }

        try {
            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE admin_users SET display_name=?, email=?, is_active=?, password_hash=? WHERE id=?');
                $stmt->execute([$displayName, $email !== '' ? $email : null, $isActive, password_hash($password, PASSWORD_DEFAULT), $targetId]);
            } else {
                $stmt = $pdo->prepare('UPDATE admin_users SET display_name=?, email=?, is_active=? WHERE id=?');
                $stmt->execute([$displayName, $email !== '' ? $email : null, $isActive, $targetId]);
            }
            write_admin_log($pdo, (int)$user['id'], 'edit', 'admin_user', (string)$targetId, 'ログインユーザーを更新しました');
            set_flash('success', 'ユーザー情報を更新しました。');
        } catch (PDOException $e) {
            set_flash('error', 'ユーザー情報の更新に失敗しました。');
        }
        redirect_to($baseUrl . '/settings.php#settings-admin-users');
    }

    if ($action === 'delete_admin_user') {
        if (!$canManageUsers) {
            set_flash('error', 'ログインユーザーの削除権限がありません。');
            redirect_to($baseUrl . '/settings.php');
        }

        $targetId = (int)($_POST['delete_user_id'] ?? 0);

        if ($targetId === (int)$user['id']) {
            set_flash('error', '自分自身を削除することはできません。');
            redirect_to($baseUrl . '/settings.php#settings-admin-users');
        }

        $activeCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_users WHERE is_active = 1')->fetchColumn();
        $targetActive = (int)$pdo->query("SELECT is_active FROM admin_users WHERE id = $targetId")->fetchColumn();
        if ($activeCount <= 1 && $targetActive) {
            set_flash('error', '有効なユーザーが1人しかいないため削除できません。');
            redirect_to($baseUrl . '/settings.php#settings-admin-users');
        }

        $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$targetId]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'admin_user', (string)$targetId, 'ログインユーザーを削除しました');
        set_flash('success', 'ユーザーを削除しました。');
        redirect_to($baseUrl . '/settings.php#settings-admin-users');
    }

    $publicXUrl = trim($_POST['public_social_x_url'] ?? '');
    $publicMailAddress = trim($_POST['public_social_mail_address'] ?? '');
    if ($publicXUrl !== '' && !filter_var($publicXUrl, FILTER_VALIDATE_URL)) {
        set_flash('error', '公開SNS・連絡先リンクのX URL形式が正しくありません。');
        redirect_to($baseUrl . '/settings.php#settings-public-links');
    }
    if ($publicMailAddress !== '' && !filter_var($publicMailAddress, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', '公開SNS・連絡先リンクのMailアドレス形式が正しくありません。');
        redirect_to($baseUrl . '/settings.php#settings-public-links');
    }

    $map = [];
    foreach ($defaults as $key => $default) {
        if (!array_key_exists($key, $_POST)) {
            continue;
        }
        $value = trim(isset($_POST[$key]) ? $_POST[$key] : '');
        if (in_array($key, ['smtp_pass', 'mail_pop_pass'], true) && $value === '' && !empty($currentSettings[$key])) {
            $value = (string)$currentSettings[$key];
        }
        $map[$key] = $value;
    }
    save_app_settings_map($pdo, $user['id'], $map);
    write_admin_log($pdo, (int)$user['id'], 'edit', 'settings', null, '設定を更新しました');
    set_flash('success', '設定を保存しました。');
    redirect_to($baseUrl . '/settings.php');
}
$settings = load_app_settings($pdo, $config);
$adminUsers = [];
$dataCounts = [];
$editTargetUser = null;
if ($canManageUsers) {
    $adminUsers = $pdo->query('SELECT id, login_id, display_name, email, is_active, last_login_at, created_at FROM admin_users ORDER BY id ASC')->fetchAll();
    $dataCounts = admin_data_counts($pdo);
    $editUserId = (int)($_GET['edit_user_id'] ?? 0);
    if ($editUserId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$editUserId]);
        $editTargetUser = $stmt->fetch() ?: null;
    }
}
start_page('設定', '各部門共通の設定をまとめて管理します。');
?>
<main class="page-container narrow">
  <form method="post" class="form-stack">
    <input type="hidden" name="action" value="save_settings">

    <!-- 全体設定 -->
    <div id="settings-general" class="card form-card form-stack settings-section">
      <h2 class="section-heading">全体設定</h2>
      <div class="form-grid two">
        <label><span>事務所名</span><input type="text" name="office_name" value="<?= h($settings['office_name']) ?>"></label>
        <label><span>代表メールアドレス</span><input type="email" name="office_email" value="<?= h($settings['office_email']) ?>"></label>
      </div>
    </div>

    <div id="settings-public-links" class="card form-card form-stack settings-section">
      <h2 class="section-heading">公開SNS・連絡先リンク</h2>
      <p class="muted">公開ページのフッターやメールボタンに使うリンクです。現在はXとMailのみ表示します。</p>
      <div class="form-grid two">
        <label><span>X URL</span><input type="url" name="public_social_x_url" value="<?= h($settings['public_social_x_url']) ?>" placeholder="https://x.com/CoroProjectJP"></label>
        <label><span>Mail アドレス</span><input type="email" name="public_social_mail_address" value="<?= h($settings['public_social_mail_address']) ?>" placeholder="info@coroproject.jp"></label>
      </div>
    </div>

    <!-- メール送信（SMTP）設定 -->
    <div id="settings-mail" class="card form-card form-stack settings-section">
      <h2 class="section-heading">メール送信設定（SMTP）</h2>
      <p class="muted">管理画面からお問い合わせへ返信する際に使用します。未入力の場合はメール送信が無効になります。</p>
      <div class="form-grid two">
        <label><span>SMTPホスト</span><input type="text" name="smtp_host" value="<?= h($settings['smtp_host']) ?>" placeholder="例: s221.myssl.jp"></label>
        <label><span>SMTPポート</span><input type="number" name="smtp_port" value="<?= h($settings['smtp_port']) ?>" placeholder="465"></label>
      </div>
      <label><span>SMTP暗号化</span>
        <select name="smtp_secure">
          <option value="ssl" <?= selected($settings['smtp_secure'] ?? 'ssl', 'ssl') ?>>SSL / SMTPS</option>
          <option value="tls" <?= selected($settings['smtp_secure'] ?? 'ssl', 'tls') ?>>STARTTLS</option>
          <option value="none" <?= selected($settings['smtp_secure'] ?? 'ssl', 'none') ?>>なし</option>
        </select>
      </label>
      <div class="form-grid two">
        <label><span>SMTPユーザー名</span><input type="text" name="smtp_user" value="<?= h($settings['smtp_user']) ?>" placeholder="例: m12974-info"></label>
        <label><span>SMTPパスワード</span><input type="password" name="smtp_pass" value="<?= h($settings['smtp_pass']) ?>"></label>
      </div>
      <div class="form-grid two">
        <label><span>送信元メールアドレス</span><input type="email" name="smtp_from_email" value="<?= h($settings['smtp_from_email']) ?>"></label>
        <label><span>送信者名</span><input type="text" name="smtp_from_name" value="<?= h($settings['smtp_from_name']) ?>"></label>
      </div>
    </div>

    <!-- Production 会計設定 -->
    <div id="settings-accounting" class="card form-card form-stack settings-section">
      <h2 class="section-heading">Production — 会計設定</h2>
      <label><span>事務所振込先情報</span><textarea name="office_bank_info" rows="4"><?= h($settings['office_bank_info']) ?></textarea></label>
      <label><span>請求書備考テンプレート</span><textarea name="office_invoice_note" rows="4"><?= h($settings['office_invoice_note']) ?></textarea></label>
      <div class="form-grid two">
        <label><span>デフォルト為替レート（USD→JPY）</span><input type="text" name="fx_default_rate" value="<?= h($settings['fx_default_rate']) ?>"></label>
        <label><span>為替APIキー（exchangerate-api.com）</span><input type="text" name="fx_api_key" value="<?= h($settings['fx_api_key'] ?? '') ?>"></label>
      </div>
      <p class="help-text">為替APIキーは請求書作成画面で最新レートを取得する際に使います。</p>
    </div>

    <!-- PDF設定（全部門共通） -->
    <div id="settings-pdf" class="card form-card form-stack settings-section">
      <h2 class="section-heading">PDF設定（全部門共通）</h2>
      <div class="form-grid two">
        <label><span>フォントパス</span><input type="text" name="pdf_font_path" value="<?= h($settings['pdf_font_path']) ?>"></label>
        <label><span>印影PNGパス</span><input type="text" name="pdf_stamp_path" value="<?= h($settings['pdf_stamp_path']) ?>"></label>
      </div>
      <p class="help-text">フォント: admin/resources/fonts/ipaexg.ttf　印影: admin/resources/stamps/hanko.png</p>
    </div>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">設定を保存する</button>
    </div>
  </form>

  <?php if ($canManageUsers): ?>
    <section id="settings-data-transfer" class="card form-card form-stack mt-24 settings-section">
      <h2 class="section-heading">データ入出力</h2>
      <p class="muted">顧客、タレント、案件、会計、メッセージ、設定などの管理データをJSONでバックアップ・復元できます。ログインユーザーは対象外です。</p>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>データ種別</th>
              <th style="width:120px;">件数</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dataCounts as $row): ?>
              <tr>
                <td><?= h($row['label']) ?></td>
                <td class="text-right"><?= h((string)$row['count']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form method="post" class="actions-inline">
        <input type="hidden" name="action" value="export_admin_data">
        <button class="primary-btn" type="submit">管理データをエクスポート</button>
      </form>

      <form method="post" enctype="multipart/form-data" class="form-stack" data-confirm="選択したJSONファイルをインポートします。よろしいですか？">
        <input type="hidden" name="action" value="import_admin_data">
        <label>
          <span>インポートJSON</span>
          <input type="file" name="import_file" accept="application/json,.json" required>
        </label>
        <label>
          <span>インポート方式</span>
          <select name="import_mode">
            <option value="merge">追加・更新（既存データは残す）</option>
            <option value="replace">全置き換え（対象データを削除してから取り込む）</option>
          </select>
        </label>
        <label class="checkbox-row">
          <input type="checkbox" name="confirm_replace" value="1">
          <span>全置き換えを選んだ場合、現在の対象データが削除されることを理解しました</span>
        </label>
        <div class="actions-inline">
          <button class="primary-btn" type="submit">管理データをインポート</button>
        </div>
      </form>
    </section>

    <section id="settings-admin-users" class="card form-card form-stack mt-24 settings-section">
      <h2 class="section-heading">ログインユーザー管理</h2>
      <p class="muted">この項目はログインID「admin」でログインしている場合のみ表示されます。</p>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>ログインID</th>
              <th>表示名</th>
              <th>メールアドレス</th>
              <th>状態</th>
              <th>最終ログイン</th>
              <th style="width:130px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($adminUsers as $adminUser): ?>
              <tr <?= ($editTargetUser && (int)$editTargetUser['id'] === (int)$adminUser['id']) ? 'style="background:var(--bg);"' : '' ?>>
                <td><strong><?= h($adminUser['login_id']) ?></strong></td>
                <td><?= h($adminUser['display_name']) ?></td>
                <td class="muted"><?= h($adminUser['email'] ?? '') ?></td>
                <td>
                  <span class="status-badge <?= !empty($adminUser['is_active']) ? 'success' : 'muted' ?>">
                    <?= !empty($adminUser['is_active']) ? '有効' : '無効' ?>
                  </span>
                </td>
                <td class="muted"><?= h(format_datetime($adminUser['last_login_at'])) ?></td>
                <td>
                  <div class="actions-inline">
                    <a class="ghost-btn"
                       href="<?= h($baseUrl) ?>/settings.php?edit_user_id=<?= (int)$adminUser['id'] ?>#settings-admin-users">編集</a>
                    <?php if ((int)$adminUser['id'] !== (int)$user['id']): ?>
                      <form method="post" data-confirm="「<?= h($adminUser['login_id']) ?>」を削除しますか？この操作は取り消せません。">
                        <input type="hidden" name="action" value="delete_admin_user">
                        <input type="hidden" name="delete_user_id" value="<?= (int)$adminUser['id'] ?>">
                        <button class="danger-btn" type="submit">削除</button>
                      </form>
                    <?php else: ?>
                      <span class="muted" style="font-size:.78em;">自分</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($editTargetUser): ?>
        <div class="card form-card form-stack" style="margin-top:16px;border:2px solid var(--primary);">
          <h3 class="section-heading" style="margin:0 0 4px;">「<?= h($editTargetUser['login_id']) ?>」を編集</h3>
          <p class="muted" style="margin:0 0 12px;">ログインIDは変更できません。パスワードは変更しない場合は空欄のままにしてください。</p>
          <form method="post" class="form-stack">
            <input type="hidden" name="action" value="update_admin_user">
            <input type="hidden" name="edit_user_id" value="<?= (int)$editTargetUser['id'] ?>">
            <div class="form-grid two">
              <label>
                <span>ログインID（変更不可）</span>
                <input type="text" value="<?= h($editTargetUser['login_id']) ?>" disabled>
              </label>
              <label>
                <span>表示名</span>
                <input type="text" name="edit_display_name" value="<?= h($editTargetUser['display_name']) ?>" maxlength="100" required>
              </label>
            </div>
            <label><span>メールアドレス（任意）</span><input type="email" name="edit_email" value="<?= h($editTargetUser['email'] ?? '') ?>"></label>
            <div class="form-grid two">
              <label><span>新しいパスワード（変更する場合のみ）</span><input type="password" name="edit_password" minlength="8" autocomplete="new-password"></label>
              <label><span>パスワード確認</span><input type="password" name="edit_password_confirm" minlength="8" autocomplete="new-password"></label>
            </div>
            <label class="checkbox-row">
              <input type="checkbox" name="edit_is_active" value="1" <?= !empty($editTargetUser['is_active']) ? 'checked' : '' ?>>
              <span>有効なユーザー</span>
              <?php if ((int)$editTargetUser['id'] === (int)$user['id']): ?>
                <span class="muted">（自分自身は無効にできません）</span>
              <?php endif; ?>
            </label>
            <div class="actions-inline">
              <button class="primary-btn" type="submit">変更を保存</button>
              <a class="ghost-btn" href="<?= h($baseUrl) ?>/settings.php#settings-admin-users">キャンセル</a>
            </div>
          </form>
        </div>
      <?php else: ?>
        <form method="post" class="form-stack" style="margin-top:16px;">
          <h3 class="section-heading">新規ユーザーを追加</h3>
          <input type="hidden" name="action" value="create_admin_user">
          <div class="form-grid two">
            <label><span>ログインID</span><input type="text" name="new_login_id" maxlength="100" required></label>
            <label><span>表示名</span><input type="text" name="new_display_name" maxlength="100" required></label>
          </div>
          <label><span>メールアドレス（任意）</span><input type="email" name="new_email"></label>
          <div class="form-grid two">
            <label><span>パスワード</span><input type="password" name="new_password" minlength="8" required></label>
            <label><span>パスワード確認</span><input type="password" name="new_password_confirm" minlength="8" required></label>
          </div>
          <label class="checkbox-row">
            <input type="checkbox" name="new_is_active" value="1" checked>
            <span>有効なユーザーとして追加する</span>
          </label>
          <div class="actions-inline">
            <button class="primary-btn" type="submit">ログインユーザーを追加する</button>
          </div>
        </form>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>
<?php end_page(); ?>
