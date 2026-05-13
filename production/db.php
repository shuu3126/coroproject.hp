<?php
// Shared database connection for the production site and the admin system.

$isLocalRequest = false;
if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
    $isLocalRequest = true;
} else {
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalRequest = (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $httpHost);
}

$connectionCandidates = [];

$envHost = getenv('CORO_DB_HOST');
$envName = getenv('CORO_DB_NAME');
$envUser = getenv('CORO_DB_USER');
if ($envHost && $envName && $envUser) {
    $connectionCandidates[] = [
        'host' => $envHost,
        'name' => $envName,
        'user' => $envUser,
        'pass' => getenv('CORO_DB_PASS') ?: '',
        'label' => 'environment',
    ];
}

if ($isLocalRequest) {
    $connectionCandidates[] = [
        'host' => 'localhost',
        'name' => 'db_coroproject_1',
        'user' => 'root',
        'pass' => '',
        'label' => 'xampp-local',
    ];
}

$connectionCandidates[] = [
    'host' => 'localhost',
    'name' => 'db_coroproject_1',
    'user' => 'db_coroproject',
    'pass' => 'FwMMCTUO',
    'label' => 'production',
];

$lastError = null;
foreach ($connectionCandidates as $candidate) {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $candidate['host'],
        $candidate['name']
    );

    try {
        $pdo = new PDO($dsn, $candidate['user'], $candidate['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $GLOBALS['coro_db_connection_label'] = $candidate['label'];
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log('CORO DB connection failed: ' . ($lastError ? $lastError->getMessage() : 'unknown error'));
    http_response_code(500);
    exit('DB接続エラーが発生しました。時間をおいて再度お試しください。');
}
