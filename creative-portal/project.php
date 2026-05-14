<?php
require_once __DIR__ . '/_bootstrap.php';
cp_require_login();

$creator = cp_current_creator();
$projectId = trim((string)($_GET['id'] ?? $_POST['project_id'] ?? ''));
$project = $projectId !== '' ? cp_fetch_project($pdo, $creator['creator_id'], $projectId) : null;
if (!$project) {
    cp_flash_set('error', '案件が見つかりません。');
    cp_redirect($creativePortalBase . '/projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cp_verify_csrf($_POST['_csrf'] ?? '')) {
        cp_flash_set('error', '不正なリクエストです。ページを再読み込みしてください。');
        cp_redirect($creativePortalBase . '/project.php?id=' . urlencode($projectId));
    }
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'comment') {
        $result = cp_add_comment($pdo, $creator['creator_id'], (int)$creator['id'], $projectId, $_POST['body'] ?? '');
        cp_flash_set(!empty($result['success']) ? 'success' : 'error', !empty($result['success']) ? 'コメントを送信しました。' : ($result['error'] ?? '送信に失敗しました。'));
    } elseif ($action === 'submission') {
        $result = cp_submit_project_file($pdo, $creator['creator_id'], (int)$creator['id'], $projectId, $_POST, $_FILES['submission_file'] ?? []);
        cp_flash_set(!empty($result['success']) ? 'success' : 'error', !empty($result['success']) ? '提出物を送信しました。' : ($result['error'] ?? '提出に失敗しました。'));
    }
    cp_redirect($creativePortalBase . '/project.php?id=' . urlencode($projectId));
}

$comments = cp_fetch_comments($pdo, $creator['creator_id'], $projectId);
$submissions = cp_fetch_submissions($pdo, $creator['creator_id'], $projectId, 50);

