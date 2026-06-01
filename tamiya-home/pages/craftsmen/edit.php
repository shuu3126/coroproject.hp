<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /tamiya-home/pages/craftsmen/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM craftsmen WHERE id = ?');
$stmt->execute([$id]);
$craftsman = $stmt->fetch();

if (!$craftsman) {
    header('Location: /tamiya-home/pages/craftsmen/index.php');
    exit;
}

$errors = [];
$input  = $craftsman;

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
            UPDATE craftsmen SET name=?, job_type=?, phone=?, status=?, memo=?
            WHERE id=?
        ");
        $stmt->execute([$input['name'], $input['job_type'], $input['phone'], $input['status'], $input['memo'], $id]);
        header('Location: /tamiya-home/pages/craftsmen/detail.php?id=' . $id);
        exit;
    }
}

renderHead('職人編集');
renderHeader('職人編集');
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
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
      <input type="tel" name="phone" value="<?= htmlspecialchars($input['phone'] ?? '') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($input['memo'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $id ?>"
         class="flex-1 text-center bg-gray-100 text-gray-600 font-bold py-3 rounded-xl text-sm">キャンセル</a>
      <button type="submit"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm">保存する</button>
    </div>

  </form>

  <!-- 削除 -->
  <form method="post" action="/tamiya-home/pages/craftsmen/delete.php"
        onsubmit="return confirm('<?= htmlspecialchars($craftsman['name']) ?> を削除しますか？\nアサイン履歴も全て削除されます。')"
        class="mt-4">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit"
      class="w-full bg-white border border-red-300 text-red-500 font-bold py-3 rounded-xl text-sm hover:bg-red-50">
      この職人を削除する
    </button>
  </form>

</main>

<?php
renderBottomNav('craftsmen');
renderFoot();
?>
