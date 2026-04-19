<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) ? $_POST['action'] : '') === 'delete') {
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $row = accounting_fetch_journal($pdo, $id);
    if ($row && $row['source'] !== 'invoice_auto') {
        $pdo->prepare('DELETE FROM accounting_journal_entries WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_journal', $id, '記帳を削除しました');
        set_flash('success', '記帳を削除しました。');
    } else {
        set_flash('error', '請求書由来の自動記帳は削除できません。');
    }
    redirect_to($baseUrl . '/accounting/journals.php');
}
$filters = [
    'date_from' => isset($_GET['date_from']) ? trim($_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? trim($_GET['date_to']) : '',
    'kind' => isset($_GET['kind']) ? trim($_GET['kind']) : '',
    'category' => isset($_GET['category']) ? trim($_GET['category']) : '',
    'talent_id' => isset($_GET['talent_id']) ? trim($_GET['talent_id']) : '',
];
$rows = accounting_fetch_journal_rows($pdo, $filters);
$income = 0; $expense = 0;
foreach ($rows as $row) { if ($row['kind'] === 'income') $income += $row['amount']; else $expense += $row['amount']; }
$categories = accounting_fetch_categories($pdo, null);
$talents = accounting_list_talents($pdo, false);
start_page('会計一覧', '収入・支出・差引を確認し、手入力の記帳を追加できます。');
?>
<main class="page-container">
  <section class="card-grid three"><div class="card stat-card"><div class="muted">収入合計</div><div class="stat-number">¥<?= h(format_money($income)) ?></div></div><div class="card stat-card"><div class="muted">支出合計</div><div class="stat-number">¥<?= h(format_money($expense)) ?></div></div><div class="card stat-card"><div class="muted">差引</div><div class="stat-number">¥<?= h(format_money((float)$income - (float)$expense)) ?></div></div></section>
  <section class="page-header-block with-actions mt-24"><div><h1>会計一覧</h1><p>記帳の確認・手入力・証憑管理を行います。</p></div><div class="actions-inline"><a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/journal_edit.php">記帳を追加する</a></div></section>
  <form method="get" class="card form-card form-grid two">
    <label><span>期間（開始）</span><input type="date" name="date_from" value="<?= h($filters['date_from']) ?>"></label>
    <label><span>期間（終了）</span><input type="date" name="date_to" value="<?= h($filters['date_to']) ?>"></label>
    <label><span>種別</span><select name="kind"><option value="">すべて</option><option value="income" <?= selected($filters['kind'], 'income') ?>>income</option><option value="expense" <?= selected($filters['kind'], 'expense') ?>>expense</option></select></label>
    <label><span>カテゴリ</span><input type="text" name="category" list="category-list" value="<?= h($filters['category']) ?>"><datalist id="category-list"><?php foreach($categories as $c): ?><option value="<?= h($c['name']) ?>"><?php endforeach; ?></datalist></label>
    <label><span>タレント</span><select name="talent_id"><option value="">すべて</option><?php foreach($talents as $t): ?><option value="<?= h($t['id']) ?>" <?= selected($filters['talent_id'], $t['id']) ?>><?= h($t['name']) ?></option><?php endforeach; ?></select></label>
    <div class="actions-inline" style="align-self:end;"><button class="ghost-btn" type="submit">絞り込む</button><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/journals.php">リセット</a></div>
  </form>
  <div class="card table-card mt-24"><div class="table-wrap"><table><thead><tr><th>日付</th><th>種別</th><th>カテゴリ</th><th>内容</th><th>タレント</th><th>金額</th><th>出所</th><th>操作</th></tr></thead><tbody>
  <?php if(!$rows): ?><tr><td colspan="8" class="empty-state">まだ会計データがありません。</td></tr><?php endif; ?>
  <?php foreach($rows as $row): ?><tr><td><?= h($row['date']) ?></td><td><?= h($row['kind']) ?></td><td><?= h($row['category']) ?></td><td><?= h($row['description']) ?></td><td><?= h((isset($row['talent_name']) ? $row['talent_name'] : '')) ?></td><td class="text-right">¥<?= h(format_money($row['amount'])) ?></td><td><?= h($row['source']) ?></td><td class="actions-inline"><?php if($row['source'] !== 'invoice_auto'): ?><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/journal_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a><form method="post" data-confirm="この記帳を削除しますか？"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h((string)$row['id']) ?>"><button class="danger-btn" type="submit">削除</button></form><?php else: ?><span class="muted">自動記帳</span><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
</main>
<?php end_page(); ?>
