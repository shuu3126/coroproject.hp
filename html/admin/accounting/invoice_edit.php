<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

require_admin_login();
$user = current_admin_user();

function invoice_edit_fetch_latest_usd_jpy_rate($apiKey) {
    $apiKey = trim((string)$apiKey);
    if ($apiKey === '') {
        throw new RuntimeException('為替APIキーが未設定です。設定画面で入力してください。');
    }

    $url = 'https://v6.exchangerate-api.com/v6/' . rawurlencode($apiKey) . '/latest/USD';

    if (!function_exists('curl_init')) {
        throw new RuntimeException('サーバーで cURL が使えません。');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'CORO-PROJECT-ADMIN/1.0',
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $response === '') {
        throw new RuntimeException('為替APIへの接続に失敗しました: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new RuntimeException('為替APIの応答が不正です。HTTP ' . $httpCode);
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        throw new RuntimeException('為替APIの応答を解析できませんでした。');
    }

    if (($json['result'] ?? '') !== 'success') {
        $errorType = isset($json['error-type']) ? (string)$json['error-type'] : 'unknown-error';
        throw new RuntimeException('為替APIエラー: ' . $errorType);
    }

    $rate = $json['conversion_rates']['JPY'] ?? null;
    if (!is_numeric($rate)) {
        throw new RuntimeException('JPYレートを取得できませんでした。');
    }

    return [
        'rate' => (float)$rate,
        'updated_at' => (string)($json['time_last_update_utc'] ?? ''),
        'next_update' => (string)($json['time_next_update_utc'] ?? ''),
    ];
}

$mode = isset($_GET['mode']) ? (string)$_GET['mode'] : 'revenue';
$talents = accounting_list_talents($pdo, false);
$settings = load_app_settings($pdo, $config);
$defaultFx = (float)$settings['fx_default_rate'];
$fxApiKey = isset($settings['fx_api_key']) ? (string)$settings['fx_api_key'] : '';

$form = [
    'mode' => $mode,
    'talent_id' => '',
    'year' => date('Y'),
    'month' => date('n'),
    'fx_rate' => number_format($defaultFx, 4, '.', ''),
    'note' => (string)$settings['office_invoice_note'],
    'details_text' => '',
    'subject' => '',
    'use_latest_fx' => '1',
];

$latestFxInfo = null;
$pageError = '';
$pageSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : 'create';
    $mode = isset($_POST['mode']) ? (string)$_POST['mode'] : 'revenue';

    $form['mode'] = $mode;
    $form['talent_id'] = isset($_POST['talent_id']) ? (string)$_POST['talent_id'] : '';
    $form['year'] = isset($_POST['year']) ? (string)$_POST['year'] : date('Y');
    $form['month'] = isset($_POST['month']) ? (string)$_POST['month'] : date('n');
    $form['fx_rate'] = isset($_POST['fx_rate']) ? (string)$_POST['fx_rate'] : number_format($defaultFx, 4, '.', '');
    $form['note'] = isset($_POST['note']) ? (string)$_POST['note'] : '';
    $form['details_text'] = isset($_POST['details_text']) ? (string)$_POST['details_text'] : '';
    $form['subject'] = isset($_POST['subject']) ? (string)$_POST['subject'] : '';
    $form['use_latest_fx'] = !empty($_POST['use_latest_fx']) ? '1' : '0';

    if ($action === 'fetch_fx') {
        try {
            $latestFxInfo = invoice_edit_fetch_latest_usd_jpy_rate($fxApiKey);
            $form['fx_rate'] = number_format((float)$latestFxInfo['rate'], 4, '.', '');
            $pageSuccess = '最新のUSD/JPYレートを取得しました。'
                . ($latestFxInfo['updated_at'] !== '' ? ' 更新時刻: ' . $latestFxInfo['updated_at'] : '');
        } catch (Exception $e) {
            $pageError = '最新レートの取得に失敗しました: ' . $e->getMessage();
        }
    }

    if ($action === 'create') {
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
                if (!empty($form['use_latest_fx'])) {
                    $latestFxInfo = invoice_edit_fetch_latest_usd_jpy_rate($fxApiKey);
                    $fxRate = (float)$latestFxInfo['rate'];
                    $form['fx_rate'] = number_format($fxRate, 4, '.', '');
                } else {
                    $fxRate = (float)$form['fx_rate'];
                }

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
          <div class="help-text">「最新レートを取得」または作成時自動取得が使えます。</div>
        </label>
      <?php endif; ?>
    </div>

    <?php if ($mode !== 'manual'): ?>
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
          <?php if (!empty($latestFxInfo['updated_at'])): ?>
            / 更新時刻: <?= h($latestFxInfo['updated_at']) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

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
      <button class="primary-btn" type="submit" name="action" value="create">請求書を作成する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/accounting/invoices.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>