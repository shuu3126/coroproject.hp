<?php
// import_news.php
require_once __DIR__ . '/db.php';

// news.json の場所（あなたの構成では data/news.json）
$jsonPath = __DIR__ . '/data/news.json';

if (!file_exists($jsonPath)) {
    exit('news.json が見つかりません: ' . $jsonPath);
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);

if (!is_array($data)) {
    exit('JSON のパースに失敗しました');
}

$sql = "REPLACE INTO news 
    (id, title, date, tag, thumb, excerpt, content_json, url, is_published, sort_order)
    VALUES
    (:id, :title, :date, :tag, :thumb, :excerpt, :content_json, :url, :is_published, :sort_order)";

$stmt = $pdo->prepare($sql);

foreach ($data as $index => $item) {
    $contentJson = json_encode($item['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt->execute([
        ':id'           => $item['id'],
        ':title'        => $item['title'],
        ':date'         => $item['date'],
        ':tag'          => $item['tag'],
        ':thumb'        => $item['thumb'],
        ':excerpt'      => $item['excerpt'],
        ':content_json' => $contentJson,
        ':url'          => $item['url'],
        ':is_published' => 1,
        ':sort_order'   => $index,
    ]);
}

echo 'インポート完了！！ニュースがDBに入りました。';
