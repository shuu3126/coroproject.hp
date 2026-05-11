<?php
session_start();
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/production/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/production/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/production/lib/PHPMailer/SMTP.php';

$pdo = coro_public_settings_db();

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$error = '';

// SMTP設定を読み込む
$smtpConfig = [
    'host' => 's221.myssl.jp',
    'port' => 465,
    'secure' => 'ssl',
    'user' => '',
    'pass' => '',
    'from_email' => 'info@coroproject.jp',
    'from_name' => 'CORO PROJECT',
    'contact_reply_subject' => 'お問い合わせありがとうございます | CORO PROJECT',
    'contact_reply_body' => '',
];

// 管理画面の設定テーブルから読み込みを試みる
try {
    if ($pdo) {
        $keys = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'smtp_from_email', 'smtp_from_name', 'contact_reply_subject', 'contact_reply_body'];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key   = $row['setting_key'] ?? '';
            $value = $row['setting_value'] ?? '';
            if ($key === 'smtp_host')             $smtpConfig['host']                  = $value ?: $smtpConfig['host'];
            if ($key === 'smtp_port')             $smtpConfig['port']                  = $value ? (int)$value : $smtpConfig['port'];
            if ($key === 'smtp_secure')           $smtpConfig['secure']                = $value ?: $smtpConfig['secure'];
            if ($key === 'smtp_user')             $smtpConfig['user']                  = $value;
            if ($key === 'smtp_pass')             $smtpConfig['pass']                  = $value;
            if ($key === 'smtp_from_email')       $smtpConfig['from_email']            = $value ?: $smtpConfig['from_email'];
            if ($key === 'smtp_from_name')        $smtpConfig['from_name']             = $value ?: $smtpConfig['from_name'];
            if ($key === 'contact_reply_subject') $smtpConfig['contact_reply_subject'] = $value;
            if ($key === 'contact_reply_body')    $smtpConfig['contact_reply_body']    = $value;
        }
    }
} catch (Exception $e) {
    // 設定テーブルが無い場合はデフォルト値を使用
}

if ($submitted) {
    try {
        // バリデーション
        $topic = trim($_POST['topic'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (!$name || !$email || !$message || !$topic) {
            throw new Exception('必須項目を入力してください。');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('正しいメールアドレスを入力してください。');
        }

        // DBにデータを保存
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS contacts (
                  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                  topic VARCHAR(255) NOT NULL,
                  company VARCHAR(255) NULL,
                  name VARCHAR(255) NOT NULL,
                  email VARCHAR(255) NOT NULL,
                  message LONGTEXT NOT NULL,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $stmt = $pdo->prepare("
                INSERT INTO contacts (topic, company, name, email, message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$topic, $company ?: null, $name, $email, $message]);
        } catch (Exception $e) {
            throw new Exception('データベースへの保存に失敗しました。');
        }

        // 自動返信メール送信
        $sendMail = false;
        $usePhpMail = in_array($smtpConfig['host'], ['', 'localhost', '127.0.0.1'], true);
        if ($smtpConfig['from_email'] && ($usePhpMail || ($smtpConfig['user'] && $smtpConfig['pass']))) {
            try {
                // テンプレートを読み込む
                $replySubject = $smtpConfig['contact_reply_subject'] ?? 'お問い合わせありがとうございます | CORO PROJECT';
                $replyBodyTemplate = $smtpConfig['contact_reply_body'] ?? "お疲れ様です。\n\n{name}様\n\nこの度は、CORO PROJECTへのお問い合わせをいただき、ありがとうございます。\n\nお送りいただいたお問い合わせを受け付けいたしました。\n内容を確認のうえ、このメールアドレス宛にご返信差し上げます。\n\n【お問い合わせ内容】\nお問い合わせ種別: {topic}\n{company_line}\n\n---\n{message}\n---\n\nご不明な点がございましたら、お気軽にお問い合わせください。\n\nよろしくお願いいたします。\n\nCORO PROJECT\nhttps://coroproject.jp/";

                // テンプレート変数を置換
                $companyLine = $company ? "会社名: " . $company : "";
                $replyBody = strtr($replyBodyTemplate, [
                    '{name}' => $name,
                    '{company}' => $company,
                    '{company_line}' => $companyLine,
                    '{topic}' => $topic,
                    '{message}' => $message,
                ]);

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                if ($usePhpMail) {
                    $mail->isMail();
                } else {
                    $mail->isSMTP();
                    $mail->Host = $smtpConfig['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpConfig['user'];
                    $mail->Password = $smtpConfig['pass'];
                    $mail->Port = (int)$smtpConfig['port'];

                    if ($smtpConfig['secure'] === 'ssl') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } elseif ($smtpConfig['secure'] === 'tls') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } else {
                        $mail->SMTPSecure = '';
                        $mail->SMTPAutoTLS = false;
                    }
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer'       => false,
                            'verify_peer_name'  => false,
                            'allow_self_signed' => true,
                        ],
                    ];
                }

                $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
                $mail->addAddress($email, $name);
                $mail->Subject = $replySubject;
                $mail->Body = $replyBody;
                $mail->send();
                $sendMail = true;
            } catch (Exception $mailError) {
                // ログに記録するが、フォーム送信は続ける
                error_log('Contact form auto-reply failed: ' . $mailError->getMessage());
            }
        }

        // セッションに保存
        $_SESSION['contact_submitted'] = true;
        $_SESSION['contact_topic'] = $topic;
        $_SESSION['contact_company'] = $company;
        $_SESSION['contact_name'] = $name;
        $_SESSION['contact_email'] = $email;
        $_SESSION['contact_message'] = $message;

        // thanks.php へリダイレクト
        $homeUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        header('Location: ' . rtrim($homeUrl, '/') . '/thanks.php', true, 303);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
        $submitted = false;
    }
}

