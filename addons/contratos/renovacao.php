<?php
require_once 'addons.class.php';
session_name('mka');
if (!isset($_SESSION)) {
    session_start();
}
if (empty($_SESSION['mka_logado']) && empty($_SESSION['MKA_Logado'])) {
    http_response_code(403);
    exit('Acesso negado.');
}

require 'database/conexao.php';
require_once 'functions/contract_history.php';
contratos_ensure_history_schema($conecta);

$uuid = trim((string) ($_REQUEST['uuid'] ?? ''));
$login = trim((string) ($_REQUEST['login'] ?? ''));
$nome = trim((string) ($_REQUEST['nome'] ?? $login));
$message = '';
$messageType = 'success';

$validation = $conecta->prepare('SELECT sc.texto FROM sis_cliente c JOIN sis_contrato sc ON sc.codigo = c.contrato WHERE c.uuid_cliente = ? LIMIT 1');
if (!$validation) { http_response_code(500); exit('Não foi possível validar o modelo do contrato.'); }
$validation->bind_param('s', $uuid);
$validation->execute();
$modelRow = $validation->get_result()->fetch_assoc();
$validation->close();
$modelValid = $modelRow && trim(html_entity_decode(strip_tags((string) $modelRow['texto']), ENT_QUOTES, 'UTF-8')) !== '';

if (!$modelValid) {
    http_response_code(422);
    exit('PDF pendente: vincule o modelo correto, revise o PDF existente e solicite nova assinatura. Renovar a vigência não corrige um documento sem texto.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration = (int) ($_POST['duration_months'] ?? 12);
    $startDate = trim((string) ($_POST['start_date'] ?? date('Y-m-d')));
    $user = !empty($_SESSION['MKA_Usuario']) ? $_SESSION['MKA_Usuario'] : ($_SESSION['MM_Usuario'] ?? 'sistema');
    if ($uuid === '' || $login === '') {
        $message = 'Não foi possível identificar o cliente.';
        $messageType = 'error';
    } elseif (contratos_save_history($conecta, $uuid, $login, $duration, $user, $startDate, 'Renovado pelo addon Controle de Contratos.')) {
        $message = 'Vigência renovada com sucesso.';
    } else {
        $message = 'Não foi possível salvar a renovação.';
        $messageType = 'error';
    }
}

$current = contratos_get_latest_history($conecta, $uuid, $login);
$currentStart = $current['start_date'] ?? '';
$currentEnd = $current['end_date'] ?? '';
$conecta->close();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Renovar contrato</title>
    <style>
        *{box-sizing:border-box} body{margin:0;background:#eef4fb;color:#17324d;font-family:Arial,sans-serif}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:22px}.card{width:100%;max-width:560px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 22px 55px rgba(18,38,63,.18)}
        .head{padding:24px 28px;background:linear-gradient(135deg,#eaf5ff,#cfe9ff)}.head small{font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#1674b8}.head h1{margin:8px 0 4px;font-size:28px}.head p{margin:0;color:#52677d}
        .body{padding:26px}.flash{padding:12px 14px;border-radius:12px;margin-bottom:16px;font-weight:700}.success{background:#e7f8ef;color:#157347}.error{background:#fde7ea;color:#b42318}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:block;background:#f7faff;border:1px solid #dbe5f0;border-radius:14px;padding:14px}.field span{display:block;font-size:11px;text-transform:uppercase;font-weight:700;color:#687b90;margin-bottom:7px}.field strong{font-size:18px}.field input,.field select{width:100%;border:1px solid #bfd0e0;border-radius:10px;padding:11px;font-size:15px;background:#fff}
        .actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}.btn{border:0;border-radius:12px;padding:12px 18px;font-weight:700;cursor:pointer}.primary{background:#209cee;color:#fff}.secondary{background:#e8eef5;color:#30465b}
        @media(max-width:560px){.grid{grid-template-columns:1fr}.actions{flex-direction:column-reverse}.btn{width:100%}}
    </style>
</head>
<body><div class="wrap"><div class="card">
    <div class="head"><small>Controle de Contratos</small><h1>Renovar vigência</h1><p><?= contratos_escape($nome) ?> [<?= contratos_escape($login) ?>]</p></div>
    <div class="body">
        <?php if ($message !== ''): ?><div class="flash <?= $messageType ?>"><?= contratos_escape($message) ?></div><?php endif; ?>
        <div class="grid" style="margin-bottom:16px">
            <div class="field"><span>Início atual</span><strong><?= $currentStart ? date('d/m/Y', strtotime($currentStart)) : '--' ?></strong></div>
            <div class="field"><span>Vencimento atual</span><strong><?= $currentEnd ? date('d/m/Y', strtotime($currentEnd)) : '--' ?></strong></div>
        </div>
        <form method="post">
            <input type="hidden" name="uuid" value="<?= contratos_escape($uuid) ?>"><input type="hidden" name="login" value="<?= contratos_escape($login) ?>"><input type="hidden" name="nome" value="<?= contratos_escape($nome) ?>">
            <div class="grid">
                <label class="field"><span>Nova vigência começa em</span><input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required></label>
                <label class="field"><span>Prazo</span><select name="duration_months"><?php foreach (contratos_allowed_durations() as $months): ?><option value="<?= $months ?>" <?= $months === 12 ? 'selected' : '' ?>><?= $months ?> <?= $months === 1 ? 'mês' : 'meses' ?></option><?php endforeach; ?></select></label>
            </div>
            <div class="actions"><button class="btn secondary" type="button" onclick="fecharRenovacao()">Fechar</button><button class="btn primary" type="submit">Renovar contrato</button></div>
        </form>
    </div>
</div></div>
<script>
function fecharRenovacao() {
    if (window.parent && window.parent !== window) {
        window.parent.postMessage({type: 'contratos-renovacao-close'}, window.location.origin);
        return;
    }
    window.close();
}
<?php if ($messageType === 'success' && $message !== ''): ?>
if (window.parent && window.parent !== window) {
    window.parent.postMessage({type: 'contratos-renovacao-refresh'}, window.location.origin);
} else if (window.opener && !window.opener.closed) {
    window.opener.location.reload();
}
<?php endif; ?>
</script>
</body></html>
