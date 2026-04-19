<?php
return [
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
    'pdf' => [
        'font_path' => __DIR__ . '/resources/fonts/ipaexg.ttf',
        'stamp_path' => __DIR__ . '/resources/stamps/hanko.png',
        'render_width' => 1654,
        'render_height' => 2339,
    ],
];