render_head('CONTACT', '企業案件、制作相談、所属相談、取材・提携などを受け付けるCORO PROJECTの総合問い合わせ窓口です。', [
    'canonical' => 'https://coroproject.jp/contact.php',
    'robots'    => 'index, follow',
    'og_type'   => 'website',
    'og_image'  => 'https://coroproject.jp/images/ogp.png',
]);
render_header('contact');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">CONTACT NODE</span>
        </div>
        <h1 class="sub-title">相談内容を整理し、<br><span>必要な窓口へ接続します。</span></h1>
        <p class="sub-lead">企業案件、PR・タイアップ、イベント出演、制作依頼、所属相談、活動相談、クリエイター提携、取材、業務提携など、内容に応じて適切な窓口へご案内します。まだ要件が固まっていない段階でも、目的や状況を整理するところからご相談いただけます。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">REPLY: 3 BUSINESS DAYS</span>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container section-head reveal">
      <div class="section-title-wrap">
        <span class="pink-bar"></span>
        <div>
          <span class="section-sub">CONTACT ROUTES</span>
          <h2 class="section-title">相談の種類に合わせて、確認する内容を変えます。</h2>
        </div>
      </div>
    </div>
    <div class="container contact-route-grid reveal">
      <article class="route-card cyber-clip-lg">
        <span>BUSINESS</span>
        <h3>企業案件・PR相談</h3>
        <p>商品やサービスの特徴、実施したい時期、想定している配信・投稿形式、予算感をもとに整理します。</p>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>CREATIVE</span>
        <h3>制作・デザイン相談</h3>
        <p>必要な制作物、使用用途、希望納期、参考イメージ、公開範囲を確認し、依頼内容を明確にします。</p>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>PRODUCTION</span>
        <h3>所属・活動相談</h3>
        <p>現在の活動状況、目標、困っていること、希望する支援範囲を伺い、必要なサポートを確認します。</p>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>PARTNER</span>
        <h3>取材・提携・その他</h3>
        <p>掲載媒体、提携内容、実施時期、確認したい内容をもとに、関係する担当窓口へ接続します。</p>
      </article>
    </div>
  </section>

  <section class="content-section">
    <div class="container contact-grid reveal is-visible">
      <div class="contact-info cyber-clip-lg">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">GUIDE</span>
        </div>
        <h2 class="content-title small">お問い合わせ前のご案内</h2>
        <div class="stack-md contact-copy">
          <p>ご相談内容が固まっていない段階でも問題ありません。目的や困りごとが曖昧でも、整理の段階からサポートします。最初の連絡では、完璧な企画書よりも「何を実現したいか」「いつ頃動かしたいか」が分かることを重視しています。</p>
          <ul class="info-list">
            <li>返信目安は通常3営業日以内です。</li>
            <li>企業案件、制作相談、所属相談、活動相談、取材、業務提携なども含め、すべて総合フォームから受付します。</li>
            <li>具体的な予算や納期が未定でもご相談可能です。</li>
          </ul>
        </div>
        <div class="contact-aside-block">
          <h3>対応可能な内容</h3>
          <p>案件相談、キャスティング、PR施策、イベント出演、制作依頼、動画・MV関連、所属相談、活動相談、クリエイター提携、取材、業務提携、その他CORO PROJECTに関するお問い合わせ。</p>
        </div>
        <div class="contact-aside-block">
          <h3>送ると整理しやすい情報</h3>
          <ul class="info-list">
            <li>相談の目的、または困っていること</li>
            <li>希望時期、公開日、納品希望日などの目安</li>
            <li>予算感や、まだ未定であることの共有</li>
            <li>参考URL、資料、イメージに近い事例</li>
          </ul>
        </div>
        <div class="contact-aside-block">
          <h3>注意事項</h3>
          <p>内容によっては確認にお時間をいただく場合があります。営業・売り込みのみを目的としたご連絡には返信できない場合があります。</p>
        </div>
      </div>

      <div class="contact-form-wrap cyber-clip-lg">
        <?php if ($error): ?>
          <div class="error-box cyber-clip">
            <strong>エラーが発生しました</strong>
            <p><?= h($error) ?></p>
          </div>
        <?php endif; ?>
        <form method="post" class="contact-form">
          <label>
            <span>お問い合わせ種別</span>
            <select name="topic" required>
              <?php foreach ($contactTopics as $topic): ?>
                <option value="<?= h($topic) ?>"><?= h($topic) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="field-note">該当する項目が近いものを選んでください。迷う場合は「その他」で問題ありません。</small>
          </label>
          <label>
            <span>会社名 / 団体名</span>
            <input type="text" name="company" placeholder="任意">
          </label>
          <label>
            <span>お名前</span>
            <input type="text" name="name" required>
          </label>
          <label>
            <span>メールアドレス</span>
            <input type="email" name="email" required>
          </label>
          <label>
            <span>お問い合わせ内容</span>
            <textarea name="message" rows="6" placeholder="相談したい内容、目的、希望時期、予算感、参考URLなどを分かる範囲でご記入ください。" required></textarea>
          </label>
          <button type="submit" class="primary-button cyber-clip">SEND SIGNAL</button>
        </form>
      </div>
    </div>
  </section>
</main>
<?php render_footer(); ?>
