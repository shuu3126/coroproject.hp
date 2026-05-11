<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
$user = current_admin_user();

$id     = trim($_GET['id'] ?? '');
$isEdit = $id !== '';
$row    = ['id' => '', 'name' => '', 'channel_url' => '', 'genre' => '', 'subscriber_count' => '', 'memo' => ''];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM biz_ext_talents WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('error', '見つかりません。'); redirect_to($baseUrl . '/business/ext_talents.php'); }
    $row = array_merge($row, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $channelUrl     = trim($_POST['channel_url'] ?? '');
    $genre          = trim($_POST['genre'] ?? '');
    $subscriberCount = $_POST['subscriber_count'] !== '' ? (int)$_POST['subscriber_count'] : null;
    $memo           = trim($_POST['memo'] ?? '');

    if ($name === '') { set_flash('error', '名前は必須です。'); redirect_to($baseUrl . '/business/ext_talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : '')); }

    try {
        if ($isEdit) {
            $pdo->prepare('UPDATE biz_ext_talents SET name=?,channel_url=?,genre=?,subscriber_count=?,memo=? WHERE id=?')
                ->execute([$name, $channelUrl, $genre, $subscriberCount, $memo, $id]);
            write_admin_log($pdo, (int)$user['id'], 'edit', 'biz_ext_talent', $id, '所属外VTuberを更新しました');
        } else {
            $saveId = normalize_file_stem($name . '-' . date('Ymd'), 'vtuber');
            $base = $saveId; $i = 2;
            while ((int)$pdo->query("SELECT COUNT(*) FROM biz_ext_talents WHERE id = " . $pdo->quote($saveId))->fetchColumn() > 0) {
                $saveId = $base . '-' . $i++;
            }
            $pdo->prepare('INSERT INTO biz_ext_talents (id,name,channel_url,genre,subscriber_count,memo,created_by) VALUES (?,?,?,?,?,?,?)')
                ->execute([$saveId, $name, $channelUrl, $genre, $subscriberCount, $memo, (int)$user['id']]);
            write_admin_log($pdo, (int)$user['id'], 'create', 'biz_ext_talent', $saveId, '所属外VTuberを追加しました');
        }
        set_flash('success', '保存しました。');
        redirect_to($baseUrl . '/business/ext_talents.php');
    } catch (Exception $e) {
        set_flash('error', '保存に失敗しました: ' . $e->getMessage());
        redirect_to($baseUrl . '/business/ext_talent_edit.php' . ($isEdit ? '?id=' . urlencode($id) : ''));
    }
}

start_page($isEdit ? '所属外VTuberを編集' : '所属外VTuberを追加');
?>
<main class="page-container narrow">
  <section class="page-header-block"><h1><?= h($isEdit ? '所属外VTuberを編集' : '所属外VTuberを追加') ?></h1></section>
  <form method="post" class="card form-card form-stack">
    <div class="form-grid two">
      <label><span>名前</span><input type="text" name="name" value="<?= h($row['name']) ?>" required></label>
      <label><span>ジャンル</span><input type="text" name="genre" value="<?= h($row['genre'] ?? '') ?>" placeholder="ゲーム・雑談・歌枠など"></label>
    </div>
    <label><span>チャンネルURL</span><input type="text" name="channel_url" value="<?= h($row['channel_url'] ?? '') ?>"></label>
    <label><span>登録者数</span><input type="number" name="subscriber_count" value="<?= h($row['subscriber_count'] ?? '') ?>"></label>
    <label><span>メモ</span><textarea name="memo" rows="4"><?= h($row['memo'] ?? '') ?></textarea></label>
    <div class="actions-inline">
      <button class="primary-btn" type="submit">保存する</button>
      <a class="ghost-btn" href="<?= h($baseUrl) ?>/business/ext_talents.php">一覧へ戻る</a>
    </div>
  </form>
</main>
<?php end_page(); ?>
