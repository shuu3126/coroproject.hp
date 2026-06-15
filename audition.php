<?php
session_start();
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/production/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/production/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/production/lib/PHPMailer/SMTP.php';

$pdo = coro_public_settings_db();

// DBテーブルを自動作成
if ($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS talent_applications (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              vtuber_name VARCHAR(100) NOT NULL,
              real_name VARCHAR(100) NULL,
              gender ENUM('female','male','other','private') NOT NULL,
              age TINYINT UNSIGNED NOT NULL,
              prefecture VARCHAR(20) NOT NULL,
              email VARCHAR(191) NOT NULL,
              activity_start VARCHAR(20) NOT NULL,
              main_platform VARCHAR(30) NOT NULL,
              youtube_url VARCHAR(255) NULL,
              youtube_followers INT UNSIGNED NULL,
              twitch_url VARCHAR(255) NULL,
              twitch_followers INT UNSIGNED NULL,
              twitter_url VARCHAR(255) NULL,
              twitter_followers INT UNSIGNED NULL,
              twitcasting_url VARCHAR(255) NULL,
              twitcasting_followers INT UNSIGNED NULL,
              other_platform TEXT NULL,
              stream_frequency VARCHAR(50) NOT NULL,
              stream_genre TEXT NOT NULL,
              sample_url_1 VARCHAR(255) NULL,
              sample_url_2 VARCHAR(255) NULL,
              sample_url_3 VARCHAR(255) NULL,
              past_achievements TEXT NULL,
              event_experience TEXT NULL,
              skills TEXT NOT NULL,
              affiliation_type ENUM('exclusive','non_exclusive','negotiable') NOT NULL,
              work_style ENUM('full_time','part_time','other') NOT NULL,
              work_detail VARCHAR(100) NULL,
              motivation TEXT NOT NULL,
              goal TEXT NOT NULL,
              mic_equipment VARCHAR(100) NULL,
              pc_spec VARCHAR(200) NULL,
              questions TEXT NULL,
              status ENUM('new','reviewing','passed','rejected','hold') NOT NULL DEFAULT 'new',
              admin_note TEXT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX idx_status (status),
              INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Exception $_e) {}
}

$error = '';
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

if (empty($_SESSION['audition_csrf'])) {
    $_SESSION['audition_csrf'] = bin2hex(random_bytes(32));
}

// SMTP設定を読み込む
$smtpConfig = [
    'host'       => 's221.myssl.jp',
    'port'       => 465,
    'secure'     => 'ssl',
    'user'       => '',
    'pass'       => '',
    'from_email' => 'info@coroproject.jp',
    'from_name'  => 'CORO PROJECT',
];

try {
    if ($pdo) {
        $keys = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'smtp_from_email', 'smtp_from_name'];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key   = $row['setting_key'] ?? '';
            $value = $row['setting_value'] ?? '';
            if ($key === 'smtp_host')       $smtpConfig['host']       = $value ?: $smtpConfig['host'];
            if ($key === 'smtp_port')       $smtpConfig['port']       = $value ? (int)$value : $smtpConfig['port'];
            if ($key === 'smtp_secure')     $smtpConfig['secure']     = $value ?: $smtpConfig['secure'];
            if ($key === 'smtp_user')       $smtpConfig['user']       = $value;
            if ($key === 'smtp_pass')       $smtpConfig['pass']       = $value;
            if ($key === 'smtp_from_email') $smtpConfig['from_email'] = $value ?: $smtpConfig['from_email'];
            if ($key === 'smtp_from_name')  $smtpConfig['from_name']  = $value ?: $smtpConfig['from_name'];
        }
    }
} catch (Exception $e) {}

$prefectures = [
    '北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
    '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
    '新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県',
    '静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県',
    '奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県',
    '徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県',
    '熊本県','大分県','宮崎県','鹿児島県','沖縄県','海外在住',
];

