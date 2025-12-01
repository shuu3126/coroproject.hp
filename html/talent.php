<?php
require_once __DIR__ . '/db.php';

function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ---------- ID 取得 ----------
$id = $_GET['id'] ?? '';
if ($id === '') {
    http_response_code(404);
    echo 'ID not found';
    exit;
}

// ---------- タレント1件 ----------
$sql = "SELECT * FROM talents WHERE id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$talent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$talent) {
    http_response_code(404);
    echo 'Talent not found';
    exit;
}

// ---------- SNSリンク ----------
$stmt = $pdo->prepare("
    SELECT *
    FROM talent_links
    WHERE talent_id = :id
    ORDER BY id ASC
");
$stmt->execute([':id' => $id]);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- 配信プラットフォーム ----------
$stmt = $pdo->prepare("
    SELECT *
    FROM talent_platforms
    WHERE talent_id = :id
    ORDER BY id ASC
");
$stmt->execute([':id' => $id]);
$platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- Avatar パス調整（/html からの相対パスにする） ----------
$avatar = $talent['avatar'] ?? '';
if ($avatar !== '') {
    if (strpos($avatar, '../') === 0) {
        // ../images/～ はそのまま使える
    } elseif (strpos($avatar, './') === 0) {
        $avatar = '../' . ltrim(substr($avatar, 2), '/');
    } elseif (strpos($avatar, 'images/') === 0) {
        $avatar = '../' . $avatar;
    }
}

// ---------- タグ（tags_json） ----------
$tagChips = [];
if (!empty($talent['tags_json'])) {
    $tmp = json_decode($talent['tags_json'], true);
    if (is_array($tmp)) {
        // ["雑談","歌"] でも {"main":["雑談","歌"]} でもそこそこ拾えるように
        if (array_keys($tmp) === range(0, count($tmp) - 1)) {
            $tagChips = $tmp;
        } else {
            foreach ($tmp as $v) {
                if (is_array($v)) {
                    $tagChips = array_merge($tagChips, $v);
                } else {
                    $tagChips[] = $v;
                }
            }
        }
    }
}

// ---------- 詳細プロフィール（long_bio_json） ----------
$profileParagraphs = [];
if (!empty($talent['long_bio_json'])) {
    $tmp = json_decode($talent['long_bio_json'], true);
    if (is_array($tmp)) {
        // ["文1","文2"] または {"body":["文1","文2"]} を想定
        if (isset($tmp['body']) && is_array($tmp['body'])) {
            $profileParagraphs = $tmp['body'];
        } elseif (array_keys($tmp) === range(0, count($tmp) - 1)) {
            $profileParagraphs = $tmp;
        }
    }
}
if (!$profileParagraphs && !empty($talent['bio'])) {
    // なければ bio を1〜2行に分けて出す
    $profileParagraphs = preg_split('/\r\n|\r|\n/', $talent['bio']);
}

// ---------- 概要用の追加情報（long_bio_json にあれば使う） ----------
$extra = [
    'birthday'   => '',
    'height'     => '',
    'color'      => '',
    'fanmark'    => '',
    'hashtags'   => [],  // general / live / art / fan などを想定
];

if (!empty($talent['long_bio_json'])) {
    $tmp = json_decode($talent['long_bio_json'], true);
    if (is_array($tmp)) {
        foreach ($extra as $key => $_) {
            if (isset($tmp[$key])) {
                $extra[$key] = $tmp[$key];
            }
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= esc($talent['name']) ?> | Talents | CORO PROJECT</title>
  <meta name="description" content="<?= esc($talent['bio'] ?: $talent['name'].' のプロフィールページです。') ?>">
  <link rel="canonical" href="https://coroproject.jp/html/talent.php?id=<?= esc($talent['id']) ?>">

  <!-- OGP / Twitter -->
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= esc($talent['name']) ?> | CORO PROJECT">
  <meta property="og:description" content="<?= esc($talent['bio'] ?: $talent['name'].' のプロフィールページです。') ?>">
  <meta property="og:url" content="https://coroproject.jp/html/talent.php?id=<?= esc($talent['id']) ?>">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">
  <meta name="twitter:card" content="summary_large_image">

  <!-- CSS は styles.css だけでOK（top.css は不要） -->
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="apple-touch-icon" href="../images/logo.png">
</head>
<body>
<div id="app" class="app visible">

  <!-- ================== Header ================== -->
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="../index.php">
        <img src="../images/toukalogo.png" alt="CORO PROJECT ロゴ" class="brand-logo">
        <span class="brand-text">CORO PROJECT</span>
      </a>

      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="メニューを開く">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav" id="siteNav" aria-label="メインナビゲーション">
        <a href="../index.php#about">About</a>
        <a href="./news.php">News</a>
        <a href="./talents.php" aria-current="page">Talents</a>
        <a href="./audition.html">Audition</a>
        <a href="./contact.html">Contact</a>
      </nav>
    </div>
  </header>

  <main id="top">

    <!-- ========== Hero / Basic Info ========== -->
    <section class="talent-hero">
      <div class="talent-hero-bg" aria-hidden="true"></div>

      <div class="container talent-hero-inner">
        <div class="talent-hero-main">
          <p class="eyebrow">TALENT PROFILE</p>
          <h1 class="talent-name-main"><?= esc($talent['name']) ?></h1>
          <?php if (!empty($talent['kana'])): ?>
            <p class="talent-name-kana"><?= esc($talent['kana']) ?></p>
          <?php endif; ?>

          <?php if ($tagChips): ?>
            <div class="talent-tags">
              <?php foreach ($tagChips as $tag): ?>
                <span><?= esc($tag) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($talent['bio'])): ?>
            <p class="talent-catch"><?= esc($talent['bio']) ?></p>
          <?php endif; ?>
        </div>

        <?php if ($avatar): ?>
          <div class="talent-hero-visual">
            <div class="talent-hero-avatar" style="background-image:url('<?= esc($avatar) ?>');"></div>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ========== Profile + Side Info ========== -->
    <section class="section talent-profile-section">
      <div class="container talent-profile-inner">
        <div class="talent-profile-main">
          <h2 class="section-title">Profile</h2>

          <?php if ($profileParagraphs): ?>
            <?php foreach ($profileParagraphs as $p): ?>
              <?php if (trim($p) === '') continue; ?>
              <p><?= esc($p) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p>準備中です。</p>
          <?php endif; ?>
        </div>

        <aside class="talent-profile-side">
          <!-- Overview -->
          <section class="talent-card">
            <h3 class="talent-card-title">Overview</h3>
            <dl class="talent-meta-list">
              <div>
                <dt>Status</dt>
                <dd><?= esc($talent['status'] ?: '―') ?></dd>
              </div>
              <div>
                <dt>Group</dt>
                <dd><?= esc($talent['talent_group'] ?: '-') ?></dd>
              </div>
              <div>
                <dt>Debut</dt>
                <dd><?= esc($talent['debut'] ?: '-') ?></dd>
              </div>
              <div>
                <dt>Last Active</dt>
                <dd><?= esc($talent['last_active'] ?: '-') ?></dd>
              </div>
              <?php if ($extra['birthday']): ?>
              <div>
                <dt>Birthday</dt>
                <dd><?= esc($extra['birthday']) ?></dd>
              </div>
              <?php endif; ?>
              <?php if ($extra['height']): ?>
              <div>
                <dt>Height</dt>
                <dd><?= esc($extra['height']) ?></dd>
              </div>
              <?php endif; ?>
              <?php if ($extra['color']): ?>
              <div>
                <dt>Color</dt>
                <dd><?= esc($extra['color']) ?></dd>
              </div>
              <?php endif; ?>
              <?php if ($extra['fanmark']): ?>
              <div>
                <dt>Fan mark</dt>
                <dd><?= esc($extra['fanmark']) ?></dd>
              </div>
              <?php endif; ?>
            </dl>
          </section>

          <!-- Streaming Platforms -->
          <section class="talent-card">
            <h3 class="talent-card-title">Streaming</h3>
            <?php if ($platforms): ?>
              <ul class="talent-link-list">
                <?php foreach ($platforms as $p): ?>
                  <li>
                    <a href="<?= esc($p['url']) ?>" target="_blank" rel="noopener">
                      <?= esc($p['name']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="talent-card-note">準備中です。</p>
            <?php endif; ?>
          </section>

          <!-- Other Links (SNS etc.) -->
          <section class="talent-card">
            <h3 class="talent-card-title">Links</h3>
            <?php if ($links): ?>
              <ul class="talent-link-list">
                <?php foreach ($links as $ln): ?>
                  <li>
                    <a href="<?= esc($ln['url']) ?>" target="_blank" rel="noopener">
                      <?= esc($ln['label']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="talent-card-note">準備中です。</p>
            <?php endif; ?>
          </section>

          <!-- Hashtags（あれば） -->
          <?php
          $hashtags = [];
          if (is_array($extra['hashtags'])) {
              $hashtags = $extra['hashtags'];
          }
          ?>
          <?php if ($hashtags): ?>
          <section class="talent-card">
            <h3 class="talent-card-title">Hashtags</h3>
            <dl class="talent-meta-list">
              <?php foreach ($hashtags as $k => $v): ?>
                <div>
                  <dt><?= esc(ucfirst($k)) ?></dt>
                  <dd><?= esc($v) ?></dd>
                </div>
              <?php endforeach; ?>
            </dl>
          </section>
          <?php endif; ?>

        </aside>
      </div>
    </section>

  </main>

  <!-- ================== Footer（他ページと同じ構造） ================== -->
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-col">
        <div class="footer-brand">
          <img src="../images/logo.png" alt="CORO PROJECT ロゴ" class="footer-logo">
          <span class="footer-name">CORO PROJECT</span>
        </div>
        <p class="footer-text">
          VTuberのプロデュース・配信サポートを行うプロダクションです。
        </p>
      </div>
      <div class="footer-col">
        <h4>Links</h4>
        <ul class="footer-links">
          <li><a href="./news.php">News</a></li>
          <li><a href="./talents.php">Talents</a></li>
          <li><a href="./audition.html">Audition</a></li>
          <li><a href="./privacy.html">Privacy Policy</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Social</h4>
        <ul class="footer-links">
          <li><a href="https://x.com/CoroProject0111" target="_blank" rel="noopener">X（Twitter）</a></li>
          <li><a href="#">YouTube</a></li>
          <li><a href="mailto:info@coroproject.jp">Mail</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <small>© <span id="year"></span> CORO PROJECT</small>
    </div>
  </footer>

</div><!-- /#app -->

<script>
  document.getElementById('year').textContent = new Date().getFullYear();

  (function(){
    const btn = document.getElementById('navToggle');
    const nav = document.getElementById('siteNav');
    if (!btn || !nav) return;

    btn.addEventListener('click', () => {
      const open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!open));
      document.body.classList.toggle('nav-open', !open);
    });
  })();
</script>
</body>
</html>
