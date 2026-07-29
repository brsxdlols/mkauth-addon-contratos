<?php
// INCLUE FUNCOES DE ADDONS
require_once 'addons.class.php';

// VERIFICA SE USUARIO ESTA LOGADO
if (!isset($_SESSION['mka_logado']) && !isset($_SESSION['MKA_Logado'])) {
    header('Location: /admin/login.hhvm');
    exit;
}

// Verificar permissões do AppArmor
require_once 'functions/verificar_apparmor.php';
$diagnostico = verificarAppArmor();
$apparmor_info = getAppArmorInfo();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="has-navbar-fixed-top">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>MK-AUTH :: Diagnóstico do Sistema - Contratos</title>

    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />
    <link href="css/diagnostico.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <?php include('../../topo.php'); ?>

    <div class="diagnostico-container">
        <?php if (!$diagnostico['ok']): ?>
            <!-- Problema detectado -->
            <div class="diagnostico-header">
                <i class="bi-exclamation-triangle-fill"></i>
                <h1>Problema de Permissões Detectado</h1>
                <p>O sistema não consegue gravar arquivos no diretório de contratos</p>
            </div>

            <div class="diagnostico-status erro">
                <h2><i class="bi-x-circle"></i> <?= htmlspecialchars($diagnostico['mensagem'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p>É necessário corrigir as permissões para que os contratos possam ser salvos corretamente.</p>
            </div>

            <?php if (!empty($diagnostico['detalhes'])): ?>
            <div class="diagnostico-detalhes">
                <h3><i class="bi-info-circle"></i> Detalhes do Problema</h3>
                <ul>
                    <?php foreach ($diagnostico['detalhes'] as $detalhe): ?>
                        <li><?= htmlspecialchars($detalhe, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($diagnostico['comando_correcao'])): ?>
            <div class="diagnostico-comando">
                <h3><i class="bi-terminal"></i> Comando de Correção</h3>
                <div style="position: relative;">
                    <button id="btn-copiar" class="btn-copiar" onclick="copiarComando()">
                        <i class="bi-clipboard"></i>
                        <span>Copiar</span>
                    </button>
                    <div class="comando-box">
                        <pre id="comando-texto"><?= htmlspecialchars($diagnostico['comando_correcao'], ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                </div>
            </div>

            <div class="diagnostico-instrucoes">
                <h4><i class="bi-lightbulb"></i> Como Aplicar a Correção</h4>
                <ol>
                    <li>Clique no botão <strong>"Copiar"</strong> acima para copiar o comando</li>
                    <li>Abra um terminal no servidor com acesso root</li>
                    <li>Cole e execute o comando copiado</li>
                    <li>Aguarde a mensagem de sucesso</li>
                    <li>Clique em <strong>"Recarregar e Verificar"</strong> abaixo</li>
                </ol>
            </div>
            <?php endif; ?>

            <!-- Informações de Debug (apenas se AppArmor estiver ativo) -->
            <?php if ($apparmor_info['ativo']): ?>
            <div class="diagnostico-detalhes" style="margin-top: 2rem;">
                <h3><i class="bi-bug"></i> Informações de Debug</h3>
                <ul>
                    <li>AppArmor: <?= $apparmor_info['ativo'] ? 'Ativo ⚠️' : 'Inativo' ?></li>
                    <li>Perfil Central: <?= $apparmor_info['perfil_central'] ? 'Encontrado' : 'Não encontrado' ?></li>
                    <li>Permissão Configurada: <?= $apparmor_info['tem_permissao'] ? 'Sim ✓' : 'Não ✗' ?></li>
                    <?php if (!empty($apparmor_info['bloqueios_recentes'])): ?>
                        <li>Bloqueios Recentes: <?= count($apparmor_info['bloqueios_recentes']) ?> encontrado(s)</li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Tudo OK -->
            <div class="diagnostico-header">
                <i class="bi-check-circle-fill diagnostico-sucesso-icon"></i>
                <h1>Sistema Funcionando Corretamente</h1>
                <p>Todas as permissões estão configuradas adequadamente</p>
            </div>

            <div class="diagnostico-status sucesso">
                <h2><i class="bi-check-circle"></i> <?= htmlspecialchars($diagnostico['mensagem'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p>O sistema está pronto para receber e armazenar contratos.</p>
            </div>

            <div class="diagnostico-detalhes">
                <h3><i class="bi-info-circle"></i> Configuração Atual</h3>
                <ul>
                    <?php foreach ($diagnostico['detalhes'] as $detalhe): ?>
                        <li><?= htmlspecialchars($detalhe, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Ações -->
        <div class="diagnostico-acoes">
            <?php if (!$diagnostico['ok']): ?>
                <button id="btn-recarregar" class="btn-action btn-recarregar" onclick="recarregarPagina()">
                    <i class="bi-arrow-clockwise"></i>
                    <span>Recarregar e Verificar</span>
                </button>
            <?php else: ?>
                <a href="index.php" class="btn-action btn-recarregar">
                    <i class="bi-arrow-right-circle"></i>
                    <span>Ir para Contratos</span>
                </a>
            <?php endif; ?>
            
            <a href="../../" class="btn-action btn-voltar">
                <i class="bi-house"></i>
                <span>Voltar ao Painel</span>
            </a>
        </div>
    </div>

    <?php include('../../baixo.php'); ?>

    <script src="js/diagnostico.js"></script>
</body>
</html>
