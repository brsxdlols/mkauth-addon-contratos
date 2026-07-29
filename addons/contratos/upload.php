<?php
include "database/conexao.php";
include "send_sms.php";

$nomeCliente = $_GET['nome'] ?? 'O cliente';
$uuid_cliente = $_GET['uuid'] ?? uniqid();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $diretorioDestino = CONTRATOS_DIR;
    $diretorioUUID = $diretorioDestino . $uuid_cliente;

    if (!is_dir($diretorioUUID)) {
        mkdir($diretorioUUID, 0777, true);
    }

    $caminhoArquivo = $diretorioUUID . '/contrato_' . $uuid_cliente . '.pdf';

    if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoArquivo)) {
        send_sms("*" . $nomeCliente . "* já assinou o contrato!");
        
        // Salvar mensagem de sucesso na sessão
        session_start();
        $_SESSION['contrato_assinado'] = true;
        $_SESSION['contrato_cliente'] = $nomeCliente;
        
        echo json_encode(['status' => 'success', 'message' => 'PDF salvo com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Falha ao mover o PDF.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Arquivo não enviado.']);
}
?>
