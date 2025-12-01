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
$talent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$talent){
    die("Talent not found.");
}

// JSON項目復元
$talent['tags'] = $talent['tags_json'] ? json_decode($talent['tags_json'], true) : [];
$talent['platforms'] = $talent['platforms_json'] ? json_decode($talent['platforms_json'], true) : [];
$talent['links'] = $talent['links_json'] ? json_decode($talent['links_json'], true) : [];
$talent['longbio'] = $talent['long_bio_json'] ? json_decode($talent['long_bio_json'], true) : [];
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= esc($talent['name']) ?> | CORO PROJECT</title>
  <link rel="stylesheet" href="../css/styles.css">
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

<main>
  <section class="section">
    <div class="container">
      <div class="talent-detail">

        <img src="<?= esc($talent['avatar']) ?>" 
             alt="<?= esc($talent['name']) ?>" 
             style="max-width:320px; border-radius:16px;">

        <h1><?= esc($talent['name']) ?></h1>
        <p><?= esc($talent['bio']) ?></p>

        <?php if (!empty($talent['longbio'])): ?>
          <?php foreach ($talent['longbio'] as $p): ?>
            <p><?= esc($p) ?></p>
          <?php endforeach; ?>
        <?php endif; ?>

        <h2>Links</h2>
        <ul>
          <?php foreach ($talent['platforms'] as $p): ?>
            <li><a href="<?= esc($p['url']) ?>" target="_blank"><?= esc($p['name']) ?></a></li>
          <?php endforeach; ?>

          <?php foreach ($talent['links'] as $l): ?>
            <li><a href="<?= esc($l['url']) ?>" target="_blank"><?= esc($l['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>

        <h2>Tags</h2>
        <p>
          <?php foreach ($talent['tags'] as $tag): ?>
            <span class="tag"><?= esc($tag) ?></span>
          <?php endforeach; ?>
        </p>

      </div>
    </div>
  </section>
</main>

</body>
</html>
