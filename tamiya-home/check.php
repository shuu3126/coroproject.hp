<?php
// 確認後すぐ削除してください
echo 'CORO_DB_PASS: ' . (getenv('CORO_DB_PASS') !== false ? '設定あり（' . strlen(getenv('CORO_DB_PASS')) . '文字）' : '未設定') . '<br>';
echo 'HTTP_HOST: ' . ($_SERVER['HTTP_HOST'] ?? '空') . '<br>';
