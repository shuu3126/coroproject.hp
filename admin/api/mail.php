<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// GET /mail?accounts=1 — 登録アカウント一覧
if ($method === 'GET' && !empty($_GET['accounts'])) {
    try {
        $rows = $pdo->query(
            "SELECT id, label, email, is_default, is_active, last_sync_at FROM mail_accounts ORDER BY is_default DESC, id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        api_ok($rows);
    } catch (\Throwable $e) {
        api_ok([]);
    }
}

// GET /mail/{id} — 詳細（自動既読）
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare(
        "SELECT id, account_email, mailbox, from_name, from_email, to_text, subject, body_text, status, has_attachments, received_at
         FROM mail_messages WHERE id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { api_error(404, 'Message not found'); }
    $pdo->prepare("UPDATE mail_messages SET status = 'read', updated_at = NOW() WHERE id = ? AND status = 'unread'")->execute([$id]);
    api_ok($row);
}

// GET /mail — 受信トレイ一覧
if ($method === 'GET') {
    $where = ["mailbox = 'inbox'"]; $params = [];
    if (!empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['account'])) {
        $where[] = 'account_email = ?';
        $params[] = $_GET['account'];
    }
    $limit = min((int)($_GET['limit'] ?? 30), 100);
    $stmt = $pdo->prepare(
        "SELECT id, account_email, from_name, from_email, subject, status, has_attachments, received_at
         FROM mail_messages WHERE " . implode(' AND ', $where) . " ORDER BY received_at DESC LIMIT {$limit}"
    );
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// PATCH /mail/{id} — ステータス更新（read / unread）
if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['status'];
    $sets = []; $params = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $body)) { $sets[] = "{$f} = ?"; $params[] = $body[$f]; }
    }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE mail_messages SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($params);
    api_ok(['id' => $id]);
}

// POST /mail — メール送信
if ($method === 'POST') {
    $body = api_input();
    $to      = trim($body['to'] ?? '');
    $toName  = trim($body['to_name'] ?? '');
    $subject = trim($body['subject'] ?? '');
    $text    = trim($body['body'] ?? '');
    if (!$to || !$subject || !$text) { api_error(400, 'to, subject, body are required'); }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { api_error(400, 'Invalid to address'); }

    // SMTP設定をDBから取得
    $smtpCfg = ['host' => 's221.myssl.jp', 'port' => 465, 'secure' => 'ssl', 'user' => '', 'pass' => '', 'from_email' => 'info@coroproject.jp', 'from_name' => 'CORO PROJECT'];
    try {
        $keys = ['smtp_host','smtp_port','smtp_secure','smtp_user','smtp_pass','smtp_from_email','smtp_from_name'];
        $ph   = implode(',', array_fill(0, count($keys), '?'));
        $st   = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($ph)");
        $st->execute($keys);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $k = $row['setting_key']; $v = $row['setting_value'];
            if ($k === 'smtp_host' && $v)       $smtpCfg['host']       = $v;
            if ($k === 'smtp_port' && $v)        $smtpCfg['port']       = (int)$v;
            if ($k === 'smtp_secure' && $v)      $smtpCfg['secure']     = $v;
            if ($k === 'smtp_user')              $smtpCfg['user']       = $v;
            if ($k === 'smtp_pass')              $smtpCfg['pass']       = $v;
            if ($k === 'smtp_from_email' && $v)  $smtpCfg['from_email'] = $v;
            if ($k === 'smtp_from_name' && $v)   $smtpCfg['from_name']  = $v;
        }
    } catch (\Throwable $_e) {}

    $libDir = dirname(__DIR__, 2) . '/production/lib/PHPMailer';
    require_once $libDir . '/Exception.php';
    require_once $libDir . '/PHPMailer.php';
    require_once $libDir . '/SMTP.php';

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host       = $smtpCfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpCfg['user'];
        $mail->Password   = $smtpCfg['pass'];
        $mail->Port       = $smtpCfg['port'];
        $mail->SMTPSecure = $smtpCfg['secure'] === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
        $mail->setFrom($smtpCfg['from_email'], $smtpCfg['from_name']);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo($smtpCfg['from_email'], $smtpCfg['from_name']);
        $mail->Subject = $subject;
        $mail->Body    = $text;

        // 添付ファイル（attachment_name + attachment_base64 で指定）
        $attachName   = trim($body['attachment_name'] ?? '');
        $attachBase64 = trim($body['attachment_base64'] ?? '');
        if ($attachName !== '' && $attachBase64 !== '') {
            $attachData = base64_decode($attachBase64, true);
            if ($attachData !== false) {
                $mail->addStringAttachment($attachData, $attachName);
            }
        }

        $mail->send();

        // mail_messages テーブルに送信済みレコードを保存
        $toText = $toName ? "{$toName} <{$to}>" : $to;
        try {
            $pdo->prepare("
                INSERT INTO mail_messages
                    (account_email, mailbox, direction, from_name, from_email, to_text, subject, body_text, status, sent_at, created_at, updated_at)
                VALUES
                    (?, 'sent', 'outbound', ?, ?, ?, ?, ?, 'sent', NOW(), NOW(), NOW())
            ")->execute([
                $smtpCfg['from_email'],
                $smtpCfg['from_name'],
                $smtpCfg['from_email'],
                $toText,
                $subject,
                $text,
            ]);
            $sentId = (int)$pdo->lastInsertId();
        } catch (\Throwable $_e) { $sentId = 0; }

        // inquiry_id が指定されていれば問い合わせを返信済みに更新
        $inquiryId = (int)($body['inquiry_id'] ?? 0);
        if ($inquiryId > 0) {
            try {
                $pdo->prepare("UPDATE inquiries SET status = 'replied', updated_at = NOW() WHERE id = ?")->execute([$inquiryId]);
                $pdo->prepare("INSERT INTO inquiry_replies (inquiry_id, admin_user_id, body, mail_sent, created_at) VALUES (?, 0, ?, 1, NOW())")->execute([$inquiryId, $text]);
            } catch (\Throwable $_e) {}
        }

        api_ok(['sent' => true, 'to' => $to, 'sent_id' => $sentId]);
    } catch (\Exception $e) {
        api_error(500, 'Mail send failed: ' . $e->getMessage());
    }
}

api_error(405, 'Method not allowed');
