<?php

function renderHead(string $title = '職人管理システム'): void {
    echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} | タミヤホーム</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { padding-bottom: 64px; }
    @media (min-width: 768px) {
      body { padding-bottom: 0; padding-left: 220px; }
    }

    /* ページフェードイン */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    main { animation: fadeIn 0.25s ease both; }

    /* カードホバー */
    .rounded-xl:hover, .rounded-lg:hover { transition: box-shadow 0.15s ease; }

    /* ボタン押下 */
    button[type="submit"], a.bg-blue-600, a.bg-green-600,
    a.bg-indigo-600, a.bg-gray-800, a.bg-zinc-900 {
      transition: transform 0.1s ease, opacity 0.1s ease;
    }
    button[type="submit"]:active, a.bg-blue-600:active,
    a.bg-green-600:active, a.bg-indigo-600:active {
      transform: scale(0.97); opacity: 0.85;
    }

    /* サイドバーナビリンク・テーブル行ホバー */
    nav a { transition: background 0.15s ease, color 0.15s ease; }
    tbody tr { transition: background 0.1s ease; }
    tbody tr:hover td { background: #f4f4f5; }

    /* ローディング画面 */
    #loading-overlay {
      position: fixed; inset: 0; z-index: 9999;
      background: #ffffff;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      transition: opacity 0.4s ease;
    }
    .house-path {
      stroke-dasharray: 80;
      stroke-dashoffset: 80;
      animation: draw-house 2s ease-in-out infinite;
    }
    @keyframes draw-house {
      0%   { stroke-dashoffset: 80;  fill: transparent; }
      40%  { stroke-dashoffset: 0;   fill: transparent; }
      60%  { stroke-dashoffset: 0;   fill: rgba(30,58,95,0.08); }
      100% { stroke-dashoffset: -80; fill: transparent; }
    }
    .loading-dot { display: inline-block; animation: bounce-dot 1.4s infinite; }
    .loading-dot:nth-child(2) { animation-delay: 0.2s; }
    .loading-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce-dot {
      0%, 80%, 100% { transform: translateY(0); }
      40%           { transform: translateY(-4px); }
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

  <!-- ローディングオーバーレイ -->
  <div id="loading-overlay">
    <svg viewBox="0 0 24 24" style="width:96px;height:96px;stroke:#1e3a5f;stroke-width:1.5;fill:transparent;stroke-linecap:round;stroke-linejoin:round;filter:drop-shadow(0 0 6px rgba(30,58,95,0.2))">
      <path class="house-path" d="M4 21 V9 L12 3 L20 9 V21 H14 V14 H10 V21 Z"/>
    </svg>
    <div style="margin-top:2rem;text-align:center;">
      <p style="color:#1e3a5f;font-size:1.1rem;letter-spacing:0.2em;font-family:'Noto Sans JP',sans-serif;">
        読み込み中<span class="loading-dot">.</span><span class="loading-dot">.</span><span class="loading-dot">.</span>
      </p>
      <p style="color:#f97316;font-size:0.75rem;margin-top:0.5rem;letter-spacing:0.25em;opacity:0.8;">TAMIYA HOME</p>
    </div>
  </div>
  <script>
    var _loadStart = Date.now();
    window.addEventListener('load', function() {
      var el = document.getElementById('loading-overlay');
      var elapsed = Date.now() - _loadStart;
      var wait = Math.max(0, 1500 - elapsed);
      setTimeout(function() {
        el.style.opacity = '0';
        setTimeout(function() { el.style.display = 'none'; }, 400);
      }, wait);
    });
  </script>
HTML;
}

function renderHeader(string $title): void {
    $user = currentUser();
    $name = htmlspecialchars($user['name'] ?? '');
    echo <<<HTML
<header class="bg-white border-b border-gray-200 sticky top-0 z-10">
  <div class="flex items-center justify-between px-4 py-3 md:px-8">
    <h1 class="text-base font-bold text-gray-800">{$title}</h1>
    <div class="flex items-center gap-3 text-sm text-gray-400">
      <span class="hidden md:inline">{$name}</span>
      <a href="/tamiya-home/logout.php" class="text-sm text-red-400 hover:text-red-600">ログアウト</a>
    </div>
  </div>
</header>
HTML;
}

function job_badge(string $job_type): string {
    $colors = [
        '解体' => 'bg-red-100 text-red-800',
        '鍛冶' => 'bg-orange-100 text-orange-800',
        '大工' => 'bg-amber-100 text-amber-800',
        '電気' => 'bg-yellow-100 text-yellow-800',
        '水道' => 'bg-blue-100 text-blue-800',
        '内装' => 'bg-purple-100 text-purple-800',
        'その他' => 'bg-gray-100 text-gray-700',
    ];
    $cls = $colors[$job_type] ?? 'bg-gray-100 text-gray-700';
    return '<span class="text-xs px-2 py-0.5 rounded font-medium ' . $cls . '">' . htmlspecialchars($job_type) . '</span>';
}

