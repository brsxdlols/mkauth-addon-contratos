<?php
// Verifica se o parâmetro cpf_cnpj está presente na URL
if (isset($_GET['cpf_cnpj'])) {
    $cpf_cnpj = urlencode($_GET['cpf_cnpj']); // Escapa o valor do parâmetro para evitar problemas de segurança

    // Define a URL de redirecionamento com o parâmetro
    $redirect_url = "https://dominio.com.br/admin/addons/contratos/contrato.php?cpf_cnpj=$cpf_cnpj";
} else {
    // URL de redirecionamento caso o parâmetro cpf_cnpj não esteja presente
    $redirect_url = "https://dominio.com.br/admin/addons/contratos/login.php";
}

// Realiza o redirecionamento
header("Location: $redirect_url");
exit();
