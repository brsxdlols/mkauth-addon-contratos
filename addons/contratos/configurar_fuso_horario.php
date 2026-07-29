<?php
// Configuração de cabeçalhos para permitir requisições AJAX
header('Content-Type: application/json');

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados JSON enviados
    $input = json_decode(file_get_contents('php://input'), true);

    // Verifica se o fuso horário foi enviado
    if (isset($input['timezone'])) {
        $timezone = $input['timezone'];

        // Aqui você pode salvar o fuso horário no banco de dados ou em outro local, se necessário.
        // Exemplo de como definir o fuso horário no PHP
        date_default_timezone_set($timezone);

        // Se desejar, pode armazenar o fuso horário em um banco de dados ou em um arquivo.
        // Exemplo para armazenar em um banco de dados:
        /*
        $conn = new mysqli('127.0.0.1', 'usuario', 'senha', 'banco');
        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }

        $sql = "UPDATE configuracoes SET timezone = ? WHERE id = 1";  // Exemplo de atualização de configuração
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $timezone);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        */

        // Resposta de sucesso
        echo json_encode([
            'status' => 'success',
            'message' => 'Fuso horário configurado com sucesso',
            'timezone' => $timezone
        ]);
    } else {
        // Resposta de erro se o fuso horário não foi enviado
        echo json_encode([
            'status' => 'error',
            'message' => 'Fuso horário não fornecido.'
        ]);
    }
} else {
    // Resposta de erro se a requisição não for POST
    echo json_encode([
        'status' => 'error',
        'message' => 'Método inválido. Use POST.'
    ]);
}
?>
