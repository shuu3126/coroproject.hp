<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$stmt = $pdo->query("
    SELECT q.*, c.name AS craftsman_name,
           DATEDIFF(q.expiry_date, CURDATE()) AS remaining_days
    FROM qualifications q
    JOIN craftsmen c ON q.craftsman_id = c.id
    WHERE q.expiry_date IS NOT NULL
      AND q.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY q.expiry_date ASC, c.name, q.name
");
$alert_qualifications = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT q.*, c.name AS craftsman_name
    FROM qualifications q
    JOIN craftsmen c ON q.craftsman_id = c.id
    ORDER BY c.name, q.expiry_date IS NULL, q.expiry_date ASC, q.name
");
$qualifications = $stmt->fetchAll();

renderHead('資格管理');
renderHeader('資格管理');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-3xl mx-auto">

  <div class="bg-white rounded-xl border border-gray-100 p-5 mb-5">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-bold text-gray-700">期限切れ・期限間近</h2>
      <span class="text-xs px-2 py-0.5 rounded font-medium <?= count($alert_qualifications) ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800' ?>">
        <?= count($alert_qualifications) ?> 件
      </span>
    </div>

    <?php if ($alert_qualifications): ?>
      <div class="space-y-2">
        <?php foreach ($alert_qualifications as $q): ?>
          <div class="border border-gray-100 rounded-lg p-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $q['craftsman_id'] ?>"
                   class="text-sm font-semibold text-blue-700 hover:underline"><?= htmlspecialchars($q['craftsman_name']) ?></a>
                <div class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars($q['name']) ?></div>
              </div>
              <div class="shrink-0"><?= qualification_expiry_badge($q['expiry_date']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center text-gray-400 py-6 text-sm">期限切れ・期限間近の資格はありません</div>
    <?php endif; ?>
  </div>

  <div class="flex items-center justify-between mb-3">
    <h2 class="text-sm font-bold text-gray-700">全資格一覧</h2>
    <span class="text-sm text-gray-400"><?= count($qualifications) ?> 件</span>
  </div>

  <?php if ($qualifications): ?>
    <div class="md:hidden space-y-2">
      <?php foreach ($qualifications as $q): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <div class="flex items-start justify-between gap-3 mb-2">
            <div class="min-w-0">
              <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $q['craftsman_id'] ?>"
                 class="font-semibold text-sm text-blue-700 hover:underline"><?= htmlspecialchars($q['craftsman_name']) ?></a>
              <div class="text-sm text-gray-800 mt-0.5"><?= htmlspecialchars($q['name']) ?></div>
            </div>
            <div class="shrink-0"><?= qualification_expiry_badge($q['expiry_date']) ?></div>
          </div>
          <?php if ($q['issued_date']): ?>
            <div class="text-xs text-gray-400">取得日: <?= htmlspecialchars($q['issued_date']) ?></div>
          <?php endif; ?>
          <?php if ($q['note']): ?>
            <div class="text-xs text-gray-500 mt-2"><?= nl2br(htmlspecialchars($q['note'])) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="hidden md:block bg-white rounded-xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500">
          <tr>
            <th class="text-left font-semibold px-4 py-3">職人名</th>
            <th class="text-left font-semibold px-4 py-3">資格名</th>
            <th class="text-left font-semibold px-4 py-3">取得日</th>
            <th class="text-left font-semibold px-4 py-3">有効期限</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($qualifications as $q): ?>
            <tr class="border-t border-gray-100">
              <td class="px-4 py-3">
                <a href="/tamiya-home/pages/craftsmen/detail.php?id=<?= $q['craftsman_id'] ?>"
                   class="font-medium text-blue-700 hover:underline"><?= htmlspecialchars($q['craftsman_name']) ?></a>
              </td>
              <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($q['name']) ?></td>
              <td class="px-4 py-3 text-gray-500"><?= $q['issued_date'] ? htmlspecialchars($q['issued_date']) : '—' ?></td>
              <td class="px-4 py-3"><?= qualification_expiry_badge($q['expiry_date']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-400 py-12 bg-white rounded-xl border border-gray-100">資格が登録されていません</div>
  <?php endif; ?>
</main>

<?php
renderBottomNav('qualifications');
renderFoot();
?>
