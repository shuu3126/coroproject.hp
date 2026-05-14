<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id     = trim($_GET['id'] ?? '');
$isEdit = $id !== '';
$row    = [
    'id' => '', 'name' => '', 'skill_tags_json' => '[]', 'rate_memo' => '', 'contact' => '', 'portfolio_url' => '', 'type' => 'external', 'memo' => '', 'is_active' => 1,
    'display_name' => '', 'real_name' => '', 'email' => '', 'discord_name' => '', 'postal_code' => '', 'address' => '', 'bank_info' => '',
    'invoice_registration_no' => '', 'withholding_type' => 'individual', 'availability_status' => 'available', 'available_note' => '',
];
$portalFields = [
    'display_name', 'real_name', 'email', 'discord_name', 'postal_code', 'address', 'bank_info',
    'invoice_registration_no', 'withholding_type', 'availability_status', 'available_note',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM cre_creators WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('error', 'クリエイターが見つかりません。'); redirect_to($baseUrl . '/creative/creators.php'); }
    $row = array_merge($row, $found);
}

$skillsText = implode(', ', json_decode($row['skill_tags_json'] ?? '[]', true) ?: []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $skillsRaw    = trim($_POST['skills_text'] ?? '');
    $rateMemo     = trim($_POST['rate_memo'] ?? '');
    $contact      = trim($_POST['contact'] ?? '');
    $portfolioUrl = trim($_POST['portfolio_url'] ?? '');
    $type         = trim($_POST['type'] ?? 'external');
    $memo         = trim($_POST['memo'] ?? '');
    $isActive     = isset($_POST['is_active']) ? 1 : 0;
    $portalData = [];
    foreach ($portalFields as $field) {
        $portalData[$field] = trim((string)($_POST[$field] ?? ''));
    }
    if ($portalData['withholding_type'] === '') {
        $portalData['withholding_type'] = 'individual';
    }
    if ($portalData['availability_status'] === '') {
        $portalData['availability_status'] = 'available';
    }

    $skills     = array_values(array_filter(array_map('trim', preg_split('/[,、\s]+/u', $skillsRaw))));
    $skillsJson = json_encode($skills, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($name === '') { set_flash('error', '名前は必須です。'); redirect_to($baseUrl . '/creative/creator_edit.php' . ($isEdit ? '?id=' . urlencode($id) : '')); }

    try {
        if ($isEdit) {
            $sets = ['name=?', 'skill_tags_json=?', 'rate_memo=?', 'contact=?', 'portfolio_url=?', 'type=?', 'memo=?', 'is_active=?'];
            $params = [$name, $skillsJson, $rateMemo, $contact, $portfolioUrl, $type, $memo, $isActive];
            foreach ($portalFields as $field) {
                if (!admin_table_has_column($pdo, 'cre_creators', $field)) {
                    continue;
                }
                $sets[] = $field . '=?';
                $params[] = $portalData[$field];
            }
            $params[] = $id;
            $pdo->prepare('UPDATE cre_creators SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'cre_creator', $id, 'クリエイターを更新しました');
        } else {
            $saveId = normalize_file_stem($name . '-' . date('Ymd'), 'creator');
            $base = $saveId; $i = 2;
            while ((int)$pdo->query("SELECT COUNT(*) FROM cre_creators WHERE id = " . $pdo->quote($saveId))->fetchColumn() > 0) {
                $saveId = $base . '-' . $i++;
            }
            $columns = ['id', 'name', 'skill_tags_json', 'rate_memo', 'contact', 'portfolio_url', 'type', 'memo', 'is_active', 'created_by'];
            $placeholders = array_fill(0, count($columns), '?');
            $params = [$saveId, $name, $skillsJson, $rateMemo, $contact, $portfolioUrl, $type, $memo, $isActive, (int)$user['id']];
            foreach ($portalFields as $field) {
                if (!admin_table_has_column($pdo, 'cre_creators', $field)) {
                    continue;
                }
                $columns[] = $field;
                $placeholders[] = '?';
                $params[] = $portalData[$field];
            }
            $pdo->prepare('INSERT INTO cre_creators (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')')
                ->execute($params);
            write_admin_log($pdo, (int)$user['id'], 'create', 'cre_creator', $saveId, 'クリエイターを追加しました');
        }
        set_flash('success', '保存しました。');
        redirect_to($baseUrl . '/creative/creators.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/creative/creator_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}

start_page($isEdit ? 'クリエイターを編集' : 'クリエイターを追加', '');
?>
<main class="page-container narrow">
  <section class="page-header-block with-actions">
    <h1><?= $isEdit ? h($row['name']) : 'クリエイターを追加' ?></h1>
    <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/creators.php">一覧へ戻る</a>
  </section>

  <form method="post" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>名前</span>
        <input type="text" name="name" value="<?= h($row['name']) ?>" required placeholder="例：田中 太郎 / TanakaArt">
      </label>
      <label><span>種別</span>
        <select name="type">
          <option value="inhouse"  <?= selected($row['type'], 'inhouse')  ?>>社内スタッフ</option>
          <option value="external" <?= selected($row['type'], 'external') ?>>外部クリエイター</option>
        </select>
      </label>
    </div>

    <label><span>スキルタグ</span>
      <input type="text" name="skills_text" value="<?= h($skillsText) ?>"
        placeholder="イラスト, Live2D, 一枚絵, 音楽 など（カンマ区切り）">
      <span class="help-text">案件一覧でタグとして表示されます。</span>
    </label>

    <div class="form-grid two">
      <label><span>連絡先（メールアドレス / SNS）</span>
        <input type="text" name="contact" value="<?= h($row['contact'] ?? '') ?>" placeholder="例：artist@example.com / @handle">
      </label>
      <label><span>ポートフォリオ URL</span>
        <input type="text" name="portfolio_url" value="<?= h($row['portfolio_url'] ?? '') ?>" placeholder="https://...">
      </label>
    </div>

    <label><span>単価・料金メモ</span>
      <textarea name="rate_memo" rows="2" placeholder="例：イラスト 1枚 ¥15,000 / 修正2回まで込み"><?= h($row['rate_memo'] ?? '') ?></textarea>
    </label>

    <section class="form-stack" style="border-top:1px solid var(--line);padding-top:18px;">
      <h2 class="section-heading">ポータル登録情報</h2>
      <div class="form-grid two">
        <label><span>表示名</span><input type="text" name="display_name" value="<?= h($row['display_name'] ?? '') ?>"></label>
        <label><span>本名 / 事業者名</span><input type="text" name="real_name" value="<?= h($row['real_name'] ?? '') ?>"></label>
        <label><span>メールアドレス</span><input type="email" name="email" value="<?= h($row['email'] ?? '') ?>"></label>
        <label><span>Discord</span><input type="text" name="discord_name" value="<?= h($row['discord_name'] ?? '') ?>"></label>
        <label><span>郵便番号</span><input type="text" name="postal_code" value="<?= h($row['postal_code'] ?? '') ?>"></label>
        <label><span>インボイス登録番号</span><input type="text" name="invoice_registration_no" value="<?= h($row['invoice_registration_no'] ?? '') ?>"></label>
        <label><span>源泉区分</span>
          <select name="withholding_type">
            <option value="individual" <?= selected($row['withholding_type'] ?? '', 'individual') ?>>個人 / 源泉対象</option>
            <option value="corporation" <?= selected($row['withholding_type'] ?? '', 'corporation') ?>>法人</option>
            <option value="none" <?= selected($row['withholding_type'] ?? '', 'none') ?>>対象外</option>
          </select>
        </label>
        <label><span>受注状況</span>
          <select name="availability_status">
            <option value="available" <?= selected($row['availability_status'] ?? '', 'available') ?>>受付可</option>
            <option value="busy" <?= selected($row['availability_status'] ?? '', 'busy') ?>>多忙</option>
            <option value="paused" <?= selected($row['availability_status'] ?? '', 'paused') ?>>一時停止</option>
          </select>
        </label>
      </div>
      <label><span>住所</span><textarea name="address" rows="3"><?= h($row['address'] ?? '') ?></textarea></label>
      <label><span>振込先</span><textarea name="bank_info" rows="4"><?= h($row['bank_info'] ?? '') ?></textarea></label>
      <label><span>稼働メモ</span><textarea name="available_note" rows="3"><?= h($row['available_note'] ?? '') ?></textarea></label>
    </section>

    <label><span>メモ</span>
      <textarea name="memo" rows="2" placeholder="得意な作風・納期の傾向・注意事項など"><?= h($row['memo'] ?? '') ?></textarea>
    </label>

    <label class="checkbox-row">
      <input type="checkbox" name="is_active" value="1" <?= checked((bool)$row['is_active']) ?>>
      <span>有効（案件へのアサイン候補にする）</span>
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/creators.php">キャンセル</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
