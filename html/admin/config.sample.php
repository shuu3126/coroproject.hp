<?php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'db_coroproject_1',
        'user' => 'db_coroproject',
        'pass' => 'YOUR_DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/html/admin',
        'timezone' => 'Asia/Tokyo',
        'site_title' => 'CORO PROJECT 管理画面',
        'session_name' => 'coro_admin_session',
    ],
    'uploads' => [
        // news / talents は公開サイトと同じ images 配下へ保存
        'news_public_dir' => dirname(dirname(__DIR__)) . '/images/news',
        'news_public_prefix' => 'images/news',
        'talent_public_dir' => dirname(dirname(__DIR__)) . '/images/talents',
        'talent_public_prefix' => 'images/talents',

        // 会計系ファイル保存先
        'accounting_root' => dirname(dirname(__DIR__)) . '/uploads/accounting',
        'accounting_prefix' => 'uploads/accounting',
    ],
];
