<?php
require __DIR__.'/../addons/contratos/functions/attachments.php';
function rejects($fn) {
    try { $fn(); } catch (RuntimeException $e) { return; }
    throw new Exception('Entrada inválida aceita.');
}
contratos_attachment_dates('2024-02-29', '');
contratos_attachment_dates('2024-02-29', '2024-02-29');
rejects(function(){ contratos_attachment_dates('2025-02-29', ''); });
rejects(function(){ contratos_attachment_dates('', '2026-01-01'); });
rejects(function(){ contratos_attachment_dates('2025-01-01', '2024-12-31'); });
$file = tempnam(sys_get_temp_dir(), 'contract-type-');
try {
    file_put_contents($file, '<?php echo "not a PDF";');
    rejects(function() use ($file) { contratos_attachment_type($file); });
    file_put_contents($file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    if (contratos_attachment_type($file) !== 'png') throw new Exception('PNG recusado.');
} finally { unlink($file); }
echo "Attachment validation passed.\n";