if ($submitted) {
    try {
        // CSRFチェック
        if (!hash_equals((string)($_SESSION['audition_csrf'] ?? ''), (string)($_POST['_csrf'] ?? ''))) {
            throw new Exception('不正なリクエストです。ページを再読み込みして再送信してください。');
        }
        // ハニーポット
        if (trim((string)($_POST['website'] ?? '')) !== '') {
            throw new Exception('送信できませんでした。');
        }
        // 連続送信防止（30秒）
        $lastSubmitAt = (int)($_SESSION['audition_last_submit_at'] ?? 0);
        if ($lastSubmitAt > 0 && time() - $lastSubmitAt < 30) {
            throw new Exception('連続送信はできません。少し時間をおいて再送信してください。');
        }

        // --- バリデーション ---
        $vtuberName      = trim($_POST['vtuber_name'] ?? '');
        $realName        = trim($_POST['real_name'] ?? '') ?: null;
        $gender          = trim($_POST['gender'] ?? '');
        $age             = (int)($_POST['age'] ?? 0);
        $prefecture      = trim($_POST['prefecture'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $activityStart   = trim($_POST['activity_start'] ?? '');
        $mainPlatform    = trim($_POST['main_platform'] ?? '');
        $youtubeUrl      = trim($_POST['youtube_url'] ?? '') ?: null;
        $youtubeFollowers = $_POST['youtube_followers'] !== '' ? (int)$_POST['youtube_followers'] : null;
        $twitchUrl       = trim($_POST['twitch_url'] ?? '') ?: null;
        $twitchFollowers = $_POST['twitch_followers'] !== '' ? (int)$_POST['twitch_followers'] : null;
        $twitterUrl      = trim($_POST['twitter_url'] ?? '') ?: null;
        $twitterFollowers = $_POST['twitter_followers'] !== '' ? (int)$_POST['twitter_followers'] : null;
        $twitcastingUrl  = trim($_POST['twitcasting_url'] ?? '') ?: null;
        $twitcastingFollowers = $_POST['twitcasting_followers'] !== '' ? (int)$_POST['twitcasting_followers'] : null;
        $otherPlatform   = trim($_POST['other_platform'] ?? '') ?: null;
        $streamFrequency = trim($_POST['stream_frequency'] ?? '');
        $streamGenre     = trim($_POST['stream_genre'] ?? '');
        $sampleUrl1      = trim($_POST['sample_url_1'] ?? '') ?: null;
        $sampleUrl2      = trim($_POST['sample_url_2'] ?? '') ?: null;
        $sampleUrl3      = trim($_POST['sample_url_3'] ?? '') ?: null;
        $pastAchievements = trim($_POST['past_achievements'] ?? '') ?: null;
        $eventExperience = trim($_POST['event_experience'] ?? '') ?: null;
        $skills          = trim($_POST['skills'] ?? '');
        $affiliationType = trim($_POST['affiliation_type'] ?? '');
        $workStyle       = trim($_POST['work_style'] ?? '');
        $workDetail      = trim($_POST['work_detail'] ?? '') ?: null;
        $motivation      = trim($_POST['motivation'] ?? '');
        $goal            = trim($_POST['goal'] ?? '');
        $micEquipment    = trim($_POST['mic_equipment'] ?? '') ?: null;
        $pcSpec          = trim($_POST['pc_spec'] ?? '') ?: null;
        $questions       = trim($_POST['questions'] ?? '') ?: null;
        $privacyAgree    = (bool)($_POST['privacy_agree'] ?? false);

        // 必須チェック
        if (!$vtuberName) throw new Exception('VTuber名を入力してください。');
        if (!in_array($gender, ['female','male','other','private'], true)) throw new Exception('性別を選択してください。');
        if ($age < 15 || $age > 100) throw new Exception('年齢を正しく入力してください（15歳以上）。');
        if (!in_array($prefecture, $prefectures, true)) throw new Exception('都道府県を選択してください。');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('正しいメールアドレスを入力してください。');
        if (!$activityStart) throw new Exception('活動開始時期を入力してください。');
        if (!in_array($mainPlatform, ['YouTube','Twitch','ニコ生','Twitcasting','その他'], true)) throw new Exception('主な活動プラットフォームを選択してください。');
        if (!in_array($streamFrequency, ['週1〜2日','週3〜4日','週5日以上','不定期'], true)) throw new Exception('配信頻度を選択してください。');
        if (!$streamGenre) throw new Exception('配信ジャンル・内容を入力してください。');
        if (!$skills) throw new Exception('スキル・特技を入力してください。');
        if (!in_array($affiliationType, ['exclusive','non_exclusive','negotiable'], true)) throw new Exception('所属形態を選択してください。');
        if (!in_array($workStyle, ['full_time','part_time','other'], true)) throw new Exception('活動スタイルを選択してください。');
        if (!$motivation) throw new Exception('応募動機を入力してください。');
        if (!$goal) throw new Exception('活動目標を入力してください。');
        if (!$privacyAgree) throw new Exception('個人情報の取り扱いへの同意が必要です。');

        // --- DB保存 ---
        if (!$pdo) throw new Exception('データベースへの接続に失敗しました。');

        $stmt = $pdo->prepare("
            INSERT INTO talent_applications (
                vtuber_name, real_name, gender, age, prefecture, email,
                activity_start, main_platform,
                youtube_url, youtube_followers,
                twitch_url, twitch_followers,
                twitter_url, twitter_followers,
                twitcasting_url, twitcasting_followers,
                other_platform, stream_frequency, stream_genre,
                sample_url_1, sample_url_2, sample_url_3,
                past_achievements, event_experience, skills,
                affiliation_type, work_style, work_detail,
                motivation, goal, mic_equipment, pc_spec, questions,
                status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?,
                ?, ?,
                ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                'new', NOW()
            )
        ");
        $stmt->execute([
            $vtuberName, $realName, $gender, $age, $prefecture, $email,
            $activityStart, $mainPlatform,
            $youtubeUrl, $youtubeFollowers,
            $twitchUrl, $twitchFollowers,
            $twitterUrl, $twitterFollowers,
            $twitcastingUrl, $twitcastingFollowers,
            $otherPlatform, $streamFrequency, $streamGenre,
            $sampleUrl1, $sampleUrl2, $sampleUrl3,
            $pastAchievements, $eventExperience, $skills,
            $affiliationType, $workStyle, $workDetail,
            $motivation, $goal, $micEquipment, $pcSpec, $questions,
        ]);
        $newAppId = (int)$pdo->lastInsertId();

        // --- メール送信 ---
        $usePhpMail = in_array($smtpConfig['host'], ['', 'localhost', '127.0.0.1'], true);
        $canSendMail = $smtpConfig['from_email'] && ($usePhpMail || ($smtpConfig['user'] && $smtpConfig['pass']));

        $buildMailer = function() use ($smtpConfig, $usePhpMail) {
            $m = new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->CharSet  = 'UTF-8';
            $m->Encoding = 'base64';
            if ($usePhpMail) {
                $m->isMail();
            } else {
                $m->isSMTP();
                $m->Host     = $smtpConfig['host'];
                $m->SMTPAuth = true;
                $m->Username = $smtpConfig['user'];
                $m->Password = $smtpConfig['pass'];
                $m->Port     = (int)$smtpConfig['port'];
                if ($smtpConfig['secure'] === 'ssl') {
                    $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($smtpConfig['secure'] === 'tls') {
                    $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $m->SMTPSecure    = '';
                    $m->SMTPAutoTLS   = false;
                }
                $m->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            }
            $m->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            return $m;
        };

        if ($canSendMail) {
            // 応募者への受付確認メール
            try {
                $replyBody = "{$vtuberName} 様\n\nこの度はCORO PROJECTのVTuberオーディションにご応募いただき、誠にありがとうございます。\n\n応募内容を受け付けいたしました。内容を確認のうえ、このメールアドレス宛にご連絡いたします。\n\n審査にはお時間をいただく場合がございます。あらかじめご了承ください。\n\nよろしくお願いいたします。\n\nCORO PROJECT\nhttps://coroproject.jp/";
                $mail = $buildMailer();
                $mail->addAddress($email, $vtuberName);
                $mail->Subject = '【CORO PROJECT】オーディションご応募ありがとうございます';
                $mail->Body    = $replyBody;
                $mail->send();
            } catch (Exception $mailError) {
                error_log('Audition auto-reply failed: ' . $mailError->getMessage());
            }

            // 管理者通知メール
            try {
                $genderLabels = ['female' => '女性', 'male' => '男性', 'other' => 'その他', 'private' => '非公開'];
                $affiliationLabels = ['exclusive' => '専属', 'non_exclusive' => '非専属', 'negotiable' => '相談したい'];
                $notifyBody = "VTuberオーディションの応募が届きました。\n\n"
                    . "VTuber名: {$vtuberName}\n"
                    . "年齢・性別: {$age}歳 / " . ($genderLabels[$gender] ?? $gender) . "\n"
                    . "都道府県: {$prefecture}\n"
                    . "メール: {$email}\n"
                    . "所属形態: " . ($affiliationLabels[$affiliationType] ?? $affiliationType) . "\n"
                    . "主なプラットフォーム: {$mainPlatform}\n\n"
                    . "管理画面: https://coroproject.jp/admin/inquiries/applications.php?id={$newAppId}";
                $adminMail = $buildMailer();
                $adminMail->addAddress($smtpConfig['from_email']);
                $adminMail->Subject = '【オーディション応募】' . $vtuberName . ' 様';
                $adminMail->Body    = $notifyBody;
                $adminMail->addCustomHeader('X-Application-ID', (string)$newAppId);
                $adminMail->send();
            } catch (Exception $adminMailError) {
                error_log('Audition admin notify failed: ' . $adminMailError->getMessage());
            }
        }

        // セッション更新
        $_SESSION['audition_last_submit_at'] = time();
        $_SESSION['audition_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['audition_submitted'] = true;
        $_SESSION['audition_vtuber_name'] = $vtuberName;

        $homeUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        header('Location: ' . rtrim($homeUrl, '/') . '/audition_thanks.php', true, 303);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
        $submitted = false;
    }
}

render_head('AUDITION', 'CORO PROJECT VTuberオーディション。活動中・活動予定のVTuberを対象に、所属・サポート希望者の応募を受け付けています。', [
    'canonical' => 'https://coroproject.jp/audition.php',
    'robots'    => 'index, follow',
    'og_type'   => 'website',
    'og_image'  => 'https://coroproject.jp/images/ogp.png',
]);
render_header('');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">AUDITION NODE</span>
        </div>
        <h1 class="sub-title">CORO PROJECT<br><span>VTuberオーディション</span></h1>
        <p class="sub-lead">CORO PROJECTでは、VTuberとして活動中・活動予定のクリエイターを随時募集しています。所属サポート、案件紹介、制作支援など、活動状況に合わせた関わり方をご相談いただけます。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">ROLLING AUDITION</span>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container contact-grid reveal is-visible">
      <div class="contact-info cyber-clip-lg">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">GUIDE</span>
        </div>
        <h2 class="content-title small">応募前のご案内</h2>
        <div class="stack-md contact-copy">
          <p>現在の活動規模や経験の有無は問いません。活動の目的や方向性、スタイルを重視して審査します。</p>
          <ul class="info-list">
            <li>審査結果のご連絡は、応募後1〜2週間を目安にメールにてお送りします。</li>
            <li>書類審査の後、面談・実績確認を実施する場合があります。</li>
            <li>専属・非専属いずれの形態でもご相談可能です。</li>
            <li>個人情報は審査目的にのみ使用し、第三者への提供は行いません。</li>
          </ul>
        </div>
        <div class="contact-aside-block">
          <h3>対象</h3>
          <ul class="info-list">
            <li>VTuberとして活動中または活動予定の方</li>
            <li>YouTubeやTwitchなどの配信・動画活動をされている方</li>
            <li>企業案件や所属サポートに興味をお持ちの方</li>
          </ul>
        </div>
        <div class="contact-aside-block">
          <h3>注意事項</h3>
          <p>現在他事務所に所属中の方は、契約内容をご確認のうえご応募ください。審査の状況によってはご連絡にお時間をいただく場合があります。</p>
        </div>
      </div>

      <div class="contact-form-wrap cyber-clip-lg">
        <?php if ($error): ?>
          <div class="error-box cyber-clip">
            <strong>エラーが発生しました</strong>
            <p><?= h($error) ?></p>
          </div>
        <?php endif; ?>

        <form method="post" class="contact-form" novalidate>
          <input type="hidden" name="_csrf" value="<?= h($_SESSION['audition_csrf']) ?>">
          <label style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
            <span>Website</span>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
          </label>

          <!-- セクション1: 基本情報 -->
          <fieldset>
            <legend>1. 基本情報</legend>

            <label>
              <span>VTuber名 <em class="req">*</em></span>
              <input type="text" name="vtuber_name" value="<?= h($_POST['vtuber_name'] ?? '') ?>" required maxlength="100">
            </label>

            <label>
              <span>本名</span>
              <input type="text" name="real_name" value="<?= h($_POST['real_name'] ?? '') ?>" placeholder="任意" maxlength="100">
              <small class="field-note">任意。審査に必要な場合に別途お伺いします。</small>
            </label>

            <div class="form-group">
              <span class="form-label">性別 <em class="req">*</em></span>
              <div class="radio-group">
                <?php foreach (['female' => '女性', 'male' => '男性', 'other' => 'その他', 'private' => '非公開'] as $val => $label): ?>
                  <label class="radio-label">
                    <input type="radio" name="gender" value="<?= h($val) ?>" <?= (($_POST['gender'] ?? '') === $val) ? 'checked' : '' ?> required>
                    <span><?= h($label) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <label>
              <span>年齢 <em class="req">*</em></span>
              <input type="number" name="age" value="<?= h($_POST['age'] ?? '') ?>" min="15" max="100" required style="max-width:100px;">
              <small class="field-note">15歳未満の方はご応募いただけません。</small>
            </label>

            <label>
              <span>都道府県 <em class="req">*</em></span>
              <select name="prefecture" required>
                <option value="">選択してください</option>
                <?php foreach ($prefectures as $pref): ?>
                  <option value="<?= h($pref) ?>" <?= (($_POST['prefecture'] ?? '') === $pref) ? 'selected' : '' ?>><?= h($pref) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>メールアドレス <em class="req">*</em></span>
              <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required maxlength="191">
              <small class="field-note">審査結果をお送りするため、確認可能なアドレスをご入力ください。</small>
            </label>
          </fieldset>

          <!-- セクション2: 活動情報 -->
          <fieldset>
            <legend>2. 活動情報</legend>

            <label>
              <span>活動開始時期 <em class="req">*</em></span>
              <input type="text" name="activity_start" value="<?= h($_POST['activity_start'] ?? '') ?>" placeholder="例: 2023年4月" maxlength="20" required>
            </label>

            <div class="form-group">
              <span class="form-label">主な活動プラットフォーム <em class="req">*</em></span>
              <div class="radio-group">
                <?php foreach (['YouTube','Twitch','ニコ生','Twitcasting','その他'] as $platform): ?>
                  <label class="radio-label">
                    <input type="radio" name="main_platform" value="<?= h($platform) ?>" <?= (($_POST['main_platform'] ?? '') === $platform) ? 'checked' : '' ?> required>
                    <span><?= h($platform) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="platform-group">
              <h4>各プラットフォームURL・フォロワー数</h4>
              <?php
              $platforms = [
                  ['key' => 'youtube',     'label' => 'YouTube'],
                  ['key' => 'twitch',      'label' => 'Twitch'],
                  ['key' => 'twitter',     'label' => 'X（旧Twitter）'],
                  ['key' => 'twitcasting', 'label' => 'Twitcasting'],
              ];
              foreach ($platforms as $p):
              ?>
              <div class="platform-row">
                <label>
                  <span><?= h($p['label']) ?> URL</span>
                  <input type="url" name="<?= h($p['key']) ?>_url" value="<?= h($_POST[$p['key'].'_url'] ?? '') ?>" placeholder="https://" maxlength="255">
                </label>
                <label>
                  <span><?= h($p['label']) ?> フォロワー数</span>
                  <input type="number" name="<?= h($p['key']) ?>_followers" value="<?= h($_POST[$p['key'].'_followers'] ?? '') ?>" min="0" placeholder="0" style="max-width:150px;">
                </label>
              </div>
              <?php endforeach; ?>

              <label>
                <span>その他のプラットフォーム</span>
                <textarea name="other_platform" rows="3" placeholder="上記以外のプラットフォームがあればご記入ください"><?= h($_POST['other_platform'] ?? '') ?></textarea>
              </label>
            </div>

            <label>
              <span>配信頻度 <em class="req">*</em></span>
              <select name="stream_frequency" required>
                <option value="">選択してください</option>
                <?php foreach (['週1〜2日','週3〜4日','週5日以上','不定期'] as $freq): ?>
                  <option value="<?= h($freq) ?>" <?= (($_POST['stream_frequency'] ?? '') === $freq) ? 'selected' : '' ?>><?= h($freq) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>配信・動画ジャンル・内容 <em class="req">*</em></span>
              <textarea name="stream_genre" rows="4" placeholder="ゲーム実況、雑談、歌枠、お絵描き配信など、メインの配信内容をご記入ください。" required><?= h($_POST['stream_genre'] ?? '') ?></textarea>
            </label>
          </fieldset>

          <!-- セクション3: 実績 -->
          <fieldset>
            <legend>3. 実績・サンプル</legend>

            <label>
              <span>サンプル動画・配信URL 1</span>
              <input type="url" name="sample_url_1" value="<?= h($_POST['sample_url_1'] ?? '') ?>" placeholder="https://" maxlength="255">
              <small class="field-note">審査の参考にしたい動画・配信のURLをご記入ください（任意）。</small>
            </label>
            <label>
              <span>サンプル動画・配信URL 2</span>
              <input type="url" name="sample_url_2" value="<?= h($_POST['sample_url_2'] ?? '') ?>" placeholder="https://" maxlength="255">
            </label>
            <label>
              <span>サンプル動画・配信URL 3</span>
              <input type="url" name="sample_url_3" value="<?= h($_POST['sample_url_3'] ?? '') ?>" placeholder="https://" maxlength="255">
            </label>

            <label>
              <span>過去の主な実績</span>
              <textarea name="past_achievements" rows="4" placeholder="コラボ実績、イベント出演、特定のバズ動画など、特筆すべき実績があればご記入ください（任意）。"><?= h($_POST['past_achievements'] ?? '') ?></textarea>
            </label>

            <label>
              <span>イベント・企業案件の経験</span>
              <textarea name="event_experience" rows="4" placeholder="イベント出演、企業案件対応などの経験があればご記入ください（任意）。"><?= h($_POST['event_experience'] ?? '') ?></textarea>
            </label>

            <label>
              <span>スキル・特技 <em class="req">*</em></span>
              <textarea name="skills" rows="4" placeholder="絵が描ける、歌える、3Dモデリング、動画編集、プログラミングなど、活動に活かせるスキルをご記入ください。" required><?= h($_POST['skills'] ?? '') ?></textarea>
            </label>
          </fieldset>

          <!-- セクション4: 所属 -->
          <fieldset>
            <legend>4. 所属・活動スタイル</legend>

            <div class="form-group">
              <span class="form-label">希望する所属形態 <em class="req">*</em></span>
              <div class="radio-group">
                <?php foreach (['exclusive' => '専属', 'non_exclusive' => '非専属', 'negotiable' => '相談したい'] as $val => $label): ?>
                  <label class="radio-label">
                    <input type="radio" name="affiliation_type" value="<?= h($val) ?>" <?= (($_POST['affiliation_type'] ?? '') === $val) ? 'checked' : '' ?> required>
                    <span><?= h($label) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="form-group">
              <span class="form-label">活動スタイル <em class="req">*</em></span>
              <div class="radio-group">
                <?php foreach (['full_time' => '専業（VTuber活動がメイン）', 'part_time' => '副業（別の仕事・学業と並行）', 'other' => 'その他'] as $val => $label): ?>
                  <label class="radio-label">
                    <input type="radio" name="work_style" value="<?= h($val) ?>" <?= (($_POST['work_style'] ?? '') === $val) ? 'checked' : '' ?> required>
                    <span><?= h($label) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <label>
              <span>副業・学業の内容</span>
              <input type="text" name="work_detail" value="<?= h($_POST['work_detail'] ?? '') ?>" placeholder="副業・学業の場合、本業の内容を差し支えない範囲でご記入ください（任意）" maxlength="100">
            </label>

            <label>
              <span>応募動機 <em class="req">*</em></span>
              <textarea name="motivation" rows="5" placeholder="なぜCORO PROJECTに応募しようと思ったか、どのようなサポートを希望するかをご記入ください。" required><?= h($_POST['motivation'] ?? '') ?></textarea>
            </label>

            <label>
              <span>活動目標 <em class="req">*</em></span>
              <textarea name="goal" rows="5" placeholder="今後どのような活動を目指しているか、短期・長期の目標をご記入ください。" required><?= h($_POST['goal'] ?? '') ?></textarea>
            </label>
          </fieldset>

          <!-- セクション5: 機材 -->
          <fieldset>
            <legend>5. 使用機材（任意）</legend>

            <label>
              <span>マイク・音声機材</span>
              <input type="text" name="mic_equipment" value="<?= h($_POST['mic_equipment'] ?? '') ?>" placeholder="例: SM7B + AG03MK2" maxlength="100">
            </label>

            <label>
              <span>PC スペック</span>
              <input type="text" name="pc_spec" value="<?= h($_POST['pc_spec'] ?? '') ?>" placeholder="例: Core i7-13700K / RTX 4070 / 32GB RAM" maxlength="200">
            </label>
          </fieldset>

          <!-- セクション6: その他 -->
          <fieldset>
            <legend>6. その他</legend>

            <label>
              <span>ご質問・ご要望</span>
              <textarea name="questions" rows="4" placeholder="ご不明な点、ご要望などがあればご記入ください（任意）。"><?= h($_POST['questions'] ?? '') ?></textarea>
            </label>

            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" name="privacy_agree" value="1" <?= !empty($_POST['privacy_agree']) ? 'checked' : '' ?> required>
                <span>個人情報の取り扱いについて、CORO PROJECTが審査目的で保管・使用することに同意します。 <em class="req">*</em></span>
              </label>
            </div>
          </fieldset>

          <button type="submit" class="primary-button cyber-clip">SUBMIT APPLICATION</button>
        </form>
      </div>
    </div>
  </section>
</main>
<style>
.contact-form fieldset {
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 24px;
}
.contact-form legend {
  font-weight: 700;
  font-size: 0.95em;
  padding: 0 8px;
  color: var(--accent, #a78bfa);
  letter-spacing: 0.04em;
}
.form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
.form-label { font-size: 0.9em; font-weight: 500; }
.radio-group { display: flex; flex-wrap: wrap; gap: 12px; }
.radio-label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9em; }
.checkbox-label { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 0.9em; line-height: 1.5; }
.checkbox-label input[type="checkbox"] { margin-top: 3px; flex-shrink: 0; }
.platform-group { background: rgba(255,255,255,0.03); border-radius: 6px; padding: 16px; margin-bottom: 16px; }
.platform-group h4 { font-size: 0.85em; font-weight: 600; margin: 0 0 12px; opacity: 0.7; }
.platform-row { display: grid; grid-template-columns: 1fr auto; gap: 12px; margin-bottom: 12px; align-items: end; }
@media (max-width: 600px) { .platform-row { grid-template-columns: 1fr; } }
.req { color: #f87171; font-style: normal; }
</style>
<?php render_footer(); ?>
