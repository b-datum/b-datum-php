<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

$res = $storage->get_info('pasta_exemplo_2/frutas.txt');
# 404 nao encontrado
if ($res === false){
    die("arquivo nao encontrado");
}
?>
