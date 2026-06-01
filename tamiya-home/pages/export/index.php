<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$current_month = date('Y-m');
$prev_month    = date('Y-m', strtotime('-1 month'));

renderHead('Excel出力');
renderHeader('Excel出力');
?>

<main class="px-4 py-4 md:px-8 md:py-6 max-w-2xl mx-auto">

  <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">

    <!-- 職人別レポート -->
    <div>
      <h2 class="text-base font-bold text-gray-700 mb-1">📊 職人別レポート</h2>
      <p class="text-xs text-gray-400 mb-3">期間内に各職人がどの現場に何日入ったかを出力します</p>
      <form method="get" action="/tamiya-home/pages/export/download.php" class="space-y-3">
        <input type="hidden" name="type" value="craftsman">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">開始月</label>
            <input type="month" name="from" value="<?= $prev_month ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">終了月</label>
            <input type="month" name="to" value="<?= $current_month ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
        </div>
        <button type="submit"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm">
          ⬇ 職人別Excelをダウンロード
        </button>
      </form>
    </div>

    <hr class="border-gray-100">

    <!-- 現場別レポート -->
    <div>
      <h2 class="text-base font-bold text-gray-700 mb-1">🏗️ 現場別レポート</h2>
      <p class="text-xs text-gray-400 mb-3">期間内に各現場にどの職人が何日入ったかを出力します</p>
      <form method="get" action="/tamiya-home/pages/export/download.php" class="space-y-3">
        <input type="hidden" name="type" value="site">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">開始月</label>
            <input type="month" name="from" value="<?= $prev_month ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">終了月</label>
            <input type="month" name="to" value="<?= $current_month ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
        </div>
        <button type="submit"
          class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl text-sm">
          ⬇ 現場別Excelをダウンロード
        </button>
      </form>
    </div>

    <hr class="border-gray-100">

    <!-- 月次サマリー -->
    <div>
      <h2 class="text-base font-bold text-gray-700 mb-1">📅 月次サマリー</h2>
      <p class="text-xs text-gray-400 mb-3">指定月の全アサインを一覧で出力します（請求・報告書用）</p>
      <form method="get" action="/tamiya-home/pages/export/download.php" class="space-y-3">
        <input type="hidden" name="type" value="monthly">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">対象月</label>
          <input type="month" name="from" value="<?= $current_month ?>"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm">
          ⬇ 月次サマリーをダウンロード
        </button>
      </form>
    </div>

  </div>
</main>

<?php
renderBottomNav('');
renderFoot();
?>
