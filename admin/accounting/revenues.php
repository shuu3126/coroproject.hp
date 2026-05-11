<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) ? $_POST['action'] : '') === 'delete') {
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    if ($id > 0) {
        accounting_delete_revenue($pdo, $id);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_revenue', $id, '収益データを削除しました');
        set_flash('success', '収益データを削除しました。');
    }
    redirect_to($baseUrl . '/accounting/revenues.php');
}
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$rows = accounting_fetch_all_revenues($pdo, $q);
start_page('収益入力', '会計システムで使用する月次収益データを登録します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions"><div><h1>収益入力</h1><p>登録した収益は請求候補と請求書作成に使われます。</p></div><a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/revenue_edit.php">新しく収益を登録する</a></section>
  <form method="get" class="card form-card form-grid two">
    <label><span>タレント名・メモで検索</span><input type="text" name="q" value="<?= h($q) ?>"></label>
    <div class="actions-inline" style="align-self:end;"><button class="ghost-btn" type="submit">検索する</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/revenues.php">条件をリセット</a></div>
  </form>
  <div class="card table-card mt-24"><div class="table-wrap"><table><thead><tr><th>タレント</th><th>年月</th><th>通貨</th><th>配信</th><th>グッズ</th><th>スポンサー</th><th>合計</th><th>操作</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="8" class="empty-state">まだ収益データがありません。</td></tr><?php endif; ?>
  <?php foreach ($rows as $row): $sum=(float)$row['amount_streaming']+(float)$row['amount_goods']+(float)$row['amount_sponsor']; ?>
    <tr><td><?= h($row['invoice_name']) ?></td><td><?= h(sprintf('%04d-%02d', $row['year'], $row['month'])) ?></td><td><?= h($row['currency']) ?></td><td class="text-right"><?= h(format_money($row['amount_streaming'], 2)) ?></td><td class="text-right"><?= h(format_money($row['amount_goods'], 2)) ?></td><td class="text-right"><?= h(format_money($row['amount_sponsor'], 2)) ?></td><td class="text-right"><?= h(format_money($sum, 2)) ?></td><td class="actions-inline"><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/revenue_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a><form method="post" data-confirm="この収益データを削除しますか？"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h((string)$row['id']) ?>"><button class="danger-btn" type="submit">削除</button></form></td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
</main>
<?php end_page(); ?>
