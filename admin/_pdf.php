<?php

function pdf_safe_text($text) {
    $text = (string)$text;

    $replaceMap = [
        '〜' => '～',
        '−' => '-',
        '—' => '-',
        '―' => '-',
        'ｰ' => '-',
        '　' => ' ',
    ];
    $text = strtr($text, $replaceMap);

    if (function_exists('mb_convert_kana')) {
        $text = mb_convert_kana($text, 'KVas', 'UTF-8');
    }

    $converted = @mb_convert_encoding($text, 'SJIS-win', 'UTF-8');
    if ($converted === false) {
        $converted = @iconv('UTF-8', 'SJIS-win//TRANSLIT//IGNORE', $text);
    }

    return $converted !== false ? $converted : '';
}

function pdf_html_escape($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function pdf_output_as_html_document($title, $payload, $outputPath, $type) {
    $fallback = preg_replace('/\.pdf$/i', '.html', $outputPath);
    $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
    if (!$items) {
        $items = [[
            'description' => $payload['description'] ?? ($payload['subject'] ?? ''),
            'amount_jpy' => $payload['amount_jpy'] ?? 0,
        ]];
    }

    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td>' . pdf_html_escape($item['description'] ?? '') . '</td><td class="amount">¥'
            . number_format((float)($item['amount_jpy'] ?? 0)) . '</td></tr>';
    }

    $subject = $type === 'receipt'
        ? ('但し ' . (string)($payload['description'] ?? ''))
        : ('件名: ' . (string)($payload['subject'] ?? ''));

    $html = '<!doctype html><html lang="ja"><head><meta charset="UTF-8"><title>'
        . pdf_html_escape($title)
        . '</title><style>body{font-family:Arial,sans-serif;max-width:960px;margin:40px auto;color:#111;line-height:1.7}.meta{text-align:right}.amount{text-align:right}table{width:100%;border-collapse:collapse;margin:28px 0}th,td{border:1px solid #ccc;padding:10px 12px}.total{font-size:24px;font-weight:700}.note{white-space:pre-wrap;border:1px solid #ccc;padding:16px;min-height:80px}</style></head><body><h1>'
        . pdf_html_escape($title)
        . '</h1><div class="meta"><p>CORO PROJECT<br>Mail: info@coroproject.jp<br>No: '
        . pdf_html_escape($payload['invoice_no'] ?? '')
        . '<br>発行日: ' . pdf_html_escape($payload['issue_date'] ?? date('Y-m-d'))
        . (!empty($payload['due_date']) ? '<br>請求期日: ' . pdf_html_escape($payload['due_date']) : '')
        . '</p></div><p>' . pdf_html_escape(($payload['talent_real_name'] ?? '') . ' 様')
        . '</p><p>' . pdf_html_escape($subject)
        . '</p><p class="total">¥' . number_format((float)($payload['amount_jpy'] ?? 0))
        . '</p><table><thead><tr><th>内容</th><th>金額</th></tr></thead><tbody>'
        . $rows
        . '</tbody></table>'
        . (!empty($payload['payment_bank_info']) ? '<h2>振込先</h2><div class="note">' . nl2br(pdf_html_escape($payload['payment_bank_info']), false) . '</div>' : '')
        . '<h2>備考</h2><div class="note">'
        . nl2br(pdf_html_escape($payload['note'] ?? ''), false)
        . '</div></body></html>';

    file_put_contents($fallback, $html);
    return $fallback;
}

function pdf_draw_text($image, $size, $x, $y, $color, $fontPath, $text) {
    $safe = pdf_safe_text($text);
    if ($safe === '') {
        return;
    }
    imagettftext($image, $size, 0, (int)$x, (int)$y, $color, $fontPath, $safe);
}

function pdf_text_width($size, $fontPath, $text) {
    $safe = pdf_safe_text($text);
    if ($safe === '') {
        return 0;
    }
    $box = imagettfbbox($size, 0, $fontPath, $safe);
    if (!is_array($box)) {
        return 0;
    }
    return abs($box[2] - $box[0]);
}

function pdf_output_as_pdf_from_image($image, $outputPath) {
    $tmpPng = $outputPath . '.tmp.png';
    imagepng($image, $tmpPng);
    imagedestroy($image);

    if (class_exists('Imagick')) {
        $imagick = new Imagick();
        $imagick->readImage($tmpPng);
        $imagick->setImageFormat('pdf');
        $imagick->writeImage($outputPath);
        $imagick->clear();
        $imagick->destroy();
        @unlink($tmpPng);
        return $outputPath;
    }

    $fallback = preg_replace('/\.pdf$/i', '.png', $outputPath);
    @rename($tmpPng, $fallback);
    return $fallback;
}

