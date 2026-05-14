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
render_head($item['title'], $item['title'] . ' | CORO PROJECTのニュース詳細ページです。', [
    'canonical' => $id !== '' ? 'https://coroproject.jp/news_detail.php?id=' . urlencode($id) : null,
    'robots'    => $id !== '' ? 'index, follow' : 'noindex, nofollow',
    'og_type'   => 'article',
    'og_image'  => news_og_image_url($item['thumb'] ?? ''),
]);
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
      <img class="article-image" src="<?= h(news_thumb_url($item['thumb'] ?? '')) ?>" alt="<?= h($item['title']) ?>">
      <?php foreach ($item['body'] as $paragraph): ?>
        <p><?= h($paragraph) ?></p>
      <?php endforeach; ?>
    </div>
    <div class="container article-follow reveal is-visible">
      <article class="route-card cyber-clip-lg">
        <span>NEXT ACTION</span>
        <h3>この記事に関する相談や確認</h3>
        <p>募集、案件、制作、所属タレントに関する内容は、総合問い合わせ窓口からご連絡ください。内容に応じて適切な担当へ接続します。</p>
        <a href="contact.php" class="card-link">CONTACT <span aria-hidden="true">›</span></a>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>RELATED</span>
        <h3>事業内容を確認する</h3>
        <p>Production、Business Matching、Creative Supportの役割を確認したい場合は、サービスページから各事業部へ進めます。</p>
        <a href="service.php" class="card-link">SERVICE <span aria-hidden="true">›</span></a>
      </article>
    </div>
  </section>
</main>
<?php render_footer(); ?>
