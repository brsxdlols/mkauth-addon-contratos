<?php
include 'database/conexao.php';

// Pegar UUIDs do banco que têm contrato
$sql = "SELECT uuid_cliente, nome FROM sis_cliente WHERE cli_ativado = 's' AND contrato IS NOT NULL";
$result = $conecta->query($sql);
$uuidsBanco = [];
while ($row = $result->fetch_assoc()) {
    $uuidsBanco[$row['uuid_cliente']] = $row['nome'];
}

// Pegar UUIDs que têm PDF no filesystem
$uuidsComPdf = [];
exec("find /opt/mk-auth/admin/arquivos -name 'contrato_*.pdf' -exec dirname {} \\;", $output);
foreach ($output as $path) {
    $uuid = basename($path);
    if ($uuid != '.') {
        $uuidsComPdf[] = $uuid;
    }
}

echo "=== UUIDs NO BANCO COM CONTRATO: " . count($uuidsBanco) . " ===\n";
echo "=== UUIDs COM PDF NO FILESYSTEM: " . count($uuidsComPdf) . " ===\n\n";

// PDFs sem registro no banco
echo "=== PDFs SEM REGISTRO NO BANCO OU SEM CAMPO CONTRATO ===\n";
foreach ($uuidsComPdf as $uuid) {
    if (!isset($uuidsBanco[$uuid])) {
        echo "- $uuid (não está no banco ou não tem campo contrato preenchido)\n";
    }
}

echo "\n=== CLIENTES NO BANCO SEM PDF ===\n";
foreach ($uuidsBanco as $uuid => $nome) {
    if (!in_array($uuid, $uuidsComPdf)) {
        echo "- $uuid ($nome)\n";
    }
}
?>
