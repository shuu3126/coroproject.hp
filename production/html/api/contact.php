<?php
declare(strict_types=1);

// ====== 設定（まずここだけ埋める） ======

// ▼ MINIM SMTP（あなたのスクショから）
$SMTP_HOST = 's221.myssl.jp';              // まずはこっち推奨。ダメなら mail.coroproject.minim.ne.jp に変更
$SMTP_PORT = 587;                          // 587推奨
$SMTP_SEC  = 'tls';                        // tls = STARTTLS

// ▼ ログイン情報（最重要：ここがハマりやすい）
$SMTP_USER = 'm12974-info';                // ★まずこれで試す（ダメなら下のどちらかに）
/*
$SMTP_USER = 'info@coroproject.jp';
$SMTP_USER = 'info@coroproject.minim.ne.jp';
*/
$SMTP_PASS = 'coroproject0111';            // あなたが作ったパスワード

// ▼ 送信元（認証ユーザーと一致させると通りやすい）
$FROM_MAIL = 'info@coroproject.jp';         // 通らない場合は info@coroproject.minim.ne.jp に変更
$FROM_NAME = 'CORO PROJECT';

// ▼ 管理者の受信先（Google Workspace側）
$ADMIN_TO  = 'info@coroproject.jp';

// ====== デバッグログ ======
$LOG_FILE = __DIR__ . '/contact_mail.log';  // /html/api/contact_mail.log ができる

function log_line(string $s): void {
  global $LOG_FILE;
  $dt = date('Y-m-d H:i:s');
  @file_put_contents($LOG_FILE, "[$dt] $s\n", FILE_APPEND);
}

ini_set('display_errors', '0');             // 本番では画面に出さない
ini_set('log_errors', '1');
error_reporting(E_ALL);
header('X-Content-Type-Options: nosniff');

// ====== PHPMailer 読み込み（あなたの構成：/lib/PHPMailer.php など） ======
require_once __DIR__ . '/../../lib/Exception.php';
require_once __DIR__ . '/../../lib/PHPMailer.php';
require_once __DIR__ . '/../../lib/SMTP.php';

// 名前空間を使わずフルパスで呼ぶ（use問題を完全回避）
$PHPMailerClass = '\PHPMailer\PHPMailer\PHPMailer';

function wants_json(): bool {
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  return (stripos($accept, 'application/json') !== false) || (strtolower($xhr) === 'xmlhttprequest');
}
function json_out(int $code, array $payload): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function bad_request(string $msg): void {
  if (wants_json()) json_out(400, ['ok' => false, 'error' => $msg]);
  http_response_code(400);
  echo $msg;
  exit;
}

// ====== Method check ======
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  if (wants_json()) json_out(405, ['ok' => false, 'error' => 'Method not allowed']);
  http_response_code(405);
  echo 'Method not allowed';
  exit;
}

// ====== 受け取り（フォームPOST / JSON 両対応） ======
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$payload = [];

if (stripos($ct, 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  $payload = json_decode($raw ?: '', true);
  if (!is_array($payload)) $payload = [];
} else {
  $payload = $_POST;
}

// honeypot
if (!empty($payload['company'])) {
  log_line('honeypot detected. treated as success.');
  if (wants_json()) json_out(200, ['ok' => true]);
  header('Location: ../thanks.html', true, 303);
  exit;
}

$name    = trim((string)($payload['name'] ?? ''));
$email   = trim((string)($payload['email'] ?? ''));
$topic   = trim((string)($payload['topic'] ?? ''));
$url     = trim((string)($payload['url'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));

$agreeRaw = $payload['agree'] ?? null;
$agree = ($agreeRaw === 1 || $agreeRaw === '1' || $agreeRaw === 'on' || $agreeRaw === true);

// validate
if (!$agree) bad_request('プライバシーポリシーに同意してください。');
if ($name === '' || $topic === '' || $message === '') bad_request('必須項目が未入力です。');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad_request('メールアドレスの形式が正しくありません。');
if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) bad_request('URLの形式が正しくありません。');

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// ====== DB保存（成功してるっぽいが念のためログ） ======
$inquiryId = null;
try {
  require_once __DIR__ . '/../../db.php';

  $stmt = $pdo->prepare("
    INSERT INTO inquiries (name, email, topic, url, message, ip, user_agent)
    VALUES (:name, :email, :topic, :url, :message, :ip, :ua)
  ");
  $stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':topic' => $topic,
    ':url' => $url,
    ':message' => $message,
    ':ip' => $ip,
    ':ua' => $ua,
  ]);
  $inquiryId = (int)$pdo->lastInsertId();
  log_line("DB insert OK id={$inquiryId}");
} catch (Throwable $e) {
  log_line("DB ERROR: " . $e->getMessage());
  if (wants_json()) json_out(500, ['ok' => false, 'error' => 'DBエラー']);
  http_response_code(500);
  echo 'DBエラー';
  exit;
}

