<?php
declare(strict_types=1);

// Carregar o mesmo bootstrap usado pelo index antes de abrir a sessão.
// Quando este arquivo é incluído pelo index, require_once evita duplicação.
require_once __DIR__ . '/addons.class.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('mka');
    session_start();
}

function finalizarUploadAssinatura(string $type, string $message): void
{
    $_SESSION['contratos_assinatura_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Logado'])) {
    header('Location: ../../');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finalizarUploadAssinatura('error', 'Solicitação inválida para o envio da assinatura.');
}

$sessionToken = $_SESSION['contratos_assinatura_csrf'] ?? '';
$requestToken = $_POST['csrf_token'] ?? '';
if (
    !is_string($sessionToken)
    || !is_string($requestToken)
    || $sessionToken === ''
    || !hash_equals($sessionToken, $requestToken)
) {
    finalizarUploadAssinatura('error', 'A sessão expirou. Recarregue a página e tente novamente.');
}

if (
    !isset($_FILES['assinatura_provedor'])
    || !is_array($_FILES['assinatura_provedor'])
    || ($_FILES['assinatura_provedor']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
) {
    finalizarUploadAssinatura('error', 'Não foi possível receber a imagem selecionada.');
}

$upload = $_FILES['assinatura_provedor'];
$temporaryUpload = (string) ($upload['tmp_name'] ?? '');
$uploadSize = (int) ($upload['size'] ?? 0);
$maximumSize = 5 * 1024 * 1024;

if ($uploadSize <= 0 || $uploadSize > $maximumSize) {
    finalizarUploadAssinatura('error', 'A imagem deve ter no máximo 5 MB.');
}

if ($temporaryUpload === '' || !is_uploaded_file($temporaryUpload)) {
    finalizarUploadAssinatura('error', 'O arquivo recebido não é um upload válido.');
}

$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = (string) $fileInfo->file($temporaryUpload);
$allowedTypes = [
    'image/png',
    'image/jpeg',
    'image/webp',
    'image/gif',
];

if (!in_array($mimeType, $allowedTypes, true)) {
    finalizarUploadAssinatura('error', 'Formato não permitido. Use PNG, JPG, WEBP ou GIF.');
}

$imageInfo = @getimagesize($temporaryUpload);
if ($imageInfo === false) {
    finalizarUploadAssinatura('error', 'O arquivo selecionado não contém uma imagem válida.');
}

$width = (int) ($imageInfo[0] ?? 0);
$height = (int) ($imageInfo[1] ?? 0);
if (
    $width <= 0
    || $height <= 0
    || $width > 6000
    || $height > 6000
    || ($width * $height) > 25000000
) {
    finalizarUploadAssinatura('error', 'A imagem possui dimensões maiores que o limite permitido.');
}

$targetDirectory = '/opt/mk-auth/mkfiles';
$targetPath = $targetDirectory . '/assinatura_provedor';
$backupDirectory = '/var/backups/mkauth-addon-contratos-assinaturas';

if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
    finalizarUploadAssinatura('error', 'O diretório de imagens do MK Auth não permite gravação.');
}

if (!is_dir($backupDirectory) || !is_writable($backupDirectory)) {
    finalizarUploadAssinatura('error', 'O diretório de backup da assinatura não está disponível.');
}

if (is_file($targetPath)) {
    $backupName = sprintf(
        '%s/assinatura_provedor-%s-%s',
        $backupDirectory,
        date('Ymd-His'),
        bin2hex(random_bytes(3))
    );

    if (!copy($targetPath, $backupName)) {
        finalizarUploadAssinatura('error', 'Não foi possível criar o backup da assinatura atual.');
    }
    chmod($backupName, 0600);

    $backups = glob($backupDirectory . '/assinatura_provedor-*') ?: [];
    rsort($backups, SORT_STRING);
    foreach (array_slice($backups, 20) as $oldBackup) {
        if (is_file($oldBackup)) {
            @unlink($oldBackup);
        }
    }
}

$temporaryTarget = tempnam($targetDirectory, '.assinatura_provedor.upload-');
if ($temporaryTarget === false) {
    finalizarUploadAssinatura('error', 'Não foi possível preparar o arquivo da nova assinatura.');
}

if (!move_uploaded_file($temporaryUpload, $temporaryTarget)) {
    @unlink($temporaryTarget);
    finalizarUploadAssinatura('error', 'Não foi possível salvar a imagem enviada.');
}

chmod($temporaryTarget, 0644);
if (!rename($temporaryTarget, $targetPath)) {
    @unlink($temporaryTarget);
    finalizarUploadAssinatura('error', 'Não foi possível ativar a nova assinatura.');
}

clearstatcache(true, $targetPath);
$_SESSION['contratos_assinatura_csrf'] = bin2hex(random_bytes(32));

finalizarUploadAssinatura(
    'success',
    'Assinatura do provedor atualizada. A imagem foi salva como assinatura_provedor, sem extensão.'
);
