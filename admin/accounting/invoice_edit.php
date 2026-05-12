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

// 収益試算 AJAX エンドポイント
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['action']) ? $_GET['action'] : '') === 'preview_revenue') {
    header('Content-Type: application/json; charset=utf-8');
    $previewSettings = load_app_settings($pdo, $config);
    $previewDefaultFx = (float)$previewSettings['fx_default_rate'];

    $previewTalentId = trim(isset($_GET['talent_id']) ? $_GET['talent_id'] : '');
    $previewYear     = (int)(isset($_GET['year'])      ? $_GET['year']      : date('Y'));
    $previewMonth    = (int)(isset($_GET['month'])     ? $_GET['month']     : date('n'));
    $previewFxRate   = (float)(isset($_GET['fx_rate']) ? $_GET['fx_rate']   : $previewDefaultFx);
    if ($previewFxRate <= 0) $previewFxRate = $previewDefaultFx;

    if ($previewTalentId === '') {
        echo json_encode(['ok' => false, 'error' => 'タレントを選択してください']);
        exit;
    }

    $previewMonths = accounting_get_uninvoiced_months_upto($pdo, $previewTalentId, $previewYear, $previewMonth);
    if (!$previewMonths) {
        echo json_encode(['ok' => false, 'error' => '指定した締め月までの未請求月がありません（すべて請求済み、または収益データが未登録です）']);
        exit;
    }

    $previewItems = [];
    $previewTotal = 0.0;
    foreach ($previewMonths as $pm) {
        $share = accounting_calc_office_share_jpy_for_month($pdo, $previewTalentId, $pm['year'], $pm['month'], $previewFxRate);
        if ($share <= 0) continue;
        $previewItems[] = [
            'label'  => sprintf('%d年%02d月', $pm['year'], $pm['month']),
            'amount' => (int)round($share),
        ];
        $previewTotal += $share;
    }

    echo json_encode([
        'ok'          => true,
        'items'       => $previewItems,
        'total'       => (int)round($previewTotal),
        'can_invoice' => $previewTotal >= accounting_threshold_yen(),
        'threshold'   => (int)accounting_threshold_yen(),
    ]);
    exit;
}

$mode     = isset($_GET['mode'])       ? (string)$_GET['mode']       : 'revenue';
$division = isset($_GET['division'])   ? (string)$_GET['division']   : 'production';
$preDealId    = trim($_GET['deal_id']    ?? '');
$preProjectId = trim($_GET['project_id'] ?? '');
$preClientId  = trim($_GET['client_id']  ?? '');

if ($division !== 'production') {
    $mode = 'manual';
}

$talents  = accounting_list_talents($pdo, false);
$clients  = $pdo->query("SELECT id, name FROM clients ORDER BY name ASC")->fetchAll();
$settings = load_app_settings($pdo, $config);
$defaultFx = (float)$settings['fx_default_rate'];
$fxApiKey = isset($settings['fx_api_key']) ? (string)$settings['fx_api_key'] : '';

$form = [
    'mode'         => $mode,
    'division'     => $division,
    'talent_id'    => isset($_GET['talent_id']) ? trim($_GET['talent_id']) : '',
    'client_id'    => $preClientId,
    'deal_id'      => $preDealId,
    'project_id'   => $preProjectId,
    'year'         => date('Y'),
    'month'        => date('n'),
    'fx_rate'      => number_format($defaultFx, 4, '.', ''),
    'note'         => (string)$settings['office_invoice_note'],
    'details_text' => '',
    'subject'      => '',
    'use_latest_fx' => '1',
];

