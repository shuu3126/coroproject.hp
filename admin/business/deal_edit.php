<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id     = trim($_GET['id'] ?? '');
$isEdit = $id !== '';
$row    = ['id' => '', 'client_id' => '', 'title' => '', 'status' => '相談中', 'description' => '', 'budget' => '', 'start_date' => '', 'end_date' => '', 'source' => 'manual', 'memo' => '', 'inquiry_id' => null];
$candidates = [];
$inquiryId      = 0;
$inqClientName  = '';
$inqClientEmail = '';
try { $pdo->exec("ALTER TABLE biz_deals ADD COLUMN inquiry_id INT NULL DEFAULT NULL"); } catch (Exception $e) {}

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM biz_deals WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('error', '案件が見つかりません。'); redirect_to($baseUrl . '/business/deals.php'); }
    $row = array_merge($row, $found);
    $inquiryId = (int)($row['inquiry_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT bc.*, COALESCE(t.name, et.name) AS talent_name FROM biz_deal_candidates bc LEFT JOIN talents t ON t.id = bc.talent_id LEFT JOIN biz_ext_talents et ON et.id = bc.ext_talent_id WHERE bc.deal_id = ? ORDER BY bc.id ASC');
    $stmt->execute([$id]);
    $candidates = $stmt->fetchAll();
}

// Pre-fill new deal from inquiry URL params
if (!$isEdit && (int)($_GET['inquiry_id'] ?? 0) > 0) {
    $inquiryId      = (int)$_GET['inquiry_id'];
    $inqClientName  = trim($_GET['client_name']  ?? '');
    $inqClientEmail = trim($_GET['client_email'] ?? '');
    $row['title']       = trim($_GET['title']       ?? '');
    $row['description'] = trim($_GET['description'] ?? '');
    $row['source']      = 'inquiry';
}

// 候補追加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_candidate') {
    $dealId      = trim($_POST['deal_id'] ?? '');
    $talentType  = trim($_POST['talent_type'] ?? 'external');
    $talentId    = trim($_POST['talent_id'] ?? '') ?: null;
    $extTalentId = trim($_POST['ext_talent_id'] ?? '') ?: null;
    $note        = trim($_POST['note'] ?? '');
    if ($dealId !== '') {
        $pdo->prepare('INSERT INTO biz_deal_candidates (deal_id,talent_type,talent_id,ext_talent_id,status,note) VALUES (?,?,?,?,?,?)')
            ->execute([$dealId, $talentType, $talentId, $extTalentId, '提案中', $note]);
        write_admin_log($pdo, (int)$user['id'], 'create', 'biz_candidate', $dealId, 'VTuber候補を追加しました');
        set_flash('success', '候補を追加しました。');
    }
    redirect_to($baseUrl . '/business/deal_edit.php?id=' . urlencode($dealId));
}

// 候補ステータス変更
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_candidate') {
    $cid    = (int)($_POST['candidate_id'] ?? 0);
    $cstatus = trim($_POST['candidate_status'] ?? '提案中');
    $dealId = trim($_POST['deal_id'] ?? '');
    if ($cid > 0) {
        $pdo->prepare('UPDATE biz_deal_candidates SET status=? WHERE id=?')->execute([$cstatus, $cid]);
        set_flash('success', '候補ステータスを更新しました。');
    }
    redirect_to($baseUrl . '/business/deal_edit.php?id=' . urlencode($dealId));
}

// 候補削除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_candidate') {
    $cid    = (int)($_POST['candidate_id'] ?? 0);
    $dealId = trim($_POST['deal_id'] ?? '');
    if ($cid > 0) { $pdo->prepare('DELETE FROM biz_deal_candidates WHERE id=?')->execute([$cid]); }
    redirect_to($baseUrl . '/business/deal_edit.php?id=' . urlencode($dealId));
}

