<?php

function admin_mail_ensure_schema($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mail_contacts (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(150) NULL,
          email VARCHAR(191) NOT NULL,
          company VARCHAR(150) NULL,
          memo TEXT NULL,
          last_contacted_at DATETIME NULL,
          created_by BIGINT UNSIGNED NULL,
          updated_by BIGINT UNSIGNED NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uq_mail_contacts_email (email),
          INDEX idx_mail_contacts_name (name),
          INDEX idx_mail_contacts_last_contacted (last_contacted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mail_messages (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          mailbox VARCHAR(30) NOT NULL DEFAULT 'inbox',
          direction VARCHAR(20) NOT NULL DEFAULT 'inbound',
          uidl VARCHAR(191) NULL,
          message_id VARCHAR(191) NULL,
          thread_key VARCHAR(191) NULL,
          from_name VARCHAR(191) NULL,
          from_email VARCHAR(191) NULL,
          to_text TEXT NULL,
          cc_text TEXT NULL,
          bcc_text TEXT NULL,
          subject VARCHAR(500) NULL,
          body_text LONGTEXT NULL,
          body_html LONGTEXT NULL,
          raw_headers LONGTEXT NULL,
          has_attachments TINYINT(1) NOT NULL DEFAULT 0,
          status VARCHAR(20) NOT NULL DEFAULT 'unread',
          is_starred TINYINT(1) NOT NULL DEFAULT 0,
          received_at DATETIME NULL,
          sent_at DATETIME NULL,
          admin_user_id BIGINT UNSIGNED NULL,
          reply_to_mail_id BIGINT UNSIGNED NULL,
          error_message TEXT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uq_mail_messages_uidl (uidl),
          INDEX idx_mail_messages_mailbox_created (mailbox, created_at),
          INDEX idx_mail_messages_status (status),
          INDEX idx_mail_messages_direction (direction),
          INDEX idx_mail_messages_from_email (from_email),
          INDEX idx_mail_messages_message_id (message_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function admin_mail_setting($settings, $key, $fallback = '') {
    $value = isset($settings[$key]) ? trim((string)$settings[$key]) : '';
    return $value !== '' ? $value : $fallback;
}

function admin_mail_unread_count($pdo) {
    if (!admin_table_has_column($pdo, 'mail_messages', 'status')) {
        return 0;
    }
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM mail_messages WHERE mailbox = 'inbox' AND status = 'unread'")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function admin_mail_decode_header($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    if (function_exists('iconv_mime_decode')) {
        $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }
    }

    if (function_exists('mb_decode_mimeheader')) {
        $decoded = @mb_decode_mimeheader($value);
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }
    }

    return $value;
}

function admin_mail_to_utf8($value, $charset = '') {
    $value = (string)$value;
    $charset = trim((string)$charset, " \t\r\n\"'");
    if ($value === '' || $charset === '' || strcasecmp($charset, 'UTF-8') === 0 || strcasecmp($charset, 'UTF8') === 0) {
        return $value;
    }

    if (function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($value, 'UTF-8', $charset);
        if ($converted !== false) {
            return $converted;
        }
    }

    if (function_exists('iconv')) {
        $converted = @iconv($charset, 'UTF-8//IGNORE', $value);
        if ($converted !== false) {
            return $converted;
        }
    }

    return $value;
}

function admin_mail_split_header_body($raw) {
    $raw = str_replace(["\r\n", "\r"], "\n", (string)$raw);
    $pos = strpos($raw, "\n\n");
    if ($pos === false) {
        return [$raw, ''];
    }
    return [substr($raw, 0, $pos), substr($raw, $pos + 2)];
}

function admin_mail_parse_headers($headerBlock) {
    $lines = preg_split('/\n/', str_replace(["\r\n", "\r"], "\n", (string)$headerBlock));
    $unfolded = [];
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        if (preg_match('/^[ \t]/', $line) && $unfolded) {
            $unfolded[count($unfolded) - 1] .= ' ' . trim($line);
        } else {
            $unfolded[] = rtrim($line);
        }
    }

    $headers = [];
    foreach ($unfolded as $line) {
        $pos = strpos($line, ':');
        if ($pos === false) {
            continue;
        }
        $name = strtolower(trim(substr($line, 0, $pos)));
        $value = trim(substr($line, $pos + 1));
        if (isset($headers[$name])) {
            $headers[$name] .= ', ' . $value;
        } else {
            $headers[$name] = $value;
        }
    }
    return $headers;
}

function admin_mail_parse_header_params($value) {
    $parts = explode(';', (string)$value);
    $main = strtolower(trim(array_shift($parts)));
    $params = [];
    foreach ($parts as $part) {
        $eq = strpos($part, '=');
        if ($eq === false) {
            continue;
        }
        $key = strtolower(trim(substr($part, 0, $eq)));
        $val = trim(substr($part, $eq + 1));
        $params[$key] = trim($val, " \t\r\n\"'");
    }
    return [$main, $params];
}

function admin_mail_decode_body($body, $encoding, $charset = '') {
    $encoding = strtolower(trim((string)$encoding));
    $body = (string)$body;

    if ($encoding === 'base64') {
        $decoded = base64_decode(preg_replace('/\s+/', '', $body), true);
        if ($decoded !== false) {
            $body = $decoded;
        }
    } elseif ($encoding === 'quoted-printable') {
        $body = quoted_printable_decode($body);
    }

    return admin_mail_to_utf8($body, $charset);
}

function admin_mail_split_multipart_body($body, $boundary) {
    $boundary = (string)$boundary;
    if ($boundary === '') {
        return [];
    }

    $delimiter = '--' . $boundary;
    $closing = $delimiter . '--';
    $lines = preg_split('/\r\n|\n|\r/', (string)$body);
    $parts = [];
    $buffer = [];
    $collecting = false;

    foreach ($lines as $line) {
        if ($line === $delimiter || $line === $closing) {
            if ($collecting) {
                $parts[] = implode("\r\n", $buffer);
                $buffer = [];
            }
            if ($line === $closing) {
                break;
            }
            $collecting = true;
            continue;
        }

        if ($collecting) {
            $buffer[] = $line;
        }
    }

    return $parts;
}

function admin_mail_parse_mime_part($headers, $body) {
    list($contentType, $params) = admin_mail_parse_header_params(isset($headers['content-type']) ? $headers['content-type'] : 'text/plain; charset=UTF-8');
    list($disposition, $dispositionParams) = admin_mail_parse_header_params(isset($headers['content-disposition']) ? $headers['content-disposition'] : '');
    $encoding = isset($headers['content-transfer-encoding']) ? $headers['content-transfer-encoding'] : '';
    $charset = isset($params['charset']) ? $params['charset'] : '';
    $isAttachment = $disposition === 'attachment'
        || isset($params['name'])
        || isset($dispositionParams['filename']);

    $result = [
        'text' => '',
        'html' => '',
        'has_attachments' => $isAttachment ? 1 : 0,
    ];

    if (strpos($contentType, 'multipart/') === 0 && !empty($params['boundary'])) {
        foreach (admin_mail_split_multipart_body($body, $params['boundary']) as $partRaw) {
            list($partHeaderBlock, $partBody) = admin_mail_split_header_body($partRaw);
            $part = admin_mail_parse_mime_part(admin_mail_parse_headers($partHeaderBlock), $partBody);
            if ($part['text'] !== '') {
                $result['text'] .= ($result['text'] !== '' ? "\n\n" : '') . $part['text'];
            }
            if ($part['html'] !== '') {
                $result['html'] .= ($result['html'] !== '' ? "\n\n" : '') . $part['html'];
            }
            if (!empty($part['has_attachments'])) {
                $result['has_attachments'] = 1;
            }
        }
        return $result;
    }

    if ($isAttachment) {
        return $result;
    }

    $decodedBody = admin_mail_decode_body($body, $encoding, $charset);
    if ($contentType === 'text/html') {
        $result['html'] = $decodedBody;
        $result['text'] = trim(html_entity_decode(strip_tags($decodedBody), ENT_QUOTES, 'UTF-8'));
    } else {
        $result['text'] = trim($decodedBody);
    }

    return $result;
}

function admin_mail_parse_raw_message($raw) {
    list($headerBlock, $bodyBlock) = admin_mail_split_header_body($raw);
    $headers = admin_mail_parse_headers($headerBlock);
    $body = admin_mail_parse_mime_part($headers, $bodyBlock);

    $from = admin_mail_first_address(isset($headers['from']) ? $headers['from'] : '');
    $subject = admin_mail_decode_header(isset($headers['subject']) ? $headers['subject'] : '(no subject)');
    $receivedAt = null;
    if (!empty($headers['date'])) {
        $ts = strtotime($headers['date']);
        if ($ts !== false) {
            $receivedAt = date('Y-m-d H:i:s', $ts);
        }
    }

    return [
        'headers' => $headers,
        'raw_headers' => $headerBlock,
        'message_id' => trim(isset($headers['message-id']) ? $headers['message-id'] : '', " \t\r\n<>"),
        'from_name' => $from['name'],
        'from_email' => $from['email'],
        'to_text' => admin_mail_decode_header(isset($headers['to']) ? $headers['to'] : ''),
        'cc_text' => admin_mail_decode_header(isset($headers['cc']) ? $headers['cc'] : ''),
        'subject' => $subject !== '' ? $subject : '(no subject)',
        'body_text' => $body['text'],
        'body_html' => $body['html'],
        'has_attachments' => !empty($body['has_attachments']) ? 1 : 0,
        'received_at' => $receivedAt,
    ];
}

function admin_mail_first_address($value) {
    $decoded = admin_mail_decode_header($value);
    $email = '';
    if (preg_match('/<([^<>@\s]+@[^<>\s]+)>/u', $decoded, $m)) {
        $email = trim($m[1]);
    } elseif (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $decoded, $m)) {
        $email = trim($m[0]);
    }

    $name = trim(str_replace(['<', '>', $email], '', $decoded), " \t\r\n\"'");
    if ($name === '' && $email !== '') {
        $name = $email;
    }

    return ['name' => $name, 'email' => strtolower($email)];
}

