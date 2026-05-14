<?php
function creative_portal_ready($pdo) {
    return admin_table_has_column($pdo, 'creative_portal_accounts', 'id');
}

function creative_portal_accounts_list($pdo) {
    if (!creative_portal_ready($pdo)) {
        return [];
    }
    $stmt = $pdo->query('
        SELECT pa.*, c.name AS creator_name
        FROM creative_portal_accounts pa
        LEFT JOIN cre_creators c ON c.id = pa.creator_id
        ORDER BY c.name ASC, pa.created_at DESC
    ');
    return $stmt->fetchAll();
}

function creative_portal_account_create($pdo, $creatorId, $loginId, $password, $adminId = null) {
    if (!creative_portal_ready($pdo)) {
        return ['error' => 'Creativeポータル用テーブルがありません。admin/portal_migrate.sql を実行してください。'];
    }
    $creatorId = trim((string)$creatorId);
    $loginId = trim((string)$loginId);
    $password = (string)$password;
    if ($creatorId === '' || $loginId === '' || $password === '') {
        return ['error' => '必須項目を入力してください。'];
    }
    if (strlen($password) < 8) {
        return ['error' => 'パスワードは8文字以上にしてください。'];
    }
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cre_creators WHERE id = ?');
        $stmt->execute([$creatorId]);
        if ((int)$stmt->fetchColumn() <= 0) {
            return ['error' => 'クリエイターが見つかりません。'];
        }
        $pdo->prepare('
            INSERT INTO creative_portal_accounts
                (creator_id, login_id, password_hash, is_active, password_changed_at, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, 1, NOW(), ?, ?, NOW(), NOW())
        ')->execute([
            $creatorId,
            $loginId,
            password_hash($password, PASSWORD_DEFAULT),
            $adminId ? (int)$adminId : null,
            $adminId ? (int)$adminId : null,
        ]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => '作成に失敗しました。ログインIDまたはクリエイターが重複している可能性があります。'];
    }
}

