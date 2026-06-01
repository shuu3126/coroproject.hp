<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /tamiya-home/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: /tamiya-home/dashboard.php');
        exit;
    }
}

function currentUser() {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
    ];
}

function isAdmin() {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}
