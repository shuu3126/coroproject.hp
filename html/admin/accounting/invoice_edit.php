<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__) . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

require_admin_login();
$user = current_admin_user();

$mode = (isset($_GET['mode']) ? $_GET['mode'] : 'revenue');

$talents = $pdo->query("
    SELECT id, display_name, name
    FROM accounting_talents
    WHERE status = 'active'
    ORDER BY display_name ASC
")->fetchAll();

$settings = accounting_settings($pdo);
$defaultFx = (float)(isset($settings['fx_default_rate']) ? $settings['fx_default_rate'] : 150);
$fxApiKey = (string)(isset($settings['fx_api_key']) ? $settings['fx_api_key'] : '');

$form = [
    'mode' => $mode,
    'talent_id' => '',
    'year' => date('Y'),
    'month' => date('n'),
    'fx_rate' => number_format($defaultFx, 4, '.', ''),
    'note' => (string)(isset($settings['office_invoice_note']) ? $settings['office_invoice_note'] : ''),
    'details_text' => '',
    'use_latest_fx' => '1',
];

$pageError = '';
$pageSuccess = '';
$latestFxInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['mode'] = (isset($_POST['mode']) ? (string)$_POST['mode'] : 'revenue');
    $mode = $form['mode'];

    $form['talent_id'] = (string)(isset($_POST['talent_id']) ? $_POST['talent_id'] : '');
    $form['year'] = (string)(isset($_POST['year']) ? $_POST['year'] : date('Y'));
    $form['month'] = (string)(isset($_POST['month']) ? $_POST['month'] : date('n'));
    $form['fx_rate'] = (string)(isset($_POST['fx_rate']) ? $_POST['fx_rate'] : number_format($defaultFx, 4, '.', ''));
    $form['note'] = (string)(isset($_POST['note']) ? $_POST['note'] : '');
    $form['details_text'] = (string)(isset($_POST['details_text']) ? $_POST['details_text'] : '');
    $form['use_latest_fx'] = !empty($_POST['use_latest_fx']) ? '1' : '0';

    $action = (isset($_POST['action']) ? (string)$_POST['action'] : 'create');

    if ($action === 'fetch_fx') {
        try {
            $latestFxInfo = accounting_fetch_latest_usd_jpy_rate($fxApiKey);
            $form['fx_rate'] = number_format((float)$latestFxInfo['rate'], 4, '.', '');
            $pageSuccess = '最新のUSD/JPYレートを取得しました。'
                . ($latestFxInfo['time_last_update_utc'] !== '' ? ' 更新時刻: ' . $latestFxInfo['time_last_update_utc'] : '');
        } catch (Throwable $e) {
            $pageError = '最新レートの取得に失敗しました: ' . $e->getMessage();
        }
    }

    if ($action === 'create') {
        $talentId = (int)$form['talent_id'];
        $year = (int)$form['year'];
        $month = (int)$form['month'];
        $note = trim($form['note']);
        $details = [];
        $amountJpy = 0.0;
        $months = [];

        try {
            if ($talentId <= 0) {
                throw new RuntimeException('タレントを選択してください。');
            }

            if (!empty($form['use_latest_fx'])) {
                $latestFxInfo = accounting_fetch_latest_usd_jpy_rate($fxApiKey);
                $fxRate = (float)$latestFxInfo['rate'];
                $form['fx_rate'] = number_format($fxRate, 4, '.', '');
            } else {
                $fxRate = (float)$form['fx_rate'];
            }

            if ($fxRate <= 0) {
                throw new RuntimeException('為替レートが不正です。');
            }

            if ($mode === 'manual') {
                $detailText = trim($form['details_text']);
                $details = parse_detail_lines($detailText);

                foreach ($details as $detail) {
                    $amountJpy += (float)$detail['amount'];
                }

                if ($amountJpy <= 0) {
                    throw new RuntimeException('手入力請求の明細を入力してください。');
                }
            } else {
                $months = accounting_get_uninvoiced_months($pdo, $talentId, $year, $month);
                if (!$months) {
                    throw new RuntimeException('指定締め月までの未請求月がありません。');
                }

                foreach ($months as $m) {
                    $share = accounting_calc_office_share_jpy_for_month(
                        $pdo,
                        $talentId,
                        (int)$m['year'],
                        (int)$m['month'],
                        $fxRate
                    );

                    if ($share <= 0) {
                        continue;
                    }

                    $amountJpy += $share;
                    $details[] = [
                        'desc' => sprintf('配信収益分配（%d年%02d月）', $m['year'], $m['month']),
                        'amount' => $share,
                    ];
                }

                if ($amountJpy < 5000) {
                    throw new RuntimeException('請求額が5,000円未満のため、今回は請求書を作成できません。');
                }
            }

            $invoiceNo = accounting_next_invoice_no($pdo);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO accounting_invoices
                    (invoice_no, talent_id, year, month, amount_jpy, fx_rate, status, note, created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $invoiceNo,
                $talentId,
                $year,
                $month,
                $amountJpy,
                $fxRate,
                'issued',
                $note,
                $user['id'],
                $user['id'],
            ]);

            $invoiceId = (int)$pdo->lastInsertId();

            if ($months) {
                $stmt = $pdo->prepare(
                    'INSERT INTO accounting_invoiced_months (invoice_id, talent_id, year, month, created_at)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                foreach ($months as $m) {
                    $stmt->execute([$invoiceId, $talentId, $m['year'], $m['month']]);
                }
            }

            accounting_insert_journal_for_invoice(
                $pdo,
                $invoiceId,
                $talentId,
                $year,
                $month,
                $invoiceNo,
                $amountJpy,
                $note
            );

            $talent = null;
            foreach ($talents as $t) {
                if ((int)$t['id'] === $talentId) {
                    $talent = $t;
                    break;
                }
            }

            $periodLabel = $months
                ? accounting_period_label($months)
                : sprintf('%d年%02d月', $year, $month);

            $doc = accounting_generate_document_html(
                [
                    'invoice_no' => $invoiceNo,
                    'talent_name' => $talent['name'] ?? ($talent['display_name'] ?? ''),
                    'year' => $year,
                    'month' => $month,
                    'period_label' => $periodLabel,
                    'amount_jpy' => $amountJpy,
                    'note' => $note,
                    'details' => $details,
                ],
                'invoice',
                $config['uploads']['accounting_root'] . '/invoices',
                $config['uploads']['accounting_prefix'] . '/invoices'
            );

            $pdo->prepare(
                'INSERT INTO accounting_invoice_files
                    (invoice_id, file_type, file_path, original_file_name, created_by, created_at)
                 VALUES
                    (?, ?, ?, ?, ?, NOW())'
            )->execute([
                $invoiceId,
                'invoice',
                $doc['relative_path'],
                $doc['original_name'],
                $user['id'],
            ]);

            write_admin_log(
                $pdo,
                (int)$user['id'],
                'create',
                'accounting_invoice',
                $invoiceId,
                '請求書を作成しました',
                [
                    'invoice_no' => $invoiceNo,
                    'fx_rate' => $fxRate,
                    'latest_fx_time' => $latestFxInfo['time_last_update_utc'] ?? '',
                ]
            );

            $pdo->commit();

            set_flash('success', '請求書を作成しました。使用レート: ' . number_format($fxRate, 4, '.', ''));
            redirect_to($baseUrl . '/accounting/invoice_detail.php?id=' . $invoiceId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pageError = '請求書作成に失敗しました: ' . $e->getMessage();
        }
    }
}