function admin_mail_parse_recipients($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return [];
    }

    $parts = preg_split('/[\n;,]+/u', $value);
    $recipients = [];
    foreach ($parts as $part) {
        $addr = admin_mail_first_address($part);
        if ($addr['email'] !== '' && filter_var($addr['email'], FILTER_VALIDATE_EMAIL)) {
            $recipients[$addr['email']] = $addr;
        }
    }
    return array_values($recipients);
}

function admin_mail_recipients_to_text($recipients) {
    $out = [];
    foreach ($recipients as $recipient) {
        $email = isset($recipient['email']) ? (string)$recipient['email'] : '';
        $name = isset($recipient['name']) ? trim((string)$recipient['name']) : '';
        if ($email === '') {
            continue;
        }
        $out[] = $name !== '' && $name !== $email ? $name . ' <' . $email . '>' : $email;
    }
    return implode(', ', $out);
}

function admin_mail_upsert_contact($pdo, $email, $name = '', $userId = null) {
    $email = strtolower(trim((string)$email));
    $name = trim((string)$name);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO mail_contacts (name, email, last_contacted_at, created_by, updated_by, created_at, updated_at)
        VALUES (?, ?, NOW(), ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          name = CASE WHEN VALUES(name) <> '' THEN VALUES(name) ELSE name END,
          last_contacted_at = NOW(),
          updated_by = VALUES(updated_by),
          updated_at = NOW()
    ");
    $stmt->execute([$name !== '' ? $name : null, $email, $userId ?: null, $userId ?: null]);
}

function admin_mail_pop3_connect($settings) {
    $host = admin_mail_setting($settings, 'mail_pop_host', 's221.myssl.jp');
    $port = (int)admin_mail_setting($settings, 'mail_pop_port', '995');
    $encryption = admin_mail_setting($settings, 'mail_pop_encryption', 'ssl');
    $user = admin_mail_setting($settings, 'mail_pop_user', admin_mail_setting($settings, 'smtp_user', ''));
    $pass = admin_mail_setting($settings, 'mail_pop_pass', admin_mail_setting($settings, 'smtp_pass', ''));

    if ($host === '' || $user === '' || $pass === '') {
        throw new RuntimeException('受信設定のホスト、ユーザー名、パスワードを入力してください。');
    }

    $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        throw new RuntimeException('POP3サーバーに接続できません: ' . ($errstr ?: ('errno ' . $errno)));
    }

    stream_set_timeout($fp, 30);
    admin_mail_pop3_expect_ok(admin_mail_pop3_read_line($fp));
    admin_mail_pop3_command($fp, 'USER ' . $user);
    admin_mail_pop3_command($fp, 'PASS ' . $pass);

    return $fp;
}

