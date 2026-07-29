<?php

$config = include 'database/conexao.php';

$cpf_cnpj = filter_input(INPUT_GET, 'cpf_cnpj', FILTER_SANITIZE_STRING);
$cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);

if (strlen($cpf_cnpj) !== 11 && strlen($cpf_cnpj) !== 14) {
    echo "<script>alert('CPF ou CNPJ inválido!'); window.history.back();</script>";
    exit;
}

// Consultar dados do cliente
$stmt_cliente = $conecta->prepare("
    SELECT *, 
        CASE
            WHEN nascimento LIKE '__/__/____' THEN 
                nascimento
            WHEN nascimento LIKE '__/__/__' THEN 
                CONCAT(SUBSTRING(nascimento, 1, 2), '/', SUBSTRING(nascimento, 4, 2), '/', '20', SUBSTRING(nascimento, 7, 2))
            WHEN nascimento LIKE '____-__-__' THEN 
                CONCAT(SUBSTRING(nascimento, 9, 2), '/', SUBSTRING(nascimento, 6, 2), '/', SUBSTRING(nascimento, 1, 4))
            WHEN nascimento LIKE '__-__-____' THEN 
                REPLACE(nascimento, '-', '/')
            WHEN nascimento LIKE '________' THEN 
                CONCAT(SUBSTRING(nascimento, 7, 2), '/', SUBSTRING(nascimento, 5, 2), '/', SUBSTRING(nascimento, 1, 4))
            WHEN nascimento LIKE '_/_/__' OR nascimento LIKE '_/_/____' THEN 
                CONCAT(LPAD(SUBSTRING_INDEX(nascimento, '/', 1), 2, '0'), '/', 
                       LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(nascimento, '/', -2), '/', 1), 2, '0'), 
                       '/', IF(LENGTH(SUBSTRING_INDEX(nascimento, '/', -1)) = 2, 
                               CONCAT('20', SUBSTRING_INDEX(nascimento, '/', -1)), 
                               SUBSTRING_INDEX(nascimento, '/', -1)))
            ELSE 'Formato Desconhecido'
        END AS nascimento_formatada
    FROM sis_cliente 
    WHERE cpf_cnpj = ?
");
if (!$stmt_cliente) {
    die("Erro ao preparar consulta de cliente: " . $conecta->error);
}
$stmt_cliente->bind_param("s", $cpf_cnpj);
$stmt_cliente->execute();
$response_cliente = $stmt_cliente->get_result();

if ($response_cliente && $response_cliente->num_rows > 0) {
    $clientes = [];
    while ($cliente = $response_cliente->fetch_assoc()) {
        $clientes[] = $cliente;
    }

    // Lidar com mais de um contrato
    $contratoNaoAssinado = null;
    
    foreach ($clientes as $cliente) {
        $uuid_cliente = $cliente['uuid_cliente'];
        $url = CONTRATOS_DIR . $uuid_cliente . "/contrato_" . $uuid_cliente . ".pdf";

        if (!file_exists($url)) {
            $contratoNaoAssinado = $cliente; // Salva o primeiro contrato não assinado encontrado
            break; // Sai do loop ao encontrar um contrato não assinado
        }
    }

    if (!$contratoNaoAssinado) {
        echo "<script>alert('Não há contrato pendende de assinatura!');</script>";
        echo "<script>history.back();</script>";
        exit();
    }

    // Se encontrou um contrato não assinado, continua com o processo usando os dados corretos
    $nome = $contratoNaoAssinado['nome'];
    $nomeresumido = $contratoNaoAssinado['nome_res'];
    $login = $contratoNaoAssinado['login'];
    $uuid_cliente = $contratoNaoAssinado['uuid_cliente'];
    $plano = $contratoNaoAssinado['plano'];
    $cep = $contratoNaoAssinado['cep_res'];
    $endereco = $contratoNaoAssinado['endereco_res'];
    $numero = $contratoNaoAssinado['numero_res'];
    $bairro = $contratoNaoAssinado['bairro_res'];
    $cidade = $contratoNaoAssinado['cidade_res'];
    $estado = $contratoNaoAssinado['estado_res'];
    $cadastro = $contratoNaoAssinado['cadastro'];
    $nascimento = $contratoNaoAssinado['nascimento_formatada'];
    $vencimento = $contratoNaoAssinado['venc'];
    $equipamento = $contratoNaoAssinado['equipamento'];
    $adesao = number_format($contratoNaoAssinado['adesao'], 2, ',', '.');
    $cpf_cnpj = $contratoNaoAssinado['cpf_cnpj'];
    $rgcliente = $contratoNaoAssinado['rg'];
    $expedicao_rg = $contratoNaoAssinado['expedicao_rg'];
    $fonecliente = $contratoNaoAssinado['fone'];
    $emailcliente = $contratoNaoAssinado['email'];
    $complrescliente = $contratoNaoAssinado['complemento'];
    $celular2cliente = $contratoNaoAssinado['celular2'];
    $responsavel = $contratoNaoAssinado['responsavel'];
    $termo = $contratoNaoAssinado['termo'];
} else {
    echo "<script>alert('CPF ou CNPJ não encontrado!'); window.history.back();</script>";
    exit();
}

