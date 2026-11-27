#!/usr/bin/env php
<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Somente linha de comando.');}
$seedDone=false;register_shutdown_function(function()use(&$seedDone){if(!$seedDone){fwrite(STDERR,"[contratos] Provisionamento nao concluido. Nenhum modelo existente foi atualizado.\n");exit(1);}});
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$addonDir=$argv[1]??'';
try{
 if($addonDir===''||!is_dir($addonDir))throw new RuntimeException('Diretorio do addon invalido.');
 require_once __DIR__.'/seed-contracts-lib.php';
 // Reuse the provider's existing connection; do not add credentials here.
 ob_start();require $addonDir.'/database/conexao.php';ob_end_clean();
 if(!isset($conecta)||!($conecta instanceof mysqli))throw new RuntimeException('Conexao local do addon indisponivel.');
 contratos_seed_models($conecta,$addonDir,in_array('--first-install',$argv,true));$seedDone=true;
}catch(Throwable $error){if(ob_get_level())ob_end_clean();fwrite(STDERR,'[contratos] Falha no provisionamento: '.$error->getMessage().PHP_EOL);exit(1);}
