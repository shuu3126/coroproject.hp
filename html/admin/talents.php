<?php
// html/admin/talents.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function parseLongBioToJson(string $text): string {
    $text = trim($text);
    if ($text === '') {
        return json_encode([], JSON_UNESCAPED_UNICODE);
    }

    $lines = preg_split('/\R/u', $text);
    $lines = array_values(array_filter(array_map('trim', $lines), 'strlen'));
    return json_encode($lines, JSON_UNESCAPED_UNICODE);
}

function parsePlatforms(string $text): array {
    $text = trim($text);
    $platforms = [];
    if ($text === '') {
        return $platforms;
    }

    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        $parts = explode('|', $line, 2);
        $name = trim($parts[0] ?? '');
        $url  = trim($parts[1] ?? '');

        if ($name === '' && $url === '') continue;
        $platforms[] = ['name' => $name, 'url' => $url];
    }

    return $platforms;
}

function parseLinks(string $text): array {
    $text = trim($text);
    $links = [];
    if ($text === '') {
        return $links;
    }

    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        $parts = explode('|', $line, 2);
        $label = trim($parts[0] ?? '');
        $url   = trim($parts[1] ?? '');

        if ($label === '' && $url === '') continue;
        $links[] = ['label' => $label, 'url' => $url];
    }

    return $links;
}

function parseTagsToJson(string $text): string {
    $text = trim($text);
    $tags = [];
    if ($text !== '') {
        $tmp = preg_split('/[,\r\n]+/u', $text);
        foreach ($tmp as $t) {
            $t = trim($t);
            if ($t !== '') $tags[] = $t;
        }
    }

    return json_encode(array_values($tags), JSON_UNESCAPED_UNICODE);
}

function generateSafeTalentId(PDO $pdo, string $name, string $excludeId = ''): string {
    $base = trim($name);
    $base = preg_replace('/\s+/u', '-', $base);
    $base = preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', $base);
    $base = trim((string)$base, '-_');
    $base = strtolower(substr((string)$base, 0, 40));

    if ($base === '') {
        $base = 'talent-' . date('YmdHis');
    }

    $candidate = $base;
    $i = 2;
    while (talentIdExists($pdo, $candidate, $excludeId)) {
        $suffix = '-' . $i;
        $candidate = substr($base, 0, max(1, 40 - strlen($suffix))) . $suffix;
        $i++;
    }

    return $candidate;
}

function talentIdExists(PDO $pdo, string $id, string $excludeId = ''): bool {
    if ($excludeId !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = :id AND id <> :exclude_id');
        $stmt->execute([
            ':id' => $id,
            ':exclude_id' => $excludeId,
        ]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM talents WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    return (int)$stmt->fetchColumn() > 0;
}

function syncTalentRelations(PDO $pdo, string $oldId, string $newId, array $platforms, array $links): void {
    $idsToDelete = [$newId];
    if ($oldId !== '' && $oldId !== $newId) {
        $idsToDelete[] = $oldId;
    }
    $idsToDelete = array_values(array_unique(array_filter($idsToDelete, 'strlen')));

    if ($idsToDelete) {
        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));

        $stmt = $pdo->prepare("DELETE FROM talent_platforms WHERE talent_id IN ($placeholders)");
        $stmt->execute($idsToDelete);

        $stmt = $pdo->prepare("DELETE FROM talent_links WHERE talent_id IN ($placeholders)");
        $stmt->execute($idsToDelete);
    }

    if ($platforms) {
        $stmt = $pdo->prepare('INSERT INTO talent_platforms (talent_id, name, url) VALUES (:talent_id, :name, :url)');
        foreach ($platforms as $p) {
            $stmt->execute([
                ':talent_id' => $newId,
                ':name'      => $p['name'] ?? '',
                ':url'       => $p['url'] ?? '',
            ]);
        }
    }

    if ($links) {
        $stmt = $pdo->prepare('INSERT INTO talent_links (talent_id, label, url) VALUES (:talent_id, :label, :url)');
        foreach ($links as $l) {
            $stmt->execute([
                ':talent_id' => $newId,
                ':label'     => $l['label'] ?? '',
                ':url'       => $l['url'] ?? '',
            ]);
        }
    }
}

