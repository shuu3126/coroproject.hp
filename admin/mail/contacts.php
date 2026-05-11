<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$user = current_admin_user();
admin_mail_ensure_schema($pdo);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM mail_contacts WHERE id = ?')->execute([$id]);
            write_admin_log($pdo, (int)$user['id'], 'delete', 'mail_contact', (string)$id, 'メール宛先を削除しました');
            set_flash('success', '宛先を削除しました。');
        }
        redirect_to($baseUrl . '/mail/contacts.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $company = trim($_POST['company'] ?? '');
    $memo = trim($_POST['memo'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'メールアドレスを正しく入力してください。');
        redirect_to($baseUrl . '/mail/contacts.php' . ($id > 0 ? '?id=' . $id : ''));
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE mail_contacts SET name=?, email=?, company=?, memo=?, updated_by=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$name !== '' ? $name : null, $email, $company !== '' ? $company : null, $memo !== '' ? $memo : null, (int)$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'mail_contact', (string)$id, 'メール宛先を更新しました');
        } else {
            $stmt = $pdo->prepare('INSERT INTO mail_contacts (name, email, company, memo, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$name !== '' ? $name : null, $email, $company !== '' ? $company : null, $memo !== '' ? $memo : null, (int)$user['id'], (int)$user['id']]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'mail_contact', (string)$pdo->lastInsertId(), 'メール宛先を追加しました');
        }
        set_flash('success', '宛先を保存しました。');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            set_flash('error', '同じメールアドレスの宛先がすでにあります。');
        } else {
            set_flash('error', '宛先の保存に失敗しました。');
        }
    }
    redirect_to($baseUrl . '/mail/contacts.php');
}

$editId = (int)($_GET['id'] ?? 0);
$edit = ['id' => 0, 'name' => '', 'email' => '', 'company' => '', 'memo' => ''];
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM mail_contacts WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $row = $stmt->fetch();
    if ($row) {
        $edit = $row;
    }
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q !== '') {
    $where = 'WHERE name LIKE ? OR email LIKE ? OR company LIKE ? OR memo LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

$stmt = $pdo->prepare("
    SELECT *
    FROM mail_contacts
    {$where}
    ORDER BY COALESCE(last_contacted_at, updated_at, created_at) DESC, name ASC
    LIMIT 500
");
$stmt->execute($params);
$contacts = $stmt->fetchAll();

start_page('メール宛先管理', '送信先の名前・メールアドレスを管理します');
?>
<main class="page-container">
  <section class="page-header-block with-actions">
    <div>
      <h1>メール宛先管理</h1>
      <p>受信・送信した相手は自動でここにも登録されます。</p>
    </div>
    <div class="actions-inline">
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail.php">メール管理</a>
      <a class="primary-btn" href="<?= h($baseUrl) ?>/mail_compose.php">新規作成</a>
    </div>
  </section>

  <div class="card-grid two">
    <section class="card form-card">
      <h2 class="section-heading"><?= !empty($edit['id']) ? '宛先を編集' : '宛先を追加' ?></h2>
      <form method="post" class="form-stack">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label><span>名前</span><input type="text" name="name" value="<?= h($edit['name'] ?? '') ?>"></label>
        <label><span>メールアドレス</span><input type="email" name="email" value="<?= h($edit['email'] ?? '') ?>" required></label>
        <label><span>会社・所属</span><input type="text" name="company" value="<?= h($edit['company'] ?? '') ?>"></label>
        <label><span>メモ</span><textarea name="memo" rows="5"><?= h($edit['memo'] ?? '') ?></textarea></label>
        <div class="actions-inline">
          <button class="primary-btn" type="submit">保存</button>
          <?php if (!empty($edit['id'])): ?>
            <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail_contacts.php">新規追加へ</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="card table-card">
      <form method="get" class="mail-search">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="名前・メール・所属で検索">
        <button class="ghost-btn" type="submit">検索</button>
        <?php if ($q !== ''): ?><a class="ghost-btn" href="<?= h($baseUrl) ?>/mail_contacts.php">リセット</a><?php endif; ?>
      </form>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>宛先</th>
              <th style="width:120px;">最終連絡</th>
              <th style="width:150px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$contacts): ?>
              <tr><td colspan="3" class="empty-state">宛先はまだありません。</td></tr>
            <?php endif; ?>
            <?php foreach ($contacts as $contact): ?>
              <tr>
                <td>
                  <strong><?= h($contact['name'] ?: $contact['email']) ?></strong>
                  <div class="muted"><?= h($contact['email']) ?></div>
                  <?php if (!empty($contact['company'])): ?><div class="muted"><?= h($contact['company']) ?></div><?php endif; ?>
                </td>
                <td class="muted"><?= h(format_datetime($contact['last_contacted_at'])) ?></td>
                <td>
                  <div class="actions-inline">
                    <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail_compose.php?to=<?= urlencode($contact['email']) ?>">送信</a>
                    <a class="ghost-btn" href="<?= h($baseUrl) ?>/mail_contacts.php?id=<?= (int)$contact['id'] ?>">編集</a>
                    <form method="post" data-confirm="この宛先を削除します。よろしいですか？">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$contact['id'] ?>">
                      <button class="danger-btn" type="submit">削除</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>
<?php end_page(); ?>
