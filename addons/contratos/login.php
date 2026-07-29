<?php 
    // Obtem informações do Provedor
    require 'functions/dados_login.php'; 

    // Detecta se a conexão é HTTPS ou HTTP
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    $baseURL = $protocol . '://' . $_SERVER['HTTP_HOST'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização de contrato</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="form-container">
        <!-- Logo da empresa -->
        <img src="<?= $baseURL ?>/mkfiles/logo.jpg" width="200" alt="Logo do Provedor">

        <!-- Título explicativo -->
        <p class="explanation">
            Em atendimento à Lei Geral de Proteção de Dados (LGPD) e às recentes resoluções da ANATEL, tornou-se necessário revisar e atualizar o contrato de serviço de internet para novos e antigos clientes.
        </p>

        <h4>Por favor, informe o CPF ou CNPJ!</h4>

        <form action="boas_vindas.php" method="GET">
            <input type="text" id="cpf_cnpj" name="cpf_cnpj" required oninput="applyCpfCnpjMask(this)">
            <button type="submit">Enviar</button>
        </form>

        <!-- Informação de contato -->
        <p class="contact-info">
            Em caso de dúvida, entre em contato conosco!<br>
            Telefone(s): <?= $fone_prov ?> - <?= $celular_prov ?><br>
            E-mail: <?= $email_prov ?><br>
            <?= $site_prov ?><br>
        </p>
    </div>

    <script src="js/login.js"></script>
</body>

</html>