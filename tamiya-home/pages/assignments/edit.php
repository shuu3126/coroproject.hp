<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /tamiya-home/pages/assignments/index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT a.*, c.name AS craftsman_name, s.name AS site_name
    FROM assignments a
    JOIN craftsmen c ON a.craftsman_id = c.id
    JOIN sites s ON a.site_id = s.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$assignment = $stmt->fetch();
if (!$assignment) { header('Location: /tamiya-home/pages/assignments/index.php'); exit; }

$errors = [];
$input  = $assignment;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['start_date'] = trim($_POST['start_date'] ?? '');
    $input['end_date']   = trim($_POST['end_date']   ?? '');
    $input['memo']       = trim($_POST['memo']       ?? '');

    if (!$input['start_date']) $errors[] = '開始日は必須です。';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE assignments SET start_date=?, end_date=?, memo=? WHERE id=?");
        $stmt->execute([
            $input['start_date'],
            $input['end_date'] ?: null,
            $input['memo'] ?: null,
            $id,
        ]);
        log_action($pdo, 'update', 'アサイン', $assignment['craftsman_name'],
            $assignment['site_name'] . ' / ' . $input['start_date'] . '〜' . ($input['end_date'] ?: ''));
        header('Location: /tamiya-home/pages/assignments/index.php');
        exit;
    }
}

renderHead('アサイン編集');
renderHeader('アサイン編集');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-2xl mx-auto">

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
      <?php foreach ($errors as $e): ?><div>・<?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-xl border border-gray-100 p-5 mb-4">
    <div class="text-xs text-gray-400 mb-1">対象</div>
    <div class="font-semibold text-gray-800"><?= htmlspecialchars($assignment['craftsman_name']) ?></div>
    <div class="text-sm text-gray-500"><?= htmlspecialchars($assignment['site_name']) ?></div>
  </div>

  <form method="post" class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">開始日 <span class="text-red-500">*</span></label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($input['start_date']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($input['end_date'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">メモ</label>
      <textarea name="memo" rows="2"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($input['memo'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="javascript:history.back()"
         class="flex-1 text-center bg-gray-100 text-gray-600 font-bold py-3 rounded-xl text-sm">キャンセル</a>
      <button type="submit"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm">保存する</button>
    </div>

  </form>
</main>

<?php
renderBottomNav('assignments');
renderFoot();
?>