start_page(
    $mode === 'manual' ? '手入力で請求書を作る' : '収益から請求書を作る',
    '収益ベースまたは手入力で請求書を作成します。'
);
?>
<main class="page-container narrow">
  <?php if ($pageError !== ''): ?>
    <div class="alert-box alert-error"><?= h($pageError) ?></div>
  <?php endif; ?>

  <?php if ($pageSuccess !== ''): ?>
    <div class="alert-box alert-success"><?= h($pageSuccess) ?></div>
  <?php endif; ?>

  <form method="post" class="card form-card form-stack">
    <input type="hidden" name="mode" value="<?= h($mode) ?>">

    <div class="form-grid two">
      <label>
        <span>タレント</span>
        <select name="talent_id" required>
          <option value="">選択してください</option>
          <?php foreach ($talents as $t): ?>
            <option value="<?= h((string)$t['id']) ?>" <?= ((string)$form['talent_id'] === (string)$t['id']) ? 'selected' : '' ?>>
              <?= h($t['display_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        <span>為替レート（USD→JPY）</span>
        <input type="number" step="0.0001" name="fx_rate" value="<?= h((string)$form['fx_rate']) ?>">
        <div class="help-text">「請求書を作成する」時に、下のチェックがONなら最新レートで再取得して保存します。</div>
      </label>
    </div>

    <div class="actions-inline">
      <label class="checkbox-row">
        <input type="checkbox" name="use_latest_fx" value="1" <?= !empty($form['use_latest_fx']) ? 'checked' : '' ?>>
        <span>請求書作成時に最新レートを自動取得して使う</span>
      </label>
      <button class="ghost-btn" type="submit" name="action" value="fetch_fx">最新レートを取得</button>
    </div>

    <?php if ($latestFxInfo): ?>
      <div class="help-text">
        取得レート: <?= h(number_format((float)$latestFxInfo['rate'], 4, '.', '')) ?>
        <?php if (!empty($latestFxInfo['time_last_update_utc'])): ?>
          / 更新時刻: <?= h($latestFxInfo['time_last_update_utc']) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="form-grid two">
      <label>
        <span>年</span>
        <input type="number" name="year" value="<?= h((string)$form['year']) ?>">
      </label>

      <label>
        <span>月</span>
        <input type="number" min="1" max="12" name="month" value="<?= h((string)$form['month']) ?>">
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
          30%換算で5,000円以上なら請求書を作成します。
          最新レート自動取得をONにしている場合は、作成確定時の最新USD/JPYで計算します。
        </p>
      </div>
    <?php endif; ?>

    <label>
      <span>備考</span>
      <textarea name="note" rows="4"><?= h($form['note']) ?></textarea>
    </label>

    <div class="actions-inline">
      <button class="primary-btn" type="submit" name="action" value="create">請求書を作成する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>