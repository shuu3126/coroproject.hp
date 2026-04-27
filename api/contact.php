<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/contact_submission.php';

contact_handle_request([
    'default_return_to' => '/contact.php?sent=1',
    'default_source' => 'general',
]);
