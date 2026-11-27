<?php
// Insert-only provisioning. Existing models and signed documents are never updated.
function contratos_seed_models($db,$addonDir,$firstInstall=false){
 $db->set_charset('utf8');
 $models=array(
  array('code'=>'addoncontrato_fidelidade_1ano','name'=>'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO','file'=>'modelo_contrato_fidelidade.html'),
  array('code'=>'addoncontrato_internet_padrao','name'=>'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET','file'=>'modelo_contrato_padrao.html')
 );
 if((int)$db->query("SELECT GET_LOCK(CONCAT('addon-contratos-',DATABASE()),15)")->fetch_row()[0]!==1)throw new RuntimeException('Outro instalador esta provisionando os modelos.');
 try{
  $db->query("CREATE TABLE IF NOT EXISTS addon_contratos_model_seed (codigo VARCHAR(100) NOT NULL PRIMARY KEY, estado VARCHAR(24) NOT NULL, modelo_id INT NULL, registrado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
  $newHistory=(int)$db->query('SELECT COUNT(*) FROM addon_contratos_model_seed')->fetch_row()[0]===0;
  // A fresh addon may coexist with unrelated native models. Known seed identifiers mean prior provisioning.
  $recognized=0;$probe=$db->prepare('SELECT 1 FROM sis_contrato WHERE codigo=? OR nome=? LIMIT 1');foreach($models as$model){$probe->bind_param('ss',$model['code'],$model['name']);$probe->execute();if($probe->get_result()->num_rows)$recognized++;}$probe->close();
  if($newHistory&&$firstInstall&&$recognized===0){$pending=$db->prepare("INSERT INTO addon_contratos_model_seed(codigo,estado) VALUES(?,'pending')");foreach($models as$model){$pending->bind_param('s',$model['code']);$pending->execute();}$pending->close();}
  $find=$db->prepare('SELECT id,codigo FROM sis_contrato WHERE codigo=? OR nome=? ORDER BY (codigo=?) DESC,id LIMIT 1');
  $history=$db->prepare('SELECT estado,modelo_id FROM addon_contratos_model_seed WHERE codigo=?');
  $record=$db->prepare('INSERT INTO addon_contratos_model_seed(codigo,estado,modelo_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE estado=VALUES(estado),modelo_id=VALUES(modelo_id)');
  $insert=$db->prepare("INSERT INTO sis_contrato(codigo,texto,data,ativo,nome,padrao) VALUES(?,?,NOW(),'sim',?,?)");
  foreach($models as$model){
   $code=$model['code'];$history->bind_param('s',$code);$history->execute();$known=$history->get_result()->fetch_assoc();
   $find->bind_param('sss',$code,$model['name'],$code);$find->execute();$existing=$find->get_result()->fetch_assoc();
   if($existing){$state='existing';$id=(int)$existing['id'];$record->bind_param('ssi',$code,$state,$id);$record->execute();echo '[contratos] Modelo existente preservado: '.$model['name'].PHP_EOL;continue;}
   if(!$known||$known['estado']!=='pending'){
    if(!$known){$state='legacy_preserved';$id=null;$record->bind_param('ssi',$code,$state,$id);$record->execute();}
    echo '[contratos] Ausencia preservada: modelo previamente registrado ou historico anterior sem identificacao segura. '.$model['name'].PHP_EOL;continue;
   }
   $text=file_get_contents($addonDir.'/'.$model['file']);if($text===false||trim($text)==='')throw new RuntimeException('Arquivo de modelo ausente ou vazio.');
   foreach(array('%nomecliente%','%cpfcliente%','%provedorcidade%')as$token)if(strpos($text,$token)===false)throw new RuntimeException('Modelo incompleto: '.$token);
   $default=(int)$db->query("SELECT COUNT(*) FROM sis_contrato WHERE padrao='sim'")->fetch_row()[0]===0?'sim':'nao';
   $insert->bind_param('ssss',$code,$text,$model['name'],$default);$insert->execute();$id=$insert->insert_id;$state='inserted';$record->bind_param('ssi',$code,$state,$id);$record->execute();
   echo '[contratos] Modelo inicial criado: '.$model['name'].PHP_EOL;
  }
  foreach(array($find,$history,$record,$insert)as$statement)$statement->close();
 }finally{$db->query("SELECT RELEASE_LOCK(CONCAT('addon-contratos-',DATABASE()))");}
}
