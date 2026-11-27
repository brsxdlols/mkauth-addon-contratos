<?php
require_once __DIR__.'/../config.php';
global $LOADMYSQL;
$nativeRoot=dirname(__DIR__,4);
require_once $nativeRoot.'/include/configure.php';
require_once $nativeRoot.'/include/conexao.php';
if(!isset($LOADMYSQL)||!($LOADMYSQL instanceof mysqli))throw new RuntimeException('Conexao nativa do MK-Auth indisponivel.');
$conecta=$LOADMYSQL;
$conecta->set_charset('utf8');
