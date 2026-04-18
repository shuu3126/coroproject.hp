<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_admin_login();
$user = current_admin_user();

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$isEdit = $id > 0;
$talents = $pdo->query('SELECT id, display_name FROM accounting_talents ORDER BY display_name ASC')->fetchAll();
$row = ['talent_id'=>'','year'=>date('Y'),'month'=>date('n'),'currency'=>'JPY','amount_streaming'=>'0','amount_goods'=>'0','amount_sponsor'=>'0','evidence_path'=>'','memo'=>''];
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM accounting_revenues WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $row = array_merge($row, $found);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $talentId=(int)((isset($_POST['talent_id']) ? $_POST['talent_id'] : 0)); $year=(int)((isset($_POST['year']) ? $_POST['year'] : date('Y'))); $month=(int)((isset($_POST['month']) ? $_POST['month'] : date('n'))); $currency=trim((isset($_POST['currency']) ? $_POST['currency'] : 'JPY'));
    $aStreaming=(float)((isset($_POST['amount_streaming']) ? $_POST['amount_streaming'] : 0)); $aGoods=(float)((isset($_POST['amount_goods']) ? $_POST['amount_goods'] : 0)); $aSponsor=(float)((isset($_POST['amount_sponsor']) ? $_POST['amount_sponsor'] : 0)); $memo=trim((isset($_POST['memo']) ? $_POST['memo'] : '')); $evidence=trim((isset($_POST['evidence_path']) ? $_POST['evidence_path'] : ''));
    if ($talentId<=0) { set_flash('error','タレントを選択してください。'); redirect_to($baseUrl . '/accounting/revenue_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : '')); }
    try {
        $upload = save_uploaded_file_any($_FILES['evidence_file'] ?? [], $config['uploads']['accounting_root'] . '/revenues', $config['uploads']['accounting_prefix'] . '/revenues', 'revenue-' . $talentId . '-' . $year . sprintf('%02d',$month));
        if ($upload) $evidence = $upload['path'];
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE accounting_revenues SET talent_id=?, year=?, month=?, currency=?, amount_streaming=?, amount_goods=?, amount_sponsor=?, evidence_path=?, memo=?, updated_by=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$talentId,$year,$month,$currency,$aStreaming,$aGoods,$aSponsor,$evidence,$memo,$user['id'],$id]);
            write_admin_log($pdo,(int)$user['id'],'edit','accounting_revenue',$id,'収益データを更新しました');
            set_flash('success','収益データを更新しました。');
        } else {
            $stmt = $pdo->prepare('INSERT INTO accounting_revenues (talent_id,year,month,currency,amount_streaming,amount_goods,amount_sponsor,evidence_path,memo,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$talentId,$year,$month,$currency,$aStreaming,$aGoods,$aSponsor,$evidence,$memo,$user['id'],$user['id']]);
            $newId = (int)$pdo->lastInsertId();
            write_admin_log($pdo,(int)$user['id'],'create','accounting_revenue',$newId,'収益データを作成しました');
            set_flash('success','収益データを作成しました。');
        }
        redirect_to($baseUrl . '/accounting/revenues.php');
    } catch (Exception $e) {
        set_flash('error','保存中にエラーが発生しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/revenue_edit.php' . ($isEdit ? '?id=' . urlencode((string)$id) : ''));
    }
}
start_page($isEdit ? '収益データを編集' : '収益データを追加', 'タレントごとの月次収益を入力します。');
?>
<main class="page-container narrow"><form method="post" enctype="multipart/form-data" class="card form-card form-stack">
<div class="form-grid two"><label><span>タレント</span><select name="talent_id" required><option value="">選択してください</option><?php foreach($talents as $t): ?><option value="<?= h((string)$t['id']) ?>" <?= selected($row['talent_id'],$t['id']) ?>><?= h($t['display_name']) ?></option><?php endforeach; ?></select></label><label><span>通貨</span><select name="currency"><option value="JPY" <?= selected($row['currency'],'JPY') ?>>JPY</option><option value="USD" <?= selected($row['currency'],'USD') ?>>USD</option></select></label></div>
<div class="form-grid two"><label><span>年</span><input type="number" name="year" value="<?= h((string)$row['year']) ?>"></label><label><span>月</span><input type="number" name="month" min="1" max="12" value="<?= h((string)$row['month']) ?>"></label></div>
<div class="form-grid two"><label><span>配信収益</span><input type="number" step="0.01" name="amount_streaming" value="<?= h((string)$row['amount_streaming']) ?>"></label><label><span>グッズ収益</span><input type="number" step="0.01" name="amount_goods" value="<?= h((string)$row['amount_goods']) ?>"></label></div>
<label><span>スポンサー収益</span><input type="number" step="0.01" name="amount_sponsor" value="<?= h((string)$row['amount_sponsor']) ?>"></label>
<label><span>証拠ファイルパス</span><input type="text" name="evidence_path" value="<?= h($row['evidence_path']) ?>"></label>
<label><span>証拠ファイルをアップロード</span><input type="file" name="evidence_file"></label>
<label><span>メモ</span><textarea name="memo" rows="4"><?= h($row['memo']) ?></textarea></label>
<div class="actions-inline"><button class="primary-btn" type="submit">この内容で保存する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/revenues.php">一覧へ戻る</a></div>
</form></main>
<?php end_page(); ?>