function platformsToText(array $platforms): string {
    $lines = [];
    foreach ($platforms as $p) {
        $lines[] = trim((string)($p['name'] ?? '')) . '|' . trim((string)($p['url'] ?? ''));
    }
    return implode("\n", $lines);
}

function linksToText(array $links): string {
    $lines = [];
    foreach ($links as $l) {
        $lines[] = trim((string)($l['label'] ?? '')) . '|' . trim((string)($l['url'] ?? ''));
    }
    return implode("\n", $lines);
}

function fetchPlatformRows(PDO $pdo, string $talentId): array {
    $stmt = $pdo->prepare('SELECT name, url FROM talent_platforms WHERE talent_id = :id ORDER BY id ASC');
    $stmt->execute([':id' => $talentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchLinkRows(PDO $pdo, string $talentId): array {
    $stmt = $pdo->prepare('SELECT label, url FROM talent_links WHERE talent_id = :id ORDER BY id ASC');
    $stmt->execute([':id' => $talentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$errorMessage = '';

// ===== 削除 =====
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('DELETE FROM talent_platforms WHERE talent_id = :id');
        $stmt->execute([':id' => $_GET['id']]);

        $stmt = $pdo->prepare('DELETE FROM talent_links WHERE talent_id = :id');
        $stmt->execute([':id' => $_GET['id']]);

        $stmt = $pdo->prepare('DELETE FROM talents WHERE id = :id');
        $stmt->execute([':id' => $_GET['id']]);

        $pdo->commit();
        header('Location: talents.php?msg=deleted');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMessage = '削除中にエラーが発生しました: ' . $e->getMessage();
    }
}

// ===== 保存（新規/更新） =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mode        = $_POST['mode'] ?? 'create';
        $originalId  = trim($_POST['original_id'] ?? '');
        $id          = trim($_POST['id'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $kana        = trim($_POST['kana'] ?? '');
        $talentGroup = trim($_POST['talent_group'] ?? '');
        $status      = trim($_POST['status'] ?? 'active');
        $debut       = trim($_POST['debut'] ?? '');
        $lastActive  = trim($_POST['last_active'] ?? '');
        $avatar      = trim($_POST['avatar'] ?? '');
        $bio         = trim($_POST['bio'] ?? '');
        $sortOrder   = (int)($_POST['sort_order'] ?? 0);
        $isPub       = isset($_POST['is_published']) ? 1 : 0;

        $longBioText  = trim($_POST['long_bio'] ?? '');
        $platformText = trim($_POST['platforms'] ?? '');
        $linksText    = trim($_POST['links'] ?? '');
        $tagsText     = trim($_POST['tags'] ?? '');

        if ($name === '') {
            throw new RuntimeException('名前は必須です。');
        }

        if ($mode === 'update' && $originalId === '') {
            throw new RuntimeException('更新対象のIDが見つかりません。');
        }

        if ($id === '') {
            if ($mode === 'update' && $originalId !== '') {
                $id = $originalId;
            } else {
                $id = generateSafeTalentId($pdo, $name);
            }
        }

        if ($mode === 'update') {
            if (talentIdExists($pdo, $id, $originalId)) {
                throw new RuntimeException('そのIDはすでに使われています。別のIDにしてください。');
            }
        } else {
            if (talentIdExists($pdo, $id)) {
                $id = generateSafeTalentId($pdo, $id);
            }
        }

        $longBioJson   = parseLongBioToJson($longBioText);
        $platforms     = parsePlatforms($platformText);
        $platformsJson = json_encode($platforms, JSON_UNESCAPED_UNICODE);
        $links         = parseLinks($linksText);
        $linksJson     = json_encode($links, JSON_UNESCAPED_UNICODE);
        $tagsJson      = parseTagsToJson($tagsText);

        $pdo->beginTransaction();

        if ($mode === 'update') {
            $sql = "UPDATE talents SET
                        id             = :new_id,
                        name           = :name,
                        kana           = :kana,
                        talent_group   = :talent_group,
                        status         = :status,
                        debut          = :debut,
                        last_active    = :last_active,
                        avatar         = :avatar,
                        bio            = :bio,
                        long_bio_json  = :long_bio_json,
                        platforms_json = :platforms_json,
                        links_json     = :links_json,
                        tags_json      = :tags_json,
                        sort_order     = :sort_order,
                        is_published   = :is_published
                    WHERE id = :original_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':new_id'         => $id,
                ':original_id'    => $originalId,
                ':name'           => $name,
                ':kana'           => $kana,
                ':talent_group'   => $talentGroup,
                ':status'         => $status,
                ':debut'          => $debut ?: null,
                ':last_active'    => $lastActive ?: null,
                ':avatar'         => $avatar,
                ':bio'            => $bio,
                ':long_bio_json'  => $longBioJson,
                ':platforms_json' => $platformsJson,
                ':links_json'     => $linksJson,
                ':tags_json'      => $tagsJson,
                ':sort_order'     => $sortOrder,
                ':is_published'   => $isPub,
            ]);

            syncTalentRelations($pdo, $originalId, $id, $platforms, $links);
        } else {
            $sql = "INSERT INTO talents
                        (id, name, kana, talent_group, status, debut, last_active, avatar,
                         bio, long_bio_json, platforms_json, links_json, tags_json,
                         sort_order, is_published)
                    VALUES
                        (:id, :name, :kana, :talent_group, :status, :debut, :last_active, :avatar,
                         :bio, :long_bio_json, :platforms_json, :links_json, :tags_json,
                         :sort_order, :is_published)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'             => $id,
                ':name'           => $name,
                ':kana'           => $kana,
                ':talent_group'   => $talentGroup,
                ':status'         => $status,
                ':debut'          => $debut ?: null,
                ':last_active'    => $lastActive ?: null,
                ':avatar'         => $avatar,
                ':bio'            => $bio,
                ':long_bio_json'  => $longBioJson,
                ':platforms_json' => $platformsJson,
                ':links_json'     => $linksJson,
                ':tags_json'      => $tagsJson,
                ':sort_order'     => $sortOrder,
                ':is_published'   => $isPub,
            ]);

            syncTalentRelations($pdo, $id, $id, $platforms, $links);
        }

        $pdo->commit();
        header('Location: talents.php?msg=saved');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMessage = '保存中にエラーが発生しました: ' . $e->getMessage();
    }
}

