<?php
include 'database/conexao.php';  // Certifique-se de que a conexão está sendo feita corretamente

// Verificar se a requisição POST contém os dados necessários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    try {
        // Verificar se a conexão com o banco foi realizada corretamente
        if (!$conecta) {
            throw new Exception("Conexão falhou: " . $conecta->connect_error);
        }

        // Enviar o SMS
        $message = $_POST['message'];
        $response = send_sms($message);

        // Retornar a resposta ao front-end
        echo json_encode(['success' => true, 'response' => $response]);
    } catch (Exception $e) {
        // Retornar erro caso algo dê errado
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}


function send_sms($message) {
    global $conecta;  // Usando a conexão global

    // Consulta consolidada para obter os dados necessários
    $query = "
        SELECT 
            MAX(CASE WHEN nome = 'sms_servidor' THEN valor END) AS sms_servidor,
            MAX(CASE WHEN nome = 'sms_conta' THEN valor END) AS sms_conta,
            MAX(CASE WHEN nome = 'sms_senha' THEN valor END) AS sms_senha,
            (SELECT celular FROM sis_provedor LIMIT 1) AS celular
        FROM sis_opcao
    ";

    $result = $conecta->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        // Obtém os valores da consulta
        $sms_servidor = $row['sms_servidor'];
        $sms_conta = $row['sms_conta'];
        $sms_senha = $row['sms_senha'];
        $celular = "55" . preg_replace('/\D/', '', $row['celular']); // Remove caracteres não numéricos do celular

        // Monta os parâmetros para a URL
        $params = [
            'app' => 'webservices',
            'u' => $sms_conta,
            'p' => $sms_senha,
            'to' => $celular,
            'msg' => $message
        ];

        // Cria a URL completa com os parâmetros GET
        $url = $sms_servidor . "/index.php?" . http_build_query($params);

        // Inicializa o cURL para requisição GET
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30 // Ajuste o timeout conforme necessário
        ]);

        // Executa o cURL
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // Valida a resposta do cURL
        if ($error) {
            throw new Exception("Erro no cURL: $error");
        }

        return $response; // Retorna a resposta completa para análise
    } else {
        throw new Exception("Não foi possível recuperar os dados necessários para o envio do SMS.");
    }
}

?>
