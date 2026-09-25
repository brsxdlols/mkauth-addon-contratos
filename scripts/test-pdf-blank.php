<?php
require __DIR__.'/../addons/contratos/functions/pdf_render_validation.php';
if (!function_exists('imagecreatetruecolor')) { echo "GD unavailable: skipped image test.\n"; exit; }
$file=tempnam(sys_get_temp_dir(),'pdf-blank-');
try {
    $im=imagecreatetruecolor(256,256);
    imagefill($im,0,0,imagecolorallocate($im,255,255,255));
    foreach (array(true,false) as $white) {
        if (!$white) imagefilledrectangle($im,20,20,120,120,imagecolorallocate($im,0,0,0));
        ob_start();imagejpeg($im,null,90);$jpg=ob_get_clean();
        file_put_contents($file,"%PDF-1.4\n1 0 obj\n<< /Type /XObject /Subtype /Image /Width 256 /Height 256 /Filter /DCTDecode /Length ".strlen($jpg)." >>\nstream\n".$jpg."\nendstream\nendobj\n%%EOF");
        if (contratos_pdf_is_blank($file)!==$white) throw new Exception('Incorrect blank classification');
    }
    imagedestroy($im);
    file_put_contents($file, '%PDF-1.4 unknown');
    if (contratos_pdf_is_blank($file)) throw new Exception('Unknown PDF classified blank');
} finally { unlink($file); }
echo "Blank PDF tests passed.\n";
