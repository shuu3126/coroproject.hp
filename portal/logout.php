<?php
require_once __DIR__ . '/_bootstrap.php';
portal_logout();
portal_flash_set('info', 'ログアウトしました。');
portal_redirect($portalBase . '/login.php');
