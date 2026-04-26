<?php
$siteName = 'CORO PROJECT';
$basePath = '.';

$divisions = [
    [
        'slug' => 'business-matching',
        'title' => 'Business Matching',
        'title_jp' => '案件仲介',
        'desc' => 'VTuberと企業をつなぎ、最適なPR施策や出演機会をワンストップで提案します。',
        'class' => 'is-cyan',
        'num' => '01',
        'summary' => '企業担当者向けに、PR配信・イベント出演・SNS施策・アンバサダー提案などを支援。相談整理から進行管理まで一括で対応します。'
    ],
    [
        'slug' => 'creative-support',
        'title' => 'Creative Support',
        'title_jp' => 'クリエイティブ支援',
        'desc' => '信頼できるクリエイターと共に、活動に必要な制作体制を形にします。',
        'class' => 'is-pink',
        'num' => '02',
        'summary' => '立ち絵、キービジュアル、サムネイル、MV、配信画面、ロゴなど、VTuber活動に必要な制作物の相談窓口として機能します。'
    ],
    [
        'slug' => 'production',
        'title' => 'Production',
        'title_jp' => 'VTuber事務所',
        'desc' => '個性豊かな才能をマネジメントし、次世代のエンタメを提案します。',
        'class' => 'is-indigo',
        'num' => '03',
        'summary' => '所属タレントの活動支援、マネジメント、プロモーションを担い、長く活動を続けられる環境づくりを行います。'
    ],
];

$newsItems = [
    [
        'id' => '2026-01',
        'category' => 'お知らせ',
        'date' => '2026.04.22',
        'title' => 'CORO PROJECT 総合ポータルサイトを公開しました',
        'excerpt' => 'CORO PROJECTの総合ページを公開しました。各事業部への導線や最新情報を今後こちらから発信していきます。',
        'body' => [
            'CORO PROJECTの総合ポータルサイトを公開いたしました。VTuber事務所、案件仲介、クリエイティブ支援の3事業をわかりやすく案内し、それぞれの相談窓口へスムーズにつながる導線を整えています。',
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
            '総合ポータルでは最新動向の告知や募集情報を中心に掲載し、より詳しい活動内容やプロフィールについてはProductionページからご確認いただけます。'
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
            '小規模案件からでもご相談いただけるよう、ヒアリング・整理・提案・進行管理までをまとめて支援できる体制を整えています。'
        ]
    ],
];

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
