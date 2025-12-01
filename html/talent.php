<?php
// talents.php と同じく 1つ上の階層の db.php を読む
require_once __DIR__ . '/../db.php';

function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// -----------------------------
//  ID 取得 & バリデーション
// -----------------------------
$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';

if ($id === '') {
    http_response_code(404);
    $error_message = 'タレントIDが指定されていません。';
} else {
    // -----------------------------
    //  タレント本体情報
    // -----------------------------
    $stmt = $pdo->prepare("
        SELECT *
        FROM talents
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $talent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$talent) {
        http_response_code(404);
        $error_message = '指定されたタレントが見つかりませんでした。';
    } else {
        // -----------------------------
        //  SNSリンク
        // -----------------------------
        $stmt = $pdo->prepare("
            SELECT *
            FROM talent_links
            WHERE talent_id = :id
            ORDER BY id ASC
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // -----------------------------
        //  プラットフォーム
        // -----------------------------
        $stmt = $pdo->prepare("
            SELECT *
            FROM talent_platforms
            WHERE talent_id = :id
            ORDER BY id ASC
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // -----------------------------
        //  アバター画像パス調整
        //  （/html/ から見た相対パスに合わせる）
        // -----------------------------
        $avatarRaw = $talent['avatar'] ?? '';
        $avatarPath = '';

        if ($avatarRaw !== '') {
            if (strpos($avatarRaw, '../') === 0) {
                // すでに "../images/..." 形式ならそのまま使える
                $avatarPath = $avatarRaw;
            } elseif (strpos($avatarRaw, 'images/') === 0) {
                $avatarPath = '../' . $avatarRaw;
            } elseif (strpos($avatarRaw, './') === 0) {
                $avatarPath = '../' . ltrim(substr($avatarRaw, 2), '/');
            } else {
                $avatarPath = $avatarRaw;
            }
        }

        // -----------------------------
        //  ステータス表示用ラベル
        // -----------------------------
        $statusMap = [
            'active'    => '活動中',
            'rest'      => '休止中',
            'graduate'  => '卒業',
            'pre_debut' => '準備中',
        ];
        $statusKey   = $talent['status'] ?? '';
        $statusLabel = $statusMap[$statusKey] ?? ($statusKey !== '' ? $statusKey : '―');

        // -----------------------------
        //  日付の整形（デビュー / 最終活動）
        // -----------------------------
        $debutLabel = '―';
        if (!empty($talent['debut']) && $talent['debut'] !== '0000-00-00') {
            $ts = strtotime($talent['debut']);
            if ($ts) {
                $debutLabel = date('Y.m.d', $ts) . ' デビュー';
            }
        }

        $lastActiveLabel = '―';
        if (!empty($talent['last_active']) && $talent['last_active'] !== '0000-00-00') {
            $ts = strtotime($talent['last_active']);
            if ($ts) {
                $lastActiveLabel = date('Y.m.d', $ts);
            }
        }

        // -----------------------------
        //  タグ（tags_json）
        // -----------------------------
        $tags = [];
        if (!empty($talent['tags_json'])) {
            $tmp = json_decode($talent['tags_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $tags = array_values(array_filter(array_map('trim', $tmp)));
            } else {
                // JSON じゃないっぽい場合はカンマ区切りで分割
                $tags = array_values(array_filter(array_map('trim', explode(',', $talent['tags_json']))));
            }
        }

        // -----------------------------
        //  ロングプロフィール（long_bio_json）
        // -----------------------------
        $longBioParagraphs = [];
        if (!empty($talent['long_bio_json'])) {
            $tmp = json_decode($talent['long_bio_json'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($tmp)) {
                    $longBioParagraphs = array_values(array_filter(array_map('trim', $tmp)));
                } else {
                    $longBioParagraphs = [trim((string)$tmp)];
                }
            } else {
                // 改行で区切る
                $longBioParagraphs = preg_split('/\r\n|\r|\n/', $talent['long_bio_json']);
                $longBioParagraphs = array_values(array_filter(array_map('trim', $longBioParagraphs)));
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
  <title>
    <?php if (!empty($talent)): ?>
      <?= esc($talent['name']) ?> | Talents | CORO PROJECT
    <?php else: ?>
      Talent Not Found | CORO PROJECT
    <?php endif; ?>
  </title>

  <meta name="description" content="<?= !empty($talent) ? esc($talent['bio']) : 'CORO PROJECT所属タレントのプロフィールページです。' ?>">
  <link rel="canonical" href="https://coroproject.jp/html/talent.php?id=<?= esc($id) ?>">

  <meta property="og:site_name" content="CORO PROJECT">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= !empty($talent) ? esc($talent['name']) . ' | Talents | CORO PROJECT' : 'Talent Not Found | CORO PROJECT' ?>">
  <meta property="og:description" content="<?= !empty($talent) ? esc($talent['bio']) : 'CORO PROJECT所属タレントのプロフィールページです。' ?>">
  <meta property="og:url" content="https://coroproject.jp/html/talent.php?id=<?= esc($id) ?>">
  <meta property="og:image" content="<?= $avatarPath ? 'https://coroproject.jp/' . ltrim($avatarPath, '../') : 'https://coroproject.jp/images/ogp.png' ?>">

  <meta name="twitter:card" content="summary_large_image">

  <link rel="stylesheet" href="/../css/styles.css">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="apple-touch-icon" href="../images/logo.png">

  <!-- このページ専用のちょい足しスタイル -->
  <style>
    .talent-detail-hero {
      background: radial-gradient(120% 160% at 0% 100%, #3b1f5a 0%, transparent 60%),
                  radial-gradient(120% 160% at 100% 0%, #1d4ed8 0%, transparent 55%),
                  #030712;
      color: #f9f9ff;
      padding: 80px 0 60px;
    }
    .talent-detail-visual-inner {
      border-radius: 24px;
      background-color: #020617;
      background-size: cover;
      background-position: center;
      box-shadow: 0 24px 60px rgba(0,0,0,.85);
      width: 100%;
      aspect-ratio: 16 / 9;
      overflow: hidden;
    }
    .talent-detail-tags {
      margin: 8px 0 12px;
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .talent-detail-tags span {
      display: inline-flex;
      align-items: center;
      padding: 2px 10px;
      border-radius: 999px;
      font-size: .75rem;
      background: rgba(15,23,42,.9);
      border: 1px solid rgba(148,163,255,.7);
    }
    .talent-detail-main {
      background: #050313;
      padding: 80px 0 100px;
    }
    .talent-detail-grid {
      display: grid;
      gap: 32px;
    }
    @media (min-width: 900px) {
      .talent-detail-grid {
        grid-template-columns: minmax(0, 1.7fr) minmax(0, 1.1fr);
        align-items: flex-start;
      }
    }
    .talent-detail-about p {
      margin: 0 0 14px;
      font-size: .95rem;
      line-height: 1.9;
      color: #e5e7f5;
    }
    .talent-detail-panel {
      margin-bottom: 18px;
      padding: 14px 16px;
      border-radius: 16px;
      background: radial-gradient(circle at top, #111827 0, #020617 70%);
      border: 1px solid rgba(148,163,255,.45);
      box-shadow: 0 22px 60px rgba(15,23,42,.9);
      font-size: .9rem;
      color: #d1d5f0;
    }
    .talent-detail-panel h3 {
      margin: 0 0 10px;
      font-size: .95rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      opacity: .85;
    }
    .talent-meta-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      gap: 6px;
      font-size: .9rem;
    }
    .talent-meta-list li span {
      display: inline-block;
      min-width: 6em;
      font-weight: 600;
      color: #e5e7ff;
      margin-right: 4px;
    }
    .talent-links-list,
    .talent-platforms-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .talent-links-list a,
    .talent-platforms-list a {
      text-decoration: none;
      font-size: .8rem;
    }
    .talent-links-list a span,
    .talent-platforms-list a span {
      max-width: 180px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .talent-kana {
      margin: 0;
      font-size: .95rem;
      color: #e5e7f5;
      opacity: .85;
    }
    .talent-short-bio {
      margin-top: 10px;
      font-size: .9rem;
      color: #e5e7f5;
    }
  </style>
</head>
<body>
  <div id="app" class="app visible">
    <!-- Header（talents.php と同じ構成） -->
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
      <?php if (!empty($error_message)): ?>

        <!-- 404 / エラー表示 -->
        <section class="section" style="background:#050313; color:#e5e7f5; min-height:60vh;">
          <div class="container">
            <h1 class="section-title">Talents</h1>
            <p><?= esc($error_message) ?></p>
            <p><a class="btn btn-outline" href="./talents.php">一覧に戻る</a></p>
          </div>
        </section>

      <?php else: ?>

        <!-- ヒーロー：名前＋大きいアバター -->
        <section class="talent-detail-hero">
          <div class="container sub-hero-inner">
            <div class="sub-hero-copy">
              <p class="eyebrow">Talent Profile</p>
              <h1><?= esc($talent['name']) ?></h1>
              <?php if (!empty($talent['kana'])): ?>
                <p class="talent-kana"><?= esc($talent['kana']) ?></p>
              <?php endif; ?>

              <?php if (!empty($tags)): ?>
                <div class="talent-detail-tags">
                  <?php foreach ($tags as $tag): ?>
                    <span>#<?= esc($tag) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($talent['bio'])): ?>
                <p class="talent-short-bio"><?= esc($talent['bio']) ?></p>
              <?php endif; ?>
            </div>

            <div class="sub-hero-art talent-detail-visual" aria-hidden="true">
              <div class="talent-detail-visual-inner"
                   style="<?= $avatarPath ? "background-image:url('".esc($avatarPath)."');" : '' ?>">
              </div>
            </div>
          </div>
        </section>

        <!-- メインプロフィール -->
        <section class="section talent-detail-main">
          <div class="container talent-detail-grid">
            <!-- 左：ロングプロフィール・紹介文 -->
            <div class="talent-detail-about">
              <h2 class="section-title">Profile</h2>
              <?php if (!empty($longBioParagraphs)): ?>
                <?php foreach ($longBioParagraphs as $p): ?>
                  <p><?= nl2br(esc($p)) ?></p>
                <?php endforeach; ?>
              <?php else: ?>
                <?php if (!empty($talent['bio'])): ?>
                  <p><?= esc($talent['bio']) ?></p>
                <?php else: ?>
                  <p>プロフィール準備中です。</p>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <!-- 右：メタ情報・リンク類 -->
            <aside class="talent-detail-side">
              <div class="talent-detail-panel">
                <h3>Overview</h3>
                <ul class="talent-meta-list">
                  <li><span>Status</span><?= esc($statusLabel) ?></li>
                  <?php if (!empty($talent['talent_group'])): ?>
                    <li><span>Group</span><?= esc($talent['talent_group']) ?></li>
                  <?php endif; ?>
                  <li><span>Debut</span><?= esc($debutLabel) ?></li>
                  <li><span>Last Active</span><?= esc($lastActiveLabel) ?></li>
                </ul>
              </div>

              <?php if (!empty($platforms)): ?>
                <div class="talent-detail-panel">
                  <h3>Streaming</h3>
                  <ul class="talent-platforms-list">
                    <?php foreach ($platforms as $pf): ?>
                      <li>
                        <a class="btn btn-outline btn-sm" href="<?= esc($pf['url']) ?>" target="_blank" rel="noopener">
                          <span><?= esc($pf['name']) ?></span>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <?php if (!empty($links)): ?>
                <div class="talent-detail-panel">
                  <h3>Links</h3>
                  <ul class="talent-links-list">
                    <?php foreach ($links as $link): ?>
                      <li>
                        <a class="btn btn-outline btn-sm" href="<?= esc($link['url']) ?>" target="_blank" rel="noopener">
                          <span><?= esc($link['label']) ?></span>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
            </aside>
          </div>
        </section>

      <?php endif; ?>
    </main>

    <!-- Footer（talents.php と同じ構成でOKならコピペでも） -->
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
            <li><a href="#" target="_blank">YouTube</a></li>
            <li><a href="#" target="_blank">X</a></li>
            <li><a href="mailto:info@coroproject.jp">Mail</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <small>© <span id="year"></span> CORO PROJECT</small>
      </div>
    </footer>
  </div>

  <script>
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
    })();
  </script>
</body>
</html>
