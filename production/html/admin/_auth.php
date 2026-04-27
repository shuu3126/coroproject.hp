<?php
function current_admin_user() {
    return isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : null;
}

function require_admin_login() {
    global $baseUrl;
    if (!current_admin_user()) {
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
}
