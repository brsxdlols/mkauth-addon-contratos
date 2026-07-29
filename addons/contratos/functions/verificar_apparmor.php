<?php
/**
 * Verificação de Permissões AppArmor
 * 
 * Verifica se o AppArmor está bloqueando gravação no diretório de contratos
 */

function verificarAppArmor() {
    require_once __DIR__ . '/../config.php';
    
    $resultado = [
        'ok' => true,
        'mensagem' => '',
        'comando_correcao' => '',
        'detalhes' => []
    ];
    
    // 1. Verificar se o diretório existe
    if (!is_dir(CONTRATOS_DIR)) {
        $resultado['ok'] = false;
        $resultado['mensagem'] = 'Diretório de contratos não existe';
        $resultado['detalhes'][] = 'Diretório configurado: ' . CONTRATOS_DIR;
        $resultado['comando_correcao'] = 'mkdir -p ' . CONTRATOS_DIR . ' && chmod 777 ' . CONTRATOS_DIR;
        return $resultado;
    }
    
    // 2. Verificar permissões de escrita
    $testeArquivo = CONTRATOS_DIR . '.teste_permissao_' . time() . '.tmp';
    $podeEscrever = @file_put_contents($testeArquivo, 'teste');
    
    if ($podeEscrever === false) {
        // Não conseguiu escrever - pode ser AppArmor ou permissões
        $resultado['ok'] = false;
        $resultado['mensagem'] = 'Sem permissão de escrita no diretório de contratos';
        
        // Verificar se AppArmor está ativo
        $apparmor_status = shell_exec('aa-status 2>&1');
        
        if (strpos($apparmor_status, 'apparmor module is loaded') !== false) {
            // AppArmor está ativo - verificar se tem a regra necessária
            $perfil_central = @file_get_contents('/etc/apparmor.d/sistema.php-central');
            
            if ($perfil_central !== false) {
                $tem_permissao = strpos($perfil_central, CONTRATOS_DIR . '** mrwlkix') !== false ||
                                 strpos($perfil_central, CONTRATOS_DIR . '**') !== false;
                
                if (!$tem_permissao) {
                    $resultado['detalhes'][] = 'AppArmor está ativo e bloqueando gravação';
                    $resultado['detalhes'][] = 'Perfil: /etc/apparmor.d/sistema.php-central';
                    $resultado['detalhes'][] = 'Diretório necessário: ' . CONTRATOS_DIR;
                    
                    // Gerar comando de correção SIMPLIFICADO
                    $comando = "# MÉTODO RECOMENDADO: Use o script automatizado\n";
                    $comando .= "cd /opt/mk-auth/admin/addons/contratos\n";
                    $comando .= "sudo bash corrigir_apparmor.sh\n\n";
                    $comando .= "# OU execute manualmente os seguintes comandos:\n\n";
                    
                    $data = date('Ymd_His');
                    $comando .= "# 1. Backup do perfil AppArmor\n";
                    $comando .= "cp /etc/apparmor.d/sistema.php-central /etc/apparmor.d/sistema.php-central.backup-{$data}\n\n";
                    $comando .= "# 2. Adicionar permissão para o diretório de contratos\n";
                    
                    // Verificar se existe a linha do disco_virtual para usar como referência
                    if (strpos($perfil_central, '/opt/mk-auth/central/disco_virtual/') !== false) {
                        $comando .= "sed -i '/\\/opt\\/mk-auth\\/central\\/disco_virtual\\/\\*\\* rw,/a\\        " . str_replace('/', '\\/', CONTRATOS_DIR) . "** mrwlkix,' /etc/apparmor.d/sistema.php-central\n\n";
                    } else {
                        // Se não existir a linha do disco_virtual, adicionar após a linha /opt/mk-auth/** r,
                        $comando .= "sed -i '/\\/opt\\/mk-auth\\/\\*\\* r,/a\\        " . str_replace('/', '\\/', CONTRATOS_DIR) . "** mrwlkix,' /etc/apparmor.d/sistema.php-central\n\n";
                    }
                    
                    $comando .= "# 3. Recarregar perfil AppArmor (IMPORTANTE: Ambos os métodos)\n";
                    $comando .= "apparmor_parser -r /etc/apparmor.d/sistema.php-central\n";
                    $comando .= "systemctl reload apparmor\n\n";
                    $comando .= "# 4. Verificar se foi aplicado\n";
                    $comando .= "aa-status | grep php-central\n";
                    $comando .= "echo '✓ Correção aplicada com sucesso!'";
                    
                    $resultado['comando_correcao'] = $comando;
                } else {
                    // Regra existe mas ainda não funciona - precisa recarregar
                    $resultado['detalhes'][] = 'AppArmor tem a regra configurada, mas não está aplicada';
                    $resultado['detalhes'][] = 'Necessário recarregar o perfil AppArmor';
                    
                    $comando = "# A regra já existe, apenas recarregar o AppArmor\n\n";
                    $comando .= "# Método 1: Recarregar perfil específico\n";
                    $comando .= "apparmor_parser -r /etc/apparmor.d/sistema.php-central\n\n";
                    $comando .= "# Método 2: Reload completo (recomendado para Debian 25.08)\n";
                    $comando .= "systemctl reload apparmor\n\n";
                    $comando .= "# Se ainda não funcionar, reiniciar AppArmor\n";
                    $comando .= "systemctl restart apparmor\n\n";
                    $comando .= "# Verificar status\n";
                    $comando .= "aa-status | grep php-central\n";
                    $comando .= "echo '✓ AppArmor recarregado!'";
                    
                    $resultado['comando_correcao'] = $comando;
                }
            }
        } else {
            // AppArmor não está ativo - problema é de permissões do sistema
            $resultado['detalhes'][] = 'Problema de permissões do sistema de arquivos';
            $resultado['comando_correcao'] = 'chmod -R 777 ' . CONTRATOS_DIR . ' && chown -R www-data:www-data ' . CONTRATOS_DIR;
        }
        
        // Verificar logs recentes do AppArmor
        $logs = shell_exec('grep -i "DENIED.*' . basename(CONTRATOS_DIR) . '" /var/log/syslog 2>/dev/null | tail -3');
        if (!empty($logs)) {
            $resultado['detalhes'][] = 'Bloqueios recentes detectados nos logs do sistema';
        }
        
        return $resultado;
    }
    
    // Conseguiu escrever - limpar arquivo de teste
    @unlink($testeArquivo);
    
    // 3. Verificar se consegue criar subdiretório
    $testeDir = CONTRATOS_DIR . 'teste_dir_' . time();
    $podeCriarDir = @mkdir($testeDir, 0777, true);
    
    if ($podeCriarDir) {
        @rmdir($testeDir);
    } else {
        $resultado['ok'] = false;
        $resultado['mensagem'] = 'Sem permissão para criar subdiretórios';
        $resultado['detalhes'][] = 'Necessário para criar pastas por UUID de cliente';
        $resultado['comando_correcao'] = 'chmod -R 777 ' . CONTRATOS_DIR;
        return $resultado;
    }
    
    // Tudo OK
    $resultado['mensagem'] = 'Permissões OK';
    $resultado['detalhes'][] = 'Diretório: ' . CONTRATOS_DIR;
    $resultado['detalhes'][] = 'Escrita: ✓';
    $resultado['detalhes'][] = 'Criar subdiretórios: ✓';
    
    return $resultado;
}

/**
 * Retorna informações sobre o AppArmor para debug
 */
function getAppArmorInfo() {
    $info = [
        'ativo' => false,
        'perfil_central' => false,
        'tem_permissao' => false,
        'bloqueios_recentes' => []
    ];
    
    // Verificar se AppArmor está ativo
    $status = shell_exec('aa-status 2>&1');
    $info['ativo'] = strpos($status, 'apparmor module is loaded') !== false;
    
    if ($info['ativo']) {
        // Verificar perfil central
        $perfil = @file_get_contents('/etc/apparmor.d/sistema.php-central');
        $info['perfil_central'] = ($perfil !== false);
        
        if ($info['perfil_central']) {
            $info['tem_permissao'] = strpos($perfil, CONTRATOS_DIR) !== false;
        }
        
        // Buscar bloqueios recentes
        $logs = shell_exec('grep -i "DENIED.*contratos\|DENIED.*arquivos" /var/log/syslog 2>/dev/null | tail -5');
        if (!empty($logs)) {
            $info['bloqueios_recentes'] = explode("\n", trim($logs));
        }
    }
    
    return $info;
}
?>
