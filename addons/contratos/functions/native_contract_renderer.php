<?php
// Shared verbatim with dashboard/shared/native_contract_renderer.php.
// Only the MK-Auth variable engine is invoked, never gera_contrato's selection/print workflow.
if (!defined('VERSAO2UPDATE') && is_file('/opt/mk-auth/include/configure.php')) {
    require_once '/opt/mk-auth/include/configure.php';
}
if (!function_exists('mka_native_contract_render')) {
    function mka_native_contract_utf8($value) {
        $value = (string) $value;
        return preg_match('//u', $value) ? $value : iconv('ISO-8859-1', 'UTF-8', $value);
    }
    function mka_native_contract_escape($value) {
        return htmlspecialchars(mka_native_contract_utf8($value), ENT_QUOTES, 'UTF-8');
    }
    function mka_native_contract_rows($db, $sql, $login) {
        $stmt = $db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Não foi possível consultar os dados do contrato.');
        try {
            $stmt->bind_param('s', $login);
            if (!$stmt->execute()) throw new RuntimeException('Não foi possível consultar os dados do contrato.');
            $result = $stmt->get_result();
            if (!$result) throw new RuntimeException('Não foi possível consultar os dados do contrato.');
            $rows = array();
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            $result->free();
            return $rows;
        } finally { $stmt->close(); }
    }
    function mka_native_contract_table($rows, $kind) {
        $products = $kind === 'produtos';
        $html = '<table class="mka-native-contract-table" style="width:100%;max-width:450px;border-collapse:collapse;font-family:Verdana,sans-serif"><thead><tr><th style="text-align:left">nome</th><th style="text-align:left">'.($products ? 'quantidade' : 'valor').'</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $value = $products ? str_pad((string) $row['qtdcli'], 3, '0', STR_PAD_LEFT) : 'R$ '.number_format((float) $row['valor'], 2, ',', '.');
            $html .= '<tr><td>'.mka_native_contract_escape($row['nome']).'</td><td>'.mka_native_contract_escape($value).'</td></tr>';
        }
        return $html.'</tbody></table>';
    }
    function mka_native_contract_render($db, $login, $template) {
        // Native pages may use latin1 while the signing page uses UTF-8. Normalize
        // the query connection as well as the template, then restore the caller.
        $template = mka_native_contract_utf8($template);
        $charset = $db->character_set_name();
        $changeCharset = stripos($charset, 'utf8') !== 0;
        if ($changeCharset && !$db->set_charset('utf8')) throw new RuntimeException('Não foi possível preparar a codificação do contrato.');
        try { return mka_native_contract_render_utf8($db, $login, $template); }
        finally { if ($changeCharset) $db->set_charset($charset); }
    }
    function mka_native_contract_render_utf8($db, $login, $template) {
        // This public method is the expansion entry used by native gera_contrato.hhvm.
        // Fail closed on unsupported releases instead of guessing other obfuscated methods.
        $method = 'FAUKHKHMMAMMKMUMMMUMHKTMUKTMTAMMT';
        if (!class_exists('MKAC_VARS') || !method_exists('MKAC_VARS', $method)) {
            throw new RuntimeException('Gerador de variáveis nativo indisponível nesta versão do MK-Auth.');
        }
        $template = mka_native_contract_utf8($template);
        $replacements = array();
        foreach (array('produtos', 'servicos', 'equipamentos') as $kind) {
            if (strpos($template, '%'.$kind.'%') === false) continue;
            $marker = 'MKA_CONTRACT_'.bin2hex(random_bytes(16));
            if ($kind === 'produtos') {
                $rows = mka_native_contract_rows($db, 'SELECT p.nome,a.qtdcli FROM sis_prodcliente a JOIN sis_produto p ON p.id=a.idprod WHERE a.cliente=? ORDER BY a.id', $login);
                $replacements[$marker] = mka_native_contract_table($rows, $kind);
            } elseif ($kind === 'servicos') {
                $rows = mka_native_contract_rows($db, 'SELECT nome,valor FROM sis_sercontratos WHERE login=? ORDER BY id', $login);
                $replacements[$marker] = mka_native_contract_table($rows, $kind);
            } else {
                $equipment = '/opt/mk-auth/admin/addons/client_equipment/model.php';
                if (is_file($equipment)) require_once $equipment;
                if (!function_exists('mka_equipment_contract_html')) throw new RuntimeException('A variável %equipamentos% requer o módulo de equipamentos atualizado.');
                $replacements[$marker] = mka_native_contract_utf8(mka_equipment_contract_html($db, $login));
            }
            $template = str_replace('%'.$kind.'%', $marker, $template);
        }
        $hadConnection = array_key_exists('LOADMYSQL', $GLOBALS);
        $previousConnection = $hadConnection ? $GLOBALS['LOADMYSQL'] : null;
        $GLOBALS['LOADMYSQL'] = $db;
        try {
            $vars = new MKAC_VARS('cliente', $login);
            $html = $vars->$method($template);
            if (!is_string($html)) throw new RuntimeException('O gerador nativo não retornou um contrato válido.');
            $html = strtr(mka_native_contract_utf8($html), $replacements);
            if (preg_match_all('/%[A-Za-z][A-Za-z0-9_.-]*%/', $html, $missing)) {
                throw new RuntimeException('Variáveis não reconhecidas pelo MK-Auth: '.implode(', ', array_unique($missing[0])).'. Revise o modelo antes de assinar.');
            }
            return preg_replace('/<meta\b[^>]*>/i', '', $html);
        } finally {
            if ($hadConnection) $GLOBALS['LOADMYSQL'] = $previousConnection;
            else unset($GLOBALS['LOADMYSQL']);
        }
    }
}
