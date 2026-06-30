<?php
$siteName = 'CORO PROJECT';
$basePath = '.';

$divisions = [
    [
        'slug'     => 'credit',
        'url'      => 'https://credit.coroproject.jp/',
        'title'    => 'CREDiT',
        'title_jp' => 'B2Bマッチング',
        'desc'     => 'VTuber・クリエイター・企業の三者をつなぐ業界特化B2Bプラットフォーム。案件打診から契約・請求まで一貫対応。',
        'class'    => 'is-cyan',
        'num'      => '01',
        'summary'  => '審査済みVTuber・クリエイターへの案件打診、条件フィルタ検索、クレジットツリー管理、契約書自動生成、インボイス対応請求書発行まで。企業担当者の案件打診が30分以内で完了する業界インフラ。',
    ],
    [
        'slug'     => 'production',
        'url'      => null,
        'title'    => 'Production',
        'title_jp' => 'VTuber事務所',
        'desc'     => '所属タレントの活動を全方位で支援。配信・企画・案件・制作を無理なく接続し、長く応援される環境を設計します。',
        'class'    => 'is-pink',
        'num'      => '02',
        'summary'  => 'タレントの個性・生活リズム・成長段階に合わせた活動設計。企業案件の接続、クリエイターとの制作連携、SNS運用支援まで、マネジメントを一括伴走します。',
    ],
];

$newsItems = [
    [
        'id' => '2026-01',
        'category' => 'お知らせ',
        'date' => '2026.04.22',
        'title' => 'CORO PROJECT 総合ポータルサイトを公開しました',
        'excerpt' => 'CORO PROJECTの総合ページを公開しました。活動支援、企業案件、制作相談を横断して確認できる入口として運用していきます。',
        'body' => [
            'CORO PROJECTの総合ポータルサイトを公開いたしました。VTuber事務所、案件仲介、クリエイティブ支援の3事業をわかりやすく案内し、それぞれの相談窓口へスムーズにつながる導線を整えています。',
            '総合ページでは、活動者・企業・クリエイターがどの入口から相談すればよいかを判断しやすいよう、事業の役割、対応できる相談、問い合わせ前に確認したい情報をまとめています。',
            '今後はお知らせ、募集情報、取り組み内容、更新情報などをこちらのNEWSページを通じて順次発信してまいります。'
        ]
    ],
    [
        'id' => '2026-02',
        'category' => '募集',
        'date' => '2026.04.20',
        'title' => 'クリエイター・制作パートナーの相談受付を開始しました',
        'excerpt' => 'イラスト、ロゴ、配信画面、MVなど、VTuber活動を支える制作パートナーとの連携体制を拡張しています。',
        'body' => [
            'CORO PROJECTでは、VTuber活動に必要な各種制作領域において、相談しやすい体制づくりを進めています。',
            '対象となる領域は、立ち絵、キービジュアル、ロゴ、配信画面、サムネイル、動画、MV、告知素材などです。単発制作だけでなく、活動や案件に合わせて継続的に相談できる体制を目指しています。',
            '案件単位のご相談だけでなく、継続的に連携できる制作パートナーの募集・ヒアリングも行っています。クリエイティブ支援の詳細は今後順次公開予定です。'
        ]
    ],
    [
        'id' => '2026-03',
        'category' => '所属タレント',
        'date' => '2026.04.18',
        'title' => '所属タレントの活動情報はProductionページで随時更新しています',
        'excerpt' => '所属タレントのビジュアル、プロフィール、各種リンク、活動情報についてはProductionページに集約して掲載します。',
        'body' => [
            '所属タレント情報は、Productionページ側で独立した導線として整理しています。',
            '総合ポータルでは最新動向の告知や募集情報を中心に掲載し、より詳しい活動内容やプロフィール、各種リンクについてはProductionページからご確認いただけます。',
            '企業案件や制作相談から所属タレントの情報を確認したい場合も、総合ポータルを経由してProductionページへ進めるよう導線を整備しています。'
        ]
    ],
    [
        'id' => '2026-04',
        'category' => 'お知らせ',
        'date' => '2026.04.15',
        'title' => '企業向け案件相談フローを整備しました',
        'excerpt' => '商品紹介、店舗紹介、イベント出演、SNS施策など、目的に応じた提案がしやすい相談フローを整備しています。',
        'body' => [
            '企業担当者の皆さまに向けて、案件のご相談から実施までの導線をよりわかりやすく整理しました。',
            '商品紹介、店舗紹介、イベント出演、SNS施策、アンバサダー起用など、目的に合わせて必要な確認項目が変わるため、初回相談では実施目的、希望時期、予算感、必要素材などを整理します。',
            '小規模案件からでもご相談いただけるよう、ヒアリング・整理・提案・進行管理までをまとめて支援できる体制を整えています。'
        ]
    ],
];

$dbNewsItems = load_news_items_from_database();
if (!empty($dbNewsItems)) {
    $newsItems = $dbNewsItems;
}

$contactTopics = [
    '企業案件・キャスティングのご相談',
    '広告・PR・タイアップのご相談',
    'イベント出演・企画のご相談',
    '制作・デザインのご相談',
    '動画・MV・配信素材のご相談',
    'VTuber所属・オーディションについて',
    'VTuber活動に関するご相談',
    'クリエイター参加・提携について',
    '取材・メディア掲載について',
    '営業・業務提携について',
    'その他',
];

