<?php
require_once 'addons.class.php';
session_name('mka');
session_start();
if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Logado'])) {
    http_response_code(403); exit('Acesso negado.');
}
require_once 'config.php';
$base = realpath(CONTRATOS_DIR);
$input = (string) ($_POST['file'] ?? $_GET['file'] ?? '');
$file = realpath($input);
if (!$base || !$file || !is_file($file) || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0 ||
    dirname(dirname($file)) !== $base || !preg_match('/^contrato_[a-zA-Z0-9_-]+\.(pdf|jpg|png)$/', basename($file))) {
    http_response_code(400); exit('Documento inválido ou não encontrado.');
}
if (empty($_SESSION['contratos_delete_csrf'])) $_SESSION['contratos_delete_csrf'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['contratos_delete_csrf'], (string) ($_POST['csrf'] ?? '')) ||
        ($_POST['confirmar'] ?? '') !== 'sim') {
        http_response_code(403); exit('Confirmação inválida. Reabra a página.');
    }
    if (!unlink($file)) { http_response_code(500); exit('Falha ao excluir o documento. Tente novamente.'); }
    header('Location: index.php'); exit;
}
function deleteEscape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Confirmar exclusão do contrato</title>
<style>body{font:16px Arial;background:#f3f6fa;padding:24px;color:#233548}main{max-width:620px;margin:40px auto;background:#fff;padding:28px;border-radius:14px;overflow-wrap:anywhere}button,a{display:inline-block;padding:12px;margin:8px;border-radius:8px}button{background:#ce3434;color:white;border:0;cursor:pointer}</style>
<main><h1>Excluir definitivamente este contrato?</h1><p><?= deleteEscape(basename($file)) ?></p>
<p>O arquivo selecionado será apagado definitivamente. Esta ação não pode ser desfeita pelo addon. O cliente poderá assinar novamente.</p>
<a href="<?= deleteEscape('/admin/arquivos/'.rawurlencode(basename(dirname($file))).'/'.rawurlencode(basename($file))) ?>" target="_blank" rel="noopener">Conferir documento</a>
<form method="post"><input type="hidden" name="file" value="<?= deleteEscape($file) ?>"><input type="hidden" name="csrf" value="<?= deleteEscape($_SESSION['contratos_delete_csrf']) ?>">
<button name="confirmar" value="sim" type="submit">Confirmar exclusão</button><a href="index.php">Cancelar</a></form></main></html>
