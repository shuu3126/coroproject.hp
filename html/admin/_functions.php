<?php
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function redirect_to(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function checked(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function selected($value, $expected): string
{
    return (string)$value === (string)$expected ? 'selected' : '';
}

function parse_lines_to_array(string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return [];
    }
    $lines = preg_split('/\R/u', $text);
    return array_values(array_filter(array_map('trim', $lines), fn($v) => $v !== ''));
}

function parse_text_lines_to_json(string $text): string
{
    return json_encode(parse_lines_to_array($text), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function lines_from_json(?string $json): string
{
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

function format_datetime(?string $value): string
{
    if (!$value) return '';
    try {
        return (new DateTime($value))->format('Y-m-d H:i');
    } catch (Throwable $e) {
        return (string)$value;
    }
}

function format_money($amount): string
{
    return number_format((float)$amount, 0);
}

function status_badge_class(string $status): string
{
    $status = strtolower($status);
    return match ($status) {
        'published', 'active', 'paid', 'receipt_issued', 'success' => 'success',
        'warning', 'draft', 'issued' => 'warning',
        'error', 'deleted', 'inactive' => 'danger',
        default => 'muted',
    };
}

function normalize_file_stem(string $value, string $fallback = 'file'): string
{
    $value = preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', trim($value));
    $value = trim((string)$value, '-_');
    $value = strtolower(substr((string)$value, 0, 80));
    return $value !== '' ? $value : $fallback;
}

function parse_pipe_lines(string $text, string $leftKey = 'label', string $rightKey = 'url'): array
{
    $rows = [];
    foreach (parse_lines_to_array($text) as $line) {
        $parts = explode('|', $line, 2);
        $left = trim($parts[0] ?? '');
        $right = trim($parts[1] ?? '');
        if ($left === '' && $right === '') continue;
        $rows[] = [$leftKey => $left, $rightKey => $right];
    }
    return $rows;
}

function pipe_lines_from_rows(array $rows, string $leftKey = 'label', string $rightKey = 'url'): string
{
    $lines = [];
    foreach ($rows as $row) {
        $left = trim((string)($row[$leftKey] ?? ''));
        $right = trim((string)($row[$rightKey] ?? ''));
        if ($left === '' && $right === '') continue;
        $lines[] = $left . '|' . $right;
    }
    return implode("\n", $lines);
}

function parse_detail_lines(string $text): array
{
    $details = [];
    foreach (parse_lines_to_array($text) as $line) {
        if (!str_contains($line, '|')) continue;
        [$desc, $amount] = array_map('trim', explode('|', $line, 2));
        if ($desc === '') continue;
        $amountVal = (float)preg_replace('/[^0-9.\-]/', '', $amount);
        if ($amountVal <= 0) continue;
        $details[] = ['desc' => $desc, 'amount' => $amountVal];
    }
    return $details;
}

function start_page(string $title, string $description = ''): void
{
    global $page_title, $page_description;
    $page_title = $title;
    $page_description = $description;
    require __DIR__ . '/_header.php';
}

function end_page(): void
{
    require __DIR__ . '/_footer.php';
}
