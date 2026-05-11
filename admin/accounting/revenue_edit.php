<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$isEdit = $id > 0;
$talents = accounting_list_talents($pdo, false);
$row = [
    'talent_id' => isset($talents[0]['id']) ? $talents[0]['id'] : '',
    'year' => (int)date('Y'),
    'month' => (int)date('n'),
    'currency' => 'USD',
    'amount_streaming' => 0,
    'amount_goods' => 0,
    'amount_sponsor' => 0,
    'evidence_path' => '',
    'memo' => '',
];
if ($isEdit) {
    $found = accounting_fetch_revenue($pdo, $id);
    if (!$found) {
        set_flash('error', '収益データが見つかりません。');
        redirect_to($baseUrl . '/accounting/revenues.php');
    }
    $row = array_merge($row, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'talent_id' => trim(isset($_POST['talent_id']) ? $_POST['talent_id'] : ''),
        'year' => (int)(isset($_POST['year']) ? $_POST['year'] : date('Y')),
        'month' => (int)(isset($_POST['month']) ? $_POST['month'] : date('n')),
        'currency' => trim(isset($_POST['currency']) ? $_POST['currency'] : 'USD'),
        'amount_streaming' => (float)(isset($_POST['amount_streaming']) ? $_POST['amount_streaming'] : 0),
        'amount_goods' => (float)(isset($_POST['amount_goods']) ? $_POST['amount_goods'] : 0),
        'amount_sponsor' => (float)(isset($_POST['amount_sponsor']) ? $_POST['amount_sponsor'] : 0),
        'evidence_path' => trim(isset($_POST['evidence_path']) ? $_POST['evidence_path'] : ''),
        'memo' => trim(isset($_POST['memo']) ? $_POST['memo'] : ''),
    ];
    try {
        $upload = save_uploaded_file_any($_FILES['evidence_file'] ?? [], $config['uploads']['accounting_root'] . '/revenues', $config['uploads']['accounting_prefix'] . '/revenues', $data['talent_id'] . '-' . $data['year'] . '-' . $data['month']);
        if ($upload) {
            $data['evidence_path'] = $upload['path'];
        }
        $savedId = accounting_save_revenue($pdo, $user['id'], $isEdit ? $id : null, $data);
        write_admin_log($pdo, (int)$user['id'], $isEdit ? 'edit' : 'create', 'accounting_revenue', $savedId, '収益データを保存しました', ['talent_id' => $data['talent_id'], 'year' => $data['year'], 'month' => $data['month']]);
        set_flash('success', '収益データを保存しました。');
        redirect_to($baseUrl . '/accounting/revenues.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/revenue_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : ''));
    }
}
start_page($isEdit ? '収益を編集' : '収益を追加', '過去分を含めて収益入力ができます。');
?>
<main class="page-container narrow"><form method="post" enctype="multipart/form-data" class="card form-card form-stack">
<div class="form-grid two"><label><span>タレント</span><select name="talent_id" required><?php foreach($talents as $t): ?><option value="<?= h($t['id']) ?>" <?= selected($row['talent_id'], $t['id']) ?>><?= h($t['name']) ?></option><?php endforeach; ?></select></label><label><span>通貨</span><select name="currency"><option value="USD" <?= selected($row['currency'], 'USD') ?>>USD</option><option value="JPY" <?= selected($row['currency'], 'JPY') ?>>JPY</option></select></label></div>
<div class="form-grid two"><label><span>年</span><input type="number" name="year" value="<?= h((string)$row['year']) ?>"></label><label><span>月</span><input type="number" min="1" max="12" name="month" value="<?= h((string)$row['month']) ?>"></label></div>
<div class="form-grid two"><label><span>配信収益</span><input type="number" step="0.01" name="amount_streaming" value="<?= h((string)$row['amount_streaming']) ?>"></label><label><span>グッズ収益</span><input type="number" step="0.01" name="amount_goods" value="<?= h((string)$row['amount_goods']) ?>"></label></div>
<label><span>スポンサー収益</span><input type="number" step="0.01" name="amount_sponsor" value="<?= h((string)$row['amount_sponsor']) ?>"></label>
<label><span>証拠ファイルパス</span><input type="text" name="evidence_path" value="<?= h($row['evidence_path']) ?>"></label>
<label><span>証拠ファイルをアップロード</span><input type="file" name="evidence_file"></label>
<label><span>メモ</span><textarea name="memo" rows="4"><?= h($row['memo']) ?></textarea></label>
<div class="actions-inline"><button class="primary-btn" type="submit">この内容で保存する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/revenues.php">一覧へ戻る</a></div>
</form></main>
<?php end_page(); ?>
