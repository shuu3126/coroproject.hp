<?php
function public_html_root_url() {
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptPath = (string)(parse_url($scriptName, PHP_URL_PATH) ?: '');
    $scriptDir = '/' . trim(dirname($scriptPath), '/');
    if ($scriptDir === '/.') {
        $scriptDir = '/html';
    }

    if (substr($scriptDir, -5) === '/html') {
        $root = substr($scriptDir, 0, -5);
    } else {
        $root = dirname($scriptDir);
    }

    $root = '/' . trim((string)$root, '/');
    return $root === '/' ? '' : $root;
}

function public_html_asset_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0 || strpos($path, '../') === 0 || strpos($path, './') === 0) {
        return $path;
    }

    $path = str_replace('\\', '/', ltrim($path, '/'));
    foreach (['coroproject_jp/'] as $oldPrefix) {
        if (strpos($path, $oldPrefix) === 0) {
            $path = substr($path, strlen($oldPrefix));
            break;
        }
    }

    $root = trim(public_html_root_url(), '/');
    if ($root !== '' && strpos($path, $root . '/') !== 0) {
        $path = $root . '/' . $path;
    }

    return '/' . ltrim($path, '/');
}