function qualification_expiry_badge($expiry_date): string {
    if (!$expiry_date) {
        return '<span class="text-xs px-2 py-0.5 rounded font-medium bg-gray-100 text-gray-600">無期限</span>';
    }

    $today = date('Y-m-d');
    $days = (int)floor((strtotime($expiry_date) - strtotime($today)) / 86400);

    if ($expiry_date < $today) {
        $cls = 'bg-red-100 text-red-800';
        $label = '期限切れ';
    } elseif ($days <= 60) {
        $cls = 'bg-orange-100 text-orange-800';
        $label = '残' . $days . '日';
    } else {
        $cls = 'bg-green-100 text-green-800';
        $label = $expiry_date . ' まで';
    }

    return '<span class="text-xs px-2 py-0.5 rounded font-medium ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function renderBottomNav(string $current = ''): void {
    $nav = [
        ['href' => '/tamiya-home/dashboard.php',               'label' => 'ホーム',   'key' => 'dashboard'],
        ['href' => '/tamiya-home/pages/craftsmen/index.php',   'label' => '職人',     'key' => 'craftsmen'],
        ['href' => '/tamiya-home/pages/sites/index.php',       'label' => '現場',     'key' => 'sites'],
        ['href' => '/tamiya-home/pages/assignments/index.php', 'label' => 'アサイン', 'key' => 'assignments'],
    ];

    $desktop_nav = $nav;
    $desktop_nav[] = ['href' => '/tamiya-home/pages/assignments/calendar.php', 'label' => 'カレンダー', 'key' => 'calendar'];

    $admin_nav = [
        ['href' => '/tamiya-home/pages/qualifications/index.php', 'label' => '資格管理', 'key' => 'qualifications'],
        ['href' => '/tamiya-home/pages/export/index.php', 'label' => 'Excel出力', 'key' => 'export'],
        ['href' => '/tamiya-home/pages/users/index.php',  'label' => 'ユーザー',  'key' => 'users'],
    ];

    // ── デスクトップ: 左サイドバー ──
    echo '<nav class="hidden md:flex flex-col fixed left-0 top-0 h-full w-[220px] bg-white border-r border-gray-200 z-20">';
    echo '<div class="px-5 py-5 border-b border-gray-100">';
    echo '<div class="text-base font-bold text-gray-800">タミヤホーム</div>';
    echo '<div class="text-xs text-gray-400 mt-0.5">職人現場管理システム</div>';
    echo '</div>';
    echo '<div class="flex flex-col gap-0.5 p-3 flex-1 overflow-y-auto">';

    foreach ($desktop_nav as $item) {
        $active = ($current === $item['key'])
            ? 'bg-blue-50 text-blue-700 font-semibold'
            : 'text-gray-600 hover:bg-gray-50';
        echo '<a href="' . $item['href'] . '" class="px-3 py-2.5 rounded-lg text-sm ' . $active . '">' . $item['label'] . '</a>';
    }

    if (isAdmin()) {
        echo '<div class="mt-3 mb-1 px-3 text-xs text-gray-400 font-medium uppercase tracking-wide">管理</div>';
        foreach ($admin_nav as $item) {
            $active = ($current === $item['key'])
                ? 'bg-blue-50 text-blue-700 font-semibold'
                : 'text-gray-600 hover:bg-gray-50';
            echo '<a href="' . $item['href'] . '" class="px-3 py-2.5 rounded-lg text-sm ' . $active . '">' . $item['label'] . '</a>';
        }
    }

    echo '</div>';

    $user = currentUser();
    $name = htmlspecialchars($user['name'] ?? '');
    $role = ($user['role'] ?? '') === 'admin' ? '管理者' : '現場監督';
    echo '<div class="border-t border-gray-100 px-4 py-4">';
    echo '<div class="text-sm font-medium text-gray-700">' . $name . '</div>';
    echo '<div class="text-xs text-gray-400 mb-3">' . $role . '</div>';
    echo '<a href="/tamiya-home/logout.php" class="block text-center text-xs text-red-400 hover:text-red-500 border border-red-200 rounded-lg py-1.5">ログアウト</a>';
    echo '</div>';
    echo '</nav>';

    // ── モバイル: ボトムナビ ──
    $icons = [
        'dashboard'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        'craftsmen'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'sites'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'assignments' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    ];

    echo '<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-20">';
    echo '<div class="flex">';
    foreach ($nav as $item) {
        $isActive = ($current === $item['key']);
        $color    = $isActive ? 'text-blue-600' : 'text-gray-400';
        $bar      = $isActive ? '<span class="absolute top-0 left-1/2 -translate-x-1/2 w-6 h-0.5 bg-blue-600 rounded-full"></span>' : '';
        $icon     = $icons[$item['key']] ?? '';
        echo '<a href="' . $item['href'] . '" class="relative flex flex-col items-center justify-center flex-1 py-3 gap-1 ' . $color . '">';
        echo $bar;
        echo $icon;
        echo '<span class="text-xs leading-none">' . $item['label'] . '</span>';
        echo '</a>';
    }
    echo '</div></nav>';
}

function renderFoot(): void {
    echo '</body></html>';
}
