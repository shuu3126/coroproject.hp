<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();
$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$isEdit = $id > 0;
$row = [
    'date' => date('Y-m-d'),
    'kind' => 'expense',
    'category' => '',
    'amount' => 0,
    'description' => '',
    'talent_id' => '',
    'evidence_path' => '',
    'source' => 'manual',
];
if ($isEdit) {
    $found = accounting_fetch_journal($pdo, $id);
    if (!$found) {
        set_flash('error', '記帳データが見つかりません。');
        redirect_to($baseUrl . '/accounting/journals.php');
    }
    if ($found['source'] === 'invoice_auto') {
        set_flash('error', '自動記帳は編集できません。');
        redirect_to($baseUrl . '/accounting/journals.php');
    }
    $row = array_merge($row, $found);
}
$talents = accounting_list_talents($pdo, false);
$categories = accounting_fetch_categories($pdo, null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'date' => trim(isset($_POST['date']) ? $_POST['date'] : date('Y-m-d')),
        'kind' => trim(isset($_POST['kind']) ? $_POST['kind'] : 'expense'),
        'category' => trim(isset($_POST['category']) ? $_POST['category'] : ''),
        'amount' => (float)(isset($_POST['amount']) ? $_POST['amount'] : 0),
        'description' => trim(isset($_POST['description']) ? $_POST['description'] : ''),
        'talent_id' => trim(isset($_POST['talent_id']) ? $_POST['talent_id'] : ''),
        'evidence_path' => trim(isset($_POST['evidence_path']) ? $_POST['evidence_path'] : ''),
    ];
    try {
        $upload = save_uploaded_file_any($_FILES['evidence_file'] ?? [], $config['uploads']['accounting_root'] . '/journal', $config['uploads']['accounting_prefix'] . '/journal', 'journal-' . $data['date']);
        if ($upload) {
            $data['evidence_path'] = $upload['path'];
        }
        $savedId = accounting_save_journal($pdo, $user['id'], $isEdit ? $id : null, $data);
        write_admin_log($pdo, (int)$user['id'], $isEdit ? 'edit' : 'create', 'accounting_journal', $savedId, '記帳を保存しました');
        set_flash('success', '記帳を保存しました。');
        redirect_to($baseUrl . '/accounting/journals.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/journal_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : ''));
    }
}
start_page($isEdit ? '記帳を編集' : '記帳を追加', '手入力で収入・支出の記録を追加します。');
?>
<main class="page-container narrow"><form method="post" enctype="multipart/form-data" class="card form-card form-stack">
<div class="form-grid two"><label><span>日付</span><input type="date" name="date" value="<?= h($row['date']) ?>"></label><label><span>種別</span><select name="kind"><option value="income" <?= selected($row['kind'],'income') ?>>income</option><option value="expense" <?= selected($row['kind'],'expense') ?>>expense</option></select></label></div>
<div class="form-grid two"><label><span>カテゴリ</span><input type="text" list="category-list" name="category" value="<?= h($row['category']) ?>"><datalist id="category-list"><?php foreach($categories as $c): ?><option value="<?= h($c['name']) ?>"><?php endforeach; ?></datalist></label><label><span>金額</span><input type="number" step="0.01" name="amount" value="<?= h((string)$row['amount']) ?>"></label></div>
<label><span>内容</span><textarea name="description" rows="4"><?= h($row['description']) ?></textarea></label>
<label><span>タレント（任意）</span><select name="talent_id"><option value="">選択しない</option><?php foreach($talents as $t): ?><option value="<?= h((string)$t['id']) ?>" <?= selected($row['talent_id'],$t['id']) ?>><?= h($t['name']) ?></option><?php endforeach; ?></select></label>
<label><span>証憑ファイルパス</span><input type="text" name="evidence_path" value="<?= h($row['evidence_path']) ?>"></label>
<label><span>証憑ファイルをアップロード</span><input type="file" name="evidence_file"></label>
<div class="actions-inline"><button class="primary-btn" type="submit">この内容で保存する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/journals.php">一覧へ戻る</a></div>
</form></main>
<?php end_page(); ?>
