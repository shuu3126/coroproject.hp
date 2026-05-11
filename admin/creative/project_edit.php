<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id     = trim($_GET['id'] ?? '');
$isEdit = $id !== '';
$row    = ['id' => '', 'client_id' => '', 'title' => '', 'category' => 'illustration', 'status' => '受付', 'creator_id' => '', 'deadline' => '', 'deliverable_url' => '', 'client_amount' => '', 'creator_amount' => '', 'memo' => '', 'source' => 'manual'];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM cre_projects WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('error', '案件が見つかりません。'); redirect_to($baseUrl . '/creative/projects.php'); }
    $row = array_merge($row, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title'] ?? '');
    $clientId       = trim($_POST['client_id'] ?? '') ?: null;
    $category       = trim($_POST['category'] ?? 'illustration');
    $status         = trim($_POST['status'] ?? '受付');
    $creatorId      = trim($_POST['creator_id'] ?? '') ?: null;
    $deadline       = trim($_POST['deadline'] ?? '') ?: null;
    $deliverableUrl = trim($_POST['deliverable_url'] ?? '');
    $clientAmount   = $_POST['client_amount'] !== '' ? (float)$_POST['client_amount'] : null;
    $creatorAmount  = $_POST['creator_amount'] !== '' ? (float)$_POST['creator_amount'] : null;
    $memo           = trim($_POST['memo'] ?? '');

    if ($title === '') { set_flash('error', '案件名は必須です。'); redirect_to($baseUrl . '/creative/project_edit.php' . ($isEdit ? '?id=' . urlencode($id) : '')); }

    try {
        if ($isEdit) {
            $pdo->prepare('UPDATE cre_projects SET client_id=?,title=?,category=?,status=?,creator_id=?,deadline=?,deliverable_url=?,client_amount=?,creator_amount=?,memo=?,updated_by=? WHERE id=?')
                ->execute([$clientId, $title, $category, $status, $creatorId, $deadline, $deliverableUrl, $clientAmount, $creatorAmount, $memo, (int)$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'cre_project', $id, '制作案件を更新しました');
        } else {
            $saveId = normalize_file_stem('cre-' . date('Ymd-His'), 'project');
            $pdo->prepare('INSERT INTO cre_projects (id,client_id,title,category,status,creator_id,deadline,deliverable_url,client_amount,creator_amount,memo,source,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$saveId, $clientId, $title, $category, $status, $creatorId, $deadline, $deliverableUrl, $clientAmount, $creatorAmount, $memo, 'manual', (int)$user['id']]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'cre_project', $saveId, '制作案件を作成しました');
        }
        set_flash('success', '保存しました。');
        redirect_to($baseUrl . '/creative/projects.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/creative/project_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}

$clients    = $pdo->query("SELECT id, name FROM clients ORDER BY name ASC")->fetchAll();
$creators   = $pdo->query("SELECT id, name, type FROM cre_creators WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$statuses   = ['受付', '企画・ラフ', '制作中', '確認中', '納品', '完了'];
$categories = ['illustration' => 'イラスト', 'live2d' => 'Live2D', 'single_art' => '一枚絵', 'music' => '音楽', 'video' => '動画', 'other' => 'その他'];

$ca  = (float)($row['client_amount']  ?? 0);
$cra = (float)($row['creator_amount'] ?? 0);
$margin = ($ca > 0 && $cra > 0) ? $ca - $cra : null;

start_page($isEdit ? '制作案件を編集' : '制作案件を追加', '');
?>
<main class="page-container narrow">
  <section class="page-header-block with-actions">
    <h1><?= $isEdit ? h($row['title']) : '新規案件' ?></h1>
    <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/projects.php">一覧へ戻る</a>
  </section>

  <form method="post" class="card form-card form-stack">
    <label><span>案件名</span><input type="text" name="title" value="<?= h($row['title']) ?>" required placeholder="例：キャラクターデザイン / ○○様 Live2D制作"></label>

    <div class="form-grid two">
      <label><span>依頼者（クライアント）</span>
        <select name="client_id">
          <option value="">— 未選択 —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= h($c['id']) ?>" <?= selected($row['client_id'] ?? '', $c['id']) ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="help-text"><a href="<?= h($baseUrl) ?>/client_edit.php" target="_blank">新規クライアントを追加</a></span>
      </label>
      <label><span>担当クリエイター</span>
        <select name="creator_id">
          <option value="">— 未選択 —</option>
          <?php foreach ($creators as $cr): ?>
            <option value="<?= h($cr['id']) ?>" <?= selected($row['creator_id'] ?? '', $cr['id']) ?>><?= h($cr['name']) ?> (<?= $cr['type'] === 'inhouse' ? '社内' : '外部' ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="form-grid two">
      <label><span>制作カテゴリ</span>
        <select name="category">
          <?php foreach ($categories as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= selected($row['category'], $val) ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><span>ステータス</span>
        <select name="status">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= h($s) ?>" <?= selected($row['status'], $s) ?>><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <label><span>納期</span><input type="date" name="deadline" value="<?= h($row['deadline'] ?? '') ?>"></label>

    <div class="form-grid two">
      <label><span>クライアントへの請求額（円）</span>
        <input type="number" step="1" name="client_amount" value="<?= h($row['client_amount'] ?? '') ?>" placeholder="0">
      </label>
      <label><span>クリエイターへの支払額（円）</span>
        <input type="number" step="1" name="creator_amount" value="<?= h($row['creator_amount'] ?? '') ?>" placeholder="0">
        <?php if ($margin !== null): ?>
          <span class="help-text">仲介料: ¥<?= h(number_format($margin)) ?></span>
        <?php endif; ?>
      </label>
    </div>

    <label><span>成果物URL（Google Drive / Dropbox など）</span>
      <input type="text" name="deliverable_url" value="<?= h($row['deliverable_url'] ?? '') ?>" placeholder="https://...">
    </label>

    <label><span>メモ</span><textarea name="memo" rows="3" placeholder="内部共有メモ。クライアントには見えません。"><?= h($row['memo'] ?? '') ?></textarea></label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/creative/projects.php">キャンセル</a>
    </div>
  </form>

  <?php if ($isEdit): ?>
  <div class="card form-card mt-16" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
      <div style="font-weight:700;font-size:.88em;margin-bottom:2px;">会計</div>
      <div class="muted" style="font-size:.8em;">請求書を作成するとクライアント・案件情報が自動引き継ぎされます</div>
    </div>
    <div class="actions-inline">
      <?php
        $invoiceUrl = $baseUrl . '/accounting/invoice_edit.php?mode=manual&division=creative&project_id=' . urlencode($id);
        if (!empty($row['client_id'])) $invoiceUrl .= '&client_id=' . urlencode($row['client_id']);
        if (!empty($row['title']))     $invoiceUrl .= '&subject=' . urlencode($row['title']);
      ?>
      <a class="ghost-btn" href="<?= h($invoiceUrl) ?>">請求書を作成</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php?division=creative">Creative 請求書一覧</a>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php end_page(); ?>
