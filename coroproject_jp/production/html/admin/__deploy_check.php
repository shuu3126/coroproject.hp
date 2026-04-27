<?php
header('Content-Type: text/plain; charset=UTF-8');

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset: OK\n";
} else {
    echo "OPcache reset: unavailable\n";
}

$headerPath = __DIR__ . '/_header.php';
echo "Check file: " . __FILE__ . "\n";
echo "Header file: " . $headerPath . "\n";
echo "Header exists: " . (is_file($headerPath) ? 'yes' : 'no') . "\n";

if (is_file($headerPath)) {
    echo "Header mtime: " . date('Y-m-d H:i:s', filemtime($headerPath)) . "\n";
    $header = file_get_contents($headerPath);
    echo "Fix marker: " . (strpos($header, 'admin-header-fix-20260427-5') !== false ? 'yes' : 'no') . "\n";
    echo "\nFirst lines:\n";
    echo implode("\n", array_slice(preg_split('/\R/', (string)$header), 0, 12));
    echo "\n";
}
