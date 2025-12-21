<?php
declare(strict_types=1);

// ===== デバッグ（原因特定が終わったらOFF推奨）=====
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ===== ログに残す（画面に出ない/JSが握りつぶす時に便利）=====
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/contact_error.log'); // html/api/contact_error.log に出ます

header('X-Content-Type-Options: nosniff');

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

function server_error(string $msg = 'サーバー側でエラーが発生しました。'): void {
  if (wants_json()) json_out(500, ['ok' => false, 'error' => $msg]);
  http_response_code(500);
  echo $msg;
  exit;
}

// GETで開いたら正常系は405を返す（500にならなければOK）
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  if (wants_json()) json_out(405, ['ok' => false, 'error' => 'Method not allowed']);
  http_response_code(405);
  exit('Method not allowed');
}

// =====================================================
// 入力の取得（フォームPOST / JSON POST 両対応）
// =====================================================
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$payload = [];

if (stripos($ct, 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  $payload = json_decode($raw ?: '', true);
  if (!is_array($payload)) $payload = [];
} else {
  $payload = $_POST;
}

// ハニーポット（スパム対策）: company が入ってたら捨てる
if (!empty($payload['company'])) {
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

// バリデーション
if (!$agree) bad_request('プライバシーポリシーに同意してください。');
if ($name === '' || $topic === '' || $message === '') bad_request('必須項目が未入力です。');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad_request('メールアドレスの形式が正しくありません。');
if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) bad_request('URLの形式が正しくありません。');

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// =====================================================
// DB保存
// 依存: プロジェクト直下の db.php が $pdo を提供している前提
// テーブル: inquiries (id, name, email, topic, url, message, ip, user_agent, created_at)
// =====================================================
$inquiryId = null;

try {
  require_once __DIR__ . '/../../db.php';

  // 連投対策（同一IPの直近30秒を弾く：任意）
  try {
    $stmt = $pdo->prepare("SELECT created_at FROM inquiries WHERE ip = :ip ORDER BY id DESC LIMIT 1");
    $stmt->execute([':ip' => $ip]);
    $last = $stmt->fetchColumn();
    if ($last) {
      $lastTs = strtotime((string)$last);
      if ($lastTs && (time() - $lastTs) < 30) {
        bad_request('送信間隔が短すぎます。少し待ってから再度お試しください。');
      }
    }
  } catch (Throwable $e) {
    error_log('[contact] rate-limit check failed: ' . $e->getMessage());
  }

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

} catch (Throwable $e) {
  error_log('[contact] DB error: ' . $e->getMessage());
  server_error('DB保存に失敗しました。');
}

// =====================================================
// PHPMailer 読み込み（あなたの構成：/lib に3ファイルがある）
// =====================================================
$libBase = __DIR__ . '/../../lib';

$paths = [
  $libBase . '/Exception.php',
  $libBase . '/PHPMailer.php',
  $libBase . '/SMTP.php',
];

foreach ($paths as $p) {
  if (!is_file($p)) {
    error_log('[contact] PHPMailer file missing: ' . $p);
    // DB保存は成功してるのでユーザー体験優先で成功扱い
    if (wants_json()) json_out(200, ['ok' => true, 'id' => $inquiryId, 'mail_admin_ok' => false, 'mail_auto_ok' => false]);
    header('Location: ../thanks.html', true, 303);
    exit;
  }
}

require_once $libBase . '/Exception.php';
require_once $libBase . '/PHPMailer.php';
require_once $libBase . '/SMTP.php';

// =====================================================
// SMTP設定（MINIMのスクショ確定値）
// =====================================================
$SMTP_HOST = 'mail.coroproject.minim.ne.jp'; // スクショのSMTP
$SMTP_PORT = 587;                           // STARTTLS
$SMTP_SEC  = 'tls';                         // tls or ssl
$SMTP_USER = 'info@coroproject.jp';
$SMTP_PASS = 'coroproject0111';             // ←あなたのMINIMメールパスワード（後で変更推奨）

$FROM_MAIL = 'info@coroproject.jp';
$FROM_NAME = 'CORO PROJECT';
$ADMIN_TO  = 'info@coroproject.jp';

// =====================================================
// メール送信（管理者通知 + 自動返信）
// =====================================================
$mailAdminOk = false;
$mailAutoOk  = false;

$makeMailer = function() use ($SMTP_HOST, $SMTP_PORT, $SMTP_SEC, $SMTP_USER, $SMTP_PASS, $FROM_MAIL, $FROM_NAME) {
  $m = new \PHPMailer\PHPMailer\PHPMailer(true);
  $m->isSMTP();
  $m->Host     = $SMTP_HOST;
  $m->SMTPAuth = true;
  $m->Username = $SMTP_USER;
  $m->Password = $SMTP_PASS;
  $m->Port     = $SMTP_PORT;
  $m->CharSet  = 'UTF-8';

  if ($SMTP_SEC === 'ssl') {
    $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;     // 465
  } else {
    $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;  // 587
  }

  $m->setFrom($FROM_MAIL, $FROM_NAME);
  return $m;
};

try {
  // ① 管理者宛
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

  // ② 自動返信（ユーザー宛）
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

} catch (Throwable $e) {
  error_log('[contact] mail error: ' . $e->getMessage());
  // DB保存は完了しているので、ユーザーには成功扱いにして運用ログで追う
}

// =====================================================
// 応答
// =====================================================
if (wants_json()) {
  json_out(200, [
    'ok' => true,
    'id' => $inquiryId,
    'mail_admin_ok' => $mailAdminOk,
    'mail_auto_ok'  => $mailAutoOk,
  ]);
}

// 通常は完了ページへ
header('Location: ../thanks.html', true, 303);
exit;
