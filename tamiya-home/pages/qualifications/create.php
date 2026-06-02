<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

$craftsman_id = (int)($_GET['craftsman_id'] ?? 0);
if ($craftsman_id <= 0) {
    header('Location: /tamiya-home/pages/craftsmen/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, name FROM craftsmen WHERE id = ?');
$stmt->execute([$craftsman_id]);
$craftsman = $stmt->fetch();
if (!$craftsman) {
    header('Location: /tamiya-home/pages/craftsmen/index.php');
    exit;
}

$errors = [];
$input = [
    'name' => '',
    'issued_date' => '',
    'expiry_date' => '',
    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'name' => trim($_POST['name'] ?? ''),
        'issued_date' => trim($_POST['issued_date'] ?? ''),
        'expiry_date' => trim($_POST['expiry_date'] ?? ''),
        'note' => trim($_POST['note'] ?? ''),
    ];

    if ($input['name'] === '') {
        $errors[] = '資格名は必須です。';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO qualifications (craftsman_id, name, issued_date, expiry_date, note)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $craftsman_id,
            $input['name'],
            $input['issued_date'] ?: null,
            $input['expiry_date'] ?: null,
            $input['note'] ?: null,
        ]);

        log_action(
            $pdo,
            'create',
            '資格',
            $input['name'],
            $craftsman['name'] . ' に資格を追加'
        );

        header('Location: /tamiya-home/pages/craftsmen/detail.php?id=' . $craftsman_id);
        exit;
    }
}

renderHead('資格追加');
renderHeader('資格追加');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-3xl mx-auto">

  <div class="mb-4">
    <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $craftsman_id ?>"
       class="text-sm text-blue-500 hover:underline">職人詳細へ戻る</a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 p-5 mb-4">
    <div class="text-xs text-gray-400 mb-1">対象職人</div>
    <div class="font-bold text-gray-800"><?= htmlspecialchars($craftsman['name']) ?></div>
  </div>

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
      <?php foreach ($errors as $e): ?><div>・<?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">資格名 <span class="text-red-500">*</span></label>
      <input type="text" name="name" value="<?= htmlspecialchars($input['name']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="玉掛け技能講習">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">取得日</label>
        <input type="date" name="issued_date" value="<?= htmlspecialchars($input['issued_date']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">有効期限</label>
        <input type="date" name="expiry_date" value="<?= htmlspecialchars($input['expiry_date']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">メモ</label>
      <textarea name="note" rows="3"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        placeholder="登録番号・更新予定など"><?= htmlspecialchars($input['note']) ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $craftsman_id ?>"
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
