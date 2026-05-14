<?php
require_once __DIR__ . '/_bootstrap.php';

if (cp_current_creator()) {
    cp_redirect($creativePortalBase . '/dashboard.php');
}
cp_redirect($creativePortalBase . '/login.php');
