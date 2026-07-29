<?php
// Incluir o arquivo de conexão
require_once '../database/conexao.php';

// Configurar cabeçalho JSON
header('Content-Type: application/json');

// Verificar se é uma requisição válida
if (!isset($_GET['termo']) && !isset($_GET['filtro'])) {
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

// Obter parâmetros de busca
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$registrosPorPagina = 20;
$offset = ($pagina - 1) * $registrosPorPagina;

// Array para armazenar os resultados
$resultados = [];

// Montar a consulta SQL
$sql = "SELECT c.nome AS nome_cliente, c.contrato, c.uuid_cliente AS uuid_cliente, sc.nome AS nome_contrato 
        FROM sis_cliente c 
        JOIN sis_contrato sc ON c.contrato = sc.codigo 
        WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL";

// Adicionar filtro de busca por nome se termo não estiver vazio
if (!empty($termo)) {
    $termoEscapado = $conecta->real_escape_string($termo);
    $sql .= " AND c.nome LIKE '%" . $termoEscapado . "%'";
}

// Contar total de registros
$sqlCount = str_replace("SELECT c.nome AS nome_cliente, c.contrato, c.uuid_cliente AS uuid_cliente, sc.nome AS nome_contrato", "SELECT COUNT(*) as total", $sql);
$resultadoCount = $conecta->query($sqlCount);
$totalRegistros = 0;
if ($resultadoCount) {
    $rowCount = $resultadoCount->fetch_assoc();
    $totalRegistros = $rowCount['total'];
    $resultadoCount->free();
}

// Executar a consulta
if ($resultado = $conecta->query($sql)) {
    if ($resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            // Caminho da pasta onde o PDF deve ser buscado
            $pastaContrato = CONTRATOS_DIR . $row['uuid_cliente'] . "/";

            // Busca arquivos que começam com 'contrato_' e terminam com '.pdf'
            $arquivos = glob($pastaContrato . "contrato_*.pdf");

            // Verifica se foram encontrados arquivos
            if (!empty($arquivos)) {
                foreach ($arquivos as $arquivo) {
                    if (file_exists($arquivo)) {
                        // Obtém o timestamp da última modificação do arquivo
                        $timestamp = filemtime($arquivo);
                        $dataCriacao = new DateTime();
                        $dataCriacao->setTimestamp($timestamp);
                    
                        $dataAtual = new DateTime();
                        $dataExpiracao = clone $dataCriacao;
                        $dataExpiracao->modify('+1 year');
                        
                        $intervalo = $dataAtual->diff($dataExpiracao);
                    
                        // Obtém o tempo restante em meses e dias
                        $mesesRestantes = $intervalo->m + ($intervalo->y * 12);
                        $diasRestantes = $intervalo->d;
                    
                        // Chama a função para obter a cor e fidelidade
                        $status = getDateDifferenceColor($row['nome_contrato'], $dataCriacao->format('d/m/Y'), $dataAtual->format('d/m/Y'), $dataExpiracao->format('d/m/Y'), $mesesRestantes, $diasRestantes);
                        
                        // Aplicar filtro de cor se não for "todos"
                        $cores = [
                            'verde' => 'mediumseagreen',
                            'amarelo' => 'gold',
                            'laranja' => 'darkorange',
                            'vermelho' => 'indianred'
                        ];
                        
                        // Se o filtro não for "todos" e a cor não corresponder, pula este registro
                        if ($filtro !== 'todos' && isset($cores[$filtro]) && $status['color'] !== $cores[$filtro]) {
                            continue;
                        }
                    
                        $resultados[] = [
                            'nome_cliente' => $row['nome_cliente'],
                            'nome_contrato' => $row['nome_contrato'],
                            'uuid_cliente' => $row['uuid_cliente'],
                            'caminho_arquivo' => '/admin/arquivos/' . $row['uuid_cliente'] . "/" . basename($arquivo),
                            'data_criacao' => $dataCriacao->format('d/m/Y'),
                            'data_atual' => $dataAtual->format('d/m/Y'),
                            'data_expiracao' => $dataExpiracao->format('d/m/Y'),
                            'meses_restantes' => $mesesRestantes,
                            'dias_restantes' => $diasRestantes,
                            'status_color' => $status['color'],
                            'status_fidelidade' => $status['fidelidade'],
                            'contrato_expirado' => $dataAtual > $dataExpiracao
                        ];
                    }
                }
            }
        }
    }
    $resultado->free();
}

// Aplicar paginação
$totalRegistrosComPDF = count($resultados);
$totalPaginas = ceil($totalRegistrosComPDF / $registrosPorPagina);
$resultados = array_slice($resultados, $offset, $registrosPorPagina);

// Fechar conexão
$conecta->close();

// Retornar dados em JSON
echo json_encode([
    'resultados' => $resultados,
    'totalRegistros' => $totalRegistrosComPDF,
    'totalPaginas' => $totalPaginas,
    'paginaAtual' => $pagina,
    'registrosPorPagina' => $registrosPorPagina
]);

// Função para calcular a diferença entre a data do contrato e a data atual
function getDateDifferenceColor($nomeContrato, $dataCriacao, $dataAtual, $dataExpiracao, $mesesRestantes, $diasRestantes) {
    $status = [
        'color' => 'gray',
        'months' => 0,
        'fidelidade' => 'STATUS INDEFINIDO'
    ];

    if (!empty($dataCriacao)) {
        $dataAtual = DateTime::createFromFormat('d/m/Y', $dataAtual);
        $dataExpiracao = DateTime::createFromFormat('d/m/Y', $dataExpiracao);

        if (!$dataAtual || !$dataExpiracao) {
            throw new Exception("Formato de data inválido.");
        }

        if (stripos($nomeContrato, 'fidelidade') === false || $dataAtual > $dataExpiracao) {
            $status['color'] = 'mediumseagreen';
            $status['fidelidade'] = 'SEM FIDELIDADE';
        } else {
            if ($mesesRestantes > 6) {
                $status['color'] = 'indianred';
                $status['fidelidade'] = 'FIDELIDADE ATIVA';
            } elseif ($mesesRestantes > 1 && $mesesRestantes <= 6) {
                $status['color'] = 'darkorange';
                $status['fidelidade'] = 'FIDELIDADE ATIVA';
            } elseif ($mesesRestantes <= 1 && $diasRestantes <= 30) {
                $status['color'] = 'gold';
                $status['fidelidade'] = 'FIDELIDADE ATIVA';
            } else {
                $status['color'] = 'gray';
                $status['fidelidade'] = 'STATUS INDEFINIDO';
            }

            $status['months'] = $mesesRestantes;
        }
    }

    return $status;
}
?>
