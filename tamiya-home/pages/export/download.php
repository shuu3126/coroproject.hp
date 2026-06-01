<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

requireAdmin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$type = $_GET['type'] ?? 'craftsman';
$from = $_GET['from'] ?? date('Y-m', strtotime('-1 month'));
$to   = $_GET['to']   ?? date('Y-m');

// 月次の場合はfromとtoを同じに
if ($type === 'monthly') {
    $to = $from;
}

$date_from = $from . '-01';
$date_to   = date('Y-m-t', strtotime($to . '-01'));

// ---- ヘルパー：稼働日数計算 ----
function calc_days(string $a_start, ?string $a_end, string $range_start, string $range_end): int {
    $start = max($a_start, $range_start);
    $end   = min($a_end ?? $range_end, $range_end);
    if ($start > $end) return 0;
    return (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
}

// ---- スタイル定義 ----
function header_style(): array {
    return [
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
    ];
}

function cell_style(): array {
    return [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
    ];
}

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('タミヤホーム')
    ->setTitle('タミヤホーム アサイン管理');

// ================================================================
if ($type === 'craftsman') {
// ================================================================
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('職人別レポート');

    // データ取得
    $stmt = $pdo->prepare("
        SELECT c.name AS craftsman_name, c.job_type,
               s.name AS site_name, s.address,
               a.start_date, a.end_date
        FROM assignments a
        JOIN craftsmen c ON a.craftsman_id = c.id
        JOIN sites     s ON a.site_id = s.id
        WHERE a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?)
        ORDER BY c.job_type, c.name, a.start_date
    ");
    $stmt->execute([$date_to, $date_from]);
    $rows = $stmt->fetchAll();

    // タイトル
    $sheet->setCellValue('A1', 'タミヤホーム 職人別アサインレポート');
    $sheet->setCellValue('A2', '対象期間: ' . $date_from . ' 〜 ' . $date_to);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    // ヘッダー
    $headers = ['職人名', '職種', '現場名', '住所', '開始日', '終了日', '稼働日数'];
    foreach ($headers as $i => $h) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '4', $h);
    }
    $sheet->getStyle('A4:G4')->applyFromArray(header_style());

    // データ
    $row = 5;
    foreach ($rows as $r) {
        $days = calc_days($r['start_date'], $r['end_date'], $date_from, $date_to);
        $sheet->setCellValue('A' . $row, $r['craftsman_name']);
        $sheet->setCellValue('B' . $row, $r['job_type']);
        $sheet->setCellValue('C' . $row, $r['site_name']);
        $sheet->setCellValue('D' . $row, $r['address'] ?? '');
        $sheet->setCellValue('E' . $row, $r['start_date']);
        $sheet->setCellValue('F' . $row, $r['end_date'] ?? '終了日未定');
        $sheet->setCellValue('G' . $row, $days);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray(cell_style());
        if ($row % 2 === 0) {
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
        }
        $row++;
    }

    // 列幅
    $widths = [20, 10, 25, 30, 12, 12, 10];
    foreach ($widths as $i => $w) {
        $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
    }

    $filename = 'tamiya_craftsman_' . $from . '_' . $to . '.xlsx';

