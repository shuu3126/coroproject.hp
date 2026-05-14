<?php
require_once __DIR__ . '/includes/layout.php';
$filter = $_GET['category'] ?? 'all';
$talentFilter = trim((string)($_GET['talent'] ?? 'all'));
$counts = news_category_count($newsItems);
$talentOptions = news_talent_filter_options($newsItems);
$filteredNews = array_values(array_filter($newsItems, static function ($item) use ($filter) {
    return $filter === 'all' || $item['category'] === $filter;
}));
$filteredNews = array_values(array_filter($filteredNews, static function ($item) use ($talentFilter) {
    return $talentFilter === 'all' || (string)($item['talent_id'] ?? '') === $talentFilter;
}));
render_head('NEWS', 'CORO PROJECTのお知らせ、所属タレント情報、募集情報、事業更新を掲載しています。', [
    'canonical' => 'https://coroproject.jp/news.php',
    'robots'    => 'index, follow',
    'og_type'   => 'website',
    'og_image'  => 'https://coroproject.jp/images/ogp.png',
]);
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
        <p class="sub-lead">お知らせ、所属タレント関連、募集情報、事業更新を総合ポータル上で整理し、各ページや各事業部への導線として機能させます。活動の変化や募集の開始、相談窓口の更新もここから確認できます。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">TOTAL <?= count($newsItems) ?> ITEMS</span>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container filter-bar reveal is-visible">
      <a class="filter-pill <?= $filter === 'all' && $talentFilter === 'all' ? 'is-current' : '' ?>" href="news.php">ALL <span><?= count($newsItems) ?></span></a>
      <?php foreach ($counts as $category => $count): ?>
        <a class="filter-pill <?= $filter === $category ? 'is-current' : '' ?>" href="news.php?category=<?= urlencode($category) ?><?= $talentFilter !== 'all' ? '&talent=' . urlencode($talentFilter) : '' ?>"><?= h($category) ?> <span><?= h((string)$count) ?></span></a>
      <?php endforeach; ?>
    </div>
    <?php if ($talentOptions): ?>
      <div class="container filter-bar news-talent-filter reveal is-visible">
        <a class="filter-pill <?= $talentFilter === 'all' ? 'is-current' : '' ?>" href="news.php<?= $filter !== 'all' ? '?category=' . urlencode($filter) : '' ?>">ALL TALENTS</a>
        <?php foreach ($talentOptions as $talentId => $talentName): ?>
          <a class="filter-pill <?= $talentFilter === $talentId ? 'is-current' : '' ?>" href="news.php?<?= http_build_query(array_filter(['category' => $filter !== 'all' ? $filter : null, 'talent' => $talentId])) ?>"><?= h($talentName) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="container news-guide-grid reveal is-visible">
      <article class="matrix-card cyber-clip">
        <h3>お知らせ</h3>
        <p>サイト更新、窓口変更、事業全体に関わる案内を掲載します。</p>
      </article>
      <article class="matrix-card cyber-clip">
        <h3>募集</h3>
        <p>制作パートナー、企画参加、活動に関わる募集情報を整理します。</p>
      </article>
      <article class="matrix-card cyber-clip">
        <h3>所属タレント</h3>
        <p>Production側の更新や所属タレントに関する告知へつなぎます。</p>
      </article>
    </div>

    <div class="container news-list reveal is-visible">
      <?php foreach ($filteredNews as $item): ?>
        <article class="news-card cyber-clip-lg">
          <div class="news-card-thumb" style="background-image:url('<?= h(news_thumb_url($item['thumb'] ?? '')) ?>')" aria-hidden="true"></div>
          <div class="news-meta">
            <span class="news-category"><?= h($item['category']) ?></span>
            <span class="news-date"><?= h($item['date']) ?></span>
            <?php if (!empty($item['talent_name'])): ?><span class="news-talent"><?= h($item['talent_name']) ?></span><?php endif; ?>
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
