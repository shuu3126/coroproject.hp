<?php
require_once dirname(__DIR__) . '/includes/public-settings.php';

function production_project_base_url() {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($scriptName, '/production');

    if ($pos === false) {
        return '';
    }

    return rtrim(substr($scriptName, 0, $pos), '/');
}

function production_public_url($path) {
    $path = trim(str_replace('\\', '/', (string)$path));

    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    $path = ltrim($path, '/');

    if (strpos($path, 'coroproject_jp/images/') === 0) {
        $path = 'production/' . substr($path, strlen('coroproject_jp/'));
    } elseif (strpos($path, '../images/') === 0) {
        $path = 'production/' . substr($path, 3);
    } elseif (strpos($path, './images/') === 0) {
        $path = 'production/' . substr($path, 2);
    } elseif (strpos($path, 'images/') === 0) {
        $path = 'production/' . $path;
    }

    return rtrim(production_project_base_url(), '/') . '/' . $path;
}

function production_safe_external_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }

    if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url)) {
        return $url;
    }

    return '';
}
