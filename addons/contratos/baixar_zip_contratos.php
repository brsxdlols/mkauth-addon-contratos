<?php
// Iniciar sessão
session_name('mka');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão direta com banco
$host = "127.0.0.1";
$user = "root";
$pass = "vertrigo";
$db = "mkradius";

$conecta = mysqli_connect($host, $user, $pass, $db);
mysqli_set_charset($conecta, "utf8");

if (mysqli_connect_errno()) {
    die('Erro de conexão com banco de dados');
}

// Definir diretório dos contratos
define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');

// Buscar todos os clientes com contratos
$sql = "SELECT c.nome AS nome_cliente, c.contrato, c.uuid_cliente, sc.nome AS nome_contrato
        FROM sis_cliente c 
        JOIN sis_contrato sc ON c.contrato = sc.codigo 
        WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL
        ORDER BY c.nome ASC";

$resultado = $conecta->query($sql);

if (!$resultado) {
    die('Erro ao buscar contratos: ' . $conecta->error);
}

// Nome do arquivo ZIP
$zipFilename = 'contratos_backup_' . date('Y-m-d_H-i-s') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipFilename;

// Criar arquivo ZIP
$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die('Não foi possível criar o arquivo ZIP');
}

$totalAdicionados = 0;
$arquivosProcessados = [];

while ($row = $resultado->fetch_assoc()) {
    // Caminho da pasta do cliente onde o PDF deve ser buscado
    $pastaContrato = CONTRATOS_DIR . $row['uuid_cliente'] . "/";
    
    // Busca arquivos que começam com 'contrato_' e terminam com '.pdf'
    $arquivos = glob($pastaContrato . "contrato_*.pdf");
    
    // Verifica se foram encontrados arquivos
    if (!empty($arquivos)) {
        foreach ($arquivos as $arquivo) {
            if (file_exists($arquivo) && is_readable($arquivo)) {
                // Criar um nome único para o arquivo no ZIP
                // Formato: NomeCliente_TipoContrato_arquivo.pdf
                $nomeCliente = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['nome_cliente']);
                $nomeContrato = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['nome_contrato']);
                $nomeArquivo = basename($arquivo);
                
                $nomeNoZip = $nomeCliente . '_' . $nomeContrato . '_' . $nomeArquivo;
                
                // Adicionar arquivo ao ZIP
                if ($zip->addFile($arquivo, $nomeNoZip)) {
                    $totalAdicionados++;
                    $arquivosProcessados[] = $nomeNoZip;
                }
            }
        }
    }
}

$resultado->free();
$conecta->close();

// Fechar o arquivo ZIP
$zip->close();

// Verificar se algum arquivo foi adicionado
if ($totalAdicionados === 0) {
    unlink($zipPath);
    die('Nenhum contrato encontrado para adicionar ao ZIP');
}

// Enviar o arquivo ZIP para download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . filesize($zipPath));
header('Pragma: no-cache');
header('Expires: 0');

// Enviar arquivo
readfile($zipPath);

// Deletar arquivo temporário
unlink($zipPath);

exit;
