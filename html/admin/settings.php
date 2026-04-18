<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';
require_admin_login();
$user = current_admin_user();

$defaultSettings = [
    'office_name' => 'CORO PROJECT',
    'office_email' => 'info@coroproject.jp',
    'office_bank_info' => '',
    'office_invoice_note' => '',
    'fx_default_rate' => '150',
    'fx_api_key' => '',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaultSettings as $key => $default) {
        $value = trim((isset($_POST[$key]) ? $_POST[$key] : ''));
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([$key, $value, $user['id']]);
    }
    write_admin_log($pdo, (int)$user['id'], 'edit', 'settings', null, '設定を更新しました');
    set_flash('success', '設定を更新しました。');
    redirect_to($baseUrl . '/settings.php');
}
$stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
$settings = $defaultSettings;
foreach ($stmt->fetchAll() as $row) { $settings[$row['setting_key']] = $row['setting_value']; }
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
      <label><span>為替APIキー</span><input type="text" name="fx_api_key" value="<?= h($settings['fx_api_key']) ?>"></label>
    </div>
    <div class="actions-inline"><button class="primary-btn" type="submit">設定を保存する</button></div>
  </form>
</main>
<?php end_page(); ?>
