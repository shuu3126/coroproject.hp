<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$craftsmen = $pdo->query("SELECT id, name, job_type FROM craftsmen WHERE status = '稼働中' ORDER BY job_type, name")->fetchAll();
$sites     = $pdo->query("SELECT id, name FROM sites WHERE status IN ('準備中','施工中') ORDER BY name")->fetchAll();

$errors = [];
$input  = [
    'craftsman_id' => $_GET['craftsman_id'] ?? '',
    'site_id'      => $_GET['site_id']      ?? '',
    'start_date'   => date('Y-m-d'),
    'end_date'     => '',
    'memo'         => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'craftsman_id' => (int)($_POST['craftsman_id'] ?? 0),
        'site_id'      => (int)($_POST['site_id']      ?? 0),
        'start_date'   => trim($_POST['start_date'] ?? ''),
        'end_date'     => trim($_POST['end_date']   ?? ''),
        'memo'         => trim($_POST['memo']       ?? ''),
    ];

    if (!$input['craftsman_id']) $errors[] = '職人を選択してください。';
    if (!$input['site_id'])      $errors[] = '現場を選択してください。';
    if (!$input['start_date'])   $errors[] = '開始日は必須です。';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO assignments (craftsman_id, site_id, start_date, end_date, memo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['craftsman_id'],
            $input['site_id'],
            $input['start_date'],
            $input['end_date'] ?: null,
            $input['memo'] ?: null,
        ]);

        // 遷移先：現場詳細 or アサイン一覧
        $redirect = isset($_POST['site_id']) && $_POST['site_id']
            ? '/tamiya-home/pages/sites/detail.php?id=' . $input['site_id']
            : '/tamiya-home/pages/assignments/index.php';
        header('Location: ' . $redirect);
        exit;
    }
}

renderHead('アサイン登録');
renderHeader('アサイン登録');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-2xl mx-auto">

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
      <?php foreach ($errors as $e): ?><div>・<?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white rounded-xl shadow-sm p-5 space-y-4">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">職人を選択 <span class="text-red-500">*</span></label>
      <select name="craftsman_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">-- 選択してください --</option>
        <?php
        $current_job = '';
        foreach ($craftsmen as $c):
            if ($c['job_type'] !== $current_job) {
                if ($current_job !== '') echo '</optgroup>';
                echo '<optgroup label="' . htmlspecialchars($c['job_type']) . '">';
                $current_job = $c['job_type'];
            }
        ?>
          <option value="<?= $c['id'] ?>" <?= $input['craftsman_id'] == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; if ($current_job !== '') echo '</optgroup>'; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">現場を選択 <span class="text-red-500">*</span></label>
      <select name="site_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">-- 選択してください --</option>
        <?php foreach ($sites as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $input['site_id'] == $s['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">開始日 <span class="text-red-500">*</span></label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($input['start_date']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($input['end_date']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">メモ</label>
      <textarea name="memo" rows="2"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"><?= htmlspecialchars($input['memo']) ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="javascript:history.back()"
         class="flex-1 text-center bg-gray-100 text-gray-600 font-bold py-3 rounded-xl text-sm">キャンセル</a>
      <button type="submit"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm">登録する</button>
    </div>

  </form>
</main>

<?php
renderBottomNav('assignments');
renderFoot();
?>
