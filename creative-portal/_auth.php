<?php
function cp_current_creator() {
    return isset($_SESSION['creative_portal_creator']) ? $_SESSION['creative_portal_creator'] : null;
}

function cp_require_login() {
    global $creativePortalBase;
    if (!cp_current_creator()) {
        header('Location: ' . $creativePortalBase . '/login.php');
        exit;
    }
}

function cp_login($pdo, $loginId, $password) {
    $loginId = trim((string)$loginId);
    $password = (string)$password;
    if ($loginId === '' || $password === '') {
        return ['error' => 'IDとパスワードを入力してください。'];
    }

    try {
        $stmt = $pdo->prepare('
            SELECT pa.*, c.name AS creator_name
            FROM creative_portal_accounts pa
            JOIN cre_creators c ON c.id = pa.creator_id
            WHERE pa.login_id = ?
            LIMIT 1
        ');
        $stmt->execute([$loginId]);
        $account = $stmt->fetch();
    } catch (Exception $e) {
        return ['error' => 'CreativeポータルのDB更新が未実行です。管理者に連絡してください。'];
    }

    if (!$account) {
        return ['error' => 'IDまたはパスワードが正しくありません。'];
    }

    if (!empty($account['locked_until'])) {
        $lockedUntil = strtotime($account['locked_until']);
        if ($lockedUntil > time()) {
            $remaining = (int)ceil(($lockedUntil - time()) / 60);
            return ['error' => "ログイン試行回数が上限に達しました。{$remaining}分後に再試行してください。"];
        }
    }

    if (!(int)$account['is_active']) {
        return ['error' => 'このアカウントは無効化されています。CORO PROJECTにお問い合わせください。'];
    }

    if (!password_verify($password, $account['password_hash'])) {
        $attempts = (int)$account['login_attempts'] + 1;
        $lockedUntil = null;
        if ($attempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
            $attempts = 0;
        }
        try {
            $pdo->prepare('UPDATE creative_portal_accounts SET login_attempts = ?, locked_until = ? WHERE id = ?')
                ->execute([$attempts, $lockedUntil, (int)$account['id']]);
        } catch (Exception $e) {
        }
        return ['error' => 'IDまたはパスワードが正しくありません。'];
    }

    try {
        $pdo->prepare('UPDATE creative_portal_accounts SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?')
            ->execute([(int)$account['id']]);
    } catch (Exception $e) {
    }

    session_regenerate_id(true);
    $_SESSION['creative_portal_creator'] = [
        'id' => (int)$account['id'],
        'creator_id' => (string)$account['creator_id'],
        'creator_name' => (string)$account['creator_name'],
        'login_id' => (string)$account['login_id'],
    ];
    $_SESSION['creative_portal_last_activity'] = time();

    cp_write_activity($pdo, $account['creator_id'], (int)$account['id'], 'login', 'Creativeポータルにログイン');

    return ['success' => true];
}

function cp_logout() {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
