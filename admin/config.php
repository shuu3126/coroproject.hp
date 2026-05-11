<?php
$projectRoot = dirname(__DIR__);
$productionRoot = $projectRoot . '/production';

return [
    'app' => [
        'base_url' => null,
        'timezone' => 'Asia/Tokyo',
        'site_title' => 'CORO PROJECT 管理画面',
        'session_name' => 'coro_admin_session',
        'session_idle_timeout' => 60 * 60,
    ],
    'paths' => [
        'project_root' => $projectRoot,
        'production_root' => $productionRoot,
        'db_file' => $productionRoot . '/db.php',
    ],
    'uploads' => [
        'news_public_dir' => $productionRoot . '/images/news',
        'news_public_prefix' => 'production/images/news',
        'talent_public_dir' => $productionRoot . '/images/talents',
        'talent_public_prefix' => 'production/images/talents',
        'accounting_root' => $productionRoot . '/uploads/accounting',
        'accounting_prefix' => 'production/uploads/accounting',
    ],
    'pdf' => [
        'font_path' => __DIR__ . '/resources/fonts/ipaexg.ttf',
        'stamp_path' => __DIR__ . '/resources/stamps/hanko.png',
        'render_width' => 1654,
        'render_height' => 2339,
    ],
];
