<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

function talent_id_exists($pdo, $id, $excludeId = '') {
    if ($excludeId !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = ? AND id <> ?');
        $stmt->execute([$id, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = ?');
        $stmt->execute([$id]);
    }
    return (int)$stmt->fetchColumn() > 0;
}

function generate_talent_id($pdo, $name, $excludeId = '') {
    $base = normalize_file_stem($name, 'talent');
    $candidate = $base; $i = 2;
    while (talent_id_exists($pdo, $candidate, $excludeId)) { $candidate = $base . '-' . $i; $i++; }
    return $candidate;
}

function fetch_platform_rows($pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT name, url FROM talent_platforms WHERE talent_id = ? ORDER BY id ASC');
    $stmt->execute([$talentId]);
    return $stmt->fetchAll() ?: [];
}

function fetch_link_rows($pdo, $talentId) {
    $stmt = $pdo->prepare('SELECT label, url FROM talent_links WHERE talent_id = ? ORDER BY id ASC');
    $stmt->execute([$talentId]);
    return $stmt->fetchAll() ?: [];
}

function sync_talent_relations($pdo, $oldId, $newId, $platforms, $links) {
    $targets = array_values(array_unique(array_filter([$oldId, $newId])));
    if ($targets) {
        $ph = implode(',', array_fill(0, count($targets), '?'));
        $pdo->prepare("DELETE FROM talent_platforms WHERE talent_id IN ($ph)")->execute($targets);
        $pdo->prepare("DELETE FROM talent_links WHERE talent_id IN ($ph)")->execute($targets);
    }
    if ($platforms) {
        $stmt = $pdo->prepare('INSERT INTO talent_platforms (talent_id, name, url) VALUES (?, ?, ?)');
        foreach ($platforms as $row) {
            $stmt->execute([$newId, $row['name'], $row['url']]);
        }
    }
    if ($links) {
        $stmt = $pdo->prepare('INSERT INTO talent_links (talent_id, label, url) VALUES (?, ?, ?)');
        foreach ($links as $row) {
            $stmt->execute([$newId, $row['label'], $row['url']]);
        }
    }
}

$id = isset($_GET['id']) ? $_GET['id'] : '';
$isEdit = $id !== '';
$row = [
    'id' => '', 'name' => '', 'kana' => '', 'talent_group' => '', 'status' => 'active',
    'debut' => '', 'avatar' => '', 'bio' => '', 'long_bio_json' => '[]',
    'platforms_json' => '[]', 'links_json' => '[]', 'tags_json' => '[]', 'sort_order' => 0, 'is_published' => 1,
    'platforms_text' => '', 'links_text' => '', 'long_bio_text' => '', 'tags_text' => '',
    'office_share_percent' => accounting_share_percent_default(), 'invoice_name' => '', 'email' => '', 'bank_info' => '', 'memo' => '', 'accounting_active' => 1,
    'real_name' => '', 'phone' => '', 'postal_code' => '', 'address' => '', 'emergency_contact' => '', 'profile_note' => '',
    'portal_login_id' => '', 'portal_active' => 1, 'portal_account_exists' => 0, 'portal_password_changed_at' => '',
];
$portalReady = admin_table_has_column($pdo, 'talent_portal_accounts', 'id');
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM talents WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('error', 'タレントが見つかりません。');
        redirect_to($baseUrl . '/production/talents.php');
    }
    $row = array_merge($row, $found);
    $row['long_bio_text'] = lines_from_json(isset($found['long_bio_json']) ? $found['long_bio_json'] : '[]');
    $tags = json_decode((string)(isset($found['tags_json']) ? $found['tags_json'] : '[]'), true);
    $row['tags_text'] = is_array($tags) ? implode(', ', array_filter(array_map('strval', $tags))) : '';
    $row['platforms_text'] = pipe_lines_from_rows(fetch_platform_rows($pdo, $id), 'name', 'url');
    $row['links_text'] = pipe_lines_from_rows(fetch_link_rows($pdo, $id), 'label', 'url');
    $a = accounting_get_talent_setting($pdo, $id);
    $row = array_merge($row, $a);
    $portalAccount = accounting_portal_account_for_talent($pdo, $id);
    if ($portalAccount) {
        $row['portal_login_id'] = (string)$portalAccount['login_id'];
        $row['portal_active'] = (int)$portalAccount['is_active'];
        $row['portal_account_exists'] = 1;
        $row['portal_password_changed_at'] = isset($portalAccount['password_changed_at']) ? (string)$portalAccount['password_changed_at'] : '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $talentId = trim(isset($_POST['id']) ? $_POST['id'] : '');
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $kana = trim(isset($_POST['kana']) ? $_POST['kana'] : '');
    $talentGroup = trim(isset($_POST['talent_group']) ? $_POST['talent_group'] : '');
    $status = trim(isset($_POST['status']) ? $_POST['status'] : 'active');
    $debut = trim(isset($_POST['debut']) ? $_POST['debut'] : '');
    $avatar = trim(isset($_POST['avatar']) ? $_POST['avatar'] : '');
    $bio = trim(isset($_POST['bio']) ? $_POST['bio'] : '');
    $longBioText = trim(isset($_POST['long_bio_text']) ? $_POST['long_bio_text'] : '');
    $platformsText = trim(isset($_POST['platforms_text']) ? $_POST['platforms_text'] : '');
    $linksText = trim(isset($_POST['links_text']) ? $_POST['links_text'] : '');
    $tagsText = trim(isset($_POST['tags_text']) ? $_POST['tags_text'] : '');
    $sortOrder = (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $officeSharePercent = (float)(isset($_POST['office_share_percent']) ? $_POST['office_share_percent'] : accounting_share_percent_default());
    $invoiceName = trim(isset($_POST['invoice_name']) ? $_POST['invoice_name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $realName = trim(isset($_POST['real_name']) ? $_POST['real_name'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $postalCode = trim(isset($_POST['postal_code']) ? $_POST['postal_code'] : '');
    $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
    $bankInfo = trim(isset($_POST['bank_info']) ? $_POST['bank_info'] : '');
    $emergencyContact = trim(isset($_POST['emergency_contact']) ? $_POST['emergency_contact'] : '');
    $profileNote = trim(isset($_POST['profile_note']) ? $_POST['profile_note'] : '');
    $memo = trim(isset($_POST['memo']) ? $_POST['memo'] : '');
    $accountingActive = isset($_POST['accounting_active']) ? 1 : 0;
    $portalLoginId = trim(isset($_POST['portal_login_id']) ? $_POST['portal_login_id'] : '');
    $portalPassword = isset($_POST['portal_password']) ? (string)$_POST['portal_password'] : '';
    $portalActive = isset($_POST['portal_active']) ? 1 : 0;

    if ($name === '') {
        set_flash('error', '名前は必須です。');
        redirect_to($baseUrl . '/production/talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }

    // 請求書の宛名が未設定の場合はタレント名を使用
    if ($invoiceName === '') {
        $invoiceName = $name;
    }
    if ($talentId === '') {
        $talentId = $isEdit ? $id : generate_talent_id($pdo, $name, $id);
    }
    if (($isEdit && talent_id_exists($pdo, $talentId, $id)) || (!$isEdit && talent_id_exists($pdo, $talentId))) {
        set_flash('error', 'そのタレントIDはすでに使われています。');
        redirect_to($baseUrl . '/production/talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }

    $platforms = parse_pipe_lines($platformsText, 'name', 'url');
    $links = parse_pipe_lines($linksText, 'label', 'url');
    $tags = array_values(array_filter(array_map('trim', preg_split('/[,、]/u', $tagsText))));
    $longBioJson = json_encode(parse_lines_to_array($longBioText), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $platformsJson = json_encode($platforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $linksJson = json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tagsJson = json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    try {
        $upload = save_uploaded_image($_FILES['avatar_file'] ?? [], $config['uploads']['talent_public_dir'], $config['uploads']['talent_public_prefix'], $talentId ?: $name);
        if ($upload !== null) {
            $avatar = $upload;
        }
        $pdo->beginTransaction();
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE talents SET id=?, name=?, kana=?, talent_group=?, status=?, debut=?, avatar=?, bio=?, long_bio_json=?, platforms_json=?, links_json=?, tags_json=?, sort_order=?, is_published=? WHERE id=?');
            $stmt->execute([$talentId, $name, $kana, $talentGroup, $status, $debut ?: null, $avatar, $bio, $longBioJson, $platformsJson, $linksJson, $tagsJson, $sortOrder, $isPublished, $id]);
            sync_talent_relations($pdo, $id, $talentId, $platforms, $links);
            accounting_upsert_talent_setting($pdo, $talentId, [
                'office_share_percent' => $officeSharePercent,
                'invoice_name' => $invoiceName,
                'email' => $email,
                'real_name' => $realName,
                'phone' => $phone,
                'postal_code' => $postalCode,
                'address' => $address,
                'bank_info' => $bankInfo,
                'emergency_contact' => $emergencyContact,
                'profile_note' => $profileNote,
                'memo' => $memo,
                'is_active' => $accountingActive,
            ], $user['id']);
            if ($id !== $talentId) {
                $pdo->prepare('UPDATE accounting_revenues SET talent_id = ? WHERE talent_id = ?')->execute([$talentId, $id]);
                $pdo->prepare('UPDATE accounting_invoices SET talent_id = ? WHERE talent_id = ?')->execute([$talentId, $id]);
                $pdo->prepare('UPDATE accounting_invoiced_months SET talent_id = ? WHERE talent_id = ?')->execute([$talentId, $id]);
                $pdo->prepare('UPDATE accounting_journal_entries SET talent_id = ? WHERE talent_id = ?')->execute([$talentId, $id]);
                if ($portalReady) {
                    if (admin_table_has_column($pdo, 'talent_portal_accounts', 'updated_by')) {
                        $pdo->prepare('UPDATE talent_portal_accounts SET talent_id = ?, updated_by = ?, updated_at = NOW() WHERE talent_id = ?')
                            ->execute([$talentId, $user['id'] ?: null, $id]);
                    } else {
                        $pdo->prepare('UPDATE talent_portal_accounts SET talent_id = ?, updated_at = NOW() WHERE talent_id = ?')
                            ->execute([$talentId, $id]);
                    }
                }
                $pdo->prepare('DELETE FROM accounting_talent_settings WHERE talent_id = ? AND talent_id <> ?')->execute([$id, $talentId]);
            }
            $portalResult = accounting_portal_account_save_for_talent($pdo, $talentId, [
                'login_id' => $portalLoginId,
                'password' => $portalPassword,
                'is_active' => $portalActive,
            ], $user['id']);
            if (isset($portalResult['error'])) {
                throw new RuntimeException($portalResult['error']);
            }
            write_admin_log($pdo, (int)$user['id'], 'edit', 'talent', $talentId, 'タレントを更新しました');
        } else {
            $stmt = $pdo->prepare('INSERT INTO talents (id, name, kana, talent_group, status, debut, avatar, bio, long_bio_json, platforms_json, links_json, tags_json, sort_order, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$talentId, $name, $kana, $talentGroup, $status, $debut ?: null, $avatar, $bio, $longBioJson, $platformsJson, $linksJson, $tagsJson, $sortOrder, $isPublished]);
            sync_talent_relations($pdo, $talentId, $talentId, $platforms, $links);
            accounting_upsert_talent_setting($pdo, $talentId, [
                'office_share_percent' => $officeSharePercent,
                'invoice_name' => $invoiceName,
                'email' => $email,
                'real_name' => $realName,
                'phone' => $phone,
                'postal_code' => $postalCode,
                'address' => $address,
                'bank_info' => $bankInfo,
                'emergency_contact' => $emergencyContact,
                'profile_note' => $profileNote,
                'memo' => $memo,
                'is_active' => $accountingActive,
            ], $user['id']);
            $portalResult = accounting_portal_account_save_for_talent($pdo, $talentId, [
                'login_id' => $portalLoginId,
                'password' => $portalPassword,
                'is_active' => $portalActive,
            ], $user['id']);
            if (isset($portalResult['error'])) {
                throw new RuntimeException($portalResult['error']);
            }
            write_admin_log($pdo, (int)$user['id'], 'create', 'talent', $talentId, 'タレントを作成しました');
        }
        $pdo->commit();
        set_flash('success', 'タレント情報を保存しました。');
        redirect_to($baseUrl . '/production/talents.php');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', '保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/production/talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}

start_page($isEdit ? 'タレントを編集' : 'タレントを追加', '公開表示と会計設定をまとめて入力します。');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? 'タレントを編集' : 'タレントを追加') ?></h1><p>公開 talent.php / talents.php と会計タレント設定に合わせて保存します。</p></section>
  <form method="post" enctype="multipart/form-data" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>タレントID</span><input type="text" name="id" value="<?= h($row['id']) ?>" <?= $isEdit ? 'readonly' : '' ?>></label>
      <label><span>名前</span><input type="text" name="name" value="<?= h($row['name']) ?>" required></label>
    </div>
    <div class="form-grid two">
      <label><span>かな</span><input type="text" name="kana" value="<?= h($row['kana']) ?>"></label>
      <label><span>グループ</span><input type="text" name="talent_group" value="<?= h($row['talent_group']) ?>"></label>
    </div>
    <div class="form-grid two">
      <label><span>ステータス</span><input type="text" name="status" value="<?= h($row['status']) ?>"></label>
      <label><span>並び順</span><input type="number" name="sort_order" value="<?= h((string)$row['sort_order']) ?>"></label>
    </div>
    <div class="form-grid two">
      <label><span>デビュー日</span><input type="date" name="debut" value="<?= h($row['debut']) ?>"></label>
    </div>
    <label><span>短い紹介文</span><textarea name="bio" rows="3"><?= h($row['bio']) ?></textarea></label>
    <label><span>ロングプロフィール（1行=1段落）</span><textarea name="long_bio_text" rows="8"><?= h($row['long_bio_text']) ?></textarea></label>
    <label><span>アバター画像パス</span><input type="text" name="avatar" value="<?= h($row['avatar']) ?>"></label>
    <label><span>アバター画像をアップロード</span><input type="file" name="avatar_file" accept="image/*"></label>
    <?php if (!empty($row['avatar'])): ?><img class="inline-preview" src="<?= h(admin_public_url($row['avatar'])) ?>" alt="avatar"><?php endif; ?>
    <label><span>プラットフォーム一覧（1行ごとに 名前|URL）</span><textarea name="platforms_text" rows="6"><?= h($row['platforms_text']) ?></textarea></label>
    <label><span>その他リンク（1行ごとに ラベル|URL）</span><textarea name="links_text" rows="6"><?= h($row['links_text']) ?></textarea></label>
    <label><span>タグ（カンマ区切り）</span><input type="text" name="tags_text" value="<?= h($row['tags_text']) ?>"></label>
    <label class="checkbox-row"><input type="checkbox" name="is_published" value="1" <?= checked((bool)$row['is_published']) ?>><span>公開する</span></label>

    <div class="card">
      <h3>会計設定</h3>
      <div class="form-grid two">
        <label><span>事務所取り分率（%）</span><input type="number" step="0.01" name="office_share_percent" value="<?= h((string)$row['office_share_percent']) ?>"></label>
        <label><span>請求書の宛名</span><input type="text" name="invoice_name" value="<?= h($row['invoice_name']) ?>"></label>
      </div>
      <div class="form-grid two">
        <label><span>連絡先メール</span><input type="email" name="email" value="<?= h($row['email']) ?>"></label>
        <label class="checkbox-row" style="margin-top:32px;"><input type="checkbox" name="accounting_active" value="1" <?= checked((bool)$row['accounting_active']) ?>><span>会計対象として有効にする</span></label>
      </div>
      <div class="form-grid two">
        <label><span>本名</span><input type="text" name="real_name" value="<?= h($row['real_name']) ?>"></label>
        <label><span>電話番号</span><input type="tel" name="phone" value="<?= h($row['phone']) ?>"></label>
      </div>
      <div class="form-grid two">
        <label><span>郵便番号</span><input type="text" name="postal_code" value="<?= h($row['postal_code']) ?>"></label>
        <label><span>住所</span><textarea name="address" rows="3"><?= h($row['address']) ?></textarea></label>
      </div>
      <label><span>振込先情報</span><textarea name="bank_info" rows="4"><?= h($row['bank_info']) ?></textarea></label>
      <label><span>緊急連絡先</span><textarea name="emergency_contact" rows="3"><?= h($row['emergency_contact']) ?></textarea></label>
      <label><span>タレント本人からの補足</span><textarea name="profile_note" rows="3"><?= h($row['profile_note']) ?></textarea></label>
      <label><span>会計メモ</span><textarea name="memo" rows="4"><?= h($row['memo']) ?></textarea></label>
    </div>

    <div class="card">
      <h3>タレントポータル設定</h3>
      <?php if (!$portalReady): ?>
        <p style="font-size:.86em;color:var(--danger);margin:0 0 14px;">
          タレントポータルのDBテーブルが未作成です。admin/portal_migrate.sql を実行すると設定できます。
        </p>
      <?php else: ?>
        <p style="font-size:.86em;color:var(--sub);margin:0 0 14px;">
          タレントがポータルへログインするためのIDとパスワードを管理します。パスワードは変更する場合のみ入力してください。
        </p>
      <?php endif; ?>
      <div class="form-grid two">
        <label>
          <span>ログインID</span>
          <input type="text" name="portal_login_id" value="<?= h($row['portal_login_id']) ?>" <?= !$portalReady ? 'disabled' : '' ?>>
        </label>
        <label>
          <span><?= $row['portal_account_exists'] ? '新しいパスワード（変更時のみ）' : '初期パスワード' ?></span>
          <input type="password" name="portal_password" autocomplete="new-password" <?= !$portalReady ? 'disabled' : '' ?>>
        </label>
      </div>
      <label class="checkbox-row">
        <input type="checkbox" name="portal_active" value="1" <?= checked((bool)$row['portal_active']) ?> <?= !$portalReady ? 'disabled' : '' ?>>
        <span>ポータルログインを有効にする</span>
      </label>
      <?php if ($row['portal_account_exists']): ?>
        <p style="font-size:.82em;color:var(--text-dim);margin:6px 0 0;">
          現在のパスワードは安全のため表示しません。必要な場合は新しいパスワードを入力して再設定してください。
          <?php if (!empty($row['portal_password_changed_at'])): ?>
            最終変更: <?= h(substr($row['portal_password_changed_at'], 0, 16)) ?>
          <?php endif; ?>
        </p>
        <p style="font-size:.82em;color:var(--text-dim);margin:6px 0 0;">既存アカウントあり。パスワード欄を空にすると現在のパスワードを維持します。</p>
      <?php else: ?>
        <p style="font-size:.82em;color:var(--text-dim);margin:6px 0 0;">新規作成する場合はログインIDと初期パスワードの両方を入力してください。</p>
      <?php endif; ?>
    </div>

    <div class="actions-inline"><button class="primary-btn" type="submit">この内容で保存する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/production/talents.php">一覧へ戻る</a></div>
  </form>
</main>
<?php end_page(); ?>
