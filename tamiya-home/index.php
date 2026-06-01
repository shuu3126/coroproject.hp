<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /tamiya-home/dashboard.php');
} else {
    header('Location: /tamiya-home/login.php');
}
exit;
