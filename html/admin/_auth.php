<?php
function current_admin_user(): ?array
{
    return $_SESSION['admin_user'] ?? null;
}

function require_admin_login(): void
{
    global $baseUrl;
    if (empty($_SESSION['admin_user'])) {
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
}
