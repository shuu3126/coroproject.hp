<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// html/api/contact.php
// お問い合わせAPI（DB保存 + 管理者通知 + 自動返信）
// - フォームPOST (application/x-www-form-urlencoded) と JSON POST の両対応
// - 成功時：通常は thanks.html へリダイレクト
// - fetch等でJSONが欲しい場合：Accept: application/json で JSON を返す



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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
  // 通常フォームPOST
  $payload = $_POST;
}

// ハニーポット（スパム対策）: company が入ってたら捨てる
if (!empty($payload['company'])) {
  // スパムに成功レスを返して手応えを与えない
  if (wants_json()) json_out(200, ['ok' => true]);
  header('Location: ../thanks.html', true, 303);
  exit;
}

$name    = trim((string)($payload['name'] ?? ''));
$email   = trim((string)($payload['email'] ?? ''));
$topic   = trim((string)($payload['topic'] ?? ''));
$url     = trim((string)($payload['url'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));

// agree は form の checkbox だと "on" のことがある
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
    // 連投対策が失敗しても致命ではないので続行
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
  server_error();
}

// =====================================================
// PHPMailer 読み込み（2パターン対応）
// A) /lib/PHPMailer/PHPMailer.php …（推奨構成）
// B) /lib/PHPMailer.php …（あなたが今こう置いてる可能性がある）
// =====================================================
$phpmailerLoaded = false;

$tryPaths = [
  __DIR__ . '/../../lib/PHPMailer/PHPMailer.php',
  __DIR__ . '/../../lib/PHPMailer.php',
];

foreach ($tryPaths as $p) {
  if (is_file($p)) { $phpmailerLoaded = true; break; }
}

if (!$phpmailerLoaded) {
  error_log('[contact] PHPMailer not found in /lib.');
  // メール送信できなくてもDB保存は成功してるので、ユーザー体験優先で成功扱いにする（運用でログを見る）
  if (wants_json()) json_out(200, ['ok' => true, 'id' => $inquiryId, 'mail_admin_ok' => false, 'mail_auto_ok' => false]);
  header('Location: ../thanks.html', true, 303);
  exit;
}

// 実際に読み込み
if (is_file(__DIR__ . '/../../lib/PHPMailer/PHPMailer.php')) {
  require_once __DIR__ . '/../../lib/PHPMailer/Exception.php';
  require_once __DIR__ . '/../../lib/PHPMailer/PHPMailer.php';
  require_once __DIR__ . '/../../lib/PHPMailer/SMTP.php';
} else {
  require_once __DIR__ . '/../../lib/Exception.php';
  require_once __DIR__ . '/../../lib/PHPMailer.php';
  require_once __DIR__ . '/../../lib/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =====================================================
// SMTP設定（ここをあなたのMINIMに合わせて埋める）
// =====================================================
// 例：MINIMで作ったアカウント info@coroproject.minim.ne.jp が内部実体でも、
// SMTPログインは info@coroproject.jp でOKなケースが多いです（MINIM側の仕様）。
$SMTP_HOST = 'mail.coroproject.minim.ne.jp';
$SMTP_PORT = 587;
$SMTP_SEC  = 'tls';
$SMTP_USER = 'info@coroproject.jp';
$SMTP_PASS = 'coroproject0111';

$FROM_MAIL = 'info@coroproject.jp';
$FROM_NAME = 'CORO PROJECT';
$ADMIN_TO  = 'info@coroproject.jp';

// =====================================================
// メール送信（管理者宛 + 自動返信）
// =====================================================
$mailAdminOk = false;
$mailAutoOk  = false;

$makeMailer = function() use ($SMTP_HOST, $SMTP_PORT, $SMTP_SEC, $SMTP_USER, $SMTP_PASS, $FROM_MAIL, $FROM_NAME): PHPMailer {
  $m = new PHPMailer(true);
  $m->isSMTP();
  $m->Host       = $SMTP_HOST;
  $m->SMTPAuth   = true;
  $m->Username   = $SMTP_USER;
  $m->Password   = $SMTP_PASS;
  $m->Port       = $SMTP_PORT;
  $m->CharSet    = 'UTF-8';

  if ($SMTP_SEC === 'ssl') {
    $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 465
  } else {
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 587
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
  // DB保存は完了しているのでユーザー体験はOKにしてログだけ残す
  error_log('[contact] mail error: ' . $e->getMessage());
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
