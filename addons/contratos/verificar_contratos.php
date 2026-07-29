<?php
include 'database/conexao.php';

$sql = "SELECT uuid_cliente FROM sis_cliente WHERE cli_ativado = 's' AND contrato IS NOT NULL";
$r = $conecta->query($sql);
$comPdf = 0;
$semPdf = 0;

while ($row = $r->fetch_assoc()) {
    $pasta = '/opt/mk-auth/admin/arquivos/' . $row['uuid_cliente'] . '/';
    $arquivos = glob($pasta . 'contrato_*.pdf');
    if (!empty($arquivos)) {
        $comPdf++;
    } else {
        $semPdf++;
    }
}

echo "Com PDF: $comPdf\n";
echo "Sem PDF: $semPdf\n";
echo "Total: " . ($comPdf + $semPdf) . "\n";
?>
