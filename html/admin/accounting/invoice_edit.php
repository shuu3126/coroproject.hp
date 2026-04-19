<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

require_admin_login();
$user = current_admin_user();

$mode = isset($_GET['mode']) ? (string)$_GET['mode'] : 'revenue';
$talents = accounting_list_talents($pdo, false);
$settings = load_app_settings($pdo, $config);
$defaultFx = (float)$settings['fx_default_rate'];

$form = [
    'mode' => $mode,
    'talent_id' => '',
    'year' => date('Y'),
    'month' => date('n'),
    'fx_rate' => number_format($defaultFx, 4, '.', ''),
    'note' => (string)$settings['office_invoice_note'],
    'details_text' => '',
    'subject' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = isset($_POST['mode']) ? (string)$_POST['mode'] : 'revenue';

    $form['mode'] = $mode;
    $form['talent_id'] = isset($_POST['talent_id']) ? (string)$_POST['talent_id'] : '';
    $form['year'] = isset($_POST['year']) ? (string)$_POST['year'] : date('Y');
    $form['month'] = isset($_POST['month']) ? (string)$_POST['month'] : date('n');
    $form['fx_rate'] = isset($_POST['fx_rate']) ? (string)$_POST['fx_rate'] : number_format($defaultFx, 4, '.', '');
    $form['note'] = isset($_POST['note']) ? (string)$_POST['note'] : '';
    $form['details_text'] = isset($_POST['details_text']) ? (string)$_POST['details_text'] : '';
    $form['subject'] = isset($_POST['subject']) ? (string)$_POST['subject'] : '';

    try {
        $talentId = trim($form['talent_id']);
        $year = (int)$form['year'];
        $month = (int)$form['month'];
        $note = trim($form['note']);

        if ($talentId === '') {
            throw new RuntimeException('タレントを選択してください。');
        }

        if ($mode === 'manual') {
            $subject = trim($form['subject']);
            $details = parse_detail_lines($form['details_text']);

            if ($subject === '') {
                throw new RuntimeException('件名を入力してください。');
            }
            if (!$details) {
                throw new RuntimeException('明細を1行以上入力してください。');
            }

            $invoiceId = accounting_create_manual_invoice(
                $pdo,
                $config,
                $user['id'],
                $talentId,
                $year,
                $month,
                $subject,
                $details,
                $note
            );
        } else {
            $fxRate = (float)$form['fx_rate'];
            if ($fxRate <= 0) {
                throw new RuntimeException('為替レートを正しく入力してください。');
            }

            $invoiceId = accounting_create_revenue_invoice(
                $pdo,
                $config,
                $user['id'],
                $talentId,
                $year,
                $month,
                $fxRate,
                $note
            );
        }

        write_admin_log(
            $pdo,
            (int)$user['id'],
            'create',
            'accounting_invoice',
            $invoiceId,
            '請求書を作成しました'
        );

        set_flash('success', '請求書を作成しました。');
        redirect_to($baseUrl . '/accounting/invoice_detail.php?id=' . $invoiceId);
    } catch (Exception $e) {
        set_flash('error', '請求書作成に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/accounting/invoice_edit.php?mode=' . urlencode($mode));
    }
}

start_page(
    $mode === 'manual' ? '手入力で請求書を作る' : '収益から請求書を作る',
    '収益ベースまたは手入力で請求書を作成します。'
);
?>
<main class="page-container narrow">
  <form method="post" class="card form-card form-stack">
    <input type="hidden" name="mode" value="<?= h($mode) ?>">

    <div class="form-grid two">
      <label>
        <span>タレント</span>
        <select name="talent_id" required>
          <option value="">選択してください</option>
          <?php foreach ($talents as $t): ?>
            <option value="<?= h((string)$t['id']) ?>" <?= selected($form['talent_id'], (string)$t['id']) ?>>
              <?= h($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <?php if ($mode === 'manual'): ?>
        <label>
          <span>件名</span>
          <input type="text" name="subject" value="<?= h($form['subject']) ?>" placeholder="例：イベント出演費">
        </label>
      <?php else: ?>
        <label>
          <span>為替レート（USD→JPY）</span>
          <input type="number" step="0.0001" name="fx_rate" value="<?= h($form['fx_rate']) ?>">
        </label>
      <?php endif; ?>
    </div>

    <div class="form-grid two">
      <label>
        <span>年</span>
        <input type="number" name="year" value="<?= h($form['year']) ?>">
      </label>

      <label>
        <span>月</span>
        <input type="number" min="1" max="12" name="month" value="<?= h($form['month']) ?>">
      </label>
    </div>

    <?php if ($mode === 'manual'): ?>
      <label>
        <span>明細（1行ごとに 内容|金額）</span>
        <textarea name="details_text" rows="8" placeholder="イベント参加費|25000&#10;デザイン費|18000"><?= h($form['details_text']) ?></textarea>
      </label>
    <?php else: ?>
      <div class="card">
        <strong>自動計算ルール</strong>
        <p class="muted">
          指定した締め年月までの未請求月をまとめて計算し、
          タレントごとの取り分率で円換算、5,000円以上なら請求書を作成します。
        </p>
      </div>
    <?php endif; ?>

    <label>
      <span>備考</span>
      <textarea name="note" rows="4"><?= h($form['note']) ?></textarea>
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit">請求書を作成する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>