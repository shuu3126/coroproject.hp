<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$rows = accounting_fetch_invoices($pdo);
start_page('請求管理', '請求書の作成、入金確認、領収書発行を管理します。');
?>
<main class="page-container">
<section class="page-header-block with-actions"><div><h1>請求管理</h1><p>一覧から請求詳細へ進んで書類操作や入金処理を行います。</p></div><div class="actions-inline"><a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=revenue">収益から請求書を作る</a><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_edit.php?mode=manual">手入力で請求書を作る</a></div></section>
<div class="card table-card"><div class="table-wrap"><table><thead><tr><th>請求書番号</th><th>タレント</th><th>締め年月</th><th>件名</th><th>請求額</th><th>状態</th><th>領収書</th><th>入金日</th><th>操作</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="9" class="empty-state">まだ請求データがありません。</td></tr><?php endif; ?>
<?php foreach($rows as $row): ?><tr><td><?= h($row['invoice_no']) ?></td><td><?= h($row['talent_name']) ?></td><td><?= h(sprintf('%04d-%02d',$row['close_year'],$row['close_month'])) ?></td><td><?= h($row['subject']) ?></td><td class="text-right">¥<?= h(format_money($row['amount_jpy'])) ?></td><td><span class="status-badge <?= status_badge_class($row['status']) ?>"><?= h(invoice_status_label($row['status'])) ?></span></td><td><?= !empty($row['receipt_pdf_path']) ? 'あり' : '未発行' ?></td><td><?= h(format_datetime($row['paid_at'])) ?></td><td><a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoice_detail.php?id=<?= urlencode((string)$row['id']) ?>">詳細を見る</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
</main>
<?php end_page(); ?>
