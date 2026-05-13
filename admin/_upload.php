<?php
function ensure_dir_path($dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('保存フォルダを作成できませんでした: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('保存フォルダに書き込みできません: ' . $dir);
    }
}

function admin_uploaded_mime($tmpName) {
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        return is_string($mime) ? $mime : '';
    }
    return function_exists('mime_content_type') ? (string)mime_content_type($tmpName) : '';
}

function save_uploaded_image($file, $absoluteDir, $relativePrefix, $baseName) {
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('画像アップロードに失敗しました。エラーコード: ' . (int)$file['error']);
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('アップロードされた画像を確認できませんでした。');
    }
    if (!empty($file['size']) && (int)$file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('画像サイズは5MB以下にしてください。');
    }

    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $mime = admin_uploaded_mime($file['tmp_name']);
    if (!isset($mimeToExt[$mime])) {
        throw new RuntimeException('画像形式は jpg / png / gif / webp のみです。');
    }

    ensure_dir_path($absoluteDir);
    $filename = normalize_file_stem($baseName, 'image') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $mimeToExt[$mime];
    $dest = rtrim($absoluteDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('画像ファイルを保存できませんでした。');
    }

    return trim($relativePrefix, '/\\') . '/' . $filename;
}

function save_uploaded_file_any($file, $absoluteDir, $relativePrefix, $baseName, $allowedExts = []) {
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('ファイルアップロードに失敗しました。エラーコード: ' . (int)$file['error']);
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('アップロードされたファイルを確認できませんでした。');
    }
    if (!empty($file['size']) && (int)$file['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('ファイルサイズは10MB以下にしてください。');
    }

    $allowedExts = $allowedExts ?: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    $allowedExts = array_values(array_unique(array_map('strtolower', $allowedExts)));
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('許可されていないファイル形式です。');
    }

    $mimeByExt = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf', 'application/x-pdf'],
    ];
    $mime = admin_uploaded_mime($file['tmp_name']);
    if ($mime !== '' && isset($mimeByExt[$ext]) && !in_array($mime, $mimeByExt[$ext], true)) {
        throw new RuntimeException('ファイルの中身と拡張子が一致しません。');
    }

    ensure_dir_path($absoluteDir);
    $filename = normalize_file_stem($baseName, 'file') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = rtrim($absoluteDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('ファイルを保存できませんでした。');
    }

    return [
        'path' => trim($relativePrefix, '/\\') . '/' . $filename,
        'original_name' => (string)($file['name'] ?? ''),
    ];
}
