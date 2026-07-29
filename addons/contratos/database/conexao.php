
<?php

  // Carrega as configurações do addon
  require_once __DIR__ . '/../config.php';

  $host = "127.0.0.1";
  $user = "root";
  $pass = "vertrigo";
  $db = "mkradius";
    
  //CONEXAO MYSQL ESTRUTURAL
  $conecta = mysqli_connect($host, $user, $pass, $db);
  mysqli_set_charset($conecta, "utf8");

  //test if connection failed
  if (mysqli_connect_errno()) {
    die("conexao falhou: "
            . mysqli_connect_error()
            . " (" . mysqli_connect_errno()
            . ")");
  }

?>
