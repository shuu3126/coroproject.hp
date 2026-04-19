<?php
function pdf_require_gd() {
    if (!extension_loaded('gd') || !function_exists('imagettftext')) {
        throw new RuntimeException('PDF生成に必要な GD / FreeType が利用できません。サーバー設定を確認してください。');
    }
}

function pdf_get_font_path($settings, $config) {
    $path = isset($settings['pdf_font_path']) ? trim((string)$settings['pdf_font_path']) : '';
    if ($path === '') {
        $path = $config['pdf']['font_path'];
    }
    return $path;
}

function pdf_get_stamp_path($settings, $config) {
    $path = isset($settings['pdf_stamp_path']) ? trim((string)$settings['pdf_stamp_path']) : '';
    if ($path === '') {
        $path = $config['pdf']['stamp_path'];
    }
    return $path;
}

function pdf_text_bbox($size, $angle, $font, $text) {
    $box = imagettfbbox($size, $angle, $font, $text);
    $xs = [$box[0], $box[2], $box[4], $box[6]];
    $ys = [$box[1], $box[3], $box[5], $box[7]];
    return [
        'width' => max($xs) - min($xs),
        'height' => max($ys) - min($ys),
        'left' => min($xs),
        'top' => min($ys),
    ];
}

function pdf_draw_text($img, $font, $size, $x, $y, $text, $color, $align = 'left') {
    $text = (string)$text;
    if ($text === '') return;
    $bbox = pdf_text_bbox($size, 0, $font, $text);
    if ($align === 'center') {
        $x -= $bbox['width'] / 2;
    } elseif ($align === 'right') {
        $x -= $bbox['width'];
    }
    imagettftext($img, $size, 0, (int)$x, (int)$y, $color, $font, $text);
}

function pdf_draw_multiline($img, $font, $size, $x, $y, $lines, $color, $lineHeight = 1.5) {
    $curY = $y;
    foreach ((array)$lines as $line) {
        pdf_draw_text($img, $font, $size, $x, $curY, (string)$line, $color, 'left');
        $curY += (int)round($size * $lineHeight);
    }
}

function pdf_draw_box($img, $x1, $y1, $x2, $y2, $color, $fillColor = null) {
    if ($fillColor !== null) {
        imagefilledrectangle($img, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $fillColor);
    }
    imagerectangle($img, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $color);
}

function pdf_embed_stamp_png($img, $stampPath, $dstX, $dstY, $dstW, $dstH) {
    if (!$stampPath || !is_file($stampPath)) {
        return;
    }
    $stamp = @imagecreatefrompng($stampPath);
    if (!$stamp) {
        return;
    }
    imagealphablending($img, true);
    imagesavealpha($img, true);
    imagecopyresampled($img, $stamp, (int)$dstX, (int)$dstY, 0, 0, (int)$dstW, (int)$dstH, imagesx($stamp), imagesy($stamp));
    imagedestroy($stamp);
}

function pdf_write_single_page_from_jpeg($jpgPath, $pdfPath) {
    $imgData = file_get_contents($jpgPath);
    if ($imgData === false) {
        throw new RuntimeException('PDF化する一時画像を読み込めませんでした。');
    }
    $size = @getimagesize($jpgPath);
    if (!$size) {
        throw new RuntimeException('一時画像サイズを取得できませんでした。');
    }
    $imgW = $size[0];
    $imgH = $size[1];
    $pageW = 595.28;
    $pageH = 841.89;
    $content = sprintf("q\n%.2f 0 0 %.2f 0 0 cm\n/Im0 Do\nQ\n", $pageW, $pageH);

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Count 1 /Kids [3 0 R] >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgW} /Height {$imgH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($imgData) . " >>\nstream\n" . $imgData . "\nendstream\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    file_put_contents($pdfPath, $pdf);
}