// ================================================================
} elseif ($type === 'site') {
// ================================================================
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('現場別レポート');

    $stmt = $pdo->prepare("
        SELECT s.name AS site_name, s.address, s.work_type, s.status,
               c.name AS craftsman_name, c.job_type,
               a.start_date, a.end_date
        FROM assignments a
        JOIN sites      s ON a.site_id = s.id
        JOIN craftsmen  c ON a.craftsman_id = c.id
        WHERE a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?)
        ORDER BY s.name, c.job_type, c.name
    ");
    $stmt->execute([$date_to, $date_from]);
    $rows = $stmt->fetchAll();

    $sheet->setCellValue('A1', 'タミヤホーム 現場別アサインレポート');
    $sheet->setCellValue('A2', '対象期間: ' . $date_from . ' 〜 ' . $date_to);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $headers = ['現場名', '住所', '工事種類', '状態', '職人名', '職種', '開始日', '終了日', '稼働日数'];
    foreach ($headers as $i => $h) {
        $sheet->setCellValue(chr(65 + $i) . '4', $h);
    }
    $sheet->getStyle('A4:I4')->applyFromArray(header_style());

    $row = 5;
    foreach ($rows as $r) {
        $days = calc_days($r['start_date'], $r['end_date'], $date_from, $date_to);
        $sheet->setCellValue('A' . $row, $r['site_name']);
        $sheet->setCellValue('B' . $row, $r['address'] ?? '');
        $sheet->setCellValue('C' . $row, $r['work_type'] ?? '');
        $sheet->setCellValue('D' . $row, $r['status']);
        $sheet->setCellValue('E' . $row, $r['craftsman_name']);
        $sheet->setCellValue('F' . $row, $r['job_type']);
        $sheet->setCellValue('G' . $row, $r['start_date']);
        $sheet->setCellValue('H' . $row, $r['end_date'] ?? '終了日未定');
        $sheet->setCellValue('I' . $row, $days);
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray(cell_style());
        if ($row % 2 === 0) {
            $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
        }
        $row++;
    }

    $widths = [25, 30, 15, 8, 18, 8, 12, 12, 10];
    foreach ($widths as $i => $w) {
        $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
    }

    $filename = 'tamiya_sites_' . $from . '_' . $to . '.xlsx';

// ================================================================
} else {
// ================================================================ 月次サマリー
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($from . ' サマリー');

    $stmt = $pdo->prepare("
        SELECT c.name AS craftsman_name, c.job_type,
               s.name AS site_name,
               a.start_date, a.end_date, a.memo
        FROM assignments a
        JOIN craftsmen c ON a.craftsman_id = c.id
        JOIN sites     s ON a.site_id = s.id
        WHERE a.start_date <= ? AND (a.end_date IS NULL OR a.end_date >= ?)
        ORDER BY c.job_type, c.name, s.name
    ");
    $stmt->execute([$date_to, $date_from]);
    $rows = $stmt->fetchAll();

    $sheet->setCellValue('A1', 'タミヤホーム 月次サマリー  ' . $from);
    $sheet->setCellValue('A2', '出力日: ' . date('Y-m-d'));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $headers = ['職人名', '職種', '現場名', '開始日', '終了日', '稼働日数', 'メモ'];
    foreach ($headers as $i => $h) {
        $sheet->setCellValue(chr(65 + $i) . '4', $h);
    }
    $sheet->getStyle('A4:G4')->applyFromArray(header_style());

    $row   = 5;
    $total = 0;
    foreach ($rows as $r) {
        $days = calc_days($r['start_date'], $r['end_date'], $date_from, $date_to);
        $total += $days;
        $sheet->setCellValue('A' . $row, $r['craftsman_name']);
        $sheet->setCellValue('B' . $row, $r['job_type']);
        $sheet->setCellValue('C' . $row, $r['site_name']);
        $sheet->setCellValue('D' . $row, $r['start_date']);
        $sheet->setCellValue('E' . $row, $r['end_date'] ?? '終了日未定');
        $sheet->setCellValue('F' . $row, $days);
        $sheet->setCellValue('G' . $row, $r['memo'] ?? '');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray(cell_style());
        if ($row % 2 === 0) {
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
        }
        $row++;
    }

    // 合計行
    $sheet->setCellValue('E' . $row, '合計稼働日数');
    $sheet->setCellValue('F' . $row, $total);
    $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);

    $widths = [20, 10, 25, 12, 12, 10, 20];
    foreach ($widths as $i => $w) {
        $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
    }

    $filename = 'tamiya_monthly_' . $from . '.xlsx';
}

// ダウンロード
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
