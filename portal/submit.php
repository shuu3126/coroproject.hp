<?php
require_once __DIR__ . '/_bootstrap.php';
require_portal_login();

$talent = current_portal_talent();

// デフォルトは先月（締め後報告が一般的なため）
$defDt    = new DateTime('first day of last month');
$defYear  = (int)$defDt->format('Y');
$defMonth = (int)$defDt->format('n');

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf($_POST['_csrf'] ?? '')) {
        $errors[] = '不正なリクエストです。ページを再読み込みして再試行してください。';
    } else {
        $year  = (int)($_POST['year']  ?? $defYear);
        $month = (int)($_POST['month'] ?? $defMonth);

        $data = [
            'currency'         => $_POST['currency']         ?? 'JPY',
            'amount_streaming' => $_POST['amount_streaming'] ?? '0',
            'amount_goods'     => $_POST['amount_goods']     ?? '0',
            'amount_sponsor'   => $_POST['amount_sponsor']   ?? '0',
            'portal_note'      => $_POST['portal_note']      ?? '',
        ];

        $evidence_path = null;
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = portal_upload_evidence($_FILES['evidence'], $talent['talent_id'], $year, $month);
            if (isset($upload['error'])) {
                $errors[] = $upload['error'];
            } else {
                $evidence_path = $upload['path'];
            }
        }

        if (empty($errors)) {
            $result = portal_submit_revenue($pdo, $talent['talent_id'], $year, $month, $data, $evidence_path);
            if (isset($result['success'])) {
                portal_flash_set('success', sprintf('%d年%d月分の収益報告を送信しました。管理者の確認をお待ちください。', $year, $month));
                portal_redirect($portalBase . '/history.php');
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

// 既存データを確認（編集用プリフィル）
$prefill = null;
$editYear  = (int)($_GET['year']  ?? $defYear);
$editMonth = (int)($_GET['month'] ?? $defMonth);
if ($editYear >= 2020 && $editMonth >= 1 && $editMonth <= 12) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM accounting_revenues WHERE talent_id = ? AND year = ? AND month = ?');
        $stmt->execute([$talent['talent_id'], $editYear, $editMonth]);
        $prefill = $stmt->fetch();
    } catch (Exception $e) {}
}

// 年月の選択肢（過去2年）
$yearOptions = [];
$curYear = (int)(new DateTime())->format('Y');
for ($y = $curYear; $y >= $curYear - 2; $y--) {
    $yearOptions[] = $y;
}

$portalPageTitle = '収益報告';
require __DIR__ . '/_header.php';
?>

<h1 class="portal-page-title">月次収益を報告する</h1>
<p class="portal-page-desc">各プラットフォームの収益をまとめて入力してください。エビデンス（スクリーンショット等）を必ず添付してください。</p>

