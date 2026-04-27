<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/contact_submission.php';

contact_handle_request([
    'default_return_to' => '../thanks.html',
    'default_source' => 'production',
]);
