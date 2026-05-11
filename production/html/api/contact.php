<?php
declare(strict_types=1);

if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
    http_response_code(410);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => false,
        'error' => 'Productionのお問い合わせフォームは総合お問い合わせへ統合されました。',
        'redirect' => '../../../contact.php',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

header('Location: ../../../contact.php', true, 303);
exit;
