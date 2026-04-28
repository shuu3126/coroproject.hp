<?php
require_once __DIR__ . '/includes/layout.php';
$filter = $_GET['category'] ?? 'all';
$counts = news_category_count($newsItems);
$filteredNews = array_values(array_filter($newsItems, static function ($item) use ($filter) {
    return $filter === 'all' || $item['category'] === $filter;
}));
render_head('NEWS', 'CORO PROJECTのお知らせ、所属タレント情報、募集情報を掲載しています。');
render_header('news');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">SIGNAL ARCHIVE</span>
        </div>
        <h1 class="sub-title">最新情報を、<br><span>カテゴリごとに整理して届ける。</span></h1>
        <p class="sub-lead">お知らせ、所属タレント関連、募集情報を総合ポータル上で整理し、各ページや各事業部への導線として機能させます。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">TOTAL <?= count($newsItems) ?> ITEMS</span>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container filter-bar reveal is-visible">
      <a class="filter-pill <?= $filter === 'all' ? 'is-current' : '' ?>" href="news.php">ALL <span><?= count($newsItems) ?></span></a>
      <?php foreach ($counts as $category => $count): ?>
        <a class="filter-pill <?= $filter === $category ? 'is-current' : '' ?>" href="news.php?category=<?= urlencode($category) ?>"><?= h($category) ?> <span><?= h((string)$count) ?></span></a>
      <?php endforeach; ?>
    </div>

    <div class="container news-list reveal is-visible">
      <?php foreach ($filteredNews as $item): ?>
        <article class="news-card cyber-clip-lg">
          <div class="news-meta">
            <span class="news-category"><?= h($item['category']) ?></span>
            <span class="news-date"><?= h($item['date']) ?></span>
          </div>
          <h2><?= h($item['title']) ?></h2>
          <p><?= h($item['excerpt']) ?></p>
          <a href="news_detail.php?id=<?= urlencode($item['id']) ?>" class="card-link">READ DETAIL <span aria-hidden="true">›</span></a>
        </article>
      <?php endforeach; ?>
      <?php if (!$filteredNews): ?>
        <div class="empty-state cyber-clip">
          <strong>該当するNEWSはまだありません。</strong>
          <p>カテゴリ条件を変更するか、後日改めてご確認ください。</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php render_footer(); ?>
