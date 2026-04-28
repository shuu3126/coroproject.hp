<?php
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
    unset($_SESSION['flash']);
    return $flash;
}

function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function public_asset_url($path) {
    global $publicUrlRoot;

    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path) || preg_match('#^(data|mailto|tel):#i', $path) || strpos($path, '#') === 0) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    while (strpos($path, './') === 0) {
        $path = substr($path, 2);
    }
    while (strpos($path, '../') === 0) {
        $path = substr($path, 3);
    }
    $path = ltrim($path, '/');

    foreach (['coroproject_jp/production/', 'production/', 'coroproject_jp/'] as $oldPrefix) {
        if (strpos($path, $oldPrefix) === 0) {
            $path = substr($path, strlen($oldPrefix));
            break;
        }
    }

    $publicPrefix = trim((string)$publicUrlRoot, '/');
    if ($publicPrefix !== '' && strpos($path, $publicPrefix . '/') === 0) {
        return '/' . ltrim($path, '/');
    }

    if ($publicPrefix !== '' && strpos($path, $publicPrefix . '/') !== 0) {
        $path = $publicPrefix . '/' . $path;
    }

    return '/' . ltrim($path, '/');
}

function checked($condition) {
    return $condition ? 'checked' : '';
}

function selected($value, $expected) {
    return (string)$value === (string)$expected ? 'selected' : '';
}

function parse_lines_to_array($text) {
    $text = trim((string)$text);
    if ($text === '') {
        return [];
    }
    $lines = preg_split('/\R/u', $text);
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function parse_text_lines_to_json($text) {
    return json_encode(parse_lines_to_array($text), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function lines_from_json($json) {
    $decoded = json_decode((string)$json, true);
    if (!is_array($decoded)) {
        return '';
    }
    $lines = [];
    foreach ($decoded as $line) {
        if (is_array($line)) {
            continue;
        }
        $line = trim((string)$line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    return implode("\n", $lines);
}

function news_content_json_from_text($text) {
    return parse_text_lines_to_json($text);
}

function news_content_text_from_json($json) {
    return lines_from_json($json);
}

function format_datetime($value) {
    if (!$value) return '';
    try {
        return (new DateTime($value))->format('Y-m-d H:i');
    } catch (Exception $e) {
        return (string)$value;
    }
}

function format_money($amount, $decimals = 0) {
    return number_format((float)$amount, $decimals);
}

function normalize_file_stem($value, $fallback = 'file') {
    $value = trim((string)$value);
    $value = preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', $value);
    $value = trim((string)$value, '-_');
    $value = strtolower(substr((string)$value, 0, 80));
    return $value !== '' ? $value : $fallback;
}

function parse_pipe_lines($text, $leftKey = 'label', $rightKey = 'url') {
    $rows = [];
    foreach (parse_lines_to_array($text) as $line) {
        $parts = explode('|', $line, 2);
        $left = trim(isset($parts[0]) ? $parts[0] : '');
        $right = trim(isset($parts[1]) ? $parts[1] : '');
        if ($left === '' && $right === '') continue;
        $rows[] = [$leftKey => $left, $rightKey => $right];
    }
    return $rows;
}

function pipe_lines_from_rows($rows, $leftKey = 'label', $rightKey = 'url') {
    $lines = [];
    foreach ($rows as $row) {
        $left = trim((string)(isset($row[$leftKey]) ? $row[$leftKey] : ''));
        $right = trim((string)(isset($row[$rightKey]) ? $row[$rightKey] : ''));
        if ($left === '' && $right === '') continue;
        $lines[] = $left . '|' . $right;
    }
    return implode("\n", $lines);
}

function parse_detail_lines($text) {
    $details = [];
    foreach (parse_lines_to_array($text) as $line) {
        if (strpos($line, '|') === false) continue;
        list($desc, $amount) = array_map('trim', explode('|', $line, 2));
        if ($desc === '') continue;
        $amountVal = (float)preg_replace('/[^0-9.\-]/', '', $amount);
        if ($amountVal <= 0) continue;
        $details[] = ['desc' => $desc, 'amount' => $amountVal];
    }
    return $details;
}

function status_badge_class($status) {
    $status = strtolower((string)$status);
    switch ($status) {
        case 'replied':
        case 'closed':
        case 'published':
        case 'active':
        case 'paid':
        case 'receipt_issued':
        case 'success':
        case '入金済':
        case '領収書発行済':
        case '公開':
            return 'success';
        case 'new':
        case 'in_progress':
        case 'warning':
        case 'draft':
        case 'issued':
        case '発行済':
        case '非公開':
            return 'warning';
        case 'error':
        case 'deleted':
        case 'inactive':
        case 'danger':
            return 'danger';
        default:
            return 'muted';
    }
}

function invoice_status_label($status) {
    switch ((string)$status) {
        case 'issued': return '発行済';
        case 'paid': return '入金済';
        case 'receipt_issued': return '領収書発行済';
        default: return (string)$status;
    }
}

function talent_status_is_active($status) {
    $status = mb_strtolower(trim((string)$status));
    $inactiveWords = ['卒業', 'inactive', 'retired', 'archived'];
    return !in_array($status, $inactiveWords, true);
}

function start_page($title, $description = '') {
    global $page_title, $page_description, $baseUrl, $config, $publicUrlRoot;
    $page_title = $title;
    $page_description = $description;
    require __DIR__ . '/_header.php';
}

function end_page() {
    require __DIR__ . '/_footer.php';
}
