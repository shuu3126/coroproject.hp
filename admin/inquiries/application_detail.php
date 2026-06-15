<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect_to($baseUrl . '/inquiries/applications.php');
}

$stmt = $pdo->prepare('SELECT * FROM talent_applications WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    set_flash('error', '応募が見つかりません。');
    redirect_to($baseUrl . '/inquiries/applications.php');
}

$pageError   = '';
$pageSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $newStatus = trim($_POST['status'] ?? '');
        $allowed   = ['new','reviewing','passed','rejected','hold'];
        if (!in_array($newStatus, $allowed, true)) {
            $pageError = '無効なステータスです。';
        } else {
            $pdo->prepare('UPDATE talent_applications SET status = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$newStatus, $id]);
            $app['status'] = $newStatus;
            $pageSuccess = 'ステータスを更新しました。';
        }
    } elseif ($action === 'update_note') {
        $note = trim($_POST['admin_note'] ?? '');
        $pdo->prepare('UPDATE talent_applications SET admin_note = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$note ?: null, $id]);
        $app['admin_note'] = $note;
        $pageSuccess = 'メモを保存しました。';
    }
}

$statusLabels = [
    'new'       => '新着',
    'reviewing' => '審査中',
    'passed'    => '合格',
    'rejected'  => '不合格',
    'hold'      => '保留',
];
$statusClasses = [
    'new'       => 'danger',
    'reviewing' => 'warning',
    'passed'    => 'success',
    'rejected'  => 'muted',
    'hold'      => 'info',
];
$genderLabels = ['female' => '女性', 'male' => '男性', 'other' => 'その他', 'private' => '非公開'];
$affiliationLabels = ['exclusive' => '専属', 'non_exclusive' => '非専属', 'negotiable' => '相談したい'];
$workStyleLabels   = ['full_time' => '専業', 'part_time' => '副業', 'other' => 'その他'];

$sl = $statusLabels[$app['status']] ?? $app['status'];
$sc = $statusClasses[$app['status']] ?? 'muted';