function site_database_connection(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }

    $candidates = [];
    $envHost = getenv('CORO_DB_HOST');
    $envName = getenv('CORO_DB_NAME');
    $envUser = getenv('CORO_DB_USER');
    if ($envHost && $envName && $envUser) {
        $candidates[] = [
            'host' => $envHost,
            'name' => $envName,
            'user' => $envUser,
            'pass' => getenv('CORO_DB_PASS') ?: '',
        ];
    }

    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server'
        || (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $httpHost);
    if ($isLocal) {
        $candidates[] = [
            'host' => 'localhost',
            'name' => 'db_coroproject_1',
            'user' => 'root',
            'pass' => '',
        ];
    }

    $candidates[] = [
        'host' => 'localhost',
        'name' => 'db_coroproject_1',
        'user' => 'db_coroproject',
        'pass' => 'FwMMCTUO',
    ];

    foreach ($candidates as $candidate) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $candidate['host'],
                $candidate['name']
            );
            $pdo = new PDO($dsn, $candidate['user'], $candidate['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        } catch (Throwable $e) {
        }
    }

    $pdo = null;
    return null;
}

function site_table_has_column(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function load_news_items_from_database(): ?array {
    $pdo = site_database_connection();
    if (!$pdo) {
        return null;
    }

    try {
        $hasTalentId = site_table_has_column($pdo, 'news', 'talent_id');
        $hasTargets = site_table_has_column($pdo, 'news', 'targets');
        $selectTalent = $hasTalentId ? ', n.talent_id, t.name AS talent_name' : ', NULL AS talent_id, NULL AS talent_name';
        $joinTalent = $hasTalentId ? ' LEFT JOIN talents t ON t.id = n.talent_id' : '';
        $targetWhere = $hasTargets ? "AND (n.targets IS NULL OR n.targets = '' OR FIND_IN_SET('main', n.targets))" : '';
        $stmt = $pdo->query("
            SELECT n.id, n.title, n.date, n.tag, n.thumb, n.excerpt, n.content, n.content_json, n.url{$selectTalent}
            FROM news n
            {$joinTalent}
            WHERE n.is_published = 1
              {$targetWhere}
            ORDER BY n.sort_order ASC, n.date DESC, n.id DESC
        ");
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return null;
    }

    $items = [];
    foreach ($rows as $row) {
        $body = news_body_from_database_row($row);
        $excerpt = trim((string)($row['excerpt'] ?? ''));
        if (!$body && $excerpt !== '') {
            $body = [$excerpt];
        }

        $items[] = [
            'id' => (string)($row['id'] ?? ''),
            'category' => (string)(($row['tag'] ?? '') !== '' ? $row['tag'] : 'NEWS'),
            'date' => format_site_news_date((string)($row['date'] ?? '')),
            'title' => (string)($row['title'] ?? ''),
            'excerpt' => $excerpt,
            'body' => $body,
            'thumb' => (string)($row['thumb'] ?? ''),
            'url' => (string)($row['url'] ?? ''),
            'talent_id' => (string)($row['talent_id'] ?? ''),
            'talent_name' => (string)($row['talent_name'] ?? ''),
        ];
    }

    return $items;
}

function news_body_from_database_row(array $row): array {
    $contentJson = trim((string)($row['content_json'] ?? ''));
    if ($contentJson !== '') {
        $decoded = json_decode($contentJson, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded), static function ($line) {
                return trim($line) !== '';
            }));
        }
    }

    $content = trim((string)($row['content'] ?? ''));
    if ($content === '') {
        return [];
    }

    return array_values(array_filter(preg_split('/\R/u', $content) ?: [], static function ($line) {
        return trim((string)$line) !== '';
    }));
}

function format_site_news_date(string $date): string {
    $time = strtotime($date);
    return $time ? date('Y.m.d', $time) : $date;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function news_thumb_path(?string $thumb): string {
    $thumb = trim((string)$thumb);
    return $thumb !== '' ? $thumb : 'images/ogp.png';
}

function news_thumb_url(?string $thumb): string {
    $path = news_thumb_path($thumb);
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    return ltrim($path, '/');
}

function news_og_image_url(?string $thumb): string {
    $path = news_thumb_path($thumb);
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    return 'https://coroproject.jp/' . ltrim($path, '/');
}

function find_news_by_id(array $newsItems, string $id): ?array {
    foreach ($newsItems as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    return null;
}

function news_category_count(array $newsItems): array {
    $counts = [];
    foreach ($newsItems as $item) {
        $counts[$item['category']] = ($counts[$item['category']] ?? 0) + 1;
    }
    return $counts;
}

function news_talent_filter_options(array $newsItems): array {
    $options = [];
    foreach ($newsItems as $item) {
        $id = trim((string)($item['talent_id'] ?? ''));
        $name = trim((string)($item['talent_name'] ?? ''));
        if ($id !== '' && $name !== '') {
            $options[$id] = $name;
        }
    }
    natcasesort($options);
    return $options;
}