cp_start_page($project['title'], '案件内容、提出物、連絡をまとめて確認できます。');
?>
<div class="cp-grid aside">
  <div class="cp-grid">
    <?php if (!empty($project['portal_status_note'])): ?>
      <div class="cp-alert">
        <strong>進行メモ</strong>
        <?= cp_h($project['portal_status_note']) ?>
      </div>
    <?php endif; ?>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>案件情報</h2>
          <p>制作に必要な共有情報です。</p>
        </div>
        <span class="cp-badge <?= cp_h(cp_project_status_class($project['status'])) ?>"><?= cp_h($project['status']) ?></span>
      </div>
      <div class="cp-card-pad">
        <dl class="cp-detail-list">
          <div class="cp-detail-row">
            <dt>カテゴリ</dt>
            <dd><?= cp_h($project['category']) ?></dd>
          </div>
          <div class="cp-detail-row">
            <dt>納期</dt>
            <dd><?= cp_h(cp_format_date($project['deadline'] ?? '')) ?></dd>
          </div>
          <div class="cp-detail-row">
            <dt>支払予定</dt>
            <dd><?= cp_h(cp_format_money($project['creator_amount'] ?? 0)) ?></dd>
          </div>
          <div class="cp-detail-row">
            <dt>概要</dt>
            <dd><?= $project['portal_summary'] ? nl2br(cp_h($project['portal_summary'])) : '<span class="cp-muted">共有概要は未入力です。</span>' ?></dd>
          </div>
          <div class="cp-detail-row">
            <dt>資料URL</dt>
            <dd>
              <?php if (!empty($project['portal_reference_url'])): ?>
                <a class="cp-btn-muted" href="<?= cp_h($project['portal_reference_url']) ?>" target="_blank" rel="noopener">資料を開く</a>
              <?php else: ?>
                <span class="cp-muted">—</span>
              <?php endif; ?>
            </dd>
          </div>
          <div class="cp-detail-row">
            <dt>成果物URL</dt>
            <dd>
              <?php if (!empty($project['deliverable_url'])): ?>
                <a class="cp-btn-muted" href="<?= cp_h($project['deliverable_url']) ?>" target="_blank" rel="noopener">成果物フォルダを開く</a>
              <?php else: ?>
                <span class="cp-muted">—</span>
              <?php endif; ?>
            </dd>
          </div>
          <div class="cp-detail-row">
            <dt>条件・注意</dt>
            <dd><?= $project['portal_terms_note'] ? nl2br(cp_h($project['portal_terms_note'])) : '<span class="cp-muted">—</span>' ?></dd>
          </div>
        </dl>
      </div>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>提出履歴</h2>
          <p>ラフ、初稿、修正版、納品データの履歴です。</p>
        </div>
      </div>
      <div class="cp-table-wrap">
        <table class="cp-table">
          <thead>
            <tr>
              <th>提出内容</th>
              <th>種別</th>
              <th>状態</th>
              <th>提出日</th>
              <th>ファイル</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$submissions): ?>
            <tr><td colspan="5" class="cp-empty">提出履歴はありません。</td></tr>
          <?php endif; ?>
          <?php foreach ($submissions as $submission): $st = cp_submission_status($submission['status']); ?>
            <tr>
              <td data-label="提出内容">
                <strong><?= cp_h($submission['title'] ?: cp_submission_type_label($submission['submission_type'])) ?></strong>
                <?php if (!empty($submission['comment'])): ?>
                  <div class="cp-muted cp-small"><?= nl2br(cp_h($submission['comment'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($submission['admin_note'])): ?>
                  <div class="cp-alert cp-mt"><strong>確認コメント</strong><?= nl2br(cp_h($submission['admin_note'])) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="種別"><?= cp_h(cp_submission_type_label($submission['submission_type'])) ?></td>
              <td data-label="状態"><span class="cp-badge <?= cp_h($st['class']) ?>"><?= cp_h($st['label']) ?></span></td>
              <td data-label="提出日"><?= cp_h(cp_format_datetime($submission['created_at'])) ?></td>
              <td data-label="ファイル">
                <div class="cp-actions">
                  <?php if (!empty($submission['file_path'])): ?>
                    <a class="cp-btn-muted" href="<?= cp_h($creativePortalBase) ?>/download.php?type=submission&id=<?= (int)$submission['id'] ?>">DL</a>
                  <?php endif; ?>
                  <?php if (!empty($submission['external_url'])): ?>
                    <a class="cp-btn-muted" href="<?= cp_h($submission['external_url']) ?>" target="_blank" rel="noopener">URL</a>
                  <?php endif; ?>
                  <?php if (empty($submission['file_path']) && empty($submission['external_url'])): ?>
                    <span class="cp-muted">—</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>連絡</h2>
          <p>この案件に紐づく共有コメントです。</p>
        </div>
      </div>
      <div class="cp-card-pad cp-thread">
        <?php if (!$comments): ?>
          <div class="cp-empty">まだコメントはありません。</div>
        <?php endif; ?>
        <?php foreach ($comments as $comment): ?>
          <article class="cp-thread-item <?= $comment['sender_type'] === 'creator' ? 'creator' : 'admin' ?>">
            <div class="cp-thread-meta">
              <strong><?= $comment['sender_type'] === 'creator' ? 'あなた' : 'CORO PROJECT' ?></strong>
              <span><?= cp_h(cp_format_datetime($comment['created_at'])) ?></span>
            </div>
            <div class="cp-thread-body"><?= nl2br(cp_h($comment['body'])) ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <aside class="cp-grid">
    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>提出する</h2>
          <p>ファイルまたは共有URLを送れます。</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="cp-card-pad cp-form">
        <input type="hidden" name="action" value="submission">
        <input type="hidden" name="project_id" value="<?= cp_h($projectId) ?>">
        <label>
          <span class="cp-label">種別</span>
          <select name="submission_type">
            <option value="rough">ラフ</option>
            <option value="draft" selected>初稿</option>
            <option value="revision">修正版</option>
            <option value="final">納品</option>
            <option value="other">その他</option>
          </select>
        </label>
        <label>
          <span class="cp-label">タイトル</span>
          <input type="text" name="title" placeholder="例：初稿データ / 修正版02">
        </label>
        <label>
          <span class="cp-label">ファイル</span>
          <input type="file" name="submission_file">
          <span class="cp-help">jpg, png, webp, pdf, zip, psd, clip, txt / 50MBまで</span>
        </label>
        <label>
          <span class="cp-label">共有URL</span>
          <input type="url" name="external_url" placeholder="https://...">
        </label>
        <label>
          <span class="cp-label">コメント</span>
          <textarea name="comment" placeholder="確認してほしい点や補足があれば入力"></textarea>
        </label>
        <button class="cp-btn" type="submit">提出する</button>
      </form>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>コメント送信</h2>
          <p>案件についてCORO PROJECTへ共有します。</p>
        </div>
      </div>
      <form method="post" class="cp-card-pad cp-form">
        <input type="hidden" name="action" value="comment">
        <input type="hidden" name="project_id" value="<?= cp_h($projectId) ?>">
        <label>
          <span class="cp-label">コメント</span>
          <textarea name="body" required placeholder="確認事項や進捗メモなど"></textarea>
        </label>
        <button class="cp-btn" type="submit">送信する</button>
      </form>
    </section>

    <section class="cp-card">
      <div class="cp-card-head">
        <div>
          <h2>請求</h2>
          <p>この案件の請求書提出へ進めます。</p>
        </div>
      </div>
      <div class="cp-card-pad">
        <a class="cp-btn" href="<?= cp_h($creativePortalBase) ?>/billing.php?project_id=<?= urlencode($projectId) ?>">請求書を提出</a>
      </div>
    </section>
  </aside>
</div>
<?php cp_end_page(); ?>