function pdf_make_invoice($config, $settings, $payload, $absolutePath) {
    pdf_require_gd();
    ensure_dir_path(dirname($absolutePath));

    $font = pdf_get_font_path($settings, $config);
    if (!is_file($font)) {
        throw new RuntimeException('PDF用フォントが見つかりません: ' . $font);
    }

    $w = (int)$config['pdf']['render_width'];
    $h = (int)$config['pdf']['render_height'];
    $img = imagecreatetruecolor($w, $h);
    imageantialias($img, true);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 25, 25, 25);
    $line = imagecolorallocate($img, 180, 180, 180);
    $light = imagecolorallocate($img, 245, 245, 245);
    imagefill($img, 0, 0, $white);

    $left = 120;
    $right = $w - 120;
    $y = 140;

    pdf_draw_text($img, $font, 44, $w / 2, $y, '御請求書', $black, 'center');
    $y += 120;

    pdf_draw_text($img, $font, 28, $left, $y, ($payload['talent_real_name'] ?: $payload['talent_display_name']) . ' 様', $black);

    $infoX = (int)($w * 0.64);
    pdf_draw_text($img, $font, 24, $infoX, $y, $settings['office_name'], $black);
    pdf_draw_text($img, $font, 21, $infoX, $y + 40, 'Mail: ' . $settings['office_email'], $black);
    pdf_draw_text($img, $font, 21, $infoX, $y + 80, '請求日：' . $payload['issue_date'], $black);
    pdf_draw_text($img, $font, 21, $infoX, $y + 120, '請求書No：' . $payload['invoice_no'], $black);

    pdf_embed_stamp_png($img, pdf_get_stamp_path($settings, $config), $right - 210, $y - 40, 160, 160);

    $y += 230;
    pdf_draw_text($img, $font, 23, $left, $y, '下記の通り、ご請求申し上げます。', $black);
    $y += 44;
    pdf_draw_text($img, $font, 24, $left, $y, '件名：' . $payload['subject'], $black);
    $y += 55;

    $labelW = 210;
    $boxH = 66;
    pdf_draw_box($img, $left, $y, $left + $labelW, $y + $boxH, $black, null);
    pdf_draw_box($img, $left + $labelW, $y, $right, $y + $boxH, $black, null);
    pdf_draw_text($img, $font, 24, $left + 20, $y + 44, '請求金額', $black);
    pdf_draw_text($img, $font, 32, $right - 20, $y + 46, '¥' . format_money($payload['amount_jpy']) . '  （税込）', $black, 'right');
    $y += 120;

    $tableTop = $y;
    $col1 = (int)(($right - $left) * 0.72);
    $col2 = ($right - $left) - $col1;
    $headerH = 40;
    $rowH = 40;
    $rows = $payload['items'];
    $drawRows = max(count($rows), 6);

    pdf_draw_box($img, $left, $tableTop, $left + $col1, $tableTop + $headerH, $black, $light);
    pdf_draw_box($img, $left + $col1, $tableTop, $right, $tableTop + $headerH, $black, $light);
    pdf_draw_text($img, $font, 20, $left + 16, $tableTop + 28, '内　容', $black);
    pdf_draw_text($img, $font, 20, $left + $col1 + 16, $tableTop + 28, '金額（円）', $black);

    for ($i = 0; $i < $drawRows; $i++) {
        $rowY = $tableTop + $headerH + ($i * $rowH);
        pdf_draw_box($img, $left, $rowY, $left + $col1, $rowY + $rowH, $black, null);
        pdf_draw_box($img, $left + $col1, $rowY, $right, $rowY + $rowH, $black, null);
        if (isset($rows[$i])) {
            pdf_draw_text($img, $font, 18, $left + 14, $rowY + 27, mb_strimwidth($rows[$i]['description'], 0, 52, '…', 'UTF-8'), $black);
            pdf_draw_text($img, $font, 18, $right - 14, $rowY + 27, '¥' . format_money($rows[$i]['amount_jpy']), $black, 'right');
        }
    }

    $y = $tableTop + $headerH + ($drawRows * $rowH) + 70;
    pdf_draw_text($img, $font, 22, $left, $y, '備考', $black);
    $y += 18;
    pdf_draw_box($img, $left, $y, $right, $y + 220, $black, null);
    $noteLines = parse_lines_to_array($payload['note']);
    if (!$noteLines) $noteLines = [''];
    pdf_draw_multiline($img, $font, 18, $left + 18, $y + 46, $noteLines, $black, 1.6);

    $tmpJpg = tempnam(sys_get_temp_dir(), 'coro_inv_') . '.jpg';
    imagejpeg($img, $tmpJpg, 90);
    imagedestroy($img);
    pdf_write_single_page_from_jpeg($tmpJpg, $absolutePath);
    @unlink($tmpJpg);
}

