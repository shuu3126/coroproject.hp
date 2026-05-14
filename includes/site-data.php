<?php
$siteName = 'CORO PROJECT';
$basePath = '.';

$divisions = [
    [
        'slug' => 'business',
        'title' => 'Business Matching',
        'title_jp' => '案件仲介',
        'desc' => 'VTuberの個性と企業の目的を照合し、PR配信・出演・SNS施策まで実行しやすい形に整えます。',
        'class' => 'is-cyan',
        'num' => '01',
        'summary' => '企業担当者向けに、PR配信、イベント出演、SNS施策、アンバサダー提案などを支援。目的整理、候補提案、条件調整、制作物確認、当日進行まで、案件が止まりやすい部分をまとめて伴走します。'
    ],
    [
        'slug' => 'creative',
        'title' => 'Creative Support',
        'title_jp' => 'クリエイティブ支援',
        'desc' => '活動の世界観を崩さずに、ビジュアル・動画・配信素材など必要な制作物を整えます。',
        'class' => 'is-pink',
        'num' => '02',
        'summary' => '立ち絵、キービジュアル、サムネイル、MV、配信画面、ロゴなど、VTuber活動に必要な制作物の相談窓口として機能します。依頼内容の言語化、クリエイター選定、進行管理、納品前確認まで支援します。'
    ],
    [
        'slug' => 'production',
        'title' => 'Production',
        'title_jp' => 'VTuber事務所',
        'desc' => 'タレントの個性・生活リズム・成長段階に合わせて、長く続けられる活動環境を設計します。',
        'class' => 'is-indigo',
        'num' => '03',
        'summary' => '所属タレントの活動支援、マネジメント、プロモーションを担い、配信・企画・案件・制作を無理なく接続します。短期的な話題化だけでなく、継続して応援される状態づくりを重視します。'
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
if ($dbNewsItems !== null) {
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

function load_news_items_from_database(): ?array {
    $pdo = site_database_connection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->query("
            SELECT id, title, date, tag, thumb, excerpt, content, content_json, url
            FROM news
            WHERE is_published = 1
              AND (targets IS NULL OR targets = '' OR FIND_IN_SET('main', targets))
            ORDER BY sort_order ASC, date DESC, id DESC
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
