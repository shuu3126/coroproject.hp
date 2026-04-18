<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';
require_admin_login();
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT i.*, t.display_name AS talent_name, SUM(CASE WHEN f.file_type = "receipt" THEN 1 ELSE 0 END) AS receipt_count FROM accounting_invoices i JOIN accounting_talents t ON t.id = i.talent_id LEFT JOIN accounting_invoice_files f ON f.invoice_id = i.id GROUP BY i.id ORDER BY i.created_at DESC';
$rows = $pdo->query($sql)->fetchAll();
start_page('請求管理', '請求書の作成、入金確認、領収書発行を管理します。');
?>
<main class="page-container">
<section class="page-header-block with-actions"><div><h1>請求管理</h1><p>一覧から請求詳細へ進んで書類操作や入金処理を行います。</p></div><div class="actions-inline"><a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=revenue">収益から請求書を作る</a><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=manual">手入力で請求書を作る</a></div></section>
<div class="card table-card"><div class="table-wrap"><table><thead><tr><th>請求書番号</th><th>タレント</th><th>対象年月</th><th>請求額</th><th>状態</th><th>領収書</th><th>入金日</th><th>操作</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="8" class="empty-state">まだ請求データがありません。</td></tr><?php endif; ?>
<?php foreach($rows as $row): ?><tr><td><?= h($row['invoice_no']) ?></td><td><?= h($row['talent_name']) ?></td><td><?= h(sprintf('%04d-%02d',$row['year'],$row['month'])) ?></td><td class="text-right">¥<?= h(format_money($row['amount_jpy'])) ?></td><td><span class="status-badge <?= status_badge_class((string)$row['status']) ?>"><?= h($row['status']) ?></span></td><td><?= $row['receipt_count'] ? 'あり' : '未発行' ?></td><td><?= h(format_datetime($row['paid_at'])) ?></td><td><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_detail.php?id=<?= urlencode((string)$row['id']) ?>">詳細を見る</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
</main>
<?php end_page(); ?>
