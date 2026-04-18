<?php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'db_coroproject_1',
        'user' => 'db_coroproject',
        'pass' => 'FwMMCTUO',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/coroproject_jp/html/admin',
        'timezone' => 'Asia/Tokyo',
        'site_title' => 'CORO PROJECT 管理画面',
        'session_name' => 'coro_admin_session',
    ],
    'uploads' => [
        'news_public_dir' => dirname(dirname(__DIR__)) . '/images/news',
        'news_public_prefix' => 'images/news',
        'talent_public_dir' => dirname(dirname(__DIR__)) . '/images/talents',
        'talent_public_prefix' => 'images/talents',
        'accounting_root' => dirname(dirname(__DIR__)) . '/uploads/accounting',
        'accounting_prefix' => 'uploads/accounting',
    ],
];