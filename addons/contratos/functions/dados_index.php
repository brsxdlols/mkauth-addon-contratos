<?php
// Incluir o arquivo de conexão
include 'database/conexao.php';

// Definir CONTRATOS_DIR
if (!defined('CONTRATOS_DIR')) {
    define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');
}

// Defina a URL base
$baseUrl = "https://" . $_SERVER['HTTP_HOST'] . "/admin/arquivos/";

// Diretório dos arquivos PDF
$directory = '../../../admin/arquivos/';

// ============ PAGINAÇÃO E BUSCA ============
// Buscar registros por página da configuração do sistema
$sqlRegPagina = "SELECT regpagina FROM sis_opcao LIMIT 1";
$resultRegPagina = $conecta->query($sqlRegPagina);
if ($resultRegPagina && $row = $resultRegPagina->fetch_assoc()) {
    $registrosPorPagina = (int)$row['regpagina'];
} else {
    $registrosPorPagina = 50; // Valor padrão caso não encontre
}

// Página atual (padrão é 1)
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Parâmetro de busca
$termoBusca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Calcular offset
$offset = ($paginaAtual - 1) * $registrosPorPagina;

// Array para armazenar os resultados
$resultados = [];
$totalRegistros = 0;

// Primeiro, contar o total de clientes ativos com contratos
$sqlCount = "SELECT COUNT(*) as total 
             FROM sis_cliente c 
             WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL";

if ($resultadoCount = $conecta->query($sqlCount)) {
    $rowCount = $resultadoCount->fetch_assoc();
    $totalClientesAtivos = $rowCount['total'];
    $resultadoCount->free();
} else {
    $totalClientesAtivos = 0;
}

// Consulta para obter dados dos clientes com ativo = 'sim' e que possuem contratos
$sql = "SELECT c.nome AS nome_cliente, c.contrato, c.uuid_cliente AS uuid_cliente, sc.nome AS nome_contrato
        FROM sis_cliente c 
        JOIN sis_contrato sc ON c.contrato = sc.codigo 
        WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL";

// Executar a consulta
if ($resultado = $conecta->query($sql)) {
    // Verifica se há resultados
    if ($resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            // Caminho da pasta onde o PDF deve ser buscado
            $pastaContrato = CONTRATOS_DIR . $row['uuid_cliente'] . "/";

            // Busca arquivos que começam com 'contrato_' e terminam com '.pdf'
            $arquivos = glob($pastaContrato . "contrato_*.pdf");

            // Verifica se foram encontrados arquivos
            if (!empty($arquivos)) {
                foreach ($arquivos as $arquivo) {
                    // Verifica se o arquivo existe antes de tentar obter a data
                    if (file_exists($arquivo)) {
                        // Obtém o timestamp da última modificação do arquivo
                        $timestamp = filemtime($arquivo);
                        $dataCriacao = new DateTime();
                        $dataCriacao->setTimestamp($timestamp); // Define a data de criação do arquivo como DateTime
                    
                        $dataAtual = new DateTime();
                        $dataExpiracao = clone $dataCriacao;
                        $dataExpiracao->modify('+1 year');
                        
                        $intervalo = $dataAtual->diff($dataExpiracao);
                    
                        // Obtém o tempo restante em meses e dias
                        $mesesRestantes = $intervalo->m + ($intervalo->y * 12); // Inclui anos em meses
                        $diasRestantes = $intervalo->d; // Dias restantes após os meses completos
                    
                        // Chama a função para obter a cor e fidelidade, passando a data de criação
                        $status = getDateDifferenceColor($row['nome_contrato'], $dataCriacao->format('d/m/Y'), $dataAtual->format('d/m/Y'), $dataExpiracao->format('d/m/Y'), $mesesRestantes, $diasRestantes);
                    
                        $resultados[] = [
                            'nome_cliente' => $row['nome_cliente'],
                            'nome_contrato' => $row['nome_contrato'],
                            'uuid_cliente' => $row['uuid_cliente'],
                            'numero_contrato' => $row['contrato'] ?? '',
                            'caminho_arquivo' => '/admin/arquivos/' . $row['uuid_cliente'] . "/" . basename($arquivo),
                            'data_criacao' => $dataCriacao,
                            'data_criacao_formatada' => $dataCriacao->format('d/m/Y'),
                            'data_atual' => $dataAtual,
                            'data_expiracao' => $dataExpiracao,
                            'intervalo' => $intervalo->format('%y anos, %m meses e %d dias'),
                            'meses_restantes' => $mesesRestantes,
                            'dias_restantes' => $diasRestantes,
                            'status_color' => $status['color'],
                            'status_fidelidade' => $status['fidelidade']
                        ];
                    }
                }
            }
        }
    } else {
        echo "Nenhum cliente ativo encontrado.";
    }
    $resultado->free();
} else {
    echo "Erro na consulta: " . $conecta->error;
}

