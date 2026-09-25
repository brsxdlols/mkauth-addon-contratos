<?php
// Inspect the JPEG image streams used by html2pdf. Unknown PDF encodings are not
// classified as blank; this check does not assert legal or textual completeness.
function contratos_pdf_is_blank($path) {
    if (!function_exists('imagecreatefromstring')) return false;
    $pdf = file_get_contents($path);
    if ($pdf === false) return false;
    preg_match_all('/<<(?:(?!endobj).)*?\/Subtype\s*\/Image(?:(?!endobj).)*?stream\r?\n/s', $pdf, $matches, PREG_OFFSET_CAPTURE);
    $checked = 0;
    foreach ($matches[0] as $match) {
        $header = $match[0];
        if (strpos($header, '/DCTDecode') === false || !preg_match('/\/Length\s+(\d+)\s*(?:\/|>>)/', $header, $length)) return false;
        $bytes = (int) $length[1];
        $start = $match[1] + strlen($header);
        if ($bytes <= 0 || $start + $bytes > strlen($pdf)) return false;
        $info = @getimagesizefromstring(substr($pdf, $start, $bytes));
        if (!$info || $info[0] * $info[1] > 16000000) return false;
        $image = @imagecreatefromstring(substr($pdf, $start, $bytes));
        if (!$image) return false;
        $sample = imagecreatetruecolor(128, 128);
        imagecopyresampled($sample, $image, 0, 0, 0, 0, 128, 128, imagesx($image), imagesy($image));
        imagedestroy($image);
        for ($y = 0; $y < 128; $y++) for ($x = 0; $x < 128; $x++) {
            $color = imagecolorat($sample, $x, $y);
            if (min(($color >> 16) & 255, ($color >> 8) & 255, $color & 255) < 245) {
                imagedestroy($sample); return false;
            }
        }
        imagedestroy($sample);
        $checked++;
    }
    return $checked > 0;
}
