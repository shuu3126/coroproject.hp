<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

require_admin_login();
$user = current_admin_user();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$isEdit = $id > 0;

$talents = $pdo->query("
    SELECT id, name
    FROM talents
    ORDER BY sort_order ASC, name ASC, id ASC
")->fetchAll();

$categories = $pdo->query("
    SELECT kind, name
    FROM accounting_journal_categories
    WHERE is_active = 1
    ORDER BY kind ASC, sort_order ASC, id ASC
")->fetchAll();

$row = [
    'date' => date('Y-m-d'),
    'kind' => 'expense',
    'category' => '',
    'amount' => '0',
    'description' => '',
    'talent_id' => '',
    'evidence_path' => '',
    'source' => 'manual',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM accounting_journal_entries WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $row = array_merge($row, $found);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim(isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'));
    $kind = trim(isset($_POST['kind']) ? $_POST['kind'] : 'expense');
    $category = trim(isset($_POST['category']) ? $_POST['category'] : '');
    $amount = (float)(isset($_POST['amount']) ? $_POST['amount'] : 0);
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $talentId = ((isset($_POST['talent_id']) ? $_POST['talent_id'] : '') !== '') ? (int)$_POST['talent_id'] : null;
    $evidence = trim(isset($_POST['evidence_path']) ? $_POST['evidence_path'] : '');

    try {
        $upload = save_uploaded_file_any(
            $_FILES['evidence_file'] ?? [],
            $config['uploads']['accounting_root'] . '/journal',
            $config['uploads']['accounting_prefix'] . '/journal',
            'journal-' . $date
        );
        if ($upload) {
            $evidence = $upload['path'];
        }

        if ($isEdit) {
            $stmt = $pdo->prepare('
                UPDATE accounting_journal_entries
                SET `date` = ?, kind = ?, category = ?, amount = ?, description = ?, talent_id = ?, evidence_path = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute([$date, $kind, $category, $amount, $description, $talentId, $evidence, $user['id'], $id]);

            write_admin_log($pdo, (int)$user['id'], 'edit', 'accounting_journal', $id, '記帳を更新しました');
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO accounting_journal_entries
                    (`date`, kind, category, amount, description, talent_id, invoice_id, source, evidence_path, created_by, updated_by, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW(), NOW())
            ');
            $stmt->execute([$date, $kind, $category, $amount, $description, $talentId, 'manual', $evidence, $user['id'], $user['id']]);

            $newId = (int)$pdo->lastInsertId();
            write_admin_log($pdo, (int)$user['id'], 'create', 'accounting_journal', $newId, '記帳を作成しました');
        }

        set_flash('success', '記帳を保存しました。');
        redirect_to($baseUrl . '/accounting/journals.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/journal_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : ''));
    }
}

start_page($isEdit ? '記帳を編集' : '記帳を追加', '手入力で収入・支出の記録を追加します。');
?>
<main class="page-container narrow">
  <form method="post" enctype="multipart/form-data" class="card form-card form-stack">
    <div class="form-grid two">
      <label>
        <span>日付</span>
        <input type="date" name="date" value="<?= h($row['date']) ?>">
      </label>

      <label>
        <span>種別</span>
        <select name="kind">
          <option value="income" <?= selected($row['kind'], 'income') ?>>収入</option>
          <option value="expense" <?= selected($row['kind'], 'expense') ?>>支出</option>
        </select>
      </label>
    </div>

    <div class="form-grid two">
      <label>
        <span>カテゴリ</span>
        <input type="text" list="category-list" name="category" value="<?= h($row['category']) ?>">
        <datalist id="category-list">
          <?php foreach ($categories as $c): ?>
            <option value="<?= h($c['name']) ?>">
          <?php endforeach; ?>
        </datalist>
      </label>

      <label>
        <span>金額</span>
        <input type="number" step="0.01" name="amount" value="<?= h((string)$row['amount']) ?>">
      </label>
    </div>

    <label>
      <span>内容</span>
      <textarea name="description" rows="4"><?= h($row['description']) ?></textarea>
    </label>

    <label>
      <span>タレント（任意）</span>
      <select name="talent_id">
        <option value="">選択しない</option>
        <?php foreach ($talents as $t): ?>
          <option value="<?= h((string)$t['id']) ?>" <?= selected($row['talent_id'], $t['id']) ?>>
            <?= h($t['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      <span>証憑ファイルパス</span>
      <input type="text" name="evidence_path" value="<?= h($row['evidence_path']) ?>">
    </label>

    <label>
      <span>証憑ファイルをアップロード</span>
      <input type="file" name="evidence_file">
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">この内容で保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/journals.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>