// 案件保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $title       = trim($_POST['title'] ?? '');
    $clientId    = trim($_POST['client_id'] ?? '') ?: null;
    $status      = trim($_POST['status'] ?? '相談中');
    $description = trim($_POST['description'] ?? '');
    $budget      = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
    $startDate   = trim($_POST['start_date'] ?? '') ?: null;
    $endDate     = trim($_POST['end_date'] ?? '') ?: null;
    $source        = trim($_POST['source'] ?? 'manual');
    $memo          = trim($_POST['memo'] ?? '');
    $inquiryIdPost = ((int)($_POST['inquiry_id'] ?? 0)) ?: null;

    if ($title === '') { set_flash('error', '案件名は必須です。'); redirect_to($baseUrl . '/business/deal_edit.php' . ($isEdit ? '?id=' . urlencode($id) : '')); }

    try {
        if ($isEdit) {
            $pdo->prepare('UPDATE biz_deals SET client_id=?,title=?,status=?,description=?,budget=?,start_date=?,end_date=?,source=?,memo=?,inquiry_id=?,updated_by=? WHERE id=?')
                ->execute([$clientId, $title, $status, $description, $budget, $startDate, $endDate, $source, $memo, $inquiryIdPost, (int)$user['id'], $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'biz_deal', $id, '案件を更新しました');
        } else {
            $saveId = normalize_file_stem('deal-' . date('Ymd-His'), 'deal');
            $pdo->prepare('INSERT INTO biz_deals (id,client_id,title,status,description,budget,start_date,end_date,source,memo,inquiry_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$saveId, $clientId, $title, $status, $description, $budget, $startDate, $endDate, $source, $memo, $inquiryIdPost, (int)$user['id']]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'biz_deal', $saveId, '案件を作成しました');
            $id = $saveId;
        }
        set_flash('success', '案件を保存しました。');
        redirect_to($baseUrl . '/business/deal_edit.php?id=' . urlencode($id));
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/business/deal_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}

$clients     = $pdo->query("SELECT id, name FROM clients ORDER BY name ASC")->fetchAll();
$internalTalents = $pdo->query("SELECT id, name FROM talents WHERE is_published = 1 ORDER BY name ASC")->fetchAll();
$extTalents  = $pdo->query("SELECT id, name FROM biz_ext_talents ORDER BY name ASC")->fetchAll();
$statuses    = ['相談中', '提案済み', '条件交渉中', '実施中', '完了', '不成立'];
$candStatuses = ['提案中', '選定済み', '見送り'];

start_page($isEdit ? '案件を編集' : '案件を追加', '');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? '案件を編集' : '案件を追加') ?></h1></section>

  <?php if ($inquiryId > 0): ?>
    <div class="alert-box alert-info" style="margin-bottom:12px;">
      <?php if ($inqClientName !== ''): ?>
        問い合わせ者: <strong><?= h($inqClientName) ?></strong> &lt;<?= h($inqClientEmail) ?>&gt; —
      <?php endif; ?>
      <a href="<?= h($baseUrl) ?>/mail/index.php?mailbox=inquiries&id=<?= $inquiryId ?>">お問い合わせを確認 →</a>
    </div>
  <?php endif; ?>

  <form method="post" class="card form-card form-stack">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="inquiry_id" value="<?= (int)$inquiryId ?>">
    <label><span>案件名</span><input type="text" name="title" value="<?= h($row['title']) ?>" required></label>
    <div class="form-grid two">
      <label><span>クライアント</span>
        <select name="client_id">
          <option value="">— 未選択 —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= h($c['id']) ?>" <?= selected($row['client_id'] ?? '', $c['id']) ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="help-text">リストにない場合は<a href="<?= h($baseUrl) ?>/client_edit.php" target="_blank">クライアントを先に追加</a>してください。</span>
      </label>
      <label><span>ステータス</span>
        <select name="status">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= h($s) ?>" <?= selected($row['status'], $s) ?>><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="form-grid two">
      <label><span>予算（円）</span><input type="number" name="budget" value="<?= h($row['budget'] ?? '') ?>"></label>
      <label><span>発生源</span>
        <select name="source">
          <option value="manual" <?= selected($row['source'], 'manual') ?>>手動入力</option>
          <option value="inquiry" <?= selected($row['source'], 'inquiry') ?>>問い合わせフォーム</option>
        </select>
      </label>
    </div>
    <div class="form-grid two">
      <label><span>開始日</span><input type="date" name="start_date" value="<?= h($row['start_date'] ?? '') ?>"></label>
      <label><span>終了日</span><input type="date" name="end_date" value="<?= h($row['end_date'] ?? '') ?>"></label>
    </div>
    <label><span>案件概要</span><textarea name="description" rows="4"><?= h($row['description'] ?? '') ?></textarea></label>
    <label><span>メモ（内部用）</span><textarea name="memo" rows="3"><?= h($row['memo'] ?? '') ?></textarea></label>
    <div class="actions-inline">
      <button class="primary-btn" type="submit">保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/business/deals.php">一覧へ戻る</a>
    </div>
  </form>

  <?php if ($isEdit): ?>
  <section class="card mt-24">
    <h3>会計アクション</h3>
    <p class="muted">この案件の請求書を発行するには下のボタンから作成してください。クライアント・案件情報が自動で引き継がれます。</p>
    <?php
      $invoiceUrl = $baseUrl . '/accounting/invoice_edit.php?mode=manual&division=business&deal_id=' . urlencode($id);
      if (!empty($row['client_id'])) $invoiceUrl .= '&client_id=' . urlencode($row['client_id']);
    ?>
    <div class="actions-inline" style="margin-top:12px;">
      <a class="ghost-btn" href="<?= h($invoiceUrl) ?>">この案件の請求書を作成</a>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php?division=business">Business 請求書一覧</a>
    </div>
  </section>

  <?php endif; ?>
  <?php if ($isEdit): ?>
  <section class="card mt-24">
    <h3>VTuber候補リスト</h3>
    <?php if (!$candidates): ?>
      <div class="empty-state">候補がまだいません。下のフォームから追加してください。</div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>名前</th><th>種別</th><th>ステータス</th><th>メモ</th><th>操作</th></tr></thead>
          <tbody>
          <?php foreach ($candidates as $c): ?>
            <tr>
              <td><?= h($c['talent_name'] ?? '—') ?></td>
              <td><?= h($c['talent_type'] === 'internal' ? '所属' : '所属外') ?></td>
              <td>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="update_candidate">
                  <input type="hidden" name="deal_id" value="<?= h($id) ?>">
                  <input type="hidden" name="candidate_id" value="<?= h((string)$c['id']) ?>">
                  <select name="candidate_status" onchange="this.form.submit()">
                    <?php foreach ($candStatuses as $cs): ?>
                      <option value="<?= h($cs) ?>" <?= selected($c['status'], $cs) ?>><?= h($cs) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= h($c['note'] ?? '') ?></td>
              <td>
                <form method="post" data-confirm="この候補を削除しますか？">
                  <input type="hidden" name="action" value="delete_candidate">
                  <input type="hidden" name="deal_id" value="<?= h($id) ?>">
                  <input type="hidden" name="candidate_id" value="<?= h((string)$c['id']) ?>">
                  <button class="danger-btn" type="submit">削除</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <form method="post" class="form-stack" style="margin-top:20px; padding-top:16px; border-top:1px solid var(--line);">
      <input type="hidden" name="action" value="add_candidate">
      <input type="hidden" name="deal_id" value="<?= h($id) ?>">
      <h4 style="margin:0 0 12px;">候補を追加</h4>
      <div class="form-grid two">
        <label><span>種別</span>
          <select name="talent_type" id="cand_type">
            <option value="external">所属外VTuber</option>
            <option value="internal">所属タレント</option>
          </select>
        </label>
        <label id="cand_ext_wrap"><span>所属外VTuber</span>
          <select name="ext_talent_id">
            <option value="">— 選択 —</option>
            <?php foreach ($extTalents as $et): ?>
              <option value="<?= h($et['id']) ?>"><?= h($et['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label id="cand_int_wrap" style="display:none;"><span>所属タレント</span>
          <select name="talent_id">
            <option value="">— 選択 —</option>
            <?php foreach ($internalTalents as $t): ?>
              <option value="<?= h($t['id']) ?>"><?= h($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <label><span>メモ</span><input type="text" name="note" placeholder="提案理由など"></label>
      <div><button class="ghost-btn" type="submit">候補を追加</button></div>
    </form>
  </section>
  <script>
    document.getElementById('cand_type').addEventListener('change', function() {
      document.getElementById('cand_ext_wrap').style.display = this.value === 'external' ? '' : 'none';
      document.getElementById('cand_int_wrap').style.display = this.value === 'internal' ? '' : 'none';
    });
  </script>
  <?php endif; ?>
</main>
<?php end_page(); ?>