$stmt_cliente->close();

// Consultar dados do contrato
$stmt_contrato = $conecta->prepare("SELECT texto, nome FROM sis_contrato WHERE codigo = ?");
if (!$stmt_contrato) {
    die("Erro ao preparar consulta de contrato: " . $conecta->error);
}
$stmt_contrato->bind_param("s", $cliente['contrato']);
$stmt_contrato->execute();
$response_contrato = $stmt_contrato->get_result();

if ($response_contrato && $response_contrato->num_rows > 0) {
    $contrato = $response_contrato->fetch_assoc();
    $texto_contrato = $contrato['texto'];
    $nome_contrato = $contrato['nome'];
}
$stmt_contrato->close();

// Consultar dados do provedor
$stmt_provedor = $conecta->prepare("SELECT * FROM sis_provedor");
if (!$stmt_provedor) {
    die("Erro ao preparar consulta de provedor: " . $conecta->error);
}
$stmt_provedor->execute();
$response_provedor = $stmt_provedor->get_result();

if ($response_provedor && $response_provedor->num_rows > 0) {
    $provedor = $response_provedor->fetch_assoc();
    $nome_prov = $provedor['nome'];
    $razao_prov = $provedor['razao'];
    $cnpj_prov = $provedor['cnpj'];
    $endereco_prov = $provedor['endereco'];
    $cep_provedor = $cliente['cep'];
    $bairro_prov = $provedor['bairro'];
    $cidade_prov = $provedor['cidade'];
    $estado_prov = $provedor['estado'];
    $fone_prov = $provedor['fone'];
    $celular_prov = $provedor['celular'];
    $email_prov = $provedor['email'];
    $site_prov = $provedor['site'];
}
$stmt_provedor->close();

// Consultar velocidade e valor do plano
$stmt_plano = $conecta->prepare("SELECT veldown, valor FROM sis_plano WHERE nome = ?");
if (!$stmt_plano) {
    die("Erro ao preparar consulta de plano: " . $conecta->error);
}
$stmt_plano->bind_param("s", $plano);
$stmt_plano->execute();
$response_plano = $stmt_plano->get_result();

if ($response_plano && $response_plano->num_rows > 0) {
    $plano_info = $response_plano->fetch_assoc();
    $vel_plano = $plano_info['veldown'] / 1000;
    $valor_plano = number_format($plano_info['valor'], 2, ',', '.');
}
$stmt_plano->close();

function getUserIP() {
    // Verifica se o IP está disponível em HTTP_CLIENT_IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } 
    // Verifica se o IP está disponível em HTTP_X_FORWARDED_FOR (caso de proxies)
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } 
    // Caso contrário, obtém o endereço IP direto
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}