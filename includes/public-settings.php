<?php

function coro_public_settings_defaults() {
    return [
        'public_social_x_url' => 'https://x.com/CoroProjectJP',
        'public_social_mail_address' => 'info@coroproject.jp',
    ];
}

function coro_public_settings_db() {
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
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $candidate['host'], $candidate['name']);
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

function coro_public_settings() {
    $settings = coro_public_settings_defaults();
    $pdo = coro_public_settings_db();
    if (!$pdo) {
        return $settings;
    }

    try {
        $keys = array_keys($settings);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = (string)$row['setting_value'];
        }
    } catch (Throwable $e) {
    }

    return $settings;
}

function coro_public_mail_address($settings = null) {
    $settings = $settings ?: coro_public_settings();
    $mail = trim((string)($settings['public_social_mail_address'] ?? ''));
    if ($mail === '') {
        $mail = trim((string)($settings['office_email'] ?? ''));
    }
    return $mail !== '' ? $mail : 'info@coroproject.jp';
}

function coro_public_social_links($settings = null) {
    $settings = $settings ?: coro_public_settings();
    $links = [];

    $xUrl = trim((string)($settings['public_social_x_url'] ?? ''));
    if ($xUrl !== '') {
        $links[] = [
            'key' => 'x',
            'label' => 'X',
            'icon' => '𝕏',
            'url' => $xUrl,
            'external' => true,
        ];
    }

    $mail = coro_public_mail_address($settings);
    if ($mail !== '') {
        $links[] = [
            'key' => 'mail',
            'label' => 'Mail',
            'icon' => '✉',
            'url' => 'mailto:' . $mail,
            'external' => false,
        ];
    }

    return $links;
}

function coro_public_render_social_list() {
    foreach (coro_public_social_links() as $link) {
        $target = !empty($link['external']) ? ' target="_blank" rel="noopener"' : '';
        echo '<li><a href="' . htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') . '"' . $target . '>'
            . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</a></li>' . "\n";
    }
}

function coro_public_render_social_icons() {
    foreach (coro_public_social_links() as $link) {
        $target = !empty($link['external']) ? ' target="_blank" rel="noopener"' : '';
        echo '<a href="' . htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') . '" aria-label="'
            . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '"' . $target . '>'
            . htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') . '</a>' . "\n";
    }
}
