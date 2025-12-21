<?php
// html/api/contact.php

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit;
}

// ハニーポット（スパム対策）
if (!empty($data['company'])) {
  // スパムは成功扱いで返す（攻撃者に手応えを与えない）
  echo json_encode(['ok' => true]);
  exit;
}

// 入力
$name    = trim($data['name'] ?? '');
$email   = trim($data['email'] ?? '');
$topic   = trim($data['topic'] ?? '');
$url     = trim($data['url'] ?? '');
$message = trim($data['message'] ?? '');
$agree   = (int)($data['agree'] ?? 0);

// バリデーション
if ($agree !== 1) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'プライバシーポリシーに同意してください。']);
  exit;
}
if ($name === '' || $topic === '' || $message === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => '必須項目が未入力です。']);
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'メールアドレスの形式が正しくありません。']);
  exit;
}
if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'URLの形式が正しくありません。']);
  exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

try {
  // db.php はプロジェクト直下にある： html/api から2階層上
  require_once __DIR__ . '/../../db.php';

  // 連投対策（同一IP 30秒以内の投稿を抑止）
  $stmt = $pdo->prepare("SELECT created_at FROM inquiries WHERE ip = :ip ORDER BY id DESC LIMIT 1");
  $stmt->execute([':ip' => $ip]);
  $last = $stmt->fetchColumn();
  if ($last) {
    $lastTs = strtotime($last);
    if ($lastTs && (time() - $lastTs) < 30) {
      http_response_code(429);
      echo json_encode(['ok' => false, 'error' => '送信間隔が短すぎます。少し待ってから再度お試しください。']);
      exit;
    }
  }

  // DB保存
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

  // メール通知（まずはmail()）
  $to = 'info@coroproject.jp';
  $subject = '【お問い合わせ】' . $topic;

  $body =
    "CORO PROJECT お問い合わせ\n"
    . "------------------------------\n"
    . "お名前: {$name}\n"
    . "メール: {$email}\n"
    . "ご用件: {$topic}\n"
    . "関連URL: " . ($url !== '' ? $url : '（なし）') . "\n"
    . "IP: {$ip}\n"
    . "UA: {$ua}\n"
    . "------------------------------\n"
    . "{$message}\n";

  $headers = [];
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-Type: text/plain; charset=UTF-8';
  $headers[] = 'From: CORO PROJECT <info@coroproject.jp>';
  $headers[] = 'Reply-To: ' . $email;

  @mail($to, $subject, $body, implode("\r\n", $headers));

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'サーバー側でエラーが発生しました。']);
}