function pdf_make_receipt($config, $settings, $payload, $absolutePath) {
    pdf_require_gd();
    ensure_dir_path(dirname($absolutePath));

    $font = pdf_get_font_path($settings, $config);
    if (!is_file($font)) {
        throw new RuntimeException('PDF用フォントが見つかりません: ' . $font);
    }

    $w = (int)$config['pdf']['render_width'];
    $h = (int)$config['pdf']['render_height'];
    $img = imagecreatetruecolor($w, $h);
    imageantialias($img, true);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 25, 25, 25);
    imagefill($img, 0, 0, $white);

    $left = 120;
    $right = $w - 120;
    $y = 140;

    pdf_draw_text($img, $font, 42, $w / 2, $y, '領収書', $black, 'center');
    $y += 120;
    pdf_draw_text($img, $font, 28, $left, $y, ($payload['talent_real_name'] ?: $payload['talent_display_name']) . ' 様', $black);

    $infoX = (int)($w * 0.64);
    pdf_draw_text($img, $font, 24, $infoX, $y, $settings['office_name'], $black);
    pdf_draw_text($img, $font, 21, $infoX, $y + 40, 'Mail: ' . $settings['office_email'], $black);
    pdf_draw_text($img, $font, 21, $infoX, $y + 80, '発行日: ' . $payload['issue_date'], $black);
    pdf_draw_text($img, $font, 21, $infoX, $y + 120, '請求書No: ' . $payload['invoice_no'], $black);

    pdf_embed_stamp_png($img, pdf_get_stamp_path($settings, $config), $right - 210, $y - 40, 160, 160);

    $y += 220;
    pdf_draw_text($img, $font, 23, $left, $y, '下記の通り、領収いたしました。', $black);
    $y += 55;
    pdf_draw_box($img, $left, $y, $right, $y + 76, $black, null);
    pdf_draw_text($img, $font, 24, $left + 20, $y + 46, '領収金額', $black);
    pdf_draw_text($img, $font, 34, $right - 20, $y + 48, '¥' . format_money($payload['amount_jpy']), $black, 'right');

    $y += 150;
    pdf_draw_text($img, $font, 22, $left, $y, '但し　' . $payload['description'], $black);
    $y += 70;
    pdf_draw_text($img, $font, 21, $left, $y, '為替レート（参考）：USD→JPY ' . format_money($payload['fx_rate'], 4), $black);
    $y += 70;
    pdf_draw_text($img, $font, 22, $left, $y, '備考：', $black);
    $y += 16;
    pdf_draw_box($img, $left, $y, $right, $y + 180, $black, null);
    $noteLines = parse_lines_to_array($payload['note']);
    if (!$noteLines) $noteLines = [''];
    pdf_draw_multiline($img, $font, 18, $left + 18, $y + 46, $noteLines, $black, 1.6);

    $tmpJpg = tempnam(sys_get_temp_dir(), 'coro_rec_') . '.jpg';
    imagejpeg($img, $tmpJpg, 90);
    imagedestroy($img);
    pdf_write_single_page_from_jpeg($tmpJpg, $absolutePath);
    @unlink($tmpJpg);
}
