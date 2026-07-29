<?php
include 'database/conexao.php';

echo "=== DIAGNÓSTICO DOS 26 PDFs ===\n\n";

// Pegar todos os UUIDs com PDF
$pdfs = glob('/opt/mk-auth/admin/arquivos/*/contrato_*.pdf');
$uuids_com_pdf = [];
foreach ($pdfs as $pdf) {
    preg_match('#/arquivos/([^/]+)/#', $pdf, $matches);
    if (isset($matches[1])) {
        $uuids_com_pdf[] = $matches[1];
    }
}

echo "Total de PDFs encontrados: " . count($uuids_com_pdf) . "\n\n";

// Verificar cada UUID no banco
foreach ($uuids_com_pdf as $uuid) {
    $sql = "SELECT nome, cli_ativado, contrato FROM sis_cliente WHERE uuid_cliente = '$uuid'";
    $result = $conecta->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $status_ativo = $row['cli_ativado'] == 's' ? '✅ ATIVO' : '❌ INATIVO';
        $tem_contrato = $row['contrato'] ? '✅ TEM (' . $row['contrato'] . ')' : '❌ NULL';
        
        echo "UUID: $uuid\n";
        echo "  Nome: " . $row['nome'] . "\n";
        echo "  Ativo: $status_ativo\n";
        echo "  Contrato: $tem_contrato\n";
        
        if ($row['cli_ativado'] != 's' || !$row['contrato']) {
            echo "  ⚠️  MOTIVO: Não aparece porque ";
            $motivos = [];
            if ($row['cli_ativado'] != 's') $motivos[] = "está INATIVO";
            if (!$row['contrato']) $motivos[] = "campo CONTRATO está NULL";
            echo implode(" e ", $motivos);
            echo "\n";
        }
        echo "\n";
    } else {
        echo "UUID: $uuid\n";
        echo "  ❌ NÃO EXISTE NO BANCO\n\n";
    }
}
?>
