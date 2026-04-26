<?php
require_once __DIR__ . '/includes/layout.php';
$id = $_GET['id'] ?? '';
$item = find_news_by_id($newsItems, $id);
if (!$item) {
    http_response_code(404);
    $item = [
        'title' => 'NEWSが見つかりませんでした',
        'category' => 'ERROR',
        'date' => date('Y.m.d'),
        'body' => ['指定されたNEWSは存在しないか、現在公開されていません。', 'NEWS一覧へ戻って、別の記事をご確認ください。']
    ];
}
render_head($item['title'], $item['title']);
render_header('news');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container article-hero reveal is-visible">
      <div class="news-meta">
        <span class="news-category"><?= h($item['category']) ?></span>
        <span class="news-date"><?= h($item['date']) ?></span>
      </div>
      <h1 class="sub-title article-title"><?= h($item['title']) ?></h1>
      <a href="news.php" class="outline-button cyber-clip">BACK TO NEWS <span aria-hidden="true">›</span></a>
    </div>
  </section>

  <section class="content-section">
    <div class="container article-shell reveal is-visible">
      <?php foreach ($item['body'] as $paragraph): ?>
        <p><?= h($paragraph) ?></p>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php render_footer(); ?>
