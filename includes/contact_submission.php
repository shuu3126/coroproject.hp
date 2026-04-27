<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/production/db.php';
require_once __DIR__ . '/inquiries.php';
require_once __DIR__ . '/inquiry_mailer.php';

function contact_wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return stripos($accept, 'application/json') !== false || strtolower((string)$xhr) === 'xmlhttprequest';
}

function contact_json_out(int $code, array $payload): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function contact_fail(int $code, string $message): void
{
    if (contact_wants_json()) {
        contact_json_out($code, ['ok' => false, 'error' => $message]);
    }

    http_response_code($code);
    echo $message;
    exit;
}

function contact_payload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function sanitize_contact_return_to(string $value, string $fallback): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $value)) {
        return $fallback;
    }

    if (strpos($value, '//') === 0) {
        return $fallback;
    }

    return $value;
}

function contact_handle_request(array $options = []): void
{
    global $pdo;

    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
    header('X-Content-Type-Options: nosniff');

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        contact_fail(405, 'Method not allowed');
    }

    ensure_inquiries_schema($pdo);

    $payload = contact_payload();
    $defaultReturnTo = (string)($options['default_return_to'] ?? '/contact.php?sent=1');
    $returnTo = sanitize_contact_return_to((string)($payload['return_to'] ?? ''), $defaultReturnTo);
    $source = inquiry_normalize_source((string)($payload['source'] ?? ($options['default_source'] ?? 'general')));

    if (!empty($payload['company'])) {
        inquiry_log_line('honeypot detected. treated as success.');
        if (contact_wants_json()) {
            contact_json_out(200, ['ok' => true]);
        }
        header('Location: ' . $returnTo, true, 303);
        exit;
    }

    $name = trim((string)($payload['name'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $topic = trim((string)($payload['topic'] ?? ''));
    $url = trim((string)($payload['url'] ?? ''));
    $message = trim((string)($payload['message'] ?? ''));

    $agreeRaw = $payload['agree'] ?? null;
    $agree = $agreeRaw === 1 || $agreeRaw === '1' || $agreeRaw === 'on' || $agreeRaw === true;

    if (!$agree) {
        contact_fail(400, 'Please accept the privacy policy.');
    }
    if ($name === '' || $topic === '' || $message === '') {
        contact_fail(400, 'Required fields are missing.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        contact_fail(400, 'Invalid email address.');
    }
    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        contact_fail(400, 'Invalid URL.');
    }

    $inquiry = [
        'source' => $source,
        'name' => $name,
        'email' => $email,
        'topic' => $topic,
        'url' => $url,
        'message' => $message,
        'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'status' => 'new',
    ];

    try {
        $columns = inquiry_columns($pdo);
        $insertable = [];
        foreach (['source', 'name', 'email', 'topic', 'url', 'message', 'ip', 'user_agent', 'status'] as $column) {
            if (isset($columns[$column])) {
                $insertable[$column] = $inquiry[$column];
            }
        }

        $names = array_keys($insertable);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $names);
        $sql = 'INSERT INTO inquiries (' . implode(', ', $names) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_combine($placeholders, array_values($insertable)));
        $inquiry['id'] = (int)$pdo->lastInsertId();
        inquiry_log_line('DB insert OK id=' . $inquiry['id']);
    } catch (Throwable $e) {
        inquiry_log_line('DB ERROR: ' . $e->getMessage());
        contact_fail(500, 'Database error.');
    }

    $mailResult = send_inquiry_received_notifications($inquiry);

    if (!$mailResult['admin_ok'] && !$mailResult['auto_ok']) {
        if (contact_wants_json()) {
            contact_json_out(500, ['ok' => false, 'error' => 'Mail send failed.', 'id' => $inquiry['id']]);
        }
        http_response_code(500);
        echo 'Mail send failed.';
        exit;
    }

    if (contact_wants_json()) {
        contact_json_out(200, [
            'ok' => true,
            'id' => $inquiry['id'],
            'mail_admin_ok' => $mailResult['admin_ok'],
            'mail_auto_ok' => $mailResult['auto_ok'],
        ]);
    }

    header('Location: ' . $returnTo, true, 303);
    exit;
}
