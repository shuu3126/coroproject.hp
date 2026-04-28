<?php
declare(strict_types=1);

function inquiry_mail_config(): array
{
    return [
        'smtp_host' => 's221.myssl.jp',
        'smtp_port' => 587,
        'smtp_secure' => 'tls',
        'smtp_user' => 'm12974-info',
        'smtp_pass' => 'coroproject0111',
        'from_mail' => 'info@coroproject.jp',
        'from_name' => 'CORO PROJECT',
        'admin_to' => 'info@coroproject.jp',
    ];
}

function inquiry_log_path(): string
{
    return dirname(__DIR__) . '/production/html/api/contact_mail.log';
}

function inquiry_log_line(string $message): void
{
    $dt = date('Y-m-d H:i:s');
    @file_put_contents(inquiry_log_path(), '[' . $dt . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function inquiry_require_phpmailer(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    require_once dirname(__DIR__) . '/production/lib/Exception.php';
    require_once dirname(__DIR__) . '/production/lib/PHPMailer.php';
    require_once dirname(__DIR__) . '/production/lib/SMTP.php';

    $loaded = true;
}

function inquiry_make_mailer(): PHPMailer\PHPMailer\PHPMailer
{
    inquiry_require_phpmailer();

    $config = inquiry_mail_config();
    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $config['smtp_host'];
    $mailer->SMTPAuth = true;
    $mailer->Username = $config['smtp_user'];
    $mailer->Password = $config['smtp_pass'];
    $mailer->Port = $config['smtp_port'];
    $mailer->CharSet = 'UTF-8';
    $mailer->SMTPDebug = 2;
    $mailer->Debugoutput = static function (string $str, int $level): void {
        inquiry_log_line("SMTP[{$level}] {$str}");
    };

    $mailer->SMTPSecure = $config['smtp_secure'] === 'ssl'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    $mailer->setFrom($config['from_mail'], $config['from_name']);

    return $mailer;
}

function send_inquiry_received_notifications(array $inquiry): array
{
    $config = inquiry_mail_config();
    $sourceLabel = inquiry_source_label((string)($inquiry['source'] ?? 'general'));
    $inquiryId = (int)($inquiry['id'] ?? 0);
    $url = trim((string)($inquiry['url'] ?? ''));
    $ip = (string)($inquiry['ip'] ?? '');
    $ua = (string)($inquiry['user_agent'] ?? '');

    $result = [
        'admin_ok' => false,
        'auto_ok' => false,
        'error' => '',
    ];

    try {
        $admin = inquiry_make_mailer();
        $admin->addAddress($config['admin_to']);
        $admin->addReplyTo((string)$inquiry['email'], (string)$inquiry['name']);
        $admin->Subject = '[Inquiry] ' . (string)$inquiry['topic'] . ' (#' . $inquiryId . ')';
        $admin->Body =
            "CORO PROJECT Inquiry\n"
            . "------------------------------\n"
            . "ID: #{$inquiryId}\n"
            . "Source: {$sourceLabel}\n"
            . "Name: " . (string)$inquiry['name'] . "\n"
            . "Email: " . (string)$inquiry['email'] . "\n"
            . "Topic: " . (string)$inquiry['topic'] . "\n"
            . "URL: " . ($url !== '' ? $url : 'N/A') . "\n"
            . "IP: {$ip}\n"
            . "UA: {$ua}\n"
            . "------------------------------\n"
            . (string)$inquiry['message'] . "\n";
        $admin->send();
        $result['admin_ok'] = true;
        inquiry_log_line('MAIL admin OK id=' . $inquiryId . ' to=' . $config['admin_to']);
    } catch (Throwable $e) {
        $result['error'] = $e->getMessage();
        inquiry_log_line('MAIL admin ERROR: ' . $e->getMessage());
    }

    try {
        $auto = inquiry_make_mailer();
        $auto->addAddress((string)$inquiry['email'], (string)$inquiry['name']);
        $auto->Subject = '[CORO PROJECT] Inquiry received (#' . $inquiryId . ')';
        $auto->Body =
            (string)$inquiry['name'] . "\n\n"
            . "Thank you for contacting CORO PROJECT.\n"
            . "We received your inquiry with the details below.\n\n"
            . "------------------------------\n"
            . "ID: #{$inquiryId}\n"
            . "Source: {$sourceLabel}\n"
            . "Topic: " . (string)$inquiry['topic'] . "\n"
            . "Message:\n" . (string)$inquiry['message'] . "\n"
            . "------------------------------\n\n"
            . "We will review your message and reply as soon as possible.\n\n"
            . "CORO PROJECT\n"
            . "Mail : info@coroproject.jp\n"
            . "Web  : https://coroproject.jp\n";
        $auto->send();
        $result['auto_ok'] = true;
        inquiry_log_line('MAIL auto OK id=' . $inquiryId . ' to=' . (string)$inquiry['email']);
    } catch (Throwable $e) {
        if ($result['error'] === '') {
            $result['error'] = $e->getMessage();
        }
        inquiry_log_line('MAIL auto ERROR: ' . $e->getMessage());
    }

    return $result;
}

function send_inquiry_admin_reply(array $inquiry, string $subject, string $body): void
{
    $mailer = inquiry_make_mailer();
    $mailer->addAddress((string)$inquiry['email'], (string)$inquiry['name']);
    $mailer->Subject = $subject;
    $mailer->Body = $body . "\n\nCORO PROJECT\nMail : info@coroproject.jp\nWeb  : https://coroproject.jp\n";
    $mailer->send();
    inquiry_log_line('MAIL admin reply OK id=' . (int)$inquiry['id'] . ' to=' . (string)$inquiry['email']);
}
