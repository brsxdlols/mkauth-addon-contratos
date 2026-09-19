<?php
require_once 'addons.class.php';
session_name('mka');
session_start();
if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Logado'])) { http_response_code(403); exit('Acesso negado.'); }
require 'database/conexao.php';
require_once 'functions/contract_history.php';
require_once 'functions/attachments.php';
contratos_attachment_schema($conecta);
if (empty($_SESSION['contratos_anexo_csrf'])) $_SESSION['contratos_anexo_csrf'] = bin2hex(random_bytes(32));
$uuid = (string) ($_GET['uuid'] ?? '');
if (!preg_match('/^[a-zA-Z0-9-]{1,64}$/', $uuid)) { http_response_code(400); exit('Cliente inválido.'); }
$stmt = $conecta->prepare("SELECT nome FROM sis_cliente WHERE uuid_cliente=? AND cli_ativado='s' LIMIT 1");
$stmt->bind_param('s', $uuid); $stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
if (!$client) { http_response_code(404); exit('Cliente não encontrado ou inativo.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lock = null; $target = null; $saved = false;
    try {
        if (!hash_equals($_SESSION['contratos_anexo_csrf'], (string) ($_POST['csrf'] ?? ''))) throw new RuntimeException('Sessão inválida. Reabra a página.');
        $start = (string) ($_POST['start_date'] ?? '');
        $end = (string) ($_POST['end_date'] ?? '');
        contratos_attachment_dates($start, $end);
        $origin = (string) ($_POST['origin'] ?? '');
        if (!in_array($origin, array('digital', 'fisico'), true)) throw new RuntimeException('Selecione a origem.');
        $file = $_FILES['documento'] ?? array();
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '') ||
            ($file['size'] ?? 0) <= 0 || $file['size'] > 20 * 1024 * 1024) throw new RuntimeException('Falha no envio. Limite de 20 MB, sujeito ao limite do servidor.');
        $ext = contratos_attachment_type($file['tmp_name']);
        $dir = rtrim(CONTRATOS_DIR, '/').'/'.$uuid;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('Não foi possível criar a pasta.');
        $lock = fopen($dir.'/.anexo.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Não foi possível bloquear o envio.');
        foreach (array('pdf','jpg','png') as $suffix) {
            if (glob($dir.'/contrato_*.'.$suffix)) throw new RuntimeException('Este cliente já possui contrato. O documento existente foi preservado.');
        }
        $filename = 'contrato_anexo_'.bin2hex(random_bytes(12)).'.'.$ext;
        $target = $dir.'/'.$filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Não foi possível salvar o arquivo.');
        chmod($target, 0664);
        $user = (string) ($_SESSION['MKA_Usuario'] ?? $_SESSION['MM_Usuario'] ?? 'administrador');
        $endDb = $end === '' ? null : $end;
        $stmt = $conecta->prepare('INSERT INTO sis_contrato_anexo (uuid_cliente,filename,start_date,end_date,origin,uploaded_by) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE filename=VALUES(filename),start_date=VALUES(start_date),end_date=VALUES(end_date),origin=VALUES(origin),uploaded_by=VALUES(uploaded_by),uploaded_at=CURRENT_TIMESTAMP');
        if (!$stmt) throw new RuntimeException('Falha ao registrar o anexo.');
        $stmt->bind_param('ssssss', $uuid, $filename, $start, $endDb, $origin, $user);
        if (!$stmt->execute()) throw new RuntimeException('Falha ao registrar o anexo.');
        $saved = true;
    } catch (Throwable $e) {
        if ($target && !$saved && is_file($target)) unlink($target);
        $error = $e->getMessage();
    } finally {
        if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
    }
    if ($saved) { header('Location: index.php?busca='.rawurlencode($client['nome'])); exit; }
}
?>
<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Anexar contrato existente</title>
<style>body{font:16px Arial;background:#f3f6fa;color:#233548;margin:0;padding:24px}main{max-width:600px;margin:30px auto;background:white;padding:28px;border-radius:16px;box-shadow:0 6px 24px #0001}label{display:block;margin:20px 0}input,select{box-sizing:border-box;width:100%;padding:12px;margin-top:8px;border:1px solid #ccd5df;border-radius:8px}button,.back{display:inline-block;padding:12px 18px;border-radius:8px}button{background:#168bd4;color:white;border:0;cursor:pointer}.error{background:#fee;padding:14px;color:#a22}small{color:#657587}</style>
<main><h1>Anexar contrato existente</h1><p><?= contratos_escape($client['nome']) ?></p>
<p>Envie o contrato digital já assinado ou uma digitalização legível do contrato físico. Para várias páginas, use um único PDF.</p>
<?php if ($error): ?><p class="error" role="alert"><?= contratos_escape($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= contratos_escape($_SESSION['contratos_anexo_csrf']) ?>">
<label>Arquivo do contrato<input required type="file" name="documento" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"><small>PDF, JPG ou PNG, até 20 MB. Limite PHP: <?= contratos_escape(ini_get('upload_max_filesize')) ?>.</small></label>
<label>Origem<select required name="origin"><option value="digital">Contrato digital existente</option><option value="fisico">Contrato físico digitalizado</option></select></label>
<label>Data original do contrato<input required type="date" name="start_date" value="<?= contratos_escape($_POST['start_date'] ?? '') ?>"></label>
<label>Data de vencimento (opcional)<input type="date" name="end_date" value="<?= contratos_escape($_POST['end_date'] ?? '') ?>"><small>Deixe em branco se não houver vencimento informado. O sistema não presume um prazo.</small></label>
<button type="submit">Anexar contrato</button><a class="back" href="index.php">Voltar</a>
</form></main></html>
