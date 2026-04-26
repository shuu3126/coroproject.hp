<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$defaults = app_settings_defaults($config);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $map = [];
    foreach ($defaults as $key => $default) {
        $map[$key] = trim(isset($_POST[$key]) ? $_POST[$key] : '');
    }
    save_app_settings_map($pdo, $user['id'], $map);
    write_admin_log($pdo, (int)$user['id'], 'edit', 'settings', null, '設定を更新しました');
    set_flash('success', '設定を更新しました。');
    redirect_to($baseUrl . '/settings.php');
}
$settings = load_app_settings($pdo, $config);
start_page('設定', '管理画面と会計システムの基本設定です。');
?>
<main class="page-container narrow">
  <form method="post" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>事務所名</span><input type="text" name="office_name" value="<?= h($settings['office_name']) ?>"></label>
      <label><span>メールアドレス</span><input type="email" name="office_email" value="<?= h($settings['office_email']) ?>"></label>
    </div>
    <label><span>振込先情報</span><textarea name="office_bank_info" rows="4"><?= h($settings['office_bank_info']) ?></textarea></label>
    <label><span>請求書備考テンプレ</span><textarea name="office_invoice_note" rows="4"><?= h($settings['office_invoice_note']) ?></textarea></label>
    <div class="form-grid two">
      <label><span>デフォルト為替レート</span><input type="text" name="fx_default_rate" value="<?= h($settings['fx_default_rate']) ?>"></label>
      <label><span>為替APIキー</span><input type="text" name="fx_api_key" value="<?= h(isset($settings['fx_api_key']) ? $settings['fx_api_key'] : '') ?>"></label>
    </div>
    <div class="help-text">請求書作成画面で最新レートを取得する場合に使います。</div>
    <div class="form-grid two">
      <div class="card" style="padding:12px 16px;">
        <strong>回収ライン</strong>
        <div class="muted" style="margin-top:6px;">5,000円固定</div>
      </div>
    </div>
    <div class="form-grid two">
      <label><span>PDF用フォントパス</span><input type="text" name="pdf_font_path" value="<?= h($settings['pdf_font_path']) ?>"></label>
      <label><span>印影PNGパス</span><input type="text" name="pdf_stamp_path" value="<?= h($settings['pdf_stamp_path']) ?>"></label>
    </div>
    <div class="help-text">フォントは admin/resources/fonts/ipaexg.ttf、印影は admin/resources/stamps/hanko.png に置く想定です。</div>
    <div class="actions-inline"><button class="primary-btn" type="submit">設定を保存する</button></div>
  </form>
</main>
<?php end_page(); ?>