function admin_mail_pop3_read_line($fp) {
    $line = fgets($fp, 8192);
    if ($line === false) {
        throw new RuntimeException('POP3サーバーからの応答を読み取れませんでした。');
    }
    return rtrim($line, "\r\n");
}

function admin_mail_pop3_expect_ok($line) {
    if (substr((string)$line, 0, 3) !== '+OK') {
        throw new RuntimeException('POP3エラー: ' . (string)$line);
    }
}

function admin_mail_pop3_command($fp, $command) {
    fwrite($fp, $command . "\r\n");
    $line = admin_mail_pop3_read_line($fp);
    admin_mail_pop3_expect_ok($line);
    return $line;
}

function admin_mail_pop3_multiline($fp, $command) {
    admin_mail_pop3_command($fp, $command);
    $lines = [];
    while (!feof($fp)) {
        $line = admin_mail_pop3_read_line($fp);
        if ($line === '.') {
            break;
        }
        if (substr($line, 0, 2) === '..') {
            $line = substr($line, 1);
        }
        $lines[] = $line;
    }
    return $lines;
}

function admin_mail_sync_pop3($pdo, $settings, $userId = null) {
    admin_mail_ensure_schema($pdo);

    $limit = (int)admin_mail_setting($settings, 'mail_sync_limit', '30');
    if ($limit <= 0 || $limit > 200) {
        $limit = 30;
    }

    $fp = admin_mail_pop3_connect($settings);
    $inserted = 0;
    $skipped = 0;

    try {
        $uidlLines = admin_mail_pop3_multiline($fp, 'UIDL');
        $items = [];
        foreach ($uidlLines as $line) {
            $parts = preg_split('/\s+/', trim($line), 2);
            if (count($parts) === 2) {
                $items[(int)$parts[0]] = $parts[1];
            }
        }
        krsort($items, SORT_NUMERIC);
        $items = array_slice($items, 0, $limit, true);

        $existsStmt = $pdo->prepare('SELECT id FROM mail_messages WHERE uidl = ? LIMIT 1');
        $insertStmt = $pdo->prepare("
            INSERT INTO mail_messages
              (mailbox, direction, uidl, message_id, thread_key, from_name, from_email, to_text, cc_text,
               subject, body_text, body_html, raw_headers, has_attachments, status, received_at, created_at, updated_at)
            VALUES
              ('inbox', 'inbound', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unread', ?, NOW(), NOW())
        ");

        foreach ($items as $num => $uidl) {
            $existsStmt->execute([$uidl]);
            if ($existsStmt->fetch()) {
                $skipped++;
                continue;
            }

            $raw = implode("\r\n", admin_mail_pop3_multiline($fp, 'RETR ' . (int)$num));
            $parsed = admin_mail_parse_raw_message($raw);
            $threadKey = admin_mail_thread_key($parsed['subject'], $parsed['from_email']);

            $insertStmt->execute([
                $uidl,
                $parsed['message_id'] !== '' ? $parsed['message_id'] : null,
                $threadKey,
                $parsed['from_name'] !== '' ? $parsed['from_name'] : null,
                $parsed['from_email'] !== '' ? $parsed['from_email'] : null,
                $parsed['to_text'],
                $parsed['cc_text'],
                $parsed['subject'],
                $parsed['body_text'],
                $parsed['body_html'] !== '' ? $parsed['body_html'] : null,
                $parsed['raw_headers'],
                (int)$parsed['has_attachments'],
                $parsed['received_at'] ?: date('Y-m-d H:i:s'),
            ]);

            admin_mail_upsert_contact($pdo, $parsed['from_email'], $parsed['from_name'], $userId);
            $inserted++;
        }

        @fwrite($fp, "QUIT\r\n");
    } finally {
        if (is_resource($fp)) {
            fclose($fp);
        }
    }

    return ['inserted' => $inserted, 'skipped' => $skipped];
}

function admin_mail_thread_key($subject, $email = '') {
    $subject = preg_replace('/^\s*(re|fw|fwd)\s*:\s*/iu', '', (string)$subject);
    $subject = mb_strtolower(trim($subject));
    return sha1($subject . '|' . mb_strtolower(trim((string)$email)));
}

function admin_mail_require_phpmailer() {
    require_once dirname(__DIR__) . '/production/lib/PHPMailer/Exception.php';
    require_once dirname(__DIR__) . '/production/lib/PHPMailer/PHPMailer.php';
    require_once dirname(__DIR__) . '/production/lib/PHPMailer/SMTP.php';
}

function admin_mail_send_message($pdo, $settings, $userId, $toText, $subject, $body, $ccText = '', $bccText = '', $replyToMailId = null) {
    admin_mail_ensure_schema($pdo);
    admin_mail_require_phpmailer();

    $to = admin_mail_parse_recipients($toText);
    $cc = admin_mail_parse_recipients($ccText);
    $bcc = admin_mail_parse_recipients($bccText);
    if (!$to) {
        throw new RuntimeException('送信先メールアドレスを入力してください。');
    }

    $subject = trim((string)$subject);
    $body = trim((string)$body);
    if ($subject === '' || $body === '') {
        throw new RuntimeException('件名と本文を入力してください。');
    }

    $fromEmail = admin_mail_setting($settings, 'smtp_from_email', admin_mail_setting($settings, 'office_email', 'info@coroproject.jp'));
    $fromName = admin_mail_setting($settings, 'smtp_from_name', admin_mail_setting($settings, 'office_name', 'CORO PROJECT'));
    $smtpHost = admin_mail_setting($settings, 'smtp_host', 's221.myssl.jp');
    $smtpUser = admin_mail_setting($settings, 'smtp_user', '');
    $smtpPass = admin_mail_setting($settings, 'smtp_pass', '');
    $smtpPort = (int)admin_mail_setting($settings, 'smtp_port', '465');
    $smtpSecure = admin_mail_setting($settings, 'smtp_secure', 'ssl');

    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('送信元メールアドレスを正しく設定してください。');
    }
    if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
        throw new RuntimeException('SMTPホスト、ユーザー名、パスワードを設定してください。');
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->Port = $smtpPort;
    if ($smtpSecure === 'ssl') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpSecure === 'tls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
    }

    $mail->setFrom($fromEmail, $fromName);
    foreach ($to as $recipient) {
        $mail->addAddress($recipient['email'], $recipient['name']);
    }
    foreach ($cc as $recipient) {
        $mail->addCC($recipient['email'], $recipient['name']);
    }
    foreach ($bcc as $recipient) {
        $mail->addBCC($recipient['email'], $recipient['name']);
    }
    $mail->Subject = $subject;
    $mail->Body = $body;

    $insertId = null;
    try {
        $mail->send();
        $status = 'sent';
        $error = null;
    } catch (Exception $e) {
        $status = 'failed';
        $error = $e->getMessage();
    }

    $stmt = $pdo->prepare("
        INSERT INTO mail_messages
          (mailbox, direction, message_id, thread_key, from_name, from_email, to_text, cc_text, bcc_text,
           subject, body_text, status, sent_at, admin_user_id, reply_to_mail_id, error_message, created_at, updated_at)
        VALUES
          ('sent', 'outbound', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())
    ");
    $messageId = trim((string)$mail->getLastMessageID(), " \t\r\n<>");
    $stmt->execute([
        $messageId !== '' ? $messageId : null,
        admin_mail_thread_key($subject, admin_mail_recipients_to_text($to)),
        $fromName,
        $fromEmail,
        admin_mail_recipients_to_text($to),
        admin_mail_recipients_to_text($cc),
        admin_mail_recipients_to_text($bcc),
        $subject,
        $body,
        $status,
        $userId ?: null,
        $replyToMailId ?: null,
        $error,
    ]);
    $insertId = (int)$pdo->lastInsertId();

    foreach (array_merge($to, $cc, $bcc) as $recipient) {
        admin_mail_upsert_contact($pdo, $recipient['email'], $recipient['name'], $userId);
    }

    if ($status === 'failed') {
        throw new RuntimeException($error ?: 'メール送信に失敗しました。');
    }

    return $insertId;
}

