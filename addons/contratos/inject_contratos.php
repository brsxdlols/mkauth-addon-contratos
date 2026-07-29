<?php
// Preparar TODOS os contratos para JavaScript
// Usar conexão existente ou criar nova
if (!isset($conecta) || !$conecta) {
    include 'database/conexao.php';
    $conecta2 = $conecta;
} else {
    // Criar nova conexão para não interferir
    include 'database/conexao.php';
    $conecta2 = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['database']);
}

$sql_todos = "SELECT c.nome AS nome_cliente, c.contrato, c.uuid_cliente, sc.nome AS nome_contrato 
              FROM sis_cliente c 
              JOIN sis_contrato sc ON c.contrato = sc.codigo 
              WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL
              ORDER BY c.nome ASC";

$todosContratos = [];
if ($resultado_todos = $conecta2->query($sql_todos)) {
    while ($row = $resultado_todos->fetch_assoc()) {
        $pastaContrato = CONTRATOS_DIR . $row['uuid_cliente'] . "/";
        $arquivos = glob($pastaContrato . "contrato_*.pdf");
        
        if (!empty($arquivos)) {
            foreach ($arquivos as $arquivo) {
                if (file_exists($arquivo)) {
                    $timestamp = filemtime($arquivo);
                    $dataCriacao = new DateTime();
                    $dataCriacao->setTimestamp($timestamp);
                    $dataAtual = new DateTime();
                    $dataExpiracao = clone $dataCriacao;
                    $dataExpiracao->modify('+1 year');
                    $intervalo = $dataAtual->diff($dataExpiracao);
                    $mesesRestantes = $intervalo->m + ($intervalo->y * 12);
                    $diasRestantes = $intervalo->d;
                    $status = getDateDifferenceColor($row['nome_contrato'], $dataCriacao->format('d/m/Y'), $dataAtual->format('d/m/Y'), $dataExpiracao->format('d/m/Y'), $mesesRestantes, $diasRestantes);
                    $tempoRestante = '';
                    if ($dataAtual > $dataExpiracao) {
                        $tempoRestante = 'EXPIRADO';
                    } else {
                        $meses = max(0, $mesesRestantes);
                        $dias = max(0, $diasRestantes);
                        if ($meses > 0) {
                            $tempoRestante = "$meses " . (($meses == 1) ? "mês" : "meses") . " e $dias " . (($dias == 1) ? "dia" : "dias");
                        } else {
                            $tempoRestante = "$dias " . (($dias == 1) ? "dia" : "dias");
                        }
                    }
                    $todosContratos[] = [
                        'nome_cliente' => $row['nome_cliente'],
                        'status_color' => $status['color'],
                        'status_fidelidade' => $status['fidelidade'],
                        'data_criacao' => $dataCriacao->format('d/m/Y'),
                        'data_expiracao' => $dataExpiracao->format('d/m/Y'),
                        'tempo_restante' => $tempoRestante,
                        'caminho_arquivo' => '/admin/arquivos/' . $row['uuid_cliente'] . "/" . basename($arquivo),
                        'caminho_completo' => '/opt/mk-auth/admin/arquivos/' . $row['uuid_cliente'] . "/" . basename($arquivo)
                    ];
                }
            }
        }
    }
    $resultado_todos->free();
}
$conecta2->close();
?>
<script>
    window.todosContratosData = <?php echo json_encode($todosContratos); ?>;
    console.log('✅ PHP carregou', window.todosContratosData.length, 'contratos');
</script>
