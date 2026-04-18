<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_admin_login();
$user = current_admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM accounting_revenues WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_revenue', $id, '収益データを削除しました');
        set_flash('success', '収益データを削除しました。');
    }
    redirect_to($baseUrl . '/accounting/revenues.php');
}
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT r.*, t.display_name AS talent_name FROM accounting_revenues r JOIN accounting_talents t ON t.id = r.talent_id';
$params=[];
if ($q !== '') { $sql .= ' WHERE t.display_name LIKE ?'; $params=["%$q%"];} 
$sql .= ' ORDER BY r.year DESC, r.month DESC, r.id DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
start_page('収益入力', '会計システムで使用する月次収益データを登録します。');
?>
<main class="page-container">
  <section class="page-header-block with-actions"><div><h1>収益入力</h1><p>登録した収益は請求候補の計算に使われます。</p></div><a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/revenue_edit.php">新しく収益を登録する</a></section>
  <div class="card table-card"><div class="table-wrap"><table><thead><tr><th>タレント</th><th>年月</th><th>通貨</th><th>配信</th><th>グッズ</th><th>スポンサー</th><th>合計</th><th>操作</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="8" class="empty-state">まだ収益データがありません。</td></tr><?php endif; ?>
  <?php foreach ($rows as $row): $sum=(float)$row['amount_streaming']+(float)$row['amount_goods']+(float)$row['amount_sponsor']; ?>
    <tr><td><?= h($row['talent_name']) ?></td><td><?= h(sprintf('%04d-%02d', $row['year'], $row['month'])) ?></td><td><?= h($row['currency']) ?></td><td class="text-right"><?= h(format_money($row['amount_streaming'])) ?></td><td class="text-right"><?= h(format_money($row['amount_goods'])) ?></td><td class="text-right"><?= h(format_money($row['amount_sponsor'])) ?></td><td class="text-right"><?= h(format_money($sum)) ?></td><td class="actions-inline"><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/revenue_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a><form method="post" data-confirm="この収益データを削除しますか？"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h((string)$row['id']) ?>"><button class="danger-btn" type="submit">削除</button></form></td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
</main>
<?php end_page(); ?>