// ============ PAGINAÇÃO - APLICAR FILTRO DE BUSCA E LIMITE ============
// Filtrar resultados por busca se houver termo de busca
if (!empty($termoBusca)) {
    $resultadosFiltrados = array_filter($resultados, function($item) use ($termoBusca) {
        return stripos($item['nome_cliente'], $termoBusca) !== false;
    });
    $resultados = array_values($resultadosFiltrados); // Reindexar array
}

// Total de clientes ativos para exibição
$totalRegistros = count($resultados);

// Total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Guardar todos os resultados para JavaScript (antes da paginação)
// Converter objetos DateTime para strings para permitir serialização JSON
$todosResultadosParaJS = [];
if (count($resultados) > 0) {
    $todosResultadosParaJS = array_map(function($item) {
        return [
            'nome_cliente' => $item['nome_cliente'],
            'nome_contrato' => $item['nome_contrato'],
            'uuid_cliente' => $item['uuid_cliente'],
            'numero_contrato' => $item['numero_contrato'] ?? '',
            'caminho_arquivo' => $item['caminho_arquivo'],
            'data_criacao_formatada' => $item['data_criacao_formatada'],
            'data_expiracao' => isset($item['data_expiracao']) && is_object($item['data_expiracao']) ? 
                $item['data_expiracao']->format('Y-m-d H:i:s') : '',
            'meses_restantes' => $item['meses_restantes'],
            'dias_restantes' => $item['dias_restantes'],
            'status_color' => $item['status_color'],
            'status_fidelidade' => $item['status_fidelidade']
        ];
    }, $resultados);
}

// Aplicar paginação no array de resultados (somente para exibição inicial)
$resultados = array_slice($resultados, $offset, $registrosPorPagina);

// Fechar a conexão com o banco de dados
$conecta->close();

// Função para calcular a diferença entre a data do contrato e a data atual
function getDateDifferenceColor($nomeContrato, $dataCriacao, $dataAtual, $dataExpiracao, $mesesRestantes, $diasRestantes) {
    $status = [
        'color' => 'gray', // Cor padrão, caso o arquivo não seja encontrado
        'months' => 0,
        'fidelidade' => 'STATUS INDEFINIDO' // Status padrão da fidelidade
    ];

    if (!empty($dataCriacao)) {
        // Converte as datas para DateTime para comparação
        $dataAtual = DateTime::createFromFormat('d/m/Y', $dataAtual);
        $dataExpiracao = DateTime::createFromFormat('d/m/Y', $dataExpiracao);

        if (!$dataAtual || !$dataExpiracao) {
            throw new Exception("Formato de data inválido.");
        }

        // Verifica se o contrato não tem "fidelidade" ou se já expirou
        if (stripos($nomeContrato, 'fidelidade') === false || $dataAtual > $dataExpiracao) {
            $status['color'] = 'mediumseagreen'; // Verde
            $status['fidelidade'] = 'SEM FIDELIDADE';
        } else {
            // Define a cor e status com base nos meses e dias restantes
            if ($mesesRestantes > 6) {
                $status['color'] = 'indianred'; // Vermelho
                $status['fidelidade'] = 'FIDELIDADE ATIVA';
            } elseif ($mesesRestantes > 1 && $mesesRestantes <= 6) {
                $status['color'] = 'darkorange'; // Laranja escuro
                $status['fidelidade'] = 'FIDELIDADE ATIVA';
            } elseif ($mesesRestantes <= 1 && $diasRestantes <= 30) {
                $status['color'] = 'gold'; // Dourado
                $status['fidelidade'] = 'FIDELIDADE ATIVA';
            } else {
                $status['color'] = 'gray'; // Cinza
                $status['fidelidade'] = 'STATUS INDEFINIDO';
            }

            $status['months'] = $mesesRestantes;
        }
    }

    return $status;
}



?>
