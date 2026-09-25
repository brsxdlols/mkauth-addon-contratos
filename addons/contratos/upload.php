<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
ignore_user_abort(true);

require_once 'database/conexao.php';
require_once 'functions/contract_history.php';

$requestId = bin2hex(random_bytes(6));
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

function contratos_upload_log($event, $context = array())
{
    global $logDir, $requestId;
    $context['time'] = date('c');
    $context['request_id'] = $requestId;
    $context['event'] = $event;
    $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    @file_put_contents($logDir . '/upload.log', json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function contratos_upload_response($statusCode, $status, $message, $extra = array())
{
    global $requestId;
    http_response_code($statusCode);
    echo json_encode(array_merge(array('status' => $status, 'message' => $message, 'request_id' => $requestId), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    contratos_upload_response(405, 'error', 'Método não permitido.');
}

$uuid = trim((string) ($_GET['uuid'] ?? ''));
$nomeCliente = trim((string) ($_GET['nome'] ?? 'O cliente'));
if (!preg_match('/^[A-Za-z0-9._-]{1,80}$/', $uuid)) {
    contratos_upload_log('invalid_uuid', array('uuid' => substr($uuid, 0, 100)));
    contratos_upload_response(400, 'error', 'Identificação do cliente inválida.');
}

$validation = $conecta->prepare('SELECT sc.texto FROM sis_cliente c JOIN sis_contrato sc ON sc.codigo = c.contrato WHERE c.uuid_cliente = ? LIMIT 1');
if (!$validation) { http_response_code(500); exit('Não foi possível validar o modelo do contrato.'); }
$validation->bind_param('s', $uuid);
$validation->execute();
$modelRow = $validation->get_result()->fetch_assoc();
$validation->close();
$modelValid = $modelRow && trim(html_entity_decode(strip_tags((string) $modelRow['texto']), ENT_QUOTES, 'UTF-8')) !== '';

if (!$modelValid) {
    contratos_upload_response(422, 'error', 'Nenhum modelo com texto está vinculado ao cadastro. Solicite a correção ao provedor antes de assinar.');
}
if (!isset($_FILES['arquivo']) || !is_array($_FILES['arquivo'])) {
    contratos_upload_log('missing_file', array('uuid' => $uuid));
    contratos_upload_response(400, 'error', 'O PDF não chegou ao servidor.');
}

$file = $_FILES['arquivo'];
$uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError !== UPLOAD_ERR_OK) {
    $messages = array(
        UPLOAD_ERR_INI_SIZE => 'O PDF excedeu o limite do servidor.',
        UPLOAD_ERR_FORM_SIZE => 'O PDF excedeu o limite do formulário.',
        UPLOAD_ERR_PARTIAL => 'O envio do PDF foi interrompido.',
        UPLOAD_ERR_NO_FILE => 'O PDF não foi enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'O servidor está sem diretório temporário.',
        UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o PDF.',
        UPLOAD_ERR_EXTENSION => 'Uma extensão do servidor bloqueou o PDF.',
    );
    contratos_upload_log('php_upload_error', array('uuid' => $uuid, 'code' => $uploadError));
    contratos_upload_response(400, 'error', $messages[$uploadError] ?? 'Falha no envio do PDF.');
}

$size = (int) ($file['size'] ?? 0);
if ($size < 1000 || $size > 40 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
    contratos_upload_log('invalid_file', array('uuid' => $uuid, 'size' => $size));
    contratos_upload_response(400, 'error', 'O arquivo recebido está vazio ou é grande demais.');
}

$handle = @fopen($file['tmp_name'], 'rb');
$signature = $handle ? fread($handle, 5) : '';
if ($handle) fclose($handle);
if ($signature !== '%PDF-') {
    contratos_upload_log('invalid_signature', array('uuid' => $uuid, 'size' => $size));
    contratos_upload_response(415, 'error', 'O arquivo recebido não é um PDF válido.');
}

require_once __DIR__ . '/functions/pdf_render_validation.php';
if (contratos_pdf_is_blank($file['tmp_name'])) {
    contratos_upload_log('blank_pdf_rejected', array('uuid' => $uuid, 'size' => $size));
    contratos_upload_response(422, 'error', 'O PDF recebido contém somente páginas em branco. Atualize a página do contrato e tente assinar novamente.');
}
$baseDir = defined('CONTRATOS_DIR') ? rtrim(CONTRATOS_DIR, '/\\') : '/opt/mk-auth/admin/arquivos';
$clientDir = $baseDir . DIRECTORY_SEPARATOR . $uuid;
if (!is_dir($clientDir) && !@mkdir($clientDir, 0777, true)) {
    contratos_upload_log('mkdir_failed', array('uuid' => $uuid, 'dir' => $clientDir));
    contratos_upload_response(500, 'error', 'O servidor não conseguiu criar a pasta do contrato.');
}

$temporary = $clientDir . DIRECTORY_SEPARATOR . '.contrato-upload-' . $requestId . '.tmp';
$target = $clientDir . DIRECTORY_SEPARATOR . 'contrato_' . $uuid . '.pdf';
if (!@move_uploaded_file($file['tmp_name'], $temporary)) {
    contratos_upload_log('move_failed', array('uuid' => $uuid, 'target' => $target));
    contratos_upload_response(500, 'error', 'O servidor não conseguiu receber o PDF.');
}
@chmod($temporary, 0664);

if (is_file($target)) {
    $archive = $clientDir . '/.contratos-anteriores';
    if ((!is_dir($archive) && !@mkdir($archive, 0770, true)) ||
        !@copy($target, $archive . '/' . date('Ymd-His') . '-' . $requestId . '.pdf')) {
        @unlink($temporary);
        contratos_upload_response(500, 'error', 'Não foi possível preservar o documento anterior. Tente novamente.');
    }
}
if ((int) @filesize($temporary) !== $size || !@rename($temporary, $target)) {
    @unlink($temporary);
    contratos_upload_log('atomic_save_failed', array('uuid' => $uuid, 'size' => $size));
    contratos_upload_response(500, 'error', 'O servidor não conseguiu finalizar a gravação do PDF.');
}
@chmod($target, 0664);

$login = '';
$uuidSql = mysqli_real_escape_string($conecta, $uuid);
$clientResult = mysqli_query($conecta, "SELECT login FROM sis_cliente WHERE uuid_cliente = '{$uuidSql}' LIMIT 1");
if ($clientResult && ($clientRow = mysqli_fetch_assoc($clientResult))) {
    $login = trim((string) $clientRow['login']);
}
if ($login !== '') {
    contratos_save_history($conecta, $uuid, $login, 12, 'assinatura digital', date('Y-m-d'), 'PDF assinado e recebido pelo addon.');
}

if (!isset($_SESSION)) {
    session_name('mka');
    @session_start();
}
$_SESSION['contrato_assinado'] = true;
$_SESSION['contrato_cliente'] = $nomeCliente;
contratos_upload_log('saved', array('uuid' => $uuid, 'size' => $size, 'login' => $login, 'file' => basename($target)));

http_response_code(201);
echo json_encode(array('status' => 'success', 'message' => 'PDF salvo com sucesso.', 'request_id' => $requestId, 'size' => $size), JSON_UNESCAPED_UNICODE);

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
try {
    require_once 'send_sms.php';
    send_sms('*' . $nomeCliente . '* já assinou o contrato!');
} catch (Throwable $error) {
    contratos_upload_log('sms_failed', array('uuid' => $uuid, 'message' => substr($error->getMessage(), 0, 250)));
}
