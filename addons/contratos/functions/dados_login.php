<?php
// Incluir o arquivo de conexão
include 'database/conexao.php';

// Configuração dos dados do Provedor
$stmt = $conecta->prepare("SELECT * FROM sis_provedor");
$stmt->execute();
$response_provedor = $stmt->get_result();

if ($response_provedor->num_rows > 0) {
    $provedor = $response_provedor->fetch_assoc();
    $fone_prov = $provedor['fone'];
    $celular_prov = $provedor['celular'];
    $email_prov = $provedor['email'];
    $site_prov = $provedor['site'];
}