// ===== 編集対象1件取得 =====
$editTalent = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM talents WHERE id = :id');
    $stmt->execute([':id' => $_GET['id']]);
    $editTalent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editTalent) {
        $long = json_decode($editTalent['long_bio_json'] ?? '[]', true);
        $editTalent['_long_bio_text'] = is_array($long)
            ? implode("\n", array_filter(array_map('strval', $long), 'strlen'))
            : '';

        $jsonPlatforms = json_decode($editTalent['platforms_json'] ?? '[]', true);
        $jsonLinks     = json_decode($editTalent['links_json'] ?? '[]', true);
        $tags          = json_decode($editTalent['tags_json'] ?? '[]', true);

        $relatedPlatforms = fetchPlatformRows($pdo, $editTalent['id']);
        $relatedLinks     = fetchLinkRows($pdo, $editTalent['id']);

        if (!empty($relatedPlatforms)) {
            $editTalent['_platforms_text'] = platformsToText($relatedPlatforms);
        } elseif (is_array($jsonPlatforms)) {
            $editTalent['_platforms_text'] = platformsToText($jsonPlatforms);
        } else {
            $editTalent['_platforms_text'] = '';
        }

        if (!empty($relatedLinks)) {
            $editTalent['_links_text'] = linksToText($relatedLinks);
        } elseif (is_array($jsonLinks)) {
            $editTalent['_links_text'] = linksToText($jsonLinks);
        } else {
            $editTalent['_links_text'] = '';
        }

        if (is_array($tags)) {
            $editTalent['_tags_text'] = implode(', ', $tags);
        } else {
            $editTalent['_tags_text'] = '';
        }
    }
}

