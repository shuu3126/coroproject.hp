<?php
function ensure_dir_path( $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('保存フォルダを作成できませんでした: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('保存フォルダに書き込みできません: ' . $dir);
    }
}

function save_uploaded_image( $file, $absoluteDir, $relativePrefix, $baseName) {
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('画像アップロードに失敗しました。（エラーコード: ' . (int)$file['error'] . '）');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('アップロードされた画像を確認できませんでした。');
    }
    if (!empty($file['size']) && (int)$file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('画像サイズは5MB以下にしてください。');
    }
    $allowedExts = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo((string)((isset($file['name']) ? $file['name'] : '')), PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExts, true)) {
        $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : '';
        switch ($mime) {
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/webp':
                $ext = 'webp';
                break;
            default:
                $ext = '';
                break;
        }
    }
    if ($ext === '' || !in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('画像形式は jpg / jpeg / png / gif / webp のみです。');
    }
    ensure_dir_path($absoluteDir);
    $filename = normalize_file_stem($baseName, 'image') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = rtrim($absoluteDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('画像ファイルを保存できませんでした。');
    }
    return trim($relativePrefix, '/\\') . '/' . $filename;
}

function save_uploaded_file_any( $file, $absoluteDir, $relativePrefix, $baseName, $allowedExts = []) {
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('ファイルアップロードに失敗しました。（エラーコード: ' . (int)$file['error'] . '）');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('アップロードされたファイルを確認できませんでした。');
    }
    $ext = strtolower(pathinfo((string)((isset($file['name']) ? $file['name'] : '')), PATHINFO_EXTENSION));
    if ($allowedExts && ($ext === '' || !in_array($ext, $allowedExts, true))) {
        throw new RuntimeException('許可されていないファイル形式です。');
    }
    ensure_dir_path($absoluteDir);
    $filename = normalize_file_stem($baseName, 'file') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    if ($ext !== '') $filename .= '.' . $ext;
    $dest = rtrim($absoluteDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('ファイルを保存できませんでした。');
    }
    return [
        'path' => trim($relativePrefix, '/\\') . '/' . $filename,
        'original_name' => (string)((isset($file['name']) ? $file['name'] : '')),
    ];
}
