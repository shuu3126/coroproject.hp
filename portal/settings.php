<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent = current_portal_talent();
$profile = portal_get_talent_info($pdo, $talent['talent_id']);
$publicProfile = portal_fetch_public_profile($pdo, $talent['talent_id']);
$latestPublicRequest = portal_fetch_latest_public_profile_request($pdo, $talent['talent_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf($_POST['_csrf'] ?? '')) {
        $errors[] = '不正なリクエストです。ページを再読み込みして再試行してください。';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'profile') {
            $result = portal_update_talent_profile($pdo, $talent['talent_id'], [
                'real_name'         => $_POST['real_name'] ?? '',
                'invoice_name'      => $_POST['invoice_name'] ?? '',
                'email'             => $_POST['email'] ?? '',
                'phone'             => $_POST['phone'] ?? '',
                'postal_code'       => $_POST['postal_code'] ?? '',
                'address'           => $_POST['address'] ?? '',
                'bank_info'         => $_POST['bank_info'] ?? '',
                'emergency_contact' => $_POST['emergency_contact'] ?? '',
                'profile_note'      => $_POST['profile_note'] ?? '',
            ]);
            if (isset($result['success'])) {
                portal_flash_set('success', '設定を保存しました。');
                portal_redirect($portalBase . '/settings.php');
            }
            $errors[] = $result['error'];
        } elseif ($action === 'password') {
            $result = portal_change_password(
                $pdo,
                (int)$talent['id'],
                $talent['talent_id'],
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? '',
                $_POST['new_password_confirm'] ?? ''
            );
            if (isset($result['success'])) {
                portal_flash_set('success', 'パスワードを変更しました。次回から新しいパスワードでログインしてください。');
                portal_redirect($portalBase . '/settings.php');
            }
            $errors[] = $result['error'];
        } elseif ($action === 'public_profile') {
            $result = portal_submit_public_profile_request($pdo, $talent['talent_id'], [
                'name'           => $_POST['name'] ?? '',
                'kana'           => $_POST['kana'] ?? '',
                'talent_group'   => $_POST['talent_group'] ?? '',
                'debut'          => $_POST['debut'] ?? '',
                'bio'            => $_POST['bio'] ?? '',
                'long_bio_text'  => $_POST['long_bio_text'] ?? '',
                'platforms_text' => $_POST['platforms_text'] ?? '',
                'links_text'     => $_POST['links_text'] ?? '',
                'tags_text'      => $_POST['tags_text'] ?? '',
            ]);
            if (isset($result['success'])) {
                portal_flash_set('success', 'HP掲載情報の変更申請を送信しました。管理者の承認後にHPへ反映されます。');
                portal_redirect($portalBase . '/settings.php');
            }
            $errors[] = $result['error'];
        }
    }
    if (($_POST['action'] ?? '') === 'public_profile') {
        $publicProfile = array_merge((array)$publicProfile, $_POST);
    } else {
        $profile = array_merge((array)$profile, $_POST);
    }
}

$portalPageTitle = '設定';
require __DIR__ . '/_header.php';
?>

<h1 class="portal-page-title">設定</h1>
<p class="portal-page-desc">連絡先・請求先・振込先情報を更新できます。保存した内容は管理画面のタレント情報にも反映されます。</p>

<?php foreach ($errors as $e): ?>
  <div class="portal-flash portal-flash--error"><?= portal_h($e) ?></div>
<?php endforeach; ?>

<?php if (!portal_profile_columns_ready($pdo)): ?>
  <div class="portal-flash portal-flash--warning">
    プロフィール保存用のDB更新が未実行です。管理者は admin/portal_migrate.sql を再実行してください。
  </div>
<?php endif; ?>