function pdf_make_invoice($config, $settings, $payload, $outputPath) {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
        return pdf_output_as_html_document('御請求書', $payload, $outputPath, 'invoice');
    }

    $fontPath = $settings['pdf_font_path'] ?? ($config['pdf']['font_path'] ?? '');
    if (!is_file($fontPath)) {
        throw new RuntimeException('PDFフォントが見つかりません: ' . $fontPath);
    }

    $img = imagecreatetruecolor(1240, 1754);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 20, 20, 20);
    $gray = imagecolorallocate($img, 120, 120, 120);
    $line = imagecolorallocate($img, 210, 210, 210);

    imagefill($img, 0, 0, $white);

    $title = '御請求書';
    $titleW = pdf_text_width(28, $fontPath, $title);
    pdf_draw_text($img, 28, (1240 - $titleW) / 2, 90, $black, $fontPath, $title);

    pdf_draw_text($img, 18, 80, 170, $black, $fontPath, ($payload['talent_real_name'] ?? '') . ' 様');

    pdf_draw_text($img, 16, 760, 160, $black, $fontPath, 'CORO PROJECT');
    pdf_draw_text($img, 13, 760, 195, $black, $fontPath, 'Mail: info@coroproject.jp');
    pdf_draw_text($img, 13, 760, 230, $black, $fontPath, '代表取締役：貞方 集');
    pdf_draw_text($img, 13, 760, 265, $black, $fontPath, '請求日：' . ($payload['issue_date'] ?? date('Y-m-d')));
    pdf_draw_text($img, 13, 760, 300, $black, $fontPath, '請求書No：' . ($payload['invoice_no'] ?? ''));
    if (!empty($payload['due_date'])) {
        pdf_draw_text($img, 13, 760, 335, $black, $fontPath, '請求期日：' . $payload['due_date']);
    }

    $stampPath = $settings['pdf_stamp_path'] ?? ($config['pdf']['stamp_path'] ?? '');
    if (is_file($stampPath)) {
        $stamp = @imagecreatefrompng($stampPath);
        if ($stamp) {
            imagecopyresampled($img, $stamp, 1010, 115, 0, 0, 150, 150, imagesx($stamp), imagesy($stamp));
            imagedestroy($stamp);
        }
    }

    pdf_draw_text($img, 14, 80, 380, $black, $fontPath, '下記の通り、ご請求申し上げます。');
    pdf_draw_text($img, 16, 80, 430, $black, $fontPath, '件名：' . ($payload['subject'] ?? ''));

    imagerectangle($img, 80, 470, 1160, 550, $line);
    imageline($img, 250, 470, 250, 550, $line);
    pdf_draw_text($img, 16, 100, 520, $black, $fontPath, '請求金額');
    pdf_draw_text($img, 22, 900, 520, $black, $fontPath, '¥' . number_format((float)($payload['amount_jpy'] ?? 0)) . ' （税込）');

    $tableTop = 610;
    imagerectangle($img, 80, $tableTop, 1160, $tableTop + 44, $line);
    imageline($img, 820, $tableTop, 820, $tableTop + 44, $line);
    pdf_draw_text($img, 13, 100, $tableTop + 30, $black, $fontPath, '内　容');
    pdf_draw_text($img, 13, 860, $tableTop + 30, $black, $fontPath, '金額（円）');

    $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
    if (!$items) {
        $items = [[
            'description' => $payload['subject'] ?? '',
            'amount_jpy' => $payload['amount_jpy'] ?? 0,
        ]];
    }

    $y = $tableTop + 44;
    $rowHeight = 44;
    $maxRows = max(6, count($items));

    for ($i = 0; $i < $maxRows; $i++) {
        imagerectangle($img, 80, $y, 1160, $y + $rowHeight, $line);
        imageline($img, 820, $y, 820, $y + $rowHeight, $line);

        if (isset($items[$i])) {
            pdf_draw_text($img, 12, 100, $y + 28, $black, $fontPath, (string)$items[$i]['description']);
            pdf_draw_text($img, 12, 980, $y + 28, $black, $fontPath, '¥' . number_format((float)$items[$i]['amount_jpy']));
        }
        $y += $rowHeight;
    }

    pdf_draw_text($img, 14, 80, $y + 50, $black, $fontPath, '振込先');
    imagerectangle($img, 80, $y + 70, 1160, $y + 170, $line);

    $bankLines = preg_split('/\R/u', (string)($payload['payment_bank_info'] ?? ''));
    $bankY = $y + 100;
    foreach ($bankLines as $lineText) {
        pdf_draw_text($img, 12, 100, $bankY, $black, $fontPath, $lineText);
        $bankY += 24;
        if ($bankY > $y + 155) break;
    }

    pdf_draw_text($img, 14, 80, $y + 220, $black, $fontPath, '備考');
    imagerectangle($img, 80, $y + 240, 1160, $y + 380, $line);

    $noteLines = preg_split('/\R/u', (string)($payload['note'] ?? ''));
    $noteY = $y + 275;
    foreach ($noteLines as $lineText) {
        pdf_draw_text($img, 12, 100, $noteY, $black, $fontPath, $lineText);
        $noteY += 28;
    }

    return pdf_output_as_pdf_from_image($img, $outputPath);
}

