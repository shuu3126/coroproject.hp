<?php
function current_portal_talent() {
    return isset($_SESSION['portal_talent']) ? $_SESSION['portal_talent'] : null;
}

function require_portal_login() {
    global $portalBase;
    if (!current_portal_talent()) {
        header('Location: ' . $portalBase . '/login.php');
        exit;
    }
}

function portal_login($pdo, $loginId, $password) {
    $loginId = trim((string)$loginId);
    if ($loginId === '' || (string)$password === '') {
        return ['error' => 'IDとパスワードを入力してください。'];
    }

    try {
        $stmt = $pdo->prepare('
            SELECT pa.*, t.name AS talent_name
            FROM talent_portal_accounts pa
            JOIN talents t ON t.id = pa.talent_id
            WHERE pa.login_id = ?
        ');
        $stmt->execute([$loginId]);
        $account = $stmt->fetch();
    } catch (Exception $e) {
        return ['error' => 'ログイン処理に失敗しました。しばらくしてからお試しください。'];
    }

    if (!$account) {
        return ['error' => 'IDまたはパスワードが正しくありません。'];
    }

    if (!empty($account['locked_until'])) {
        $lockedUntil = strtotime($account['locked_until']);
        if ($lockedUntil > time()) {
            $remaining = ceil(($lockedUntil - time()) / 60);
            return ['error' => "ログイン試行回数が上限に達しました。{$remaining}分後に再試行してください。"];
        }
    }

    if (!$account['is_active']) {
        return ['error' => 'このアカウントは無効化されています。運営にお問い合わせください。'];
    }

    if (!password_verify($password, $account['password_hash'])) {
        $attempts    = (int)$account['login_attempts'] + 1;
        $lockedUntil = null;
        if ($attempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
            $attempts    = 0;
        }
        try {
            $pdo->prepare('UPDATE talent_portal_accounts SET login_attempts = ?, locked_until = ? WHERE id = ?')
                ->execute([$attempts, $lockedUntil, $account['id']]);
        } catch (Exception $e) {}
        return ['error' => 'IDまたはパスワードが正しくありません。'];
    }

    try {
        $pdo->prepare('UPDATE talent_portal_accounts SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?')
            ->execute([$account['id']]);
    } catch (Exception $e) {}

    session_regenerate_id(true);
    $_SESSION['portal_talent'] = [
        'id'          => $account['id'],
        'talent_id'   => $account['talent_id'],
        'talent_name' => $account['talent_name'],
        'login_id'    => $account['login_id'],
    ];
    $_SESSION['portal_last_activity'] = time();

    return ['success' => true];
}

function portal_logout() {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
