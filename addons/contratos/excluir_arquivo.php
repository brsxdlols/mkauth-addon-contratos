<?php
// Incluir o arquivo de conexão
include 'conexao.php';

// Verifica se o parâmetro do arquivo foi passado
if (isset($_GET['file'])) {
    $file = $_GET['file'];

    // Verifica se o arquivo existe antes de excluir
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "Arquivo excluído com sucesso.";
        } else {
            echo "Erro ao excluir o arquivo.";
        }
    } else {
        echo "Arquivo não encontrado.";
    }
} else {
    echo "Parâmetro de arquivo não especificado.";
}

// Redireciona de volta para a página principal após a exclusão
header('Location: index.php');
exit;
?>
