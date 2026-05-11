<?php
function current_admin_user() {
    return isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : null;
}

function admin_user_can_manage_users($user = null) {
    $user = $user ?: current_admin_user();
    return is_array($user) && (string)($user['login_id'] ?? '') === 'admin';
}

function require_admin_login() {
    global $baseUrl;
    if (!current_admin_user()) {
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
}
