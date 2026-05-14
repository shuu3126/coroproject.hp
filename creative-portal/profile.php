<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cp_verify_csrf($_POST['_csrf'] ?? '')) {
        cp_flash_set('error', '不正なリクエストです。ページを再読み込みしてください。');
        cp_redirect($creativePortalBase . '/profile.php');
    }
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'profile') {
        $result = cp_update_profile($pdo, $creator['creator_id'], $_POST);
        cp_flash_set(!empty($result['success']) ? 'success' : 'error', !empty($result['success']) ? '登録情報を保存しました。' : ($result['error'] ?? '保存に失敗しました。'));
    } elseif ($action === 'password') {
        $result = cp_change_password($pdo, (int)$creator['id'], $creator['creator_id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '', $_POST['new_password_confirm'] ?? '');
        cp_flash_set(!empty($result['success']) ? 'success' : 'error', !empty($result['success']) ? 'パスワードを変更しました。' : ($result['error'] ?? '変更に失敗しました。'));
    }
    cp_redirect($creativePortalBase . '/profile.php');
}

$info = cp_get_creator_info($pdo, $creator['creator_id']);
$skills = json_decode((string)($info['skill_tags_json'] ?? '[]'), true);
$skills = is_array($skills) ? $skills : [];

cp_start_page('登録情報', '連絡先、振込先、請求に必要な情報を確認できます。');
?>
<div class="cp-grid aside">
  <section class="cp-card">
    <div class="cp-card-head">
      <div>
        <h2>基本情報・支払情報</h2>
        <p>変更すると管理画面側のクリエイター情報にも反映されます。</p>
      </div>
    </div>
    <form method="post" class="cp-card-pad cp-form">
      <input type="hidden" name="action" value="profile">
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">表示名</span>
          <input type="text" name="display_name" value="<?= cp_h($info['display_name'] ?: $info['name']) ?>">
        </label>
        <label>
          <span class="cp-label">本名 / 事業者名</span>
          <input type="text" name="real_name" value="<?= cp_h($info['real_name'] ?? '') ?>">
        </label>
      </div>
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">メールアドレス</span>
          <input type="email" name="email" value="<?= cp_h($info['email'] ?? '') ?>">
        </label>
        <label>
          <span class="cp-label">Discord</span>
          <input type="text" name="discord_name" value="<?= cp_h($info['discord_name'] ?? '') ?>">
        </label>
      </div>
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">郵便番号</span>
          <input type="text" name="postal_code" value="<?= cp_h($info['postal_code'] ?? '') ?>">
        </label>
        <label>
          <span class="cp-label">インボイス登録番号</span>
          <input type="text" name="invoice_registration_no" value="<?= cp_h($info['invoice_registration_no'] ?? '') ?>" placeholder="Tから始まる番号">
        </label>
      </div>
      <label>
        <span class="cp-label">住所</span>
        <textarea name="address"><?= cp_h($info['address'] ?? '') ?></textarea>
      </label>
      <label>
        <span class="cp-label">振込先</span>
        <textarea name="bank_info" placeholder="銀行名 / 支店 / 口座種別 / 口座番号 / 名義"><?= cp_h($info['bank_info'] ?? '') ?></textarea>
      </label>
      <div class="cp-form-grid">
        <label>
          <span class="cp-label">源泉区分</span>
          <select name="withholding_type">
            <option value="individual" <?= (string)($info['withholding_type'] ?? '') === 'individual' ? 'selected' : '' ?>>個人 / 源泉対象</option>
            <option value="corporation" <?= (string)($info['withholding_type'] ?? '') === 'corporation' ? 'selected' : '' ?>>法人</option>
            <option value="none" <?= (string)($info['withholding_type'] ?? '') === 'none' ? 'selected' : '' ?>>対象外</option>
          </select>
        </label>
        <label>
          <span class="cp-label">受注状況</span>
          <select name="availability_status">
            <option value="available" <?= (string)($info['availability_status'] ?? '') === 'available' ? 'selected' : '' ?>>受付可</option>
            <option value="busy" <?= (string)($info['availability_status'] ?? '') === 'busy' ? 'selected' : '' ?>>多忙</option>
            <option value="paused" <?= (string)($info['availability_status'] ?? '') === 'paused' ? 'selected' : '' ?>>一時停止</option>
          </select>
        </label>
      </div>
      <label>
        <span class="cp-label">稼働メモ</span>
        <textarea name="available_note" placeholder="対応可能時期、得意領域、NG条件など"><?= cp_h($info['available_note'] ?? '') ?></textarea>
      </label>
      <button class="cp-btn" type="submit">保存する</button>
    </form>
  </section>

  <aside class="cp-grid">
    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>登録済み情報</h2>
          <p>管理側で保持している制作プロフィールです。</p>
        </div>
      </div>
      <div class="cp-card-pad">
        <dl class="cp-detail-list">
          <div class="cp-detail-row">
            <dt>クリエイター名</dt>
            <dd><?= cp_h($info['name'] ?? '') ?></dd>
          </div>
          <div class="cp-detail-row">
            <dt>種別</dt>
            <dd><?= ($info['type'] ?? '') === 'inhouse' ? '社内' : '外部' ?></dd>
          </div>
          <div class="cp-detail-row">
            <dt>スキル</dt>
            <dd>
              <?php if (!$skills): ?>
                <span class="cp-muted">—</span>
              <?php endif; ?>
              <?php foreach ($skills as $skill): ?>
                <span class="cp-chip"><?= cp_h($skill) ?></span>
              <?php endforeach; ?>
            </dd>
          </div>
          <div class="cp-detail-row">
            <dt>ポートフォリオ</dt>
            <dd>
              <?php if (!empty($info['portfolio_url'])): ?>
                <a class="cp-btn-muted" href="<?= cp_h($info['portfolio_url']) ?>" target="_blank" rel="noopener">開く</a>
              <?php else: ?>
                <span class="cp-muted">—</span>
              <?php endif; ?>
            </dd>
          </div>
          <div class="cp-detail-row">
            <dt>単価メモ</dt>
            <dd><?= $info['rate_memo'] ? nl2br(cp_h($info['rate_memo'])) : '<span class="cp-muted">—</span>' ?></dd>
          </div>
        </dl>
      </div>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>パスワード変更</h2>
          <p>8文字以上で設定してください。</p>
        </div>
      </div>
      <form method="post" class="cp-card-pad cp-form">
        <input type="hidden" name="action" value="password">
        <label>
          <span class="cp-label">現在のパスワード</span>
          <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>
          <span class="cp-label">新しいパスワード</span>
          <input type="password" name="new_password" autocomplete="new-password" required>
        </label>
        <label>
          <span class="cp-label">新しいパスワード（確認）</span>
          <input type="password" name="new_password_confirm" autocomplete="new-password" required>
        </label>
        <button class="cp-btn-muted" type="submit">変更する</button>
      </form>
    </section>
  </aside>
</div>
<?php cp_end_page(); ?>
