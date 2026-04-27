<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_asset.php';

// ------ フィルタ処琁E------
$filterTag = $_GET['tag'] ?? '';

$sql = "
    SELECT *
    FROM news
    WHERE is_published = 1
";
$params = [];

if ($filterTag !== '') {
    $sql .= " AND tag = :tag";
    $params[':tag'] = $filterTag;
}

$sql .= " ORDER BY sort_order ASC, date DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();


// ------ Helper ------
function esc($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function fmtDate($s) {
    $d = strtotime($s);
    return $d ? date('Y.m.d', $d) : esc($s);
}

function safeId($id) {
    return preg_replace('/[^a-zA-Z0-9\-_]/', '-', $id);
}

?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CORO PROJECT | News</title>
  <meta name="description" content="CORO PROJECTの最新ニュース一覧と詳細。お知らせ、リリース、イベント情報など、E>
  <link rel="canonical" href="https://coroproject.jp/production/html/news.php">
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="News | CORO PROJECT">
  <meta property="og:description" content="最新ニュースの一覧と詳細、E>
  <meta property="og:url" content="https://coroproject.jp/production/html/news.php">
  <meta property="og:image" content="https://coroproject.jp/production/images/ogp.png">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="apple-touch-icon" href="../images/logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
<div id="app" class="app">
  <!-- ▼ Header�E��Eコードそのまま�E�E-->
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="../index.php">
        <img src="../images/toukalogo.png" alt="CORO PROJECT ロゴ" class="brand-logo">
        <span class="brand-text">CORO PROJECT</span>
      </a>

      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="メニューを開ぁE>
        <span></span><span></span><span></span>
      </button>

      <nav class="nav" id="siteNav" aria-label="メインナビゲーション">
        <a href="../index.php#about">About</a>
        <a href="./news.php">News</a>
        <a href="./talents.php">Talents</a>
        <a href="./audition.html">Audition</a>
        <a href="./contact.html">Contact</a>
      </nav>
    </div>
  </header>

  <main id="top">
    <!-- ▼ Sub Hero�E��Eコードそのまま�E�E-->
    <section class="sub-hero">
      <div class="container sub-hero-inner">
        <div class="sub-hero-copy">
          <p class="eyebrow">News</p>
          <h1>ニュース一覧と詳細</h1>
          <p class="lead">最新の「お知らせ / リリース / イベント」をまとめて掲載、E/p>
          <div class="sub-hero-actions">
            <a class="btn btn-primary btn-lg" href="#list">一覧へ</a>
            <a class="btn btn-ghost btn-lg" href="#detail">詳細へ</a>
          </div>
        </div>
        <div class="sub-hero-art" aria-hidden="true">
          <div class="audition-visual"></div>
        </div>
      </div>
    </section>

    <!-- ▼ 一覧�E�カード！E-->
    <section id="list" class="section">
      <div class="container">
        <div class="section-head" style="align-items:flex-end; gap:12px; flex-wrap:wrap">
          <h2 class="section-title" style="margin:0">最新惁E��</h2>

          <div style="margin-left:auto; display:flex; gap:8px; flex-wrap:wrap">
            <a class="btn btn-ghost <?= $filterTag === '' ? 'btn-primary' : '' ?>" href="news.php">すべて</a>
            <a class="btn btn-ghost <?= $filterTag === 'お知らせ' ? 'btn-primary' : '' ?>" href="news.php?tag=お知らせ">お知らせ</a>
            <a class="btn btn-ghost <?= $filterTag === 'リリース' ? 'btn-primary' : '' ?>" href="news.php?tag=リリース">リリース</a>
            <a class="btn btn-ghost <?= $filterTag === 'イベント' ? 'btn-primary' : '' ?>" href="news.php?tag=%E3%82%A4%E3%83%99%E3%83%B3%E3%83%88">イベント</a>
          </div>
        </div>

        <div class="grid grid-3" id="newsGrid">
          <?php if (count($newsList) === 0): ?>
            <p class="news-empty">ニュースはまだありません、E/p>
          <?php else: ?>
            <?php foreach ($newsList as $n): 
              $anchor = "#" . safeId($n['id']);
            ?>
            <article class="card">
              <a class="card-body" href="<?= $anchor ?>">
                <div class="card-thumb" style="background-image:url('<?= esc(public_html_asset_url($n['thumb'])) ?>')" aria-hidden="true"></div>
                <div class="card-meta">
                  <time datetime="<?= esc($n['date']) ?>"><?= fmtDate($n['date']) ?></time>
                  <span class="tag"><?= esc($n['tag']) ?></span>
                </div>
                <h3 class="card-title"><?= esc($n['title']) ?></h3>
                <p class="card-text"><?= esc($n['excerpt']) ?></p>
              </a>
            </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ▼ 詳細�E�タイムライン�E�E-->
    <section id="detail" class="section section-alt">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title">詳細�E�新しい頁E��E/h2>
        </div>

        <ol class="timeline" id="newsDetail" style="--dot: var(--accent,#6b5cff)">
          <?php foreach ($newsList as $i => $n): 
            $cid = safeId($n['id']);
            $contentArr = json_decode($n['content_json'], true) ?: [];
          ?>
          <li class="time-item" id="<?= $cid ?>">
            <span class="time-badge">#<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
            <div class="time-body">
              <div class="card" style="padding:18px">
                <div class="card-meta" style="margin-bottom:6px">
                  <time datetime="<?= esc($n['date']) ?>"><?= fmtDate($n['date']) ?></time>
                  <span class="tag"><?= esc($n['tag']) ?></span>
                </div>

                <h3 class="card-title" style="margin:4px 0 10px"><?= esc($n['title']) ?></h3>

                <?php if ($n['thumb']): ?>
                  <div class="card-thumb"
                       style="margin-bottom:12px; background-image:url('<?= esc(public_html_asset_url($n['thumb'])) ?>')">
                  </div>
                <?php endif; ?>

                <?php foreach ($contentArr as $p): ?>
                  <p><?= esc($p) ?></p>
                <?php endforeach; ?>

                <?php if (!empty($n['url'])): ?>
                  <div style="margin-top:10px">
                    <a class="btn btn-ghost" href="<?= esc($n['url']) ?>">関連リンク ↁE/a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </section>

    <!-- CTA�E��Eコードそのまま�E�E-->
    <section class="section cta">
      <div class="container cta-inner">
        <div class="cta-copy">
          <h2>Audition</h2>
          <p>"自刁E��けでは届かなかった場所へ"</p>
        </div>
        <div class="cta-actions">
          <a class="btn btn-primary btn-lg" href="./audition.html">応募する</a>
          <a class="btn btn-ghost btn-lg" href="./audition.html#requirements">要E��E��読む</a>
        </div>
      </div>
    </section>
  </main>

  <!-- ▼ Footer�E��Eコードそのまま�E�E-->
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-col">
        <div class="brand brand--footer">
          <img src="../images/logo.png" alt="CORO PROJECT" class="footer-logo">
          <span class="brand-text">CORO PROJECT</span>
        </div>
        <p class="footer-desc">CORO PROJECTはVTuberのプロチE��ース・配信支援・クリエイチE��ブ制作を行うプロダクションです、E/p>
        <div class="footer-actions">
          <a class="btn btn-primary" href="./contact.html">問い合わぁE/a>
        </div>
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
          <li><a href="https://youtube.com/@YourChannel" target="_blank">YouTube</a></li>
          <li><a href="https://x.com/CoroProjectJP" target="_blank">X</a></li>
          <li><a href="mailto:info@coroproject.jp">Mail</a></li>
        </ul>
      </div>
    </div>
    <div class="container footer-copy">
      <small>© <span id="year"></span> CORO PROJECT</small>
    </div>
  </footer>
</div>

<script>
  // 年表示
  document.getElementById('year').textContent = new Date().getFullYear();

  // App フェードイン
  document.getElementById('app').classList.add('visible');

  // モバイルナビ
  (function () {
    const btn = document.getElementById('navToggle');
    const nav = document.getElementById('siteNav');
    if (!btn || !nav) return;
    btn.addEventListener('click', () => {
      const opened = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!opened));
      document.body.classList.toggle('nav-open', !opened);
    });
  })();
</script>
</body>
</html>
