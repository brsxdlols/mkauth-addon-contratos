<?php
$cpf_cnpj = $_GET['cpf_cnpj'] ?? '';
$sucesso = $_GET['sucesso'] ?? false;

if (!$sucesso) {
    require 'functions/dados_contrato.php';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $sucesso ? 'Contrato Assinado!' : 'Bem-vindo(a)' ?></title>
    <link rel="stylesheet" href="css/login.css">
    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div class="welcome-container">
    <?php if ($sucesso): ?>
        <!-- Mensagem de Sucesso -->
        <div class="success-icon">
            <i class="bi-check-circle-fill" style="font-size: 80px; color: #28a745;"></i>
        </div>
        <p class="welcome-message" style="color: #28a745;">Contrato Assinado com Sucesso!</p>
        <p class="explanation">
            Seu contrato foi assinado e enviado com sucesso. <br>
            Em breve você receberá uma confirmação no seu email ou WhatsApp.
        </p>
        <p class="explanation">
            <strong>Obrigado por confiar em nossos serviços!</strong>
        </p>
        <button class="confirm-button" onclick="window.location.href='login.php'" style="background: #28a745;">
            Voltar ao Início
        </button>
    <?php else: ?>
        <!-- Mensagem de Boas-Vindas -->
        <p class="welcome-message">Olá, <?= htmlspecialchars($nomeresumido) ?>! Seja bem-vindo!</p>
        <p class="explanation">
            Estamos prontos para atualizar o seu contrato de serviço conosco. <br>
            Este processo é simples e consiste em duas etapas:
        </p>
        <p class="explanation">
            <strong>1. Leia atentamente o contrato e, ao final, clique no botão “Assinar”.</strong>
        </p>
        
        <p class="explanation">
            <strong>2. Tire uma selfie com o documento de identidade próximo ao rosto, conforme o exemplo:</strong> 
        </p>

     <!-- Imagem de instruções -->
     <img src="images/sobre_self.png" alt="Instruções de Captura" class="instruction-image">

        <button class="confirm-button" onclick="window.location.href='contrato.php?cpf_cnpj=<?= htmlspecialchars($cpf_cnpj) ?>'">Vamos Iniciar?</button>
    <?php endif; ?>
</div>
<script src="js/welcome.js"></script>
</body>

</html>


