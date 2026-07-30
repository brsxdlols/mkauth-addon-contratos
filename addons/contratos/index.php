<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
require_once 'addons.class.php';

session_name('mka'); session_start();
if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Logado']))
    exit(header("Location: ../../")
);

// Processar o upload pela mesma entrada autenticada que renderiza o addon.
// Isso preserva o bootstrap e a sessão do MK Auth em todas as instalações.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['contratos_action'] ?? '') === 'upload_assinatura_provedor'
) {
    require __DIR__ . '/upload_assinatura_provedor.php';
}

// Verificar permissões antes de carregar a página
require_once 'functions/verificar_apparmor.php';
$diagnostico = verificarAppArmor();

// Se houver problema de permissões, redirecionar para diagnóstico
if (!$diagnostico['ok']) {
    header('Location: diagnostico.php');
    exit;
}

// Incluir dados do index
include 'functions/dados_index.php';

// Token e status do upload da assinatura do provedor
if (empty($_SESSION['contratos_assinatura_csrf'])) {
    $_SESSION['contratos_assinatura_csrf'] = bin2hex(random_bytes(32));
}

$assinaturaProvedorPath = '/opt/mk-auth/mkfiles/assinatura_provedor';
$assinaturaProvedorExiste = is_file($assinaturaProvedorPath);
$assinaturaProvedorVersao = $assinaturaProvedorExiste ? (string) filemtime($assinaturaProvedorPath) : '';
$assinaturaFlash = $_SESSION['contratos_assinatura_flash'] ?? null;
unset($_SESSION['contratos_assinatura_flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="has-navbar-fixed-top">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>MK-AUTH :: <?php echo htmlspecialchars($Manifest->{'name'}, ENT_QUOTES, 'UTF-8'); ?></title>

    <link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
    <link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />
    <link href="css/index.css" rel="stylesheet" type="text/css" />

    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>

</head>

<body>
    <?php include('../../topo.php'); ?>

    <!-- Cabeçalho do Addon -->
    <div class="addon-header">
        <div class="addon-header-content">
            <div class="addon-info">
                <h1 class="addon-title">
                    <i class="bi-file-earmark-text"></i>
                    <?php echo htmlspecialchars($Manifest->{'name'}, ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <div class="addon-meta">
                    <span class="addon-version">
                        <i class="bi-tag"></i> v<?php echo htmlspecialchars($Manifest->{'version'}, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="addon-separator">•</span>
                    <span class="addon-author">
                        <i class="bi-person-circle"></i> <?php echo htmlspecialchars($Manifest->{'author'}, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Seletor de Tema -->
        <div class="theme-selector-container">
            <button class="theme-toggle-btn" onclick="toggleThemeMenu()">
                <i class="bi-palette"></i>
            </button>
            <div class="theme-menu" id="themeMenu">
                <div class="theme-option" data-theme="default" onclick="changeTheme('default')">
                    <div class="theme-preview">
                        <span style="background: #209cee"></span>
                        <span style="background: #23d160"></span>
                        <span style="background: #ff3860"></span>
                    </div>
                    <span>Padrão</span>
                </div>
                <div class="theme-option" data-theme="dark" onclick="changeTheme('dark')">
                    <div class="theme-preview">
                        <span style="background: #363636"></span>
                        <span style="background: #48c78e"></span>
                        <span style="background: #f14668"></span>
                    </div>
                    <span>Escuro</span>
                </div>
                <div class="theme-option" data-theme="ocean" onclick="changeTheme('ocean')">
                    <div class="theme-preview">
                        <span style="background: #006994"></span>
                        <span style="background: #00d1b2"></span>
                        <span style="background: #ffdd57"></span>
                    </div>
                    <span>Oceano</span>
                </div>
                <div class="theme-option" data-theme="sunset" onclick="changeTheme('sunset')">
                    <div class="theme-preview">
                        <span style="background: #ff6b6b"></span>
                        <span style="background: #ffa502"></span>
                        <span style="background: #ff6348"></span>
                    </div>
                    <span>Pôr do Sol</span>
                </div>
                <div class="theme-option" data-theme="nature" onclick="changeTheme('nature')">
                    <div class="theme-preview">
                        <span style="background: #27ae60"></span>
                        <span style="background: #2ecc71"></span>
                        <span style="background: #95a5a6"></span>
                    </div>
                    <span>Natureza</span>
                </div>
            </div>
        </div>
    </div>

    <div class="conteiner">
        <?php if (is_array($assinaturaFlash) && !empty($assinaturaFlash['message'])): ?>
            <div class="signature-flash signature-flash-<?= ($assinaturaFlash['type'] ?? '') === 'success' ? 'success' : 'error' ?>">
                <i class="<?= ($assinaturaFlash['type'] ?? '') === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                <span><?= htmlspecialchars($assinaturaFlash['message'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <div class="actions-bar">
            <div class="filter-container">
                <label for="filterSelect">Filtrar por:</label>
                <select id="filterSelect" onchange="filterTable()">
                    <option value="todos">Todos os Contratos</option>
                    <option value="verde">Contratos em VERDE</option>
                    <option value="amarelo">Contratos em AMARELO</option>
                    <option value="laranja">Contratos em LARANJA</option>
                    <option value="vermelho">Contratos em VERMELHO</option>
                </select>
            </div>

            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Buscar cliente pelo nome..." onkeyup="filterTable()">
            </div>

            <form
                id="signatureUploadForm"
                class="signature-upload-container"
                action="index.php"
                method="post"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="contratos_action"
                    value="upload_assinatura_provedor"
                >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['contratos_assinatura_csrf'], ENT_QUOTES, 'UTF-8') ?>"
                >
                <input
                    type="file"
                    id="signatureFileInput"
                    name="assinatura_provedor"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    hidden
                    onchange="enviarAssinaturaProvedor(this)"
                >
                <button
                    type="button"
                    id="signatureUploadButton"
                    class="signature-upload-btn"
                    onclick="selecionarAssinaturaProvedor()"
                    title="Enviar a imagem usada como assinatura do provedor nos contratos"
                >
                    <?php if ($assinaturaProvedorExiste): ?>
                        <span class="signature-preview">
                            <img
                                src="/mkfiles/assinatura_provedor?v=<?= urlencode($assinaturaProvedorVersao) ?>"
                                alt="Assinatura atual"
                            >
                        </span>
                    <?php else: ?>
                        <i class="bi-image"></i>
                    <?php endif; ?>
                    <span><?= $assinaturaProvedorExiste ? 'Atualizar Assinatura' : 'Enviar Assinatura' ?></span>
                </button>
            </form>

            <div class="backup-container">
                <button class="backup-btn" onclick="fazerBackupContratos()" title="Abrir página para baixar todos os contratos">
                    <i class="bi-download"></i>
                    <span>Baixar Todos os Contratos</span>
                </button>
            </div>
        </div>

        <div class="table-container">
            <table id="clientTable">
                <thead>
                    <tr>
                        <th class="desktop-title">NOME DO CLIENTE</th>
                        <th class="desktop-title">STATUS</th>
                        <th class="desktop-title">DATA CONTRATO</th>
                        <th class="desktop-title">DATA EXPIRAÇÃO</th>
                        <th class="desktop-title">TEMPO RESTANTE</th>
                        <th class="desktop-title">CONTRATO</th>
                        <th class="desktop-title">EXCLUIR</th>
                        <th class="mobile-title">CLIENTE</th>
                        <th class="mobile-title">ST</th>
                        <th class="mobile-title">CRIADO</th>
                        <th class="mobile-title">EXPIRA</th>
                        <th class="mobile-title">RESTA</th>
                        <th class="mobile-title">PDF</th>
                        <th class="mobile-title">EXC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($resultados as $resultado): 
                        // Calcular texto do tempo restante
                        $tempoRestante = '';
                        if ($resultado['data_atual'] > $resultado['data_expiracao']) {
                            $tempoRestante = 'EXPIRADO';
                        } else {
                            $meses = max(0, $resultado['meses_restantes']);
                            $dias = max(0, $resultado['dias_restantes']);
                            if ($meses > 0) {
                                $mesesTexto = ($meses == 1) ? "mês" : "meses";
                                $diasTexto = ($dias == 1) ? "dia" : "dias";
                                $tempoRestante = "$meses $mesesTexto e $dias $diasTexto";
                            } else {
                                $diasTexto = ($dias == 1) ? "dia" : "dias";
                                $tempoRestante = "$dias $diasTexto";
                            }
                        }
                    ?>
                        <tr>
                            <td style="text-align: left;"><?= htmlspecialchars($resultado['nome_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="status-dot" style="background-color: <?= $resultado['status_color'] ?>;" title="<?= $resultado['status_fidelidade'] ?>"></span>
                                <span class="status-text"><?= $resultado['status_fidelidade'] ?></span>
                            </td>
                            <td><?= $resultado['data_criacao']->format('d/m/Y') ?></td>
                            <td><?= $resultado['data_expiracao']->format('d/m/Y') ?></td>
                            <td><?= $tempoRestante ?></td>
                            <?php
                            // Detecta se a conexão é HTTPS ou HTTP
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
                            $baseURL = $protocol . '://' . $_SERVER['HTTP_HOST'];

                            // Remove o prefixo "/opt/mk-auth" do caminho do arquivo, caso exista
                            $caminhoArquivo = str_replace('/opt/mk-auth', '', $resultado['caminho_arquivo']);
                            ?>

                            <td>
                                <a href="<?= $baseURL . '/' . ltrim(htmlspecialchars($caminhoArquivo, ENT_QUOTES, 'UTF-8'), '/') ?>" target="_blank" title="Baixar o contrato em PDF">
                                    <img src="images/pdf.png" alt="PDF" width="23" height="23">
                                </a>
                            </td>

                            <td>
                                <i class="bi-trash3-fill" style="font-size: 18px; color: #ff3860 !important; cursor: pointer;"
                                    onclick="confirmDelete('<?= htmlspecialchars('/opt/mk-auth/' . $resultado['caminho_arquivo'], ENT_QUOTES, 'UTF-8') ?>')"
                                    title="Excluir o contrato atual"></i>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <?php 
            // Calcular os números de início e fim dos registros mostrados
            $registroInicio = $offset + 1;
            $registroFim = min($offset + $registrosPorPagina, $totalRegistros);
            ?>
            <div class="pagination-info">
                Mostrando <?= $registroInicio ?> até <?= $registroFim ?> de <?= $totalRegistros ?> registros
            </div>
            <div class="pagination-controls">
                <?php if ($paginaAtual > 1): ?>
                    <a href="?pagina=1" class="pagination-btn" title="Primeira página">1</a>
                <?php endif; ?>
                
                <?php if ($paginaAtual > 2): ?>
                    <a href="?pagina=<?= $paginaAtual - 1 ?>" class="pagination-btn"><?= $paginaAtual - 1 ?></a>
                <?php endif; ?>

                <span class="pagination-btn active"><?= $paginaAtual ?></span>

                <?php if ($paginaAtual < $totalPaginas - 1): ?>
                    <a href="?pagina=<?= $paginaAtual + 1 ?>" class="pagination-btn"><?= $paginaAtual + 1 ?></a>
                <?php endif; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <?php if ($paginaAtual < $totalPaginas - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="?pagina=<?= $totalPaginas ?>" class="pagination-btn" title="Última página"><?= $totalPaginas ?></a>
                <?php endif; ?>

                <a href="?pagina=<?= min($paginaAtual + 1, $totalPaginas) ?>" class="pagination-btn next-btn" title="Próxima">Próxima »</a>
                <a href="?pagina=<?= $totalPaginas ?>" class="pagination-btn last-btn" title="Última">Última »</a>
            </div>
        </div>
    </div>

    <?php include('../../baixo.php'); ?>

    <script src="../../menu.js.hhvm"></script>
    <script src="js/themes.js"></script>
    
    <!-- Todos os contratos carregados do PHP para busca global -->
    <script>
    window.todosContratos = <?php echo json_encode($todosResultadosParaJS ?? []); ?>;
    console.log('✅ Carregados', window.todosContratos.length, 'contratos para busca global');
    console.log('📋 Dados:', window.todosContratos);
    console.log('🔍 Total registros PHP:', <?php echo $totalRegistros ?? 0; ?>);
    </script>
    
    <script src="js/index.js"></script>
</body>

</html>
