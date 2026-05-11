<?php
require __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/includes/public-settings.php';

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$newsItems = [];
$pdo = coro_public_settings_db();
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, title, date, tag, excerpt, url
            FROM news
            WHERE is_published = 1
              AND (targets IS NULL OR targets = '' OR FIND_IN_SET('business', targets))
            ORDER BY sort_order ASC, date DESC, id DESC
            LIMIT 40
        ");
        $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

render_header('news', 'お知らせ | CORO PROJECT Business Matching', [
    'canonical' => 'https://coroproject.jp/business/news.php',
    'description' => 'CORO PROJECT Business Matchingのお知らせ・最新情報。',
]);
?>
<section class="sub-hero">
  <div class="container">
    <div class="eyebrow"><span></span>NEWS</div>
    <h1 class="hero-title"><span class="line">お知らせ</span></h1>
    <p class="hero-lead">Business Matchingに関するお知らせ・最新情報を掲載しています。</p>
  </div>
</section>

<section class="content-section">
  <div class="container">
    <?php if (!$newsItems): ?>
      <p style="color:#888;padding:40px 0;">まだお知らせがありません。</p>
    <?php else: ?>
      <div class="news-list">
        <?php foreach ($newsItems as $item): ?>
          <?php
            $dateStr = !empty($item['date']) ? date('Y.m.d', strtotime($item['date'])) : '';
            $link    = !empty($item['url']) ? $item['url'] : '#';
            $target  = !empty($item['url']) ? ' target="_blank" rel="noopener"' : '';
          ?>
          <article class="news-item">
            <div class="news-item-meta">
              <?php if ($dateStr): ?><time class="news-date"><?= h($dateStr) ?></time><?php endif; ?>
              <?php if (!empty($item['tag'])): ?><span class="news-tag"><?= h($item['tag']) ?></span><?php endif; ?>
            </div>
            <a class="news-item-title" href="<?= h($link) ?>"<?= $target ?>><?= h($item['title']) ?></a>
            <?php if (!empty($item['excerpt'])): ?>
              <p class="news-item-excerpt"><?= h($item['excerpt']) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<style>
.sub-hero { padding: 140px 0 64px; }
.sub-hero .eyebrow { display:flex;align-items:center;gap:12px;margin-bottom:20px;font-family:"Barlow",sans-serif;font-style:italic;font-weight:800;font-size:.82rem;letter-spacing:.18em;color:#7b3ff5; }
.sub-hero .eyebrow span { display:block;width:28px;height:2px;background:linear-gradient(90deg,#7b3ff5,#ee4a9d); }
@media (max-width:640px) { .sub-hero { padding:120px 0 48px; } }
</style>
<?php render_footer(); ?>