function creative_portal_account_update($pdo, $id, $data, $adminId = null) {
    if (!creative_portal_ready($pdo)) {
        return ['error' => 'Creativeポータル用テーブルがありません。'];
    }
    $id = (int)$id;
    if ($id <= 0) {
        return ['error' => 'アカウントが見つかりません。'];
    }

    $sets = [];
    $params = [];
    if (array_key_exists('login_id', $data)) {
        $loginId = trim((string)$data['login_id']);
        if ($loginId === '') {
            return ['error' => 'ログインIDを入力してください。'];
        }
        $sets[] = 'login_id = ?';
        $params[] = $loginId;
    }
    if (!empty($data['password'])) {
        $password = (string)$data['password'];
        if (strlen($password) < 8) {
            return ['error' => 'パスワードは8文字以上にしてください。'];
        }
        $sets[] = 'password_hash = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
        $sets[] = 'password_changed_at = NOW()';
        $sets[] = 'login_attempts = 0';
        $sets[] = 'locked_until = NULL';
    }
    if (array_key_exists('is_active', $data)) {
        $sets[] = 'is_active = ?';
        $params[] = (int)$data['is_active'] ? 1 : 0;
    }
    if (!$sets) {
        return ['error' => '変更内容がありません。'];
    }

    $sets[] = 'updated_by = ?';
    $params[] = $adminId ? (int)$adminId : null;
    $sets[] = 'updated_at = NOW()';
    $params[] = $id;

    try {
        $pdo->prepare('UPDATE creative_portal_accounts SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => '更新に失敗しました。ログインIDが重複している可能性があります。'];
    }
}

function creative_portal_account_delete($pdo, $id) {
    if (!creative_portal_ready($pdo)) {
        return ['error' => 'Creativeポータル用テーブルがありません。'];
    }
    try {
        $pdo->prepare('DELETE FROM creative_portal_accounts WHERE id = ?')->execute([(int)$id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => '削除に失敗しました。'];
    }
}

function creative_portal_notice_create($pdo, $title, $body, $isPublished, $adminId = null) {
    if (!admin_table_has_column($pdo, 'creative_portal_notices', 'id')) {
        return ['error' => 'Creativeポータルお知らせ用テーブルがありません。'];
    }
    $title = mb_substr(trim((string)$title), 0, 255);
    $body = trim((string)$body);
    if ($title === '' || $body === '') {
        return ['error' => 'タイトルと本文を入力してください。'];
    }
    try {
        $pdo->prepare('
            INSERT INTO creative_portal_notices
                (title, body, is_published, published_at, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, IF(? = 1, NOW(), NULL), ?, ?, NOW(), NOW())
        ')->execute([$title, $body, (int)$isPublished ? 1 : 0, (int)$isPublished ? 1 : 0, $adminId, $adminId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'お知らせの作成に失敗しました。'];
    }
}

function creative_portal_notice_update($pdo, $id, $data, $adminId = null) {
    if (!admin_table_has_column($pdo, 'creative_portal_notices', 'id')) {
        return ['error' => 'Creativeポータルお知らせ用テーブルがありません。'];
    }
    $title = mb_substr(trim((string)($data['title'] ?? '')), 0, 255);
    $body = trim((string)($data['body'] ?? ''));
    $isPublished = !empty($data['is_published']) ? 1 : 0;
    if ($title === '' || $body === '') {
        return ['error' => 'タイトルと本文を入力してください。'];
    }
    try {
        $pdo->prepare('
            UPDATE creative_portal_notices
            SET title = ?, body = ?, is_published = ?,
                published_at = CASE WHEN ? = 1 AND published_at IS NULL THEN NOW() WHEN ? = 0 THEN NULL ELSE published_at END,
                updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ')->execute([$title, $body, $isPublished, $isPublished, $isPublished, $adminId, (int)$id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'お知らせの更新に失敗しました。'];
    }
}

function creative_portal_notice_delete($pdo, $id) {
    if (!admin_table_has_column($pdo, 'creative_portal_notices', 'id')) {
        return ['error' => 'Creativeポータルお知らせ用テーブルがありません。'];
    }
    try {
        $pdo->prepare('DELETE FROM creative_portal_notices WHERE id = ?')->execute([(int)$id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => 'お知らせの削除に失敗しました。'];
    }
}

function creative_portal_notices_list($pdo) {
    if (!admin_table_has_column($pdo, 'creative_portal_notices', 'id')) {
        return [];
    }
    return $pdo->query('SELECT * FROM creative_portal_notices ORDER BY created_at DESC, id DESC')->fetchAll();
}

function creative_portal_upload_document($file, $bucket, $baseName, $allowedExts = []) {
    global $config;
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $projectRoot = $config['paths']['project_root'] ?? dirname(__DIR__);
    $ym = date('Ym');
    $bucket = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$bucket));
    $absoluteDir = rtrim($projectRoot, '/\\') . '/creative-portal/uploads/' . $bucket . '/' . $ym;
    $relativePrefix = 'creative-portal/uploads/' . $bucket . '/' . $ym;
    ensure_dir_path($absoluteDir);
    if (!is_file($absoluteDir . '/.htaccess')) {
        file_put_contents($absoluteDir . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
    return save_uploaded_file_any($file, $absoluteDir, $relativePrefix, $baseName, $allowedExts ?: ['pdf', 'jpg', 'jpeg', 'png', 'webp']);
}

function creative_portal_pending_count($pdo) {
    $count = 0;
    try {
        if (admin_table_has_column($pdo, 'creative_project_submissions', 'status')) {
            $count += (int)$pdo->query("SELECT COUNT(*) FROM creative_project_submissions WHERE status = 'submitted'")->fetchColumn();
        }
        if (admin_table_has_column($pdo, 'creative_project_invoices', 'status')) {
            $count += (int)$pdo->query("SELECT COUNT(*) FROM creative_project_invoices WHERE status = 'pending'")->fetchColumn();
        }
    } catch (Exception $e) {
    }
    return $count;
}

function creative_portal_statement_status_label($status) {
    switch ((string)$status) {
        case 'paid': return '支払済';
        case 'cancelled': return '取消';
        case 'scheduled':
        default: return '支払予定';
    }
}

function creative_portal_review_status_label($status) {
    switch ((string)$status) {
        case 'approved': return '承認済';
        case 'revision_requested': return '修正依頼';
        case 'rejected': return '差し戻し';
        case 'paid': return '支払済';
        case 'receipt_received': return '領収書受領';
        case 'pending': return '確認待ち';
        case 'submitted':
        default: return '提出済';
    }
}