$latestFxInfo = null;
$pageError = '';
$pageSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = isset($_POST['action'])   ? (string)$_POST['action']   : 'create';
    $mode     = isset($_POST['mode'])     ? (string)$_POST['mode']     : 'revenue';
    $division = isset($_POST['division']) ? (string)$_POST['division'] : 'production';

    $form['mode']         = $mode;
    $form['division']     = $division;
    $form['talent_id']    = isset($_POST['talent_id'])  ? (string)$_POST['talent_id']  : '';
    $form['client_id']    = isset($_POST['client_id'])  ? (string)$_POST['client_id']  : '';
    $form['deal_id']      = isset($_POST['deal_id'])    ? (string)$_POST['deal_id']    : '';
    $form['project_id']   = isset($_POST['project_id']) ? (string)$_POST['project_id'] : '';
    $form['year']         = isset($_POST['year'])       ? (string)$_POST['year']       : date('Y');
    $form['month']        = isset($_POST['month'])      ? (string)$_POST['month']      : date('n');
    $form['fx_rate']      = isset($_POST['fx_rate'])    ? (string)$_POST['fx_rate']    : number_format($defaultFx, 4, '.', '');
    $form['note']         = isset($_POST['note'])       ? (string)$_POST['note']       : '';
    $form['details_text'] = isset($_POST['details_text']) ? (string)$_POST['details_text'] : '';
    $form['subject']      = isset($_POST['subject'])    ? (string)$_POST['subject']    : '';
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
            $year    = (int)$form['year'];
            $month   = (int)$form['month'];
            $note    = trim($form['note']);
            $subject = trim($form['subject']);

            if ($division !== 'production') {
                // Business / Creative: クライアント宛手動請求書
                $clientId  = trim($form['client_id']);
                $dealId    = trim($form['deal_id'])    ?: null;
                $projectId = trim($form['project_id']) ?: null;
                $details   = parse_detail_lines($form['details_text']);
                if ($subject === '') throw new RuntimeException('件名を入力してください。');
                if (!$details)       throw new RuntimeException('明細を1行以上入力してください。');

                $invoiceId = accounting_create_client_invoice(
                    $pdo, $config, $user['id'],
                    $clientId ?: null, $year, $month,
                    $subject, $details, $note,
                    $division, $dealId, $projectId
                );
            } else {
                $talentId = trim($form['talent_id']);
                if ($talentId === '') throw new RuntimeException('タレントを選択してください。');

                if ($mode === 'manual') {
                    $details = parse_detail_lines($form['details_text']);
                    if ($subject === '') throw new RuntimeException('件名を入力してください。');
                    if (!$details)       throw new RuntimeException('明細を1行以上入力してください。');
                    $invoiceId = accounting_create_manual_invoice($pdo, $config, $user['id'], $talentId, $year, $month, $subject, $details, $note);
                } else {
                    if (!empty($form['use_latest_fx'])) {
                        $latestFxInfo = invoice_edit_fetch_latest_usd_jpy_rate($fxApiKey);
                        $fxRate = (float)$latestFxInfo['rate'];
                        $form['fx_rate'] = number_format($fxRate, 4, '.', '');
                    } else {
                        $fxRate = (float)$form['fx_rate'];
                    }
                    if ($fxRate <= 0) throw new RuntimeException('為替レートを正しく入力してください。');
                    $invoiceId = accounting_create_revenue_invoice($pdo, $config, $user['id'], $talentId, $year, $month, $fxRate, $note);
                }
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

$divLabels = ['production' => 'Production', 'business' => 'Business', 'creative' => 'Creative'];
$pageTitle = $division !== 'production'
    ? ($divLabels[$division] ?? $division) . ' 請求書を作成'
    : ($mode === 'manual' ? '手入力で請求書を作る' : '収益から請求書を作る');
start_page($pageTitle, '請求書を作成します。');
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
    <input type="hidden" name="deal_id" value="<?= h($form['deal_id']) ?>">
    <input type="hidden" name="project_id" value="<?= h($form['project_id']) ?>">

    <div class="form-grid two">
      <label><span>事業部</span>
        <select name="division" onchange="this.form.submit()">
          <?php foreach ($divLabels as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= selected($form['division'], $val) ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <?php if ($form['division'] !== 'production'): ?>
        <label><span>クライアント</span>
          <select name="client_id">
            <option value="">— 未選択（直接入力）—</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= h($c['id']) ?>" <?= selected($form['client_id'], $c['id']) ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php else: ?>
        <label><span>タレント</span>
          <select name="talent_id" required>
            <option value="">選択してください</option>
            <?php foreach ($talents as $t): ?>
              <option value="<?= h((string)$t['id']) ?>" <?= selected($form['talent_id'], (string)$t['id']) ?>>
                <?= h($t['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>

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
      <div class="invoice-preview-box" id="revenue-preview-box">
        <div class="invoice-preview-title">試算：請求対象の未請求月</div>
        <div id="rev-preview-placeholder" class="invoice-preview-placeholder">
          タレントと締め年月を選択すると試算が表示されます
        </div>
        <div id="rev-preview-content" style="display:none;">
          <div id="rev-preview-items"></div>
          <div class="invoice-preview-total">
            <span>試算合計（取り分後）</span>
            <strong id="rev-preview-total"></strong>
          </div>
          <div class="invoice-preview-footer">
            <span id="rev-preview-status"></span>
            <span class="invoice-preview-hint" id="rev-preview-note"></span>
          </div>
        </div>
        <div id="rev-preview-error" style="display:none;font-size:12px;color:var(--danger);padding:4px 0;"></div>
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
<script>
(function () {
  var previewBox = document.getElementById('revenue-preview-box');
  if (!previewBox) return;

  var talentSel  = document.querySelector('select[name="talent_id"]');
  var yearInput  = document.querySelector('input[name="year"]');
  var monthInput = document.querySelector('input[name="month"]');
  var fxInput    = document.querySelector('input[name="fx_rate"]');

  var placeholder   = document.getElementById('rev-preview-placeholder');
  var content       = document.getElementById('rev-preview-content');
  var itemsEl       = document.getElementById('rev-preview-items');
  var totalEl       = document.getElementById('rev-preview-total');
  var statusEl      = document.getElementById('rev-preview-status');
  var noteEl        = document.getElementById('rev-preview-note');
  var errorEl       = document.getElementById('rev-preview-error');

  function showPlaceholder(msg) {
    placeholder.textContent = msg || 'タレントと締め年月を選択すると試算が表示されます';
    placeholder.style.display = '';
    content.style.display = 'none';
    errorEl.style.display = 'none';
  }

  function showError(msg) {
    errorEl.textContent = msg;
    errorEl.style.display = '';
    placeholder.style.display = 'none';
    content.style.display = 'none';
  }

  function showContent(data) {
    var html = data.items.map(function (item) {
      return '<div class="invoice-preview-row">'
        + '<span>' + item.label + '</span>'
        + '<span>¥' + item.amount.toLocaleString() + '</span>'
        + '</div>';
    }).join('');
    itemsEl.innerHTML = html || '<div style="font-size:12px;color:var(--sub);padding:4px 0;">計算対象の月がありません</div>';
    totalEl.textContent = '¥' + data.total.toLocaleString();

    if (data.can_invoice) {
      statusEl.innerHTML = '<span class="status-badge success">請求可能</span>';
      noteEl.textContent = '';
    } else {
      statusEl.innerHTML = '<span class="status-badge warning">次月繰越</span>';
      noteEl.textContent = '合計 ¥' + data.threshold.toLocaleString() + ' 未満のため請求書を発行できません';
    }

    placeholder.style.display = 'none';
    content.style.display = '';
    errorEl.style.display = 'none';
  }

  var timer = null;
  function schedulePreview() {
    clearTimeout(timer);
    timer = setTimeout(fetchPreview, 400);
  }

  function fetchPreview() {
    if (!talentSel) return;
    var talentId = talentSel.value;
    var year     = yearInput  ? yearInput.value.trim()  : '';
    var month    = monthInput ? monthInput.value.trim() : '';
    var fx       = fxInput    ? fxInput.value.trim()    : '';

    if (!talentId || !year || !month) {
      showPlaceholder();
      return;
    }

    showPlaceholder('試算中...');

    var url = '<?= h($baseUrl) ?>/accounting/invoice_edit.php'
      + '?action=preview_revenue'
      + '&talent_id=' + encodeURIComponent(talentId)
      + '&year='      + encodeURIComponent(year)
      + '&month='     + encodeURIComponent(month)
      + '&fx_rate='   + encodeURIComponent(fx || '150');

    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          showContent(data);
        } else {
          showError(data.error || 'エラーが発生しました');
        }
      })
      .catch(function () {
        showError('試算の取得に失敗しました');
      });
  }

  [talentSel, yearInput, monthInput, fxInput].forEach(function (el) {
    if (!el) return;
    el.addEventListener('change', schedulePreview);
    if (el.tagName !== 'SELECT') el.addEventListener('input', schedulePreview);
  });

  fetchPreview();
})();
</script>
<?php end_page(); ?>