<form method="post" class="portal-card">
  <input type="hidden" name="_csrf" value="<?= portal_h(portal_csrf_token()) ?>">
  <input type="hidden" name="action" value="profile">

  <div class="portal-card-title">基本情報</div>
  <div class="portal-form-row">
    <div class="portal-form-group">
      <label for="display_name">活動名</label>
      <input type="text" id="display_name" value="<?= portal_h($profile['name'] ?? $talent['talent_name']) ?>" disabled>
      <div class="portal-form-note">活動名の変更は運営に連絡してください。</div>
    </div>
    <div class="portal-form-group">
      <label for="real_name">本名</label>
      <input type="text" id="real_name" name="real_name" value="<?= portal_h($profile['real_name'] ?? '') ?>">
    </div>
    <div class="portal-form-group">
      <label for="invoice_name">請求書の宛名</label>
      <input type="text" id="invoice_name" name="invoice_name" value="<?= portal_h($profile['invoice_name'] ?? '') ?>">
      <div class="portal-form-note">未入力の場合は本名または活動名を使用します。</div>
    </div>
  </div>

  <div class="portal-card-title portal-section-title">連絡先</div>
  <div class="portal-form-row">
    <div class="portal-form-group">
      <label for="email">メールアドレス</label>
      <input type="email" id="email" name="email" value="<?= portal_h($profile['email'] ?? '') ?>">
    </div>
    <div class="portal-form-group">
      <label for="phone">電話番号</label>
      <input type="tel" id="phone" name="phone" value="<?= portal_h($profile['phone'] ?? '') ?>">
    </div>
    <div class="portal-form-group">
      <label for="postal_code">郵便番号</label>
      <input type="text" id="postal_code" name="postal_code" value="<?= portal_h($profile['postal_code'] ?? '') ?>">
    </div>
  </div>

  <div class="portal-form-group">
    <label for="address">住所</label>
    <textarea id="address" name="address" rows="3"><?= portal_h($profile['address'] ?? '') ?></textarea>
  </div>

  <div class="portal-card-title portal-section-title">精算情報</div>
  <div class="portal-form-group">
    <label for="bank_info">口座情報</label>
    <textarea id="bank_info" name="bank_info" rows="5" placeholder="銀行名 / 支店名 / 種別 / 口座番号 / 口座名義"><?= portal_h($profile['bank_info'] ?? '') ?></textarea>
  </div>

  <div class="portal-card-title portal-section-title">その他</div>
  <div class="portal-form-group">
    <label for="emergency_contact">緊急連絡先</label>
    <textarea id="emergency_contact" name="emergency_contact" rows="3" placeholder="氏名・続柄・電話番号など"><?= portal_h($profile['emergency_contact'] ?? '') ?></textarea>
  </div>
  <div class="portal-form-group">
    <label for="profile_note">補足事項</label>
    <textarea id="profile_note" name="profile_note" rows="3" placeholder="請求・精算・連絡に関する補足があれば記入してください。"><?= portal_h($profile['profile_note'] ?? '') ?></textarea>
  </div>

  <button class="portal-btn portal-btn-primary" type="submit">設定を保存する</button>
</form>

<form method="post" class="portal-card">
  <input type="hidden" name="_csrf" value="<?= portal_h(portal_csrf_token()) ?>">
  <input type="hidden" name="action" value="public_profile">

  <div class="portal-card-title">HP掲載情報の変更申請</div>
  <p class="portal-page-desc" style="margin:-8px 0 18px;">
    ここで送信した内容はすぐにはHPに反映されません。管理者が確認・承認した後に公開プロフィールへ反映されます。
  </p>

  <?php if (!portal_profile_requests_ready($pdo)): ?>
    <div class="portal-flash portal-flash--warning">
      HP掲載情報申請用のDB更新が未実行です。管理者は admin/portal_migrate.sql を再実行してください。
    </div>
  <?php endif; ?>

  <?php if ($latestPublicRequest): ?>
    <?php
      $statusMap = [
        'pending' => ['確認待ち', 'badge-warning'],
        'approved' => ['承認済', 'badge-success'],
        'rejected' => ['差し戻し', 'badge-danger'],
      ];
      $requestStatus = $statusMap[$latestPublicRequest['status']] ?? [$latestPublicRequest['status'], 'badge-muted'];
    ?>
    <div style="margin-bottom:16px;">
      <span class="badge <?= portal_h($requestStatus[1]) ?>"><?= portal_h($requestStatus[0]) ?></span>
      <span style="font-size:12px;color:var(--sub);margin-left:8px;">
        最終申請: <?= portal_h(substr($latestPublicRequest['created_at'], 0, 16)) ?>
      </span>
      <?php if (!empty($latestPublicRequest['admin_note'])): ?>
        <div class="portal-form-note">管理者メモ: <?= portal_h($latestPublicRequest['admin_note']) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="portal-form-row">
    <div class="portal-form-group">
      <label for="public_name">活動名</label>
      <input type="text" id="public_name" name="name" value="<?= portal_h($publicProfile['name'] ?? '') ?>" required>
    </div>
    <div class="portal-form-group">
      <label for="public_kana">かな</label>
      <input type="text" id="public_kana" name="kana" value="<?= portal_h($publicProfile['kana'] ?? '') ?>">
    </div>
    <div class="portal-form-group">
      <label for="public_group">グループ</label>
      <input type="text" id="public_group" name="talent_group" value="<?= portal_h($publicProfile['talent_group'] ?? '') ?>">
    </div>
    <div class="portal-form-group">
      <label for="public_debut">デビュー日</label>
      <input type="date" id="public_debut" name="debut" value="<?= portal_h($publicProfile['debut'] ?? '') ?>">
    </div>
  </div>

  <div class="portal-form-group">
    <label for="public_bio">短い紹介文</label>
    <textarea id="public_bio" name="bio" rows="3"><?= portal_h($publicProfile['bio'] ?? '') ?></textarea>
  </div>

  <div class="portal-form-group">
    <label for="public_long_bio">ロングプロフィール</label>
    <textarea id="public_long_bio" name="long_bio_text" rows="7"><?= portal_h($publicProfile['long_bio_text'] ?? '') ?></textarea>
    <div class="portal-form-note">1行ごとに1段落として扱います。</div>
  </div>

  <div class="portal-form-group">
    <label for="public_platforms">配信プラットフォーム</label>
    <textarea id="public_platforms" name="platforms_text" rows="4" placeholder="YouTube|https://..."><?= portal_h($publicProfile['platforms_text'] ?? '') ?></textarea>
    <div class="portal-form-note">1行ごとに「名前|URL」で入力してください。</div>
  </div>

  <div class="portal-form-group">
    <label for="public_links">その他リンク</label>
    <textarea id="public_links" name="links_text" rows="4" placeholder="X|https://..."><?= portal_h($publicProfile['links_text'] ?? '') ?></textarea>
    <div class="portal-form-note">1行ごとに「ラベル|URL」で入力してください。</div>
  </div>

  <div class="portal-form-group">
    <label for="public_tags">タグ</label>
    <input type="text" id="public_tags" name="tags_text" value="<?= portal_h($publicProfile['tags_text'] ?? '') ?>">
    <div class="portal-form-note">カンマ区切りで入力してください。</div>
  </div>

  <button class="portal-btn portal-btn-primary" type="submit">HP掲載情報の変更を申請する</button>
</form>

<form method="post" class="portal-card">
  <input type="hidden" name="_csrf" value="<?= portal_h(portal_csrf_token()) ?>">
  <input type="hidden" name="action" value="password">

  <div class="portal-card-title">パスワード変更</div>
  <div class="portal-form-row">
    <div class="portal-form-group">
      <label for="current_password">現在のパスワード</label>
      <input type="password" id="current_password" name="current_password" autocomplete="current-password">
    </div>
    <div class="portal-form-group">
      <label for="new_password">新しいパスワード</label>
      <input type="password" id="new_password" name="new_password" autocomplete="new-password">
      <div class="portal-form-note">8文字以上で設定してください。</div>
    </div>
    <div class="portal-form-group">
      <label for="new_password_confirm">新しいパスワード（確認）</label>
      <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password">
    </div>
  </div>

  <button class="portal-btn portal-btn-outline" type="submit">パスワードを変更する</button>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