// ===== 一覧取得 =====
$stmt = $pdo->query('SELECT * FROM talents ORDER BY sort_order ASC, debut ASC, name ASC');
$allTalents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Talents管理 | CORO PROJECT</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:14px;line-height:1.5;margin:16px;background:#111827;color:#e5e7eb;}
    a{color:#60a5fa;text-decoration:none;}
    a:hover{text-decoration:underline;}
    h1{font-size:20px;margin-bottom:8px;}
    .layout{display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;}
    .panel{background:#020617;border-radius:12px;padding:16px;border:1px solid #1f2937;box-shadow:0 8px 20px rgba(0,0,0,.5);}
    .panel h2{margin-top:0;font-size:16px;margin-bottom:8px;}
    table{width:100%;border-collapse:collapse;font-size:12px;}
    th,td{border-bottom:1px solid #1f2937;padding:6px 8px;text-align:left;vertical-align:top;}
    th{background:#020617;position:sticky;top:0;z-index:1;}
    tr:nth-child(even){background:#020617;}
    .tag{display:inline-block;padding:2px 6px;border-radius:999px;font-size:11px;background:#1f2937;}
    .btn{display:inline-block;padding:6px 10px;border-radius:6px;border:none;cursor:pointer;font-size:13px;}
    .btn-primary{background:#6366f1;color:#fff;}
    .btn-secondary{background:#374151;color:#e5e7eb;}
    input[type="text"],input[type="date"],input[type="number"],textarea,select{
      width:100%;padding:6px 8px;border-radius:6px;border:1px solid #4b5563;background:#020617;color:#e5e7eb;font-size:13px;
    }
    textarea{min-height:80px;resize:vertical;}
    label{display:block;font-size:12px;margin-top:6px;margin-bottom:2px;color:#9ca3af;}
    .row{display:flex;gap:8px;flex-wrap:wrap;}
    .row>div{flex:1;}
    .msg{margin-bottom:8px;font-size:12px;color:#a5b4fc;}
    .error{margin-bottom:8px;font-size:12px;color:#fca5a5;white-space:pre-wrap;}
    .nav{margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #1f2937;font-size:13px;}
    .nav a{margin-right:12px;}
    @media (max-width:1024px){
      .layout{flex-direction:column;}
    }
  </style>
</head>
<body>
  <h1>Talents管理（CORO PROJECT）</h1>
  <div class="nav">
    <a href="index.php">🏠 トップ</a>
    <a href="news.php">📰 News管理</a>
    <a href="talents.php">👤 Talents管理</a>
    <a href="https://coroproject.jp/index.php" target="_blank">🌐 サイトTOP</a>
  </div>

  <?php if (!empty($_GET['msg'])): ?>
    <p class="msg">
      <?php if ($_GET['msg']==='saved') echo '保存しました。'; ?>
      <?php if ($_GET['msg']==='deleted') echo '削除しました。'; ?>
    </p>
  <?php endif; ?>

  <?php if ($errorMessage !== ''): ?>
    <p class="error"><?= esc($errorMessage) ?></p>
  <?php endif; ?>

  <div class="layout">
    <!-- 一覧 -->
    <div class="panel" style="flex:1.4;max-height:80vh;overflow:auto;min-width:320px;">
      <h2>タレント一覧</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>名前</th>
            <th>期</th>
            <th>ステータス</th>
            <th>デビュー日</th>
            <th>並び順</th>
            <th>公開</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allTalents as $t): ?>
            <tr>
              <td><?= esc($t['id']) ?></td>
              <td><?= esc($t['name']) ?></td>
              <td><?= esc($t['talent_group'] ?? '') ?></td>
              <td><?= esc($t['status']) ?></td>
              <td><?= esc($t['debut']) ?></td>
              <td><?= (int)$t['sort_order'] ?></td>
              <td><?= !empty($t['is_published']) ? '公開' : '非公開' ?></td>
              <td>
                <a href="talents.php?action=edit&id=<?= esc($t['id']) ?>">編集</a> /
                <a href="talents.php?action=delete&id=<?= esc($t['id']) ?>" onclick="return confirm('本当に削除しますか？');" style="color:#f97373;">削除</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 編集フォーム -->
    <div class="panel" style="flex:1;min-width:320px;">
      <h2><?= $editTalent ? 'タレント編集' : '新規タレント追加' ?></h2>
      <form method="post" action="talents.php">
        <input type="hidden" name="mode" value="<?= $editTalent ? 'update' : 'create' ?>">
        <?php if ($editTalent): ?>
          <input type="hidden" name="original_id" value="<?= esc($editTalent['id']) ?>">
        <?php endif; ?>

        <label>ID（英数字。空なら自動生成）</label>
        <input type="text" name="id" value="<?= esc($editTalent['id'] ?? '') ?>">

        <div class="row">
          <div>
            <label>名前</label>
            <input type="text" name="name" required value="<?= esc($editTalent['name'] ?? '') ?>">
          </div>
          <div>
            <label>かな</label>
            <input type="text" name="kana" value="<?= esc($editTalent['kana'] ?? '') ?>">
          </div>
        </div>

        <div class="row">
          <div>
            <label>グループ（例：1st / 2nd）</label>
            <input type="text" name="talent_group" value="<?= esc($editTalent['talent_group'] ?? '') ?>">
          </div>
          <div>
            <label>ステータス</label>
            <select name="status">
              <?php $st = $editTalent['status'] ?? 'active'; ?>
              <option value="active"    <?= $st==='active'    ? 'selected' : '' ?>>活動中</option>
              <option value="hiatus"    <?= $st==='hiatus'    ? 'selected' : '' ?>>一時休止</option>
              <option value="graduated" <?= $st==='graduated' ? 'selected' : '' ?>>卒業</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div>
            <label>デビュー日</label>
            <input type="date" name="debut" value="<?= esc($editTalent['debut'] ?? '') ?>">
          </div>
          <div>
            <label>最近の活動日</label>
            <input type="date" name="last_active" value="<?= esc($editTalent['last_active'] ?? '') ?>">
          </div>
        </div>

        <label>アバター画像パス（例：../images/1.png）</label>
        <input type="text" name="avatar" value="<?= esc($editTalent['avatar'] ?? '') ?>">

        <label>短い紹介文（カード用）</label>
        <textarea name="bio"><?= esc($editTalent['bio'] ?? '') ?></textarea>

        <label>ロングプロフィール（1行＝1段落）</label>
        <textarea name="long_bio"><?= esc($editTalent['_long_bio_text'] ?? '') ?></textarea>

        <label>プラットフォーム一覧（1行に「名前|URL」）</label>
        <textarea name="platforms" placeholder="YouTube|https://www.youtube.com/@xxxx&#10;X|https://x.com/xxxx&#10;Twitch|https://www.twitch.tv/xxxx"><?= esc($editTalent['_platforms_text'] ?? '') ?></textarea>

        <label>その他リンク（1行に「ラベル|URL」）</label>
        <textarea name="links" placeholder="ファンサイト|https://example.com&#10;切り抜き|https://youtube.com/playlist?list=xxxx"><?= esc($editTalent['_links_text'] ?? '') ?></textarea>

        <label>タグ（カンマ or 改行区切り）</label>
        <textarea name="tags" placeholder="歌, 雑談, ゲーム"><?= esc($editTalent['_tags_text'] ?? '') ?></textarea>

        <div class="row">
          <div>
            <label>並び順（小さいほど先頭）</label>
            <input type="number" name="sort_order" value="<?= esc($editTalent['sort_order'] ?? 0) ?>">
          </div>
          <div style="display:flex;align-items:flex-end;">
            <label style="margin-bottom:0;">
              <input type="checkbox" name="is_published" value="1" <?= (!isset($editTalent['is_published']) || $editTalent['is_published']) ? 'checked' : '' ?>>
              公開する
            </label>
          </div>
        </div>

        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary">保存する</button>
          <a href="talents.php" class="btn btn-secondary">新規作成に戻る</a>
        </div>
      </form>

      <p style="font-size:11px;color:#6b7280;margin-top:12px;">
        ※ プラットフォーム・リンク・タグは JSON と関連テーブルの両方へ同期保存します。<br>
        ※ 既存の公開ページ（talents.php / talent.php）の表示も崩さないようにしています。
      </p>
    </div>
  </div>
</body>
</html>