function pdf_make_receipt($config, $settings, $payload, $outputPath) {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
        return pdf_output_as_html_document('領収書', $payload, $outputPath, 'receipt');
    }

    $fontPath = $settings['pdf_font_path'] ?? ($config['pdf']['font_path'] ?? '');
    if (!is_file($fontPath)) {
        throw new RuntimeException('PDFフォントが見つかりません: ' . $fontPath);
    }

    $img = imagecreatetruecolor(1240, 1754);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 20, 20, 20);
    $line = imagecolorallocate($img, 210, 210, 210);

    imagefill($img, 0, 0, $white);

    $title = '領収書';
    $titleW = pdf_text_width(28, $fontPath, $title);
    pdf_draw_text($img, 28, (1240 - $titleW) / 2, 90, $black, $fontPath, $title);

    pdf_draw_text($img, 18, 80, 170, $black, $fontPath, ($payload['talent_real_name'] ?? '') . ' 様');

    pdf_draw_text($img, 16, 840, 160, $black, $fontPath, 'CORO PROJECT');
    pdf_draw_text($img, 13, 840, 195, $black, $fontPath, 'Mail: info@coroproject.jp');
    pdf_draw_text($img, 13, 840, 230, $black, $fontPath, '代表取締役：貞方 集');
    pdf_draw_text($img, 13, 840, 265, $black, $fontPath, '発行日：' . ($payload['issue_date'] ?? date('Y-m-d')));
    pdf_draw_text($img, 13, 840, 300, $black, $fontPath, '請求書No：' . ($payload['invoice_no'] ?? ''));

    $stampPath = $settings['pdf_stamp_path'] ?? ($config['pdf']['stamp_path'] ?? '');
    if (is_file($stampPath)) {
        $stamp = @imagecreatefrompng($stampPath);
        if ($stamp) {
            imagecopyresampled($img, $stamp, 1010, 115, 0, 0, 150, 150, imagesx($stamp), imagesy($stamp));
            imagedestroy($stamp);
        }
    }

    pdf_draw_text($img, 14, 80, 380, $black, $fontPath, '下記の通り、領収いたしました。');

    imagerectangle($img, 80, 430, 1160, 520, $line);
    pdf_draw_text($img, 16, 100, 485, $black, $fontPath, '領収金額');
    pdf_draw_text($img, 22, 900, 485, $black, $fontPath, '¥' . number_format((float)($payload['amount_jpy'] ?? 0)));

    pdf_draw_text($img, 14, 80, 590, $black, $fontPath, '但し　' . ($payload['description'] ?? ''));
    pdf_draw_text($img, 14, 80, 660, $black, $fontPath, '為替レート（参考）：USD→JPY ' . number_format((float)($payload['fx_rate'] ?? 0), 4));

    pdf_draw_text($img, 14, 80, 740, $black, $fontPath, '備考');
    imagerectangle($img, 80, 770, 1160, 910, $line);

    $noteLines = preg_split('/\R/u', (string)($payload['note'] ?? ''));
    $noteY = 810;
    foreach ($noteLines as $lineText) {
        pdf_draw_text($img, 12, 100, $noteY, $black, $fontPath, $lineText);
        $noteY += 28;
    }

    return pdf_output_as_pdf_from_image($img, $outputPath);
}