// ====== メーラー作成 ======
$makeMailer = function() use ($PHPMailerClass, $SMTP_HOST, $SMTP_PORT, $SMTP_SEC, $SMTP_USER, $SMTP_PASS, $FROM_MAIL, $FROM_NAME) {
  $m = new $PHPMailerClass(true);
  $m->isSMTP();
  $m->Host       = $SMTP_HOST;
  $m->SMTPAuth   = true;
  $m->Username   = $SMTP_USER;
  $m->Password   = $SMTP_PASS;
  $m->Port       = $SMTP_PORT;
  $m->CharSet    = 'UTF-8';

  // デバッグをログファイルへ
  $m->SMTPDebug  = 2;
  $m->Debugoutput = function($str, $level) {
    log_line("SMTP[$level] $str");
  };

  if ($SMTP_SEC === 'ssl') {
    $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
  } else {
    $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
  }

  $m->setFrom($FROM_MAIL, $FROM_NAME);
  return $m;
};

$mailAdminOk = false;
$mailAutoOk  = false;
$mailErr     = '';

// ====== ① 管理者宛 ======
try {
  $admin = $makeMailer();
  $admin->addAddress($ADMIN_TO);
  $admin->addReplyTo($email, $name);
  $admin->Subject = "【お問い合わせ】{$topic} (#{$inquiryId})";
  $admin->Body =
    "CORO PROJECT お問い合わせ\n"
    . "------------------------------\n"
    . "受付番号: #{$inquiryId}\n"
    . "お名前: {$name}\n"
    . "メール: {$email}\n"
    . "ご用件: {$topic}\n"
    . "関連URL: " . ($url !== '' ? $url : '（なし）') . "\n"
    . "IP: {$ip}\n"
    . "UA: {$ua}\n"
    . "------------------------------\n"
    . "{$message}\n";

  $admin->send();
  $mailAdminOk = true;
  log_line("MAIL admin OK id={$inquiryId} to={$ADMIN_TO}");
} catch (Throwable $e) {
  $mailErr = $e->getMessage();
  log_line("MAIL admin ERROR: " . $mailErr);
}

// ====== ② 自動返信 ======
try {
  $auto = $makeMailer();
  $auto->addAddress($email, $name);
  $auto->Subject = "【CORO PROJECT】お問い合わせありがとうございます（受付番号 #{$inquiryId}）";
  $auto->Body =
"{$name} 様

この度は、CORO PROJECTへお問い合わせいただきありがとうございます。
以下の内容でお問い合わせを受け付けました。

------------------------------
■ 受付番号
#{$inquiryId}

■ ご用件
{$topic}

■ 内容
{$message}
------------------------------

担当者が内容を確認のうえ、
【2営業日以内】を目安にご連絡いたします。

本メールは自動送信です。
内容にお心当たりがない場合は、お手数ですが info@coroproject.jp までご連絡ください。

────────────────────
CORO PROJECT
Mail : info@coroproject.jp
Web  : https://coroproject.jp
────────────────────
";
  $auto->send();
  $mailAutoOk = true;
  log_line("MAIL auto OK id={$inquiryId} to={$email}");
} catch (Throwable $e) {
  $mailErr2 = $e->getMessage();
  log_line("MAIL auto ERROR: " . $mailErr2);
  if ($mailErr === '') $mailErr = $mailErr2;
}

// ★重要：メールが両方失敗したら、thanksへ行かず失敗を返す（原因特定のため）
if (!$mailAdminOk && !$mailAutoOk) {
  if (wants_json()) json_out(500, ['ok' => false, 'error' => 'メール送信に失敗しました', 'id' => $inquiryId]);
  http_response_code(500);
  echo 'メール送信に失敗しました。管理者に連絡してください。';
  exit;
}

// ====== 応答 ======
if (wants_json()) {
  json_out(200, [
    'ok' => true,
    'id' => $inquiryId,
    'mail_admin_ok' => $mailAdminOk,
    'mail_auto_ok'  => $mailAutoOk,
  ]);
}

header('Location: ../thanks.html', true, 303);
exit;
