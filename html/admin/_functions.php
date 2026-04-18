<?php
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function set_flash( $type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    $flash = (isset($_SESSION['flash']) ? $_SESSION['flash'] : null);
    unset($_SESSION['flash']);
    return $flash;
}

function redirect_to( $path) {
    header('Location: ' . $path);
    exit;
}

function checked( $condition) {
    return $condition ? 'checked' : '';
}

function selected($value, $expected) {
    return (string)$value === (string)$expected ? 'selected' : '';
}

function parse_lines_to_array( $text) {
    $text = trim($text);
    if ($text === '') {
        return [];
    }
    $lines = preg_split('/\R/u', $text);
    return array_values(array_filter(array_map('trim', $lines), function ($v) { return $v !== ''; }));
}

function parse_text_lines_to_json( $text) {
    return json_encode(parse_lines_to_array($text), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function lines_from_json( $json) {
    $decoded = json_decode((string)$json, true);
    if (!is_array($decoded)) {
        return '';
    }
    $out = [];
    foreach ($decoded as $line) {
        $line = trim((string)$line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return implode("\n", $out);
}

function format_datetime( $value) {
    if (!$value) return '';
    try {
        return (new DateTime($value))->format('Y-m-d H:i');
    } catch (Exception $e) {
        return (string)$value;
    }
}

function format_money($amount) {
    return number_format((float)$amount, 0);
}

function status_badge_class( $status) {
    $status = strtolower($status);
    switch ($status) {
        case 'published':
        case 'active':
        case 'paid':
        case 'receipt_issued':
        case 'success':
            return 'success';
        case 'warning':
        case 'draft':
        case 'issued':
            return 'warning';
        case 'error':
        case 'deleted':
        case 'inactive':
            return 'danger';
        default:
            return 'muted';
    }
}

function normalize_file_stem( $value, $fallback = 'file') {
    $value = preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', trim($value));
    $value = trim((string)$value, '-_');
    $value = strtolower(substr((string)$value, 0, 80));
    return $value !== '' ? $value : $fallback;
}

function parse_pipe_lines( $text, $leftKey = 'label', $rightKey = 'url') {
    $rows = [];
    foreach (parse_lines_to_array($text) as $line) {
        $parts = explode('|', $line, 2);
        $left = trim((isset($parts[0]) ? $parts[0] : ''));
        $right = trim((isset($parts[1]) ? $parts[1] : ''));
        if ($left === '' && $right === '') continue;
        $rows[] = [$leftKey => $left, $rightKey => $right];
    }
    return $rows;
}

function pipe_lines_from_rows( $rows, $leftKey = 'label', $rightKey = 'url') {
    $lines = [];
    foreach ($rows as $row) {
        $left = trim((string)((isset($row[$leftKey]) ? $row[$leftKey] : '')));
        $right = trim((string)((isset($row[$rightKey]) ? $row[$rightKey] : '')));
        if ($left === '' && $right === '') continue;
        $lines[] = $left . '|' . $right;
    }
    return implode("\n", $lines);
}

function parse_detail_lines( $text) {
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

function start_page( $title, $description = '') {
    global $page_title, $page_description;
    $page_title = $title;
    $page_description = $description;
    require __DIR__ . '/_header.php';
}

function end_page() {
    require __DIR__ . '/_footer.php';
}
