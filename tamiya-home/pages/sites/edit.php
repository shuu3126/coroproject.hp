<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /tamiya-home/pages/sites/index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM sites WHERE id = ?');
$stmt->execute([$id]);
$site = $stmt->fetch();
if (!$site) { header('Location: /tamiya-home/pages/sites/index.php'); exit; }

$supervisors = $pdo->query("SELECT id, name FROM users WHERE role = 'supervisor' ORDER BY name")->fetchAll();
$errors = [];
$input  = $site;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'name'          => trim($_POST['name']          ?? ''),
        'address'       => trim($_POST['address']       ?? ''),
        'work_type'     => trim($_POST['work_type']     ?? ''),
        'start_date'    => trim($_POST['start_date']    ?? ''),
        'end_date'      => trim($_POST['end_date']      ?? ''),
        'supervisor_id' => trim($_POST['supervisor_id'] ?? ''),
        'status'        => trim($_POST['status']        ?? ''),
        'memo'          => trim($_POST['memo']          ?? ''),
    ];

    if ($input['name'] === '') $errors[] = '現場名は必須です。';
    if (!in_array($input['status'], ['準備中', '施工中', '完了', '中断'])) $errors[] = '進捗状況が不正です。';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE sites SET name=?, address=?, work_type=?, start_date=?, end_date=?, supervisor_id=?, status=?, memo=?
            WHERE id=?
        ");
        $stmt->execute([
            $input['name'],
            $input['address'] ?: null,
            $input['work_type'] ?: null,
            $input['start_date'] ?: null,
            $input['end_date'] ?: null,
            $input['supervisor_id'] ?: null,
            $input['status'],
            $input['memo'] ?: null,
            $id,
        ]);
        log_action($pdo, 'update', '現場', $input['name'], $input['status']);
        header('Location: /tamiya-home/pages/sites/detail.php?id=' . $id);
        exit;
    }
}

renderHead('現場編集');
renderHeader('現場編集');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-2xl mx-auto">

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
      <?php foreach ($errors as $e): ?><div>・<?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white rounded-xl shadow-sm p-5 space-y-4">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">現場名 <span class="text-red-500">*</span></label>
      <input type="text" name="name" value="<?= htmlspecialchars($input['name']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">住所</label>
      <input type="text" name="address" value="<?= htmlspecialchars($input['address'] ?? '') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">工事種類</label>
      <input type="text" name="work_type" value="<?= htmlspecialchars($input['work_type'] ?? '') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">開始日</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($input['start_date'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($input['end_date'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">進捗状況</label>
        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
          <?php foreach (['準備中', '施工中', '完了', '中断'] as $st): ?>
            <option value="<?= $st ?>" <?= $input['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">担当監督</label>
        <select name="supervisor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
          <option value="">未設定</option>
          <?php foreach ($supervisors as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $input['supervisor_id'] == $s['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">メモ</label>
      <textarea name="memo" rows="3"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($input['memo'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="/tamiya-home/pages/sites/detail.php?id=<?= $id ?>"
         class="flex-1 text-center bg-gray-100 text-gray-600 font-bold py-3 rounded-xl text-sm">キャンセル</a>
      <button type="submit"
        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl text-sm">保存する</button>
    </div>

  </form>

  <form method="post" action="/tamiya-home/pages/sites/delete.php"
        onsubmit="return confirm('<?= htmlspecialchars($site['name']) ?> を削除しますか？\nアサイン情報も全て削除されます。')"
        class="mt-4">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit"
      class="w-full bg-white border border-red-300 text-red-500 font-bold py-3 rounded-xl text-sm hover:bg-red-50">
      この現場を削除する
    </button>
  </form>

</main>

<?php
renderBottomNav('sites');
renderFoot();
?>
