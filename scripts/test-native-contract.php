<?php
// Standalone: no production database, credentials, signed documents or native selections.
define('VERSAO2UPDATE', 'fixture');
class MKAC_VARS {
    public static $throw = false;
    public function __construct($kind, $login) {
        if ($kind !== 'cliente' || $login !== "client'quoted") throw new Exception('Wrong identity');
    }
    public function FAUKHKHMMAMMKMUMMMUMHKTMUKTMTAMMT($html) {
        if (self::$throw) throw new RuntimeException('fixture engine failure');
        if (!($GLOBALS['LOADMYSQL'] instanceof ContractFixtureDb)) throw new Exception('Missing native connection');
        return str_replace(array('%nomecliente%', '%mac.serial%', '%tags%'), array('Cliente', 'SERIAL', 'Tag'), $html);
    }
}
class ContractFixtureResult {
    private $rows;
    function __construct($rows) { $this->rows = $rows; }
    function fetch_assoc() { return array_shift($this->rows); }
    function free() {}
}
class ContractFixtureStmt {
    private $db, $sql, $login;
    function __construct($db, $sql) { $this->db=$db; $this->sql=$sql; }
    function bind_param($type, &$login) { if ($type !== 's') throw new Exception('Binding'); $this->login=$login; }
    function execute() { if ($this->login !== "client'quoted" || strpos($this->sql, $this->login)!==false || strpos($this->sql,'?')===false) throw new Exception('Unsafe query'); return true; }
    function get_result() { return new ContractFixtureResult(strpos($this->sql,'sis_prodcliente')!==false ? $this->db->products : $this->db->services); }
    function close() {}
}
class ContractFixtureDb {
    public $products=array(array('nome'=>'Câmera <script>x</script> & ONU','qtdcli'=>'2'),array('nome'=>'Roteador','qtdcli'=>'1'));
    public $services=array(array('nome'=>'Serviço & suporte','valor'=>'134.99'));
    public $queries=0;
    public $charset='latin1';
    function character_set_name() { return $this->charset; }
    function set_charset($charset) { $this->charset=$charset; return true; }
    function prepare($sql) { $this->queries++; return new ContractFixtureStmt($this,$sql); }
}
require $argv[1];
function check($condition, $label) { if (!$condition) throw new Exception($label); }
$db=new ContractFixtureDb(); $GLOBALS['LOADMYSQL']='original';
$html=mka_native_contract_render($db,"client'quoted",'%nomecliente% %mac.serial% %tags% %produtos% %produtos% %servicos% 100%');
check(strpos($html,'Cliente SERIAL Tag')!==false,'native variables');
check(substr_count($html,'Câmera &lt;script&gt;x&lt;/script&gt; &amp; ONU')===2,'escaped repeated products');
check(strpos($html,'<td>002</td>')!==false && strpos($html,'<td>001</td>')!==false,'quantity');
check(strpos($html,'R$ 134,99')!==false && strpos($html,'Serviço &amp; suporte')!==false,'services');
check(strpos($html,'100%')!==false && $db->queries===2,'literal percent and lazy queries');
check($GLOBALS['LOADMYSQL']==='original' && $db->charset==='latin1','connection and charset restored');
$db->products=array();$db->services=array();
$empty=mka_native_contract_render($db,"client'quoted",'%produtos% %servicos%');
check(substr_count($empty,'<tbody></tbody>')===2,'empty tables');
try { mka_native_contract_render($db,"client'quoted",'%unrecognized%'); throw new Exception('unknown accepted'); }
catch (RuntimeException $error) { check(strpos($error->getMessage(),'%unrecognized%')!==false,'unknown diagnosis'); }
MKAC_VARS::$throw=true;
try { mka_native_contract_render($db,"client'quoted",'%nomecliente%'); throw new Exception('engine failure hidden'); }
catch (RuntimeException $error) { check($GLOBALS['LOADMYSQL']==='original','failure connection restored'); }
check(mka_native_contract_utf8("Jo\xe3o")==='João','legacy encoding');
echo "Native contract renderer fixtures passed.\n";
