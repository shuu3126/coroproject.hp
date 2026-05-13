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

function admin_csrf_token() {
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function admin_verify_csrf($token) {
    return isset($_SESSION['admin_csrf']) && hash_equals($_SESSION['admin_csrf'], (string)$token);
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

function admin_table_has_column($pdo, $table, $column) {
    if (!($pdo instanceof PDO)) {
        return false;
    }

    static $cache = [];
    $key = (string)$table . '.' . (string)$column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute([(string)$table, (string)$column]);
        $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
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
        case 'published':
        case 'active':
        case 'paid':
        case 'receipt_issued':
        case 'success':
        case '入金済':
        case '領収書発行済':
        case '公開':
            return 'success';
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

function fetch_ogp_image_url($pageUrl) {
    $pageUrl = trim((string)$pageUrl);
    if ($pageUrl === '' || strpos($pageUrl, 'http') !== 0) return null;

    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'user_agent' => 'Mozilla/5.0 (compatible; CoroBot/1.0)',
            'follow_location' => true,
            'max_redirects' => 3,
        ],
    ]);

    $html = @file_get_contents($pageUrl, false, $context);
    if (!is_string($html) || $html === '') return null;

    // property="og:image" content="..." と content="..." property="og:image" の両パターンに対応
    $patterns = [
        '/<meta\s[^>]*property=["\']og:image["\'][^>]*content=["\'](https?:[^"\']+)["\']/si',
        '/<meta\s[^>]*content=["\'](https?:[^"\']+)["\']\s[^>]*property=["\']og:image["\']/si',
    ];

    foreach ($patterns as $pat) {
        if (preg_match($pat, $html, $m)) {
            return htmlspecialchars_decode(trim($m[1]));
        }
    }

    return null;
}

function start_page($title, $description = '') {
    global $page_title, $page_description, $baseUrl, $config, $pdo;
    $page_title = $title;
    $page_description = $description;
    require __DIR__ . '/_header.php';
}

function end_page() {
    require __DIR__ . '/_footer.php';
}
