<?php
include 'database/conexao.php';
require_once __DIR__ . '/contract_history.php';

if (!defined('CONTRATOS_DIR')) {
    define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');
}

contratos_ensure_history_schema($conecta);
$registrosPorPagina = 50;
$resultRegPagina = $conecta->query('SELECT regpagina FROM sis_opcao LIMIT 1');
if ($resultRegPagina && ($rowRegPagina = $resultRegPagina->fetch_assoc())) {
    $registrosPorPagina = max(1, (int) $rowRegPagina['regpagina']);
}

$paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
$termoBusca = trim((string) ($_GET['busca'] ?? ''));
$resultadosCompletos = array();

$sql = "SELECT c.nome AS nome_cliente, c.login, c.contrato, c.uuid_cliente, sc.nome AS nome_contrato
        FROM sis_cliente c
        JOIN sis_contrato sc ON c.contrato = sc.codigo
        WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL
        ORDER BY c.nome";
$query = $conecta->query($sql);

if ($query) {
    while ($row = $query->fetch_assoc()) {
        $uuid = trim((string) $row['uuid_cliente']);
        if ($uuid === '' || strpos($uuid, '..') !== false || preg_match('/[\\\\\/]/', $uuid)) {
            continue;
        }

        $arquivos = glob(rtrim(CONTRATOS_DIR, '/\\') . DIRECTORY_SEPARATOR . $uuid . DIRECTORY_SEPARATOR . 'contrato_*.pdf');
        if (!$arquivos) {
            continue;
        }
        usort($arquivos, function ($left, $right) {
            return (int) @filemtime($right) - (int) @filemtime($left);
        });
        $arquivo = $arquivos[0];
        $timestamp = (int) @filemtime($arquivo);
        if ($timestamp <= 0) {
            continue;
        }

        $dataArquivo = new DateTime('@' . $timestamp);
        $dataArquivo->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $historico = contratos_get_latest_history($conecta, $uuid, (string) $row['login']);
        if ($historico && !empty($historico['start_date']) && !empty($historico['end_date'])) {
            $dataCriacao = new DateTime($historico['start_date']);
            $dataExpiracao = new DateTime($historico['end_date']);
            $fonteVigencia = 'history';
        } else {
            $dataCriacao = clone $dataArquivo;
            $dataExpiracao = clone $dataArquivo;
            $dataExpiracao->modify('+1 year');
            $fonteVigencia = 'pdf';
        }

        $status = contratos_temporal_status($dataExpiracao, 60);
        $diasAbsolutos = abs((int) $status['days']);
        if ($status['key'] === 'expired') {
            $tempoRestante = 'VENCIDO HÁ ' . $diasAbsolutos . ' ' . ($diasAbsolutos === 1 ? 'DIA' : 'DIAS');
        } elseif ($status['days'] === 0) {
            $tempoRestante = 'VENCE HOJE';
        } else {
            $intervalo = (new DateTime('today'))->diff($dataExpiracao);
            $meses = ($intervalo->y * 12) + $intervalo->m;
            $dias = $intervalo->d;
            $tempoRestante = $meses > 0
                ? $meses . ' ' . ($meses === 1 ? 'mês' : 'meses') . ' e ' . $dias . ' ' . ($dias === 1 ? 'dia' : 'dias')
                : $dias . ' ' . ($dias === 1 ? 'dia' : 'dias');
        }

        $resultadosCompletos[] = array(
            'nome_cliente' => $row['nome_cliente'],
            'login' => $row['login'],
            'nome_contrato' => $row['nome_contrato'],
            'uuid_cliente' => $uuid,
            'numero_contrato' => $row['contrato'] ?? '',
            'caminho_arquivo' => '/admin/arquivos/' . rawurlencode($uuid) . '/' . rawurlencode(basename($arquivo)),
            'data_criacao' => $dataCriacao,
            'data_criacao_formatada' => $dataCriacao->format('d/m/Y'),
            'data_expiracao' => $dataExpiracao,
            'data_expiracao_formatada' => $dataExpiracao->format('d/m/Y'),
            'tempo_restante' => $tempoRestante,
            'dias_restantes_total' => (int) $status['days'],
            'status_key' => $status['key'],
            'status_color' => $status['color'],
            'status_label' => $status['label'],
            'fonte_vigencia' => $fonteVigencia,
        );
    }
    $query->free();
}

usort($resultadosCompletos, function ($left, $right) {
    $dateCompare = $left['data_expiracao']->getTimestamp() <=> $right['data_expiracao']->getTimestamp();
    return $dateCompare !== 0 ? $dateCompare : strcasecmp($left['nome_cliente'], $right['nome_cliente']);
});

$resumoContratos = array('all' => count($resultadosCompletos), 'active' => 0, 'warning' => 0, 'expired' => 0);
foreach ($resultadosCompletos as $item) {
    if (isset($resumoContratos[$item['status_key']])) {
        $resumoContratos[$item['status_key']]++;
    }
}

if ($termoBusca !== '') {
    $resultadosCompletos = array_values(array_filter($resultadosCompletos, function ($item) use ($termoBusca) {
        return stripos($item['nome_cliente'], $termoBusca) !== false || stripos($item['login'], $termoBusca) !== false;
    }));
}

$totalRegistros = count($resultadosCompletos);
$totalPaginas = max(1, (int) ceil($totalRegistros / $registrosPorPagina));
$paginaAtual = min($paginaAtual, $totalPaginas);
$offset = ($paginaAtual - 1) * $registrosPorPagina;
$todosResultadosParaJS = array_map(function ($item) {
    return array(
        'nome_cliente' => $item['nome_cliente'], 'login' => $item['login'],
        'nome_contrato' => $item['nome_contrato'], 'uuid_cliente' => $item['uuid_cliente'],
        'numero_contrato' => $item['numero_contrato'], 'caminho_arquivo' => $item['caminho_arquivo'],
        'data_criacao_formatada' => $item['data_criacao_formatada'],
        'data_expiracao_formatada' => $item['data_expiracao_formatada'],
        'tempo_restante' => $item['tempo_restante'], 'dias_restantes_total' => $item['dias_restantes_total'],
        'status_key' => $item['status_key'], 'status_color' => $item['status_color'], 'status_label' => $item['status_label'],
    );
}, $resultadosCompletos);

$resultados = array_slice($resultadosCompletos, $offset, $registrosPorPagina);
$conecta->close();
