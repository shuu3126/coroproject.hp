<?php
require_once __DIR__ . '/db.php';

/**
 * HTMLエスケープ
 */
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$topNews    = [];
$topTalents = [];

try {
    // ===== News（TOP表示用：最新3件） =====
    $TOP_NEWS_LIMIT = 3;

    $sql = "
        SELECT *
        FROM news
        WHERE is_published = 1
        ORDER BY sort_order ASC, date DESC, id DESC
        LIMIT :limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $TOP_NEWS_LIMIT, PDO::PARAM_INT);
    $stmt->execute();
    $topNews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== Talents（TOP表示用：最新3名） =====
    $sql = "
        SELECT *
        FROM talents
        ORDER BY sort_order ASC, debut ASC, name ASC
        LIMIT 3
    ";
    $stmt = $pdo->query($sql);
    $topTalents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TOP用 avatar のパス補正（../ や ./ を削る）
    foreach ($topTalents as &$t) {
        $avatar = $t['avatar'] ?? '';
        if (strpos($avatar, '../') === 0) {
            $avatar = substr($avatar, 3);
        } elseif (strpos($avatar, './') === 0) {
            $avatar = substr($avatar, 2);
        }
        $t['avatar_for_top'] = $avatar;
    }
    unset($t);

} catch (PDOException $e) {
    // もしDBエラーでも、画面だけは表示できるようにする
    $topNews    = [];
    $topTalents = [];
    // エラー内容はログにだけ出したい場合はここで error_log($e->getMessage());
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CORO PROJECT | VTuberプロダクション公式サイト</title>
  <meta name="description" content="CORO PROJECTは、紫・ピンクをテーマカラーとしたVTuberプロダクションです。所属タレント情報やオーディションのご案内など、最新情報をお届けします。">

  <!-- Canonical -->
  <link rel="canonical" href="https://coroproject.jp/">

  <!-- OGP -->
  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="CORO PROJECT | VTuberプロダクション公式サイト">
  <meta property="og:description" content="CORO PROJECTは、紫・ピンクをテーマカラーとしたVTuberプロダクションです。所属タレント情報やオーディションのご案内など、最新情報をお届けします。">
  <meta property="og:url" content="https://coroproject.jp/">
  <meta property="og:image" content="https://coroproject.jp/images/ogp.png">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="CORO PROJECT | VTuberプロダクション公式サイト">
  <meta name="twitter:description" content="CORO PROJECTは、紫・ピンクをテーマカラーとしたVTuberプロダクションです。">
  <meta name="twitter:image" content="https://coroproject.jp/images/ogp.png">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="images/logo.png">
  <link rel="apple-touch-icon" href="images/logo.png">

  <!-- Top page styles -->
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/top.css">
</head>
<body class="home">
  <div id="coro-loader" class="coro-loader">
    <!-- 背景の粒子 -->
    <div class="coro-loader__noise" aria-hidden="true"></div>

    <!-- 画面のど真ん中に来る“コア部分”（ロゴ＋波紋） -->
    <div class="coro-loader__core">
      <div class="coro-loader__ripples" aria-hidden="true">
        <div class="coro-loader__ripple ripple-1"></div>
        <div class="coro-loader__ripple ripple-2"></div>
        <div class="coro-loader__ripple ripple-3"></div>
      </div>

      <div class="coro-loader__logo-inner">
        <img src="images/logo.png" alt="CORO PROJECT" class="coro-loader__logo-img">
      </div>
    </div>

    <!-- ロゴの“下側”にまとめて配置するテキスト群 -->
    <div class="coro-loader__bottom">
      <p class="coro-loader__domain">coroproject.jp</p>
      <p class="coro-loader__tagline">CREATOR FIRST, CREATIVE NEXT</p>

      <div class="coro-loader__bar">
        <div class="coro-loader__bar-inner"></div>
      </div>

      <p class="coro-loader__status">Preparing stage for talents...</p>
    </div>
  </div>

  <!-- ↓ここから既存のトップページ -->
  <div id="app">
  <!-- ===== Header ===== -->
  <header class="site-header">
    <div class="container header-inner">
      <a href="#top" class="brand">
        <img src="images/toukalogo.png" alt="CORO PROJECT ロゴ" class="brand-logo">
        <span class="brand-text">CORO PROJECT</span>
      </a>

      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="メニューを開く">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav" id="siteNav" aria-label="メインナビゲーション">
        <a href="#about">About</a>
        <a href="#news">News</a>
        <a href="#talents">Talents</a>
        <a href="html/audition.html">Audition</a>
        <a href="html/contact.html">Contact</a>
      </nav>
    </div>
  </header>

  <main id="top">
    <!-- ===== Hero ===== -->
    <section class="hero">
      <div class="hero-bg" aria-hidden="true"></div>

      <div class="container hero-inner">
        <!-- Left: Copy -->
        <div class="hero-copy">
          <p class="hero-eyebrow">VTUBER PRODUCTION</p>
          <h1 class="hero-title">ころぷろじぇくと！</h1>
          <p class="hero-lead">“自分だけでは届かなかった場所へ”</p>
          <p class="hero-sub">
            紫とピンクをテーマに、配信・創作・企画のすべてを一緒に楽しむVTuberプロダクション。
            あなたの「好き」を、もっと遠くまで届けます。
          </p>

          <div class="hero-actions">
            <a class="btn btn-primary" href="html/audition.html">オーディション</a>
            <a class="btn btn-outline" href="html/talents.php">タレントを見る</a>
          </div>
        </div>

        <!-- Right: Shorts phone visual -->
        <div class="hero-visual">
          <div class="hero-visual-inner">
            <div class="hero-aurora" aria-hidden="true"></div>

            <div class="shorts-phone">
              <div class="shorts-phone-inner">
                <div class="shorts-track" id="shortsTrack">
                  <section class="shorts-item">
                    <video playsinline muted preload="metadata" src="shorts/short1.mp4"></video>
                  </section>
                  <section class="shorts-item">
                    <video playsinline muted preload="metadata" src="shorts/short2.mp4"></video>
                  </section>
                  <section class="shorts-item">
                    <video playsinline muted preload="metadata" src="shorts/short3.mp4"></video>
                  </section>
                  <section class="shorts-item">
                    <video playsinline muted preload="metadata" src="shorts/short4.mp4"></video>
                  </section>
                </div>
              </div>
              <div class="shorts-phone-bar"></div>
            </div>

            <div class="hero-badge">
              <span class="badge-label">Coro Project Shorts</span>
              <span class="badge-dot"></span>
            </div>

            <div class="hero-tags">
              <span>#切り抜き</span>
              <span>#VTuber</span>
              <span>#CoroProject</span>
            </div>

            <div class="shorts-dots" aria-label="ショート動画のインジケーター">
              <button type="button" data-index="0" class="is-active"></button>
              <button type="button" data-index="1"></button>
              <button type="button" data-index="2"></button>
              <button type="button" data-index="3"></button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== About ===== -->
    <section id="about" class="section section-about reveal">
      <div class="decor-line-left"></div>
      <div class="decor-line-right"></div>
      <div class="container about-inner">
        <div class="section-head">
          <h2 class="section-title">About</h2>
          <p class="section-kicker">CORO PROJECTとは？</p>
        </div>

        <div class="about-grid">
          <div class="about-main">
            <h3 class="about-title">「好き」と「続けられる」を、ちゃんと両立させるプロダクション。</h3>
            <p>
              CORO PROJECTは、紫とピンクをテーマにした小さなVTuberプロダクションです。<br>
              目指しているのは、大きな看板ではなく「ちゃんと隣で一緒に走ってくれる運営」。
            </p>
            <p>
              配信スケジュール、企画、コラボ、数字の伸び方。<br>
              ひとつひとつの悩みに寄り添いながら、タレントと一緒に
              <span class="about-highlight">“その人らしい活動スタイル”</span>を組み立てていきます。
            </p>
            <p>
              「もっと本気でやりたいけど、ひとりだと限界を感じている」「でも、ガチガチの箱に入りたいわけじゃない」。<br>
              そんな人の “ちょうどいい居場所” になれたら、と考えています。
            </p>
          </div>

          <div class="about-side">
            <div class="about-pill">Support &amp; Production</div>
            <ul class="about-points">
              <li>
                <strong>配信まわりの伴走サポート</strong>
                <span>企画相談 / 週次の振り返り / 方向性のすり合わせ など</span>
              </li>
              <li>
                <strong>クリエイティブ制作の窓口</strong>
                <span>キャラデザ・ロゴ・OPED・BGMなど、制作パートナーの紹介と進行サポート</span>
              </li>
              <li>
                <strong>数字と生活のバランス設計</strong>
                <span>無理のない活動ペースの設計 / 収益化までのロードマップ作成</span>
              </li>
              <li>
                <strong>ファンと一緒に育てる企画</strong>
                <span>周年企画 / グッズ / イベント運営 などの共同プランニング</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== News（DBから最新3件を表示） ===== -->
    <section id="news" class="section section-news reveal">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title">News</h2>
          <a class="section-link" href="html/news.php">すべて見る</a>
        </div>

        <div id="top-news-list" class="news-grid">
          <?php if (empty($topNews)): ?>
            <p class="news-empty" style="color:#9ca3c3; font-size:.9rem;">
              現在表示できるニュースはありません。詳細は <a href="html/news.php">Newsページ</a> をご確認ください。
            </p>
          <?php else: ?>
            <?php foreach ($topNews as $n): ?>
              <article class="news-card">
                <a href="<?= $n['url'] ? esc($n['url']) : 'html/news.php' ?>">
                  <div class="card-thumb"
                       aria-hidden="true"
                       style="<?= $n['thumb'] ? "background-image:url('".esc($n['thumb'])."')" : '' ?>"></div>
                  <span class="news-label"><?= esc($n['tag'] ?: 'News') ?></span>
                  <span class="news-date"><?= esc($n['date']) ?></span>
                  <h3 class="news-title"><?= esc($n['title']) ?></h3>
                  <p class="news-text"><?= esc($n['excerpt'] ?? '') ?></p>
                </a>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <noscript>
          <p style="font-size:.85rem; color:#9ca3c3;">
            JavaScriptが無効になっています。最新情報は<a href="html/news.php">Newsページ</a>からご確認ください。
          </p>
        </noscript>
      </div>
    </section>

    <!-- ===== Talents（TOP：最新3名） ===== -->
    <section id="talents" class="section section-talents reveal">
      <div class="container">

        <div class="section-head">
          <h2 class="section-title">Talents</h2>
          <a class="section-link" href="html/talents.php">一覧を見る</a>
        </div>

        <div class="grid grid-3" style="gap:24px;">

          <?php foreach ($topTalents as $t): ?>
            <a class="card" href="html/talents.php?id=<?= esc($t['id']) ?>" style="text-decoration:none;">
              <div class="card-thumb"
                  style="
                    width:100%;
                    height:260px;
                    background-image:url('<?= esc($t['avatar_for_top']) ?>');
                    background-size:cover;
                    background-position:center;
                    border-radius:16px;
                  ">
              </div>

              <div class="card-body" style="padding:12px 0;">
                <h3 class="card-title" style="margin:6px 0 4px;">
                  <?= esc($t['name']) ?>
                </h3>
                <p class="card-text" style="color:#b7b7c8; font-size:.9rem;">
                  <?= esc($t['bio']) ?>
                </p>
              </div>
            </a>
          <?php endforeach; ?>

          <?php if (count($topTalents) < 3): ?>
            <?php for ($i = count($topTalents); $i < 3; $i++): ?>
              <div class="card" style="
                    opacity:0.25;
                    background:radial-gradient(circle at top, #273059 0, #181828 55%, #12091d 100%);
                    border-radius:24px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    height:260px;
                    font-size:.9rem;
                    letter-spacing:.14em;
                    text-transform:uppercase;
                    color:#c3c6ff;">
                COMING SOON
              </div>
            <?php endfor; ?>
          <?php endif; ?>

        </div>

      </div>
    </section>

    <!-- ===== Audition CTA ===== -->
    <section class="section section-cta section-cta--audition reveal">
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
            <a class="btn btn-primary" href="html/audition.html">オーディションの詳細を見る</a>
            <a class="btn class="btn-outline" href="html/contact.html">まずは相談してみる</a>
          </div>
          <p class="cta-note">
            「自分に合っているのかわからない」「少しだけ話を聞きたい」などのご相談もお気軽にどうぞ。
          </p>
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
              詳細は<a href="html/audition.html">オーディションページ</a>にてご確認ください。
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
          <img src="images/logo.png" alt="CORO PROJECT ロゴ" class="footer-logo">
          <span class="footer-name">CORO PROJECT</span>
        </div>
        <p class="footer-text">
          VTuberのプロデュース・配信サポートを行うプロダクションです。
        </p>
      </div>
      <div class="footer-col">
        <h4>Links</h4>
        <ul>
          <li><a href="html/news.php">News</a></li>
          <li><a href="html/talents.php">Talents</a></li>
          <li><a href="html/audition.html">Audition</a></li>
          <li><a href="html/privacy.html">Privacy Policy</a></li>
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
  </div> <!-- /#app -->

  <!-- ===== Scripts ===== -->
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

      nav.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          document.body.classList.remove('nav-open');
          btn.setAttribute('aria-expanded', 'false');
        });
      });
    })();
  </script>

  <script>
    // Hero ショート動画（縦スワイプ風）
    (function(){
      const track = document.getElementById('shortsTrack');
      if (!track) return;

      const items  = Array.from(track.querySelectorAll('.shorts-item'));
      const videos = items.map(it => it.querySelector('video'));
      const dots   = Array.from(document.querySelectorAll('.shorts-dots button'));
      const DURATION = 8000;

      let index = 0;
      let timer = null;

      function go(to){
        index = (to + items.length) % items.length;
        track.style.transform = `translateY(-${index * 100}%)`;

        videos.forEach((v,i)=>{
          if (i === index){
            try{
              v.currentTime = 0;
              v.muted = true;
              v.play();
            }catch(e){}
          }else{
            try{ v.pause(); }catch(e){}
          }
        });

        dots.forEach((d,i)=>d.classList.toggle('is-active', i === index));
        restart();
      }

      function restart(){
        clearTimeout(timer);
        timer = setTimeout(()=>go(index + 1), DURATION);
      }

      dots.forEach((btn,i)=>{
        btn.addEventListener('click', ()=>{ go(i); });
      });

      videos.forEach(v=>{
        v.setAttribute('playsinline','');
        v.muted = true;
      });
      go(0);
    })();
  </script>

  <!-- ★ ローダーを必ず消す安全版スクリプト -->
  <script>
  (function () {
    function hideLoader() {
      const loader = document.getElementById("coro-loader");
      if (!loader) return;
      loader.classList.add("coro-loader--hide");
      document.body.classList.remove("is-loading");
      setTimeout(() => {
        if (loader && loader.parentNode) {
          loader.parentNode.removeChild(loader);
        }
      }, 800);
    }

    // 通常：load のあと少し待ってから消す
    window.addEventListener("load", function () {
      setTimeout(hideLoader, 800);
    });

    // 保険：5秒経っても load が来なければ強制で消す
    setTimeout(hideLoader, 5000);
  })();
  </script>

  <script>
    // ページを開いたら常に最上部に戻す
    window.history.scrollRestoration = "manual";
    window.addEventListener("load", () => {
      setTimeout(() => {
        window.scrollTo(0, 0);
      }, 10);
    });
  </script>

  <script>
  // Talents スライダー（今はHTML側に無いのでreturnで終了する）
  (function(){
    const slider = document.getElementById('talentSlider');
    if (!slider) return;
    // 以下は今は実行されない
  })();
  </script>

  <script>
    // スクロール時のフェードイン（.reveal に is-visible 付与）
    (function(){
      const reveals = Array.from(document.querySelectorAll('.reveal'));
      if (!('IntersectionObserver' in window) || !reveals.length) {
        reveals.forEach(el => el.classList.add('is-visible'));
        return;
      }

      const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting){
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          }
        });
      }, {
        root:null,
        threshold:0.15
      });

      reveals.forEach(el => io.observe(el));
    })();
  </script>
</body>
</html>
