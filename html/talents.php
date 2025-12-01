<?php
require_once __DIR__ . '/../db.php';

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$id = $_GET['id'] ?? '';
if (!$id){
    die("ID not found");
}

$stmt = $pdo->prepare("SELECT * FROM talents WHERE id = :id LIMIT 1");
$stmt->bindValue(':id', $id, PDO::PARAM_STR);
$stmt->execute();
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t){
    die("Talent not found.");
}

// JSON decode
$t['tags'] = $t['tags_json'] ? json_decode($t['tags_json'], true) : [];
$t['platforms'] = $t['platforms_json'] ? json_decode($t['platforms_json'], true) : [];
$t['links'] = $t['links_json'] ? json_decode($t['links_json'], true) : [];
$t['longbio'] = $t['long_bio_json'] ? json_decode($t['long_bio_json'], true) : [];
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= esc($t['name']) ?> | CORO PROJECT</title>

<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="../css/talent.css">

<!-- FontAwesome (SNSアイコン) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ====== ヘッダー画像 ====== */
.talent-hero {
  width:100%;
  height:360px;
  background-image:url('<?= esc($t['avatar']) ?>');
  background-size:cover;
  background-position:center;
  border-radius:0 0 24px 24px;
  box-shadow:0 8px 20px rgba(0,0,0,0.4);
}

/* メイン情報ラッパー */
.talent-main {
  max-width:960px;
  margin:-80px auto 40px;
  background:#1e1e2f;
  padding:32px;
  border-radius:24px;
  box-shadow:0 12px 32px rgba(0,0,0,0.45);
}

/* 名前・タグ */
.talent-name {
  font-size:2.4rem;
  margin-bottom:4px;
}

.talent-kana {
  opacity:.7;
  font-size:1rem;
}

/* SNSアイコン */
.sns-links a {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:42px;
  height:42px;
  background:#2b2b44;
  border-radius:50%;
  margin-right:10px;
  font-size:1.2rem;
  color:#fff;
  transition:0.25s;
}
.sns-links a:hover {
  background:#6b5cff;
  transform:translateY(-2px);
}

/* 長文プロフィール */
.longbio p {
  margin-bottom:12px;
  line-height:1.8;
  font-size:1rem;
}

/* タグ */
.tag {
  display:inline-block;
  padding:4px 10px;
  background:#6b5cff;
  border-radius:6px;
  font-size:.8rem;
  margin-right:6px;
  margin-bottom:6px;
}

/* レイアウト */
@media(max-width:768px){
  .talent-main {
    margin:-60px 12px 24px;
    padding:20px;
  }
  .talent-hero {
    height:260px;
    border-radius:0 0 16px 16px;
  }
}
</style>

</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="../index.php">
      <img src="../images/toukalogo.png" class="brand-logo">
      <span class="brand-text">CORO PROJECT</span>
    </a>
  </div>
</header>

<!-- ===== ヒーロー画像 ===== -->
<div class="talent-hero"></div>

<!-- ===== メインコンテンツ ===== -->
<section class="talent-main">
  
  <!-- 名前 -->
  <h1 class="talent-name"><?= esc($t['name']) ?></h1>
  <?php if ($t['kana']): ?>
    <div class="talent-kana"><?= esc($t['kana']) ?></div>
  <?php endif; ?>

  <!-- タグ -->
  <?php if (!empty($t['tags'])): ?>
    <div style="margin-top:10px;">
      <?php foreach ($t['tags'] as $tag): ?>
        <span class="tag"><?= esc($tag) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- SNSリンク -->
  <div class="sns-links" style="margin:20px 0;">
    <?php foreach ($t['platforms'] as $p): ?>
      <a href="<?= esc($p['url']) ?>" target="_blank" title="<?= esc($p['name']) ?>">
        <?php if (stripos($p['name'], 'x') !== false): ?>
          <i class="fa-brands fa-x-twitter"></i>
        <?php elseif (stripos($p['name'], 'youtube') !== false): ?>
          <i class="fa-brands fa-youtube"></i>
        <?php elseif (stripos($p['name'], 'twitch') !== false): ?>
          <i class="fa-brands fa-twitch"></i>
        <?php else: ?>
          <i class="fa-solid fa-link"></i>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>

    <?php foreach ($t['links'] as $l): ?>
      <a href="<?= esc($l['url']) ?>" target="_blank" title="<?= esc($l['label']) ?>">
        <i class="fa-solid fa-link"></i>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- プロフィール短文 -->
  <p style="font-size:1.1rem; margin-bottom:20px;"><?= esc($t['bio']) ?></p>

  <!-- 長文プロフィール -->
  <?php if (!empty($t['longbio'])): ?>
    <div class="longbio">
      <?php foreach ($t['longbio'] as $p): ?>
        <p><?= esc($p) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</section>

</body>
</html>
