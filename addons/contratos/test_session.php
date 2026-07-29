<?php
session_name('mka');
session_start();

echo "<h1>Teste de Sessão</h1>";
echo "<pre>";
echo "Session Status: " . session_status() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n\n";
echo "Variáveis de sessão:\n";
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='index.php'>Voltar para Index</a></p>";
echo "<p><a href='backup_contratos.php'>Ir para Backup</a></p>";
?>
