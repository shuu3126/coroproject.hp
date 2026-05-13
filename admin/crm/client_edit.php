<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

function client_generate_id($pdo, $name) {
    $base = normalize_file_stem($name, 'client');
    $candidate = $base; $i = 2;
    while ((int)$pdo->query("SELECT COUNT(*) FROM clients WHERE id = " . $pdo->quote($candidate))->fetchColumn() > 0) {
        $candidate = $base . '-' . $i++;
    }
    return $candidate;
}

$id     = trim($_GET['id'] ?? '');
$isEdit = $id !== '';
$row    = ['id' => '', 'name' => '', 'contact_person' => '', 'email' => '', 'phone' => '', 'category' => 'individual', 'rank' => 'new', 'memo' => ''];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('error', 'クライアントが見つかりません。'); redirect_to($baseUrl . '/crm/clients.php'); }
    $row = array_merge($row, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $category      = trim($_POST['category'] ?? 'individual');
    $rank          = trim($_POST['rank'] ?? 'new');
    $memo          = trim($_POST['memo'] ?? '');

    if ($name === '') { set_flash('error', '名前は必須です。'); redirect_to($baseUrl . '/crm/client_edit.php' . ($isEdit ? '?id=' . urlencode($id) : '')); }

    $saveId = $isEdit ? $id : normalize_file_stem($name . '-' . date('Ymd'), 'client');
    if (!$isEdit) {
        $base = $saveId; $i = 2;
        while ((int)$pdo->query("SELECT COUNT(*) FROM clients WHERE id = " . $pdo->quote($saveId))->fetchColumn() > 0) {
            $saveId = $base . '-' . $i++;
        }
    }

    try {
        if ($isEdit) {
            $pdo->prepare('UPDATE clients SET name=?,contact_person=?,email=?,phone=?,category=?,rank=?,memo=? WHERE id=?')
                ->execute([$name, $contactPerson, $email, $phone, $category, $rank, $memo, $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'client', $id, 'クライアントを更新しました');
        } else {
            $pdo->prepare('INSERT INTO clients (id,name,contact_person,email,phone,category,rank,memo,created_by) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$saveId, $name, $contactPerson, $email, $phone, $category, $rank, $memo, (int)$user['id']]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'client', $saveId, 'クライアントを作成しました');
        }
        set_flash('success', '保存しました。');
        redirect_to($baseUrl . '/crm/clients.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/crm/client_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}

start_page($isEdit ? 'クライアントを編集' : 'クライアントを追加');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? 'クライアントを編集' : 'クライアントを追加') ?></h1></section>
  <form method="post" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>名前 <small class="muted">（企業名・個人名）</small></span><input type="text" name="name" value="<?= h($row['name']) ?>" required></label>
      <label><span>担当者名</span><input type="text" name="contact_person" value="<?= h($row['contact_person'] ?? '') ?>"></label>
    </div>
    <div class="form-grid two">
      <label><span>メールアドレス</span><input type="email" name="email" value="<?= h($row['email'] ?? '') ?>"></label>
      <label><span>電話番号</span><input type="text" name="phone" value="<?= h($row['phone'] ?? '') ?>"></label>
    </div>
    <div class="form-grid two">
      <label><span>区分</span>
        <select name="category">
          <option value="individual" <?= selected($row['category'], 'individual') ?>>個人</option>
          <option value="company" <?= selected($row['category'], 'company') ?>>企業</option>
          <option value="organization" <?= selected($row['category'], 'organization') ?>>団体</option>
        </select>
      </label>
      <label><span>取引ランク</span>
        <select name="rank">
          <option value="new" <?= selected($row['rank'], 'new') ?>>新規</option>
          <option value="existing" <?= selected($row['rank'], 'existing') ?>>既存</option>
        </select>
      </label>
    </div>
    <label><span>メモ</span><textarea name="memo" rows="4"><?= h($row['memo'] ?? '') ?></textarea></label>
    <div class="actions-inline">
      <button class="primary-btn" type="submit">保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/crm/clients.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
