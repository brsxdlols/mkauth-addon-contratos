<?php
    // pega dados do contrato
    require 'functions/dados_contrato.php';
    
    // Recebe o JSON com o timezone do JavaScript
    $data = json_decode(file_get_contents("php://input"), true);
    $timezonelocal = $data['timezone'] ?? 'America/Manaus'; // Fuso horário padrão caso não seja enviado

    // Configura o fuso horário
    date_default_timezone_set($timezonelocal);

    // Cria uma nova instância de DateTime e configura a localização
    $dataHora = new DateTime();
    setlocale(LC_TIME, 'pt_BR.UTF-8');

    // Detecta se a conexão é HTTPS ou HTTP
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    $baseURL = $protocol . '://' . $_SERVER['HTTP_HOST'];

    // Obtém o IP do usuário
    $ip = getUserIP();     
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Assinatura de Contrato</title>
    <link rel="stylesheet" href="css/contrato.css">
</head>
<body>
    <div class="header">
        <img src="../../../mkfiles/logo.jpg?v=123" alt="Logo da Empresa">
    </div>

    <?php 
        // Dados reais
        $dados = [
            'provedorcnpj' => $cnpj_prov,
            'provedorendereco' => $endereco_prov,
            'provedorbairro' => $bairro_prov,
            'provedorcidade' => $cidade_prov,
            'provedorcep' => $cep_provedor,
            'provedorestado' => $estado_prov,
            'nomecliente' => $nome,
            'cpfcliente' => $cpf_cnpj,
            'dataexpedicao' => $expedicao_rg,
            'nascimento' => $nascimento,
            'enderecorescliente' => $endereco,
            'numerorescliente' => $numero,
            'bairrorescliente' => $bairro,
            'cidaderescliente' => $cidade,
            'estadorescliente' => $estado,
            'diavencimento' => $vencimento,
            'planodeacesso' => $plano,
            'velocidadeplano' => $vel_plano,
            'adesao' => $adesao,
            'valor' => $valor_plano,
            'equipamento' => $equipamento,
            'provedorsite' => $site_prov,
            'provedorfone' => $fone_prov,
            'provedoremail' => $email_prov,
            'rgcliente' => $rgcliente,
            'fonecliente' => $fonecliente,
            'emailcliente' => $emailcliente,
            'complrescliente' => $complrescliente,
            'bairrocliente' => $bairro,
            'estadocliente' => $estado,
            'cepcliente' => $cep,
            'celularcliente' => $celular_prov,
            'celular2cliente' => $celular2cliente,
            'responsavel' => $responsavel,
            'logincliente' => $login,
            'termo' => $termo,
            'datacadastro' => $cadastro,
            'data' => date('d/m/Y'),
            'provedornome' => $nome_prov,
        ];      

        function substituirVariaveis($texto, $dados) {
            foreach ($dados as $chave => $valor) {
                $texto = str_replace("%$chave%", $valor, $texto);
            }
            return $texto;
        }

        // Executar a substituição
        $texto_contrato = substituirVariaveis($texto_contrato, $dados);
    ?>
    
    <div class="texto-contrato"><?= preg_replace('/<meta[^>]*>/i', '', $texto_contrato) ?></div>

    <div class="assinaturas-container">
        <div class="assinatura-provedor">
            <div>
                <img src="<?= $baseURL ?>/mkfiles/assinatura_provedor" width="200" alt="Assinatura do Provedor">
            </div>
            <p><?= $nome_prov ?><br>CONTRATADA</p>
        </div>

        <div class="assinatura-cliente">
            <div id="conteudo-assinatura">
                <br>
                <button class="botao-assinar" onclick="abrirModal()">Assinar</button>
            </div>
            <p><?= $nome ?><br>CONTRATANTE</p>
        </div>
    </div>

    <!-- Modal para a assinatura digital -->
    <div id="modalAssinatura" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h2>Assinar Contrato</h2>
            
            <!-- Abas de métodos de assinatura -->
            <div class="tabs-assinatura">
                <button class="tab-btn active" onclick="mudarAba('desenhar')">Desenhar</button>
                <button class="tab-btn" onclick="mudarAba('digitar')">Digitar</button>
                <button class="tab-btn" onclick="mudarAba('upload')">Upload</button>
            </div>

            <!-- Aba: Desenhar -->
            <div id="aba-desenhar" class="aba-content active">
                <canvas id="signatureCanvas"></canvas>
            </div>

            <!-- Aba: Digitar -->
            <div id="aba-digitar" class="aba-content" style="display: none;">
                <input type="text" id="textoAssinatura" placeholder="Digite seu nome completo" class="input-assinatura">
                <select id="fonteAssinatura" class="select-fonte" onchange="atualizarPreviewTexto()">
                    <option value="'Brush Script MT', cursive">Estilo 1</option>
                    <option value="'Lucida Handwriting', cursive">Estilo 2</option>
                    <option value="'Dancing Script', cursive">Estilo 3</option>
                </select>
                <canvas id="canvasTexto"></canvas>
            </div>

            <!-- Aba: Upload -->
            <div id="aba-upload" class="aba-content" style="display: none;">
                <div class="upload-area" onclick="document.getElementById('fileAssinatura').click()">
                    <i class="bi-cloud-upload"></i>
                    <p>Clique para fazer upload da assinatura</p>
                    <small>PNG ou JPG (fundo transparente recomendado)</small>
                </div>
                <input type="file" id="fileAssinatura" accept="image/png,image/jpeg" style="display: none;" onchange="processarUpload(event)">
                <canvas id="canvasUpload" style="display: none;"></canvas>
            </div>

            <div class="button-group">
                <button class="botao-assinar" style="width: 100%" onclick="salvarAssinatura()">Continuar</button>
                <button class="botao-limpar" style="width: 100%" onclick="limparAssinatura()">Limpar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Orientação antes da Selfie -->
    <div id="modalOrientacaoSelfie" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalOrientacaoSelfie()">&times;</span>
            <h2>Orientações para a Selfie</h2>
            <img src="images/sobre_self.png" alt="Instruções para a Selfie" style="width:100%; height:auto;">
            <p>Para prosseguir, permita o acesso à câmera na próxima tela.</p>
            <div class="button-group">
                <button class="botao-assinar" onclick="abrirModalSelfie()">Continuar</button>
            </div>
        </div>
    </div>

    <!-- Modal para captura de selfie -->
    <div id="modalSelfie" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="cancelarAssinatura()">&times;</span>
            <h2>Tirar Selfie</h2>
            <video id="videoSelfie" width="500" height="240" style="transform: scaleX(-1);" autoplay></video>
            <div class="button-group">
                <button class="botao-assinar" onclick="capturarSelfie()">Capturar Selfie</button>
                <button class="botao-limpar" onclick="cancelarAssinatura()">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="conteudo-selfie" style="transform: scaleX(-1);"></div>

    <!-- Exibição do IP e data de acesso -->
    <div class="container">
        <div class="assinatura-info">
        <p>
            Este documento foi acessado através do endereço
            <br>IP <strong><?php echo $ip; ?></strong> na data de <strong><?php echo $dataHora->format('d/m/Y \à\s H:i'); ?></strong>.
        </p>
        </div>
    </div>

    <div id="user-data" data-uuid="<?php echo trim($uuid_cliente); ?>" data-nome="<?php echo $nome; ?>" style="display:none;"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
    <script src="js/contrato.js"></script>
</body>
</html>