start_page('応募詳細 #' . $id, h($app['vtuber_name']) . ' 様の応募');
?>
<main class="page-container narrow">

  <?php if ($pageError !== ''): ?>
    <div class="alert-box alert-error"><?= h($pageError) ?></div>
  <?php endif; ?>
  <?php if ($pageSuccess !== ''): ?>
    <div class="alert-box alert-success"><?= h($pageSuccess) ?></div>
  <?php endif; ?>

  <!-- ヘッダー -->
  <div class="card form-card form-stack">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
      <div>
        <h2 style="margin:0 0 4px;"><?= h($app['vtuber_name']) ?> 様</h2>
        <span class="status-badge <?= h($sc) ?>"><?= h($sl) ?></span>
      </div>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/inquiries/applications.php">← 一覧へ戻る</a>
    </div>

    <!-- セクション1: 基本情報 -->
    <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">基本情報</h3>
    <table class="data-table">
      <tbody>
        <tr><th style="width:160px;">VTuber名</th><td><?= h($app['vtuber_name']) ?></td></tr>
        <?php if (!empty($app['real_name'])): ?>
          <tr><th>本名</th><td><?= h($app['real_name']) ?></td></tr>
        <?php endif; ?>
        <tr><th>性別</th><td><?= h($genderLabels[$app['gender']] ?? $app['gender']) ?></td></tr>
        <tr><th>年齢</th><td><?= h($app['age']) ?>歳</td></tr>
        <tr><th>都道府県</th><td><?= h($app['prefecture']) ?></td></tr>
        <tr><th>メールアドレス</th><td><a href="mailto:<?= h($app['email']) ?>"><?= h($app['email']) ?></a></td></tr>
        <tr><th>応募日時</th><td><?= h(format_datetime($app['created_at'])) ?></td></tr>
      </tbody>
    </table>

    <!-- セクション2: 活動情報 -->
    <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">活動情報</h3>
    <table class="data-table">
      <tbody>
        <tr><th style="width:160px;">活動開始時期</th><td><?= h($app['activity_start']) ?></td></tr>
        <tr><th>主なプラットフォーム</th><td><?= h($app['main_platform']) ?></td></tr>
        <tr><th>配信頻度</th><td><?= h($app['stream_frequency']) ?></td></tr>
        <tr><th>配信ジャンル・内容</th><td style="white-space:pre-wrap;"><?= h($app['stream_genre']) ?></td></tr>
      </tbody>
    </table>

    <!-- SNSリンク -->
    <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">SNS・プラットフォーム</h3>
    <table class="data-table">
      <tbody>
        <?php
        $platforms = [
            ['label' => 'YouTube',        'url' => $app['youtube_url'],      'followers' => $app['youtube_followers']],
            ['label' => 'Twitch',         'url' => $app['twitch_url'],       'followers' => $app['twitch_followers']],
            ['label' => 'X（旧Twitter）', 'url' => $app['twitter_url'],      'followers' => $app['twitter_followers']],
            ['label' => 'Twitcasting',    'url' => $app['twitcasting_url'],  'followers' => $app['twitcasting_followers']],
        ];
        foreach ($platforms as $p):
            if (empty($p['url']) && empty($p['followers'])) continue;
        ?>
          <tr>
            <th style="width:160px;"><?= h($p['label']) ?></th>
            <td>
              <?php if (!empty($p['url'])): ?>
                <a href="<?= h($p['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($p['url']) ?></a>
              <?php endif; ?>
              <?php if (!empty($p['followers'])): ?>
                <span class="muted" style="font-size:0.85em;margin-left:8px;"><?= number_format((int)$p['followers']) ?> フォロワー</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!empty($app['other_platform'])): ?>
          <tr><th>その他</th><td style="white-space:pre-wrap;"><?= h($app['other_platform']) ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- セクション3: 実績 -->
    <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">実績・サンプル</h3>
    <table class="data-table">
      <tbody>
        <?php for ($i = 1; $i <= 3; $i++): ?>
          <?php $su = $app['sample_url_' . $i] ?? ''; ?>
          <?php if ($su): ?>
            <tr>
              <th style="width:160px;">サンプルURL <?= $i ?></th>
              <td><a href="<?= h($su) ?>" target="_blank" rel="noopener noreferrer"><?= h($su) ?></a></td>
            </tr>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if (!empty($app['past_achievements'])): ?>
          <tr><th>過去の実績</th><td style="white-space:pre-wrap;"><?= h($app['past_achievements']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($app['event_experience'])): ?>
          <tr><th>イベント・案件経験</th><td style="white-space:pre-wrap;"><?= h($app['event_experience']) ?></td></tr>
        <?php endif; ?>
        <tr><th>スキル・特技</th><td style="white-space:pre-wrap;"><?= h($app['skills']) ?></td></tr>
      </tbody>
    </table>

    <!-- セクション4: 所属 -->
    <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">所属・活動スタイル</h3>
    <table class="data-table">
      <tbody>
        <tr><th style="width:160px;">希望所属形態</th><td><?= h($affiliationLabels[$app['affiliation_type']] ?? $app['affiliation_type']) ?></td></tr>
        <tr>
          <th>活動スタイル</th>
          <td>
            <?= h($workStyleLabels[$app['work_style']] ?? $app['work_style']) ?>
            <?php if (!empty($app['work_detail'])): ?>
              <span class="muted" style="font-size:0.85em;margin-left:8px;">（<?= h($app['work_detail']) ?>）</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr><th>応募動機</th><td style="white-space:pre-wrap;"><?= h($app['motivation']) ?></td></tr>
        <tr><th>活動目標</th><td style="white-space:pre-wrap;"><?= h($app['goal']) ?></td></tr>
      </tbody>
    </table>

    <!-- セクション5: 機材 -->
    <?php if (!empty($app['mic_equipment']) || !empty($app['pc_spec'])): ?>
      <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">使用機材</h3>
      <table class="data-table">
        <tbody>
          <?php if (!empty($app['mic_equipment'])): ?>
            <tr><th style="width:160px;">マイク・音声機材</th><td><?= h($app['mic_equipment']) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($app['pc_spec'])): ?>
            <tr><th>PCスペック</th><td><?= h($app['pc_spec']) ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <!-- セクション6: その他 -->
    <?php if (!empty($app['questions'])): ?>
      <h3 style="margin:24px 0 8px;font-size:0.95em;opacity:.7;border-bottom:1px solid var(--border,#e5e5e5);padding-bottom:6px;">質問・要望</h3>
      <div style="background:var(--bg,#f9f9f9);border:1px solid var(--border,#e5e5e5);border-radius:6px;padding:16px;white-space:pre-wrap;line-height:1.7;"><?= h($app['questions']) ?></div>
    <?php endif; ?>
  </div>

  <!-- ステータス変更 -->
  <div class="card form-card form-stack">
    <h3 style="margin:0 0 12px;">ステータス変更</h3>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="update_status">
      <?php if (function_exists('admin_csrf_token')): ?>
        <input type="hidden" name="_csrf" value="<?= h(admin_csrf_token()) ?>">
      <?php endif; ?>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select name="status" style="min-width:140px;">
          <?php foreach ($statusLabels as $st => $label): ?>
            <option value="<?= h($st) ?>" <?= $app['status'] === $st ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="primary-btn" type="submit">更新する</button>
      </div>
    </form>
  </div>

  <!-- 管理者メモ -->
  <div class="card form-card form-stack">
    <h3 style="margin:0 0 12px;">管理者メモ</h3>
    <form method="post" class="form-stack">
      <input type="hidden" name="action" value="update_note">
      <?php if (function_exists('admin_csrf_token')): ?>
        <input type="hidden" name="_csrf" value="<?= h(admin_csrf_token()) ?>">
      <?php endif; ?>
      <label>
        <textarea name="admin_note" rows="6" placeholder="審査メモ、面談日程、連絡履歴などを記録してください（公開されません）。"><?= h($app['admin_note'] ?? '') ?></textarea>
      </label>
      <div class="actions-inline">
        <button class="primary-btn" type="submit">メモを保存する</button>
      </div>
    </form>
  </div>

</main>
<?php end_page(); ?>
