<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

require_admin_login();
$user = current_admin_user();

function journal_kind_label($kind) {
    if ($kind === 'income') return '収入';
    if ($kind === 'expense') return '支出';
    return (string)$kind;
}

function journal_source_label($source) {
    if ($source === 'manual') return '手入力';
    if ($source === 'invoice_auto') return '請求書自動記帳';
    return (string)$source;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ((isset($_POST['action']) ? $_POST['action'] : '')) === 'delete') {
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);

    $stmt = $pdo->prepare('SELECT source FROM accounting_journal_entries WHERE id = ?');
    $stmt->execute([$id]);
    $info = $stmt->fetch();

    if ($id > 0 && $info && ((isset($info['source']) ? $info['source'] : '')) !== 'invoice_auto') {
        $pdo->prepare('DELETE FROM accounting_journal_entries WHERE id = ?')->execute([$id]);
        write_admin_log($pdo, (int)$user['id'], 'delete', 'accounting_journal', $id, '記帳を削除しました');
        set_flash('success', '記帳を削除しました。');
    } else {
        set_flash('error', '請求書由来の自動記帳は削除できません。');
    }

    redirect_to($baseUrl . '/accounting/journals.php');
}

$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$kind = trim(isset($_GET['kind']) ? $_GET['kind'] : '');
$source = trim(isset($_GET['source']) ? $_GET['source'] : '');
$talentId = trim(isset($_GET['talent_id']) ? $_GET['talent_id'] : '');

$talents = $pdo->query("
    SELECT id, name
    FROM talents
    ORDER BY sort_order ASC, name ASC, id ASC
")->fetchAll();

$sql = "
    SELECT
        j.*,
        t.name AS talent_name
    FROM accounting_journal_entries j
    LEFT JOIN talents t ON t.id = j.talent_id
";

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(j.category LIKE ? OR j.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($kind !== '') {
    $where[] = 'j.kind = ?';
    $params[] = $kind;
}

if ($source !== '') {
    $where[] = 'j.source = ?';
    $params[] = $source;
}

if ($talentId !== '') {
    $where[] = 'j.talent_id = ?';
    $params[] = (int)$talentId;
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY j.date DESC, j.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$income = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM accounting_journal_entries WHERE kind = 'income'")->fetchColumn();
$expense = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM accounting_journal_entries WHERE kind = 'expense'")->fetchColumn();

start_page('会計一覧', '収入・支出・差引を確認し、手入力の記帳を追加できます。');
?>
<main class="page-container">
  <section class="card-grid three">
    <div class="card stat-card">
      <div class="muted">収入合計</div>
      <div class="stat-number">¥<?= h(format_money($income)) ?></div>
    </div>

    <div class="card stat-card">
      <div class="muted">支出合計</div>
      <div class="stat-number">¥<?= h(format_money($expense)) ?></div>
    </div>

    <div class="card stat-card">
      <div class="muted">差引</div>
      <div class="stat-number">¥<?= h(format_money((float)$income - (float)$expense)) ?></div>
    </div>
  </section>

  <section class="page-header-block with-actions mt-24">
    <div>
      <h1>会計一覧</h1>
      <p>記帳の確認・手入力・証憑管理を行います。</p>
    </div>
    <div class="actions-inline">
      <a class="primary-btn" href="<?= h($baseUrl) ?>/accounting/journal_edit.php">記帳を追加する</a>
    </div>
  </section>

  <form method="get" class="card form-card form-grid two">
    <label>
      <span>カテゴリ・内容で検索</span>
      <input type="text" name="q" value="<?= h($q) ?>">
    </label>

    <label>
      <span>種別</span>
      <select name="kind">
        <option value="">すべて</option>
        <option value="income" <?= selected($kind, 'income') ?>>収入</option>
        <option value="expense" <?= selected($kind, 'expense') ?>>支出</option>
      </select>
    </label>

    <label>
      <span>出所</span>
      <select name="source">
        <option value="">すべて</option>
        <option value="manual" <?= selected($source, 'manual') ?>>手入力</option>
        <option value="invoice_auto" <?= selected($source, 'invoice_auto') ?>>請求書自動記帳</option>
      </select>
    </label>

    <label>
      <span>タレント</span>
      <select name="talent_id">
        <option value="">すべて</option>
        <?php foreach ($talents as $t): ?>
          <option value="<?= h((string)$t['id']) ?>" <?= selected($talentId, (string)$t['id']) ?>>
            <?= h($t['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="actions-inline" style="grid-column: 1 / -1;">
      <button class="ghost-btn" type="submit">検索する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/journals.php">条件をリセット</a>
    </div>
  </form>

  <div class="card table-card mt-24">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>日付</th>
            <th>種別</th>
            <th>カテゴリ</th>
            <th>内容</th>
            <th>タレント</th>
            <th>金額</th>
            <th>出所</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="8" class="empty-state">まだ会計データがありません。</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= h($row['date']) ?></td>
              <td><?= h(journal_kind_label($row['kind'])) ?></td>
              <td><?= h($row['category']) ?></td>
              <td><?= h($row['description']) ?></td>
              <td><?= h((isset($row['talent_name']) ? $row['talent_name'] : '')) ?></td>
              <td class="text-right">¥<?= h(format_money($row['amount'])) ?></td>
              <td><?= h(journal_source_label($row['source'])) ?></td>
              <td class="actions-inline">
                <?php if (((isset($row['source']) ? $row['source'] : '')) !== 'invoice_auto'): ?>
                  <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/journal_edit.php?id=<?= urlencode((string)$row['id']) ?>">編集</a>
                  <form method="post" data-confirm="この記帳を削除しますか？">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">
                    <button class="danger-btn" type="submit">削除</button>
                  </form>
                <?php else: ?>
                  <span class="muted">自動記帳</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php end_page(); ?>