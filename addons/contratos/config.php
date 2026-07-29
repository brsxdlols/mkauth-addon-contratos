<?php
/**
 * Arquivo de Configuração do Addon Contratos
 * 
 * Este arquivo centraliza todas as configurações do addon
 * para facilitar manutenção e customização.
 */

// CAMINHO PARA ARMAZENAMENTO DOS CONTRATOS
// Para alterar o diretório onde os PDFs dos contratos são salvos,
// modifique apenas esta linha:
define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');

// Outros caminhos possíveis (descomente para usar):
// define('CONTRATOS_DIR', '/opt/mk-auth/central/disco_virtual/');
// define('CONTRATOS_DIR', '/var/www/contratos/');

// IMPORTANTE: Após alterar o caminho, certifique-se de que:
// 1. O diretório existe e tem permissões 777 ou www-data:www-data
// 2. O AppArmor tem permissão para o caminho escolhido

?>
