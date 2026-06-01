<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$errors = [];
$input  = ['name' => '', 'job_type' => '大工', 'phone' => '', 'status' => '稼働中', 'memo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'name'     => trim($_POST['name']     ?? ''),
        'job_type' => trim($_POST['job_type'] ?? ''),
        'phone'    => trim($_POST['phone']    ?? ''),
        'status'   => trim($_POST['status']   ?? ''),
        'memo'     => trim($_POST['memo']     ?? ''),
    ];

    if ($input['name'] === '') $errors[] = '氏名は必須です。';
    if (!in_array($input['job_type'], ['解体', '鍛冶', '大工', '電気', '水道', '内装', 'その他'])) $errors[] = '職種が不正です。';
    if (!in_array($input['status'], ['稼働中', '休業中', '退職'])) $errors[] = '稼働状況が不正です。';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO craftsmen (name, job_type, phone, status, memo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$input['name'], $input['job_type'], $input['phone'], $input['status'], $input['memo']]);
        header('Location: /tamiya-home/pages/craftsmen/index.php');
        exit;
    }
}

renderHead('職人登録');
renderHeader('職人登録');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-2xl mx-auto">

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
      <?php foreach ($errors as $e): ?>
        <div>・<?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white rounded-xl shadow-sm p-5 space-y-4">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">氏名 <span class="text-red-500">*</span></label>
      <input type="text" name="name" value="<?= htmlspecialchars($input['name']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="田中 太郎">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">職種 <span class="text-red-500">*</span></label>
      <select name="job_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <?php foreach (['解体', '鍛冶', '大工', '電気', '水道', '内装', 'その他'] as $jt): ?>
          <option value="<?= $jt ?>" <?= $input['job_type'] === $jt ? 'selected' : '' ?>><?= $jt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
      <input type="tel" name="phone" value="<?= htmlspecialchars($input['phone']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="090-0000-0000">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">稼働状況</label>
      <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <?php foreach (['稼働中', '休業中', '退職'] as $st): ?>
          <option value="<?= $st ?>" <?= $input['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">メモ</label>
      <textarea name="memo" rows="3"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="備考など"><?= htmlspecialchars($input['memo']) ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="/tamiya-home/pages/craftsmen/index.php"
         class="flex-1 text-center bg-gray-100 text-gray-600 font-bold py-3 rounded-xl text-sm">キャンセル</a>
      <button type="submit"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm">登録する</button>
    </div>

  </form>
</main>

<?php
renderBottomNav('craftsmen');
renderFoot();
?>