<?php if ($errors): ?>
  <?php foreach ($errors as $e): ?>
    <div class="portal-flash portal-flash--error"><?= portal_h($e) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= portal_h(portal_csrf_token()) ?>">

  <div class="portal-card">
    <div class="portal-card-title">📅 対象年月</div>
    <div class="portal-form-row">
      <div class="portal-form-group">
        <label for="year">年</label>
        <select id="year" name="year">
          <?php foreach ($yearOptions as $y): ?>
            <option value="<?= $y ?>" <?= ($prefill ? $prefill['year'] : $editYear) == $y ? 'selected' : '' ?>>
              <?= $y ?>年
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="portal-form-group">
        <label for="month">月</label>
        <select id="month" name="month">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($prefill ? $prefill['month'] : $editMonth) == $m ? 'selected' : '' ?>>
              <?= $m ?>月
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="portal-form-group">
        <label for="currency">通貨</label>
        <select id="currency" name="currency">
          <option value="JPY" <?= ($prefill['currency'] ?? 'JPY') === 'JPY' ? 'selected' : '' ?>>JPY（円）</option>
          <option value="USD" <?= ($prefill['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD（ドル）</option>
        </select>
      </div>
    </div>
  </div>

  <div class="portal-card">
    <div class="portal-card-title">💰 収益内訳</div>
    <div class="portal-form-row">
      <div class="portal-form-group">
        <label for="amount_streaming">配信収益</label>
        <input type="number" id="amount_streaming" name="amount_streaming"
               value="<?= portal_h($prefill['amount_streaming'] ?? $_POST['amount_streaming'] ?? '0') ?>"
               min="0" step="0.01">
        <div class="portal-form-note">YouTube・ニコニコ・Twitch など</div>
      </div>
      <div class="portal-form-group">
        <label for="amount_goods">グッズ売上</label>
        <input type="number" id="amount_goods" name="amount_goods"
               value="<?= portal_h($prefill['amount_goods'] ?? $_POST['amount_goods'] ?? '0') ?>"
               min="0" step="0.01">
        <div class="portal-form-note">BOOTH・委託販売など</div>
      </div>
      <div class="portal-form-group">
        <label for="amount_sponsor">スポンサー収入</label>
        <input type="number" id="amount_sponsor" name="amount_sponsor"
               value="<?= portal_h($prefill['amount_sponsor'] ?? $_POST['amount_sponsor'] ?? '0') ?>"
               min="0" step="0.01">
        <div class="portal-form-note">企業案件・タイアップなど</div>
      </div>
    </div>
  </div>

  <div class="portal-card">
    <div class="portal-card-title">📎 エビデンス（証拠資料）</div>
    <div class="portal-upload-box" id="uploadBox">
      <input type="file" name="evidence" id="evidenceInput"
             accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
      <div class="portal-upload-label" id="uploadLabel">
        <strong>クリックまたはドラッグ</strong>してファイルを選択<br>
        <span style="font-size:11px;">JPG・PNG・GIF・WebP・PDF（最大10MB）</span>
      </div>
    </div>
    <?php if (!empty($prefill['evidence_path'])): ?>
      <div style="font-size:12px;color:var(--sub);margin-top:8px;">
        現在のファイル: <?= portal_h(basename($prefill['evidence_path'])) ?>
        （新しいファイルを選択すると上書きされます）
      </div>
    <?php endif; ?>
  </div>

  <div class="portal-card">
    <div class="portal-card-title">💬 コメント（任意）</div>
    <div class="portal-form-group" style="margin:0;">
      <textarea id="portal_note" name="portal_note" rows="3"
                placeholder="特記事項があれば記入してください（例：〇月はグッズ売上が多い月でした、など）"><?= portal_h($prefill['portal_note'] ?? $_POST['portal_note'] ?? '') ?></textarea>
    </div>
  </div>

  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <button class="portal-btn portal-btn-primary" type="submit">
      <?= $prefill ? '修正して再送信する' : '送信する' ?>
    </button>
    <a class="portal-btn portal-btn-outline" href="<?= portal_h($portalBase) ?>/dashboard.php">キャンセル</a>
    <?php if ($prefill): ?>
      <span style="font-size:12px;color:var(--sub);">
        ※ 確定済みの月は変更できません。変更が必要な場合は運営へご連絡ください。
      </span>
    <?php endif; ?>
  </div>
</form>

<script>
const input = document.getElementById('evidenceInput');
const label = document.getElementById('uploadLabel');
const box   = document.getElementById('uploadBox');

input.addEventListener('change', () => {
  if (input.files[0]) {
    label.innerHTML = '<strong>' + input.files[0].name + '</strong>';
  }
});

box.addEventListener('dragover', (e) => { e.preventDefault(); box.classList.add('dragover'); });
box.addEventListener('dragleave', () => box.classList.remove('dragover'));
box.addEventListener('drop', (e) => {
  e.preventDefault();
  box.classList.remove('dragover');
  if (e.dataTransfer.files[0]) {
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    input.files = dt.files;
    label.innerHTML = '<strong>' + e.dataTransfer.files[0].name + '</strong>';
  }
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
