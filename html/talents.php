<?php
// デバッグ用：一旦エラー内容を画面に出す
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

// db.php 側に同名関数があっても落ちないようにガード
if (!function_exists('esc')) {
    function esc($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// ---- タレント本体 ----
$sql = "
    SELECT *
    FROM talents
    ORDER BY sort_order ASC, debut ASC, name ASC
";
$stmt    = $pdo->query($sql);
$talents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- SNSリンク ----
$stmt = $pdo->query("
    SELECT *
    FROM talent_links
    ORDER BY id ASC
");
$linksByTalent = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $linksByTalent[$row['talent_id']][] = $row;
}

// ---- 配信プラットフォーム ----
$stmt = $pdo->query("
    SELECT *
    FROM talent_platforms
    ORDER BY id ASC
");
$platformsByTalent = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $platformsByTalent[$row['talent_id']][] = $row;
}

// ---- アバター画像のパス調整（/html/ から見た相対パスに揃える）----
foreach ($talents as &$t) {
    $avatar = $t['avatar'] ?? '';

    if ($avatar === '') {
        $t['avatar_for_list'] = '';
        continue;
    }

    // "../images/..." ならそのまま
    if (strpos($avatar, '../') === 0) {
        $t['avatar_for_list'] = $avatar;
    }
    // "images/..." の場合は 1階層上に
    elseif (strpos($avatar, 'images/') === 0) {
        $t['avatar_for_list'] = '../' . $avatar;
    }
    // "./images/..." の場合
    elseif (strpos($avatar, './') === 0) {
        $t['avatar_for_list'] = '../' . ltrim(substr($avatar, 2), '/');
    } else {
        $t['avatar_for_list'] = $avatar;
    }
}
unset($t);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CORO PROJECT | Talents</title>
  <meta name="description" content="CORO PROJECT に所属するタレントの一覧とプロフィール。配信リンク、活動状況など。">

  <link rel="canonical" href="https://coroproject.jp/html/talents.php">

  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Talents | CORO PROJECT">
  <meta property="og:description" content="所属タレント一覧と詳細プロフィール。">
  <meta property="og:url" content="https://coroproject.jp/html/talents.php">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">

  <meta name="twitter:card" content="summary_large_image">

  <link rel="stylesheet" href="../css/styles.css">
  <!-- talents.css は使ってないのでリンク削除 -->

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="apple-touch-icon" href="../images/logo.png">
</head>
<body>
  <div id="app" class="app visible">

    <!-- ===== Header ===== -->
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
      <!-- ===== Sub Hero ===== -->
      <section class="sub-hero">
        <div class="container sub-hero-inner">
          <div class="sub-hero-copy">
            <p class="eyebrow">Talents</p>
            <h1>所属タレント一覧</h1>
            <p class="lead">
              所属タレントのプロフィールや配信リンクをまとめています。<br>
              カードをクリックすると詳細ページへ移動します。
            </p>
          </div>
          <div class="sub-hero-art" aria-hidden="true">
            <div class="audition-visual"></div>
          </div>
        </div>
      </section>

      <!-- ===== Talents List ===== -->
      <section class="section talent-list-section">
        <div class="container">

          <header class="talent-list-head">
            <h2 class="talent-list-title">Talents</h2>
            <p class="talent-list-sub">
              CURRENT MEMBERS
            </p>
          </header>

          <?php if (empty($talents)): ?>
            <p class="talent-list-empty">
              現在、表示できるタレント情報がありません。
            </p>
          <?php else: ?>

            <div class="talent-list-grid">
              <?php foreach ($talents as $t): ?>
                <?php
                  $links  = $linksByTalent[$t['id']]      ?? [];
                  $plats  = $platformsByTalent[$t['id']] ?? [];
                  $avatar = $t['avatar_for_list'];
                ?>
                <a class="talent-card"
                   href="talent.php?id=<?= esc($t['id']) ?>">

                  <div class="talent-card-thumb"
                       style="<?= $avatar ? "background-image:url('".esc($avatar)."')" : '' ?>"></div>

                  <div class="talent-card-body">
                    <p class="talent-card-label">Coro Project Talent</p>
                    <h3 class="talent-card-name"><?= esc($t['name']) ?></h3>

                    <?php if (!empty($t['bio'])): ?>
                      <p class="talent-card-bio">
                        <?= esc($t['bio']) ?>
                      </p>
                    <?php endif; ?>

                    <?php if ($links || $plats): ?>
                      <div class="talent-card-links">
                        <?php foreach ($plats as $p): ?>
                          <span class="talent-pill"><?= esc($p['name']) ?></span>
                        <?php endforeach; ?>

                        <?php foreach ($links as $l): ?>
                          <span class="talent-pill"><?= esc($l['label']) ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>

          <?php endif; ?>

        </div>
      </section>

      <!-- ===== CTA 共通 ===== -->
      <section class="section section-cta section-cta--audition">
        <div class="container cta-audition">
          <div class="cta-audition-copy">
            <p class="cta-label">Audition</p>
            <h2 class="cta-title">「一度ちゃんと、本気でやってみたい」人へ。</h2>
            <p class="cta-lead">
              CORO PROJECTのオーディションでは、登録者数や配信歴だけで判断しません。<br>
              いまの数字よりも、これから一緒に作っていける「熱量」と「続ける意思」を大切にしています。
            </p>
            <ul class="cta-points">
              <li><span>✔</span> 配信経験が少なくてもOK（未経験でも意欲があれば歓迎）</li>
              <li><span>✔</span> 学業・仕事との両立を前提に、活動ペースを一緒に設計</li>
              <li><span>✔</span> キャラクターや世界観づくりから相談可能</li>
            </ul>
            <div class="cta-actions">
              <a class="btn btn-primary" href="./audition.html">オーディションの詳細を見る</a>
              <a class="btn btn-outline" href="./contact.html">まずは相談してみる</a>
            </div>
          </div>
          <div class="cta-audition-side">
            <div class="cta-card">
              <h3>募集しているイメージ</h3>
              <ul>
                <li>長期的に活動を続けたい意志がある方</li>
                <li>リスナーとコミュニケーションをとるのが好きな方</li>
                <li>新しいことに挑戦してみたい方</li>
              </ul>
            </div>
            <div class="cta-card cta-card--soft">
              <h3>選考フロー（例）</h3>
              <ol>
                <li>Webフォームから応募</li>
                <li>書類・配信アーカイブの確認</li>
                <li>オンライン面談（1〜2回）</li>
              </ol>
              <p class="cta-small">
                詳細は<a href="./audition.html">オーディションページ</a>にてご確認ください。
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ===== Footer ===== -->
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
          <ul>
            <li><a href="./news.php">News</a></li>
            <li><a href="./talents.php">Talents</a></li>
            <li><a href="./audition.html">Audition</a></li>
            <li><a href="./privacy.html">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Social</h4>
          <ul>
            <li><a href="https://x.com/CoroProject0111" target="_blank" rel="noopener">X（Twitter）</a></li>
            <li><a href="#">YouTube</a></li>
            <li><a href="#">Twitch</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <small>© <span id="year"></span> CORO PROJECT</small>
      </div>
    </footer>
  </div>

  <script>
    // 年号
    document.getElementById('year').textContent = new Date().getFullYear();

    // モバイルナビ
    (function(){
      const btn = document.getElementById('navToggle');
      const nav = document.getElementById('siteNav');
      if(!btn || !nav) return;

      btn.addEventListener('click', () => {
        const open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));
        document.body.classList.toggle('nav-open', !open);
      });

      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('nav-open')) {
          btn.setAttribute('aria-expanded', 'false');
          document.body.classList.remove('nav-open');
        }
      });
    })();
  </script>
</body>
</html>
