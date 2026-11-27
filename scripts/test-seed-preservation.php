<?php
// Private database fixture only. Never seed the provider's native database.
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
if(isset($argv[3])){require $argv[3].'/include/configure.php';require $argv[3].'/include/conexao.php';$db=$LOADMYSQL;}else{$db=new mysqli(getenv('TEST_SQL_HOST')?:'127.0.0.1',getenv('TEST_SQL_USER')?:'root',getenv('TEST_SQL_PASSWORD')?:'',null,(int)(getenv('TEST_SQL_PORT')?:3306));}
$original=$db->query('SELECT DATABASE()')->fetch_row()[0];$name='suite_contract_test_'.bin2hex(random_bytes(6));if(!preg_match('/^suite_contract_test_[a-f0-9]{12}$/D',$name))throw new Exception('Unsafe fixture');
$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8');$db->select_db($name);require $argv[1];
function snapshot($db){return json_encode($db->query('SELECT * FROM sis_contrato ORDER BY id')->fetch_all(MYSQLI_ASSOC));}
try{
 foreach(array('InnoDB','MyISAM')as$engine){
  $db->query('DROP TABLE IF EXISTS sis_contrato');$db->query('DROP TABLE IF EXISTS addon_contratos_model_seed');
  $db->query("CREATE TABLE sis_contrato(id INT AUTO_INCREMENT PRIMARY KEY,codigo VARCHAR(100),texto LONGTEXT,data DATETIME,ativo VARCHAR(4),nome VARCHAR(255),padrao VARCHAR(4)) ENGINE=".$engine);
  contratos_seed_models($db,$argv[2]);if((int)$db->query('SELECT COUNT(*) FROM sis_contrato')->fetch_row()[0]!==2)throw new Exception('Fresh install needs2');
  $db->query("UPDATE sis_contrato SET codigo='provider-renamed-code',nome='Modelo totalmente editado',texto='PERSONALIZADO',ativo='nao',padrao='nao' WHERE id=1");
  $before=snapshot($db);contratos_seed_models($db,$argv[2]);if(snapshot($db)!==$before)throw new Exception('Edited models changed');
  $db->query('DELETE FROM sis_contrato WHERE id=2');$before=snapshot($db);contratos_seed_models($db,$argv[2]);if(snapshot($db)!==$before)throw new Exception('Deleted model recreated');
  // Legacy installs without a ledger: never guess that a customized/deleted model is new.
  $db->query('DROP TABLE addon_contratos_model_seed');$before=snapshot($db);contratos_seed_models($db,$argv[2]);if(snapshot($db)!==$before)throw new Exception('Ambiguous legacy models changed');
  echo 'PASS '.$engine.': fresh2, edits/renames preserved, deletion not recreated, unknown legacy preserved'.PHP_EOL;
 }
}finally{if($original)$db->select_db($original);$db->query('DROP DATABASE `'.$name.'`');}
