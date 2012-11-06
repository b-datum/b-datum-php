<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "ys9hzza605zZVKNJvdiB");
define("NODE_KEY", "ALThcI8EWJOPHeoP01mz");

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

$res = $storage->get_info('pasta_exemplo_2/frutas.txt');
# 404 nao encontrado
if ($res === false){
    die("arquivo nao encontrado");
}
?>
