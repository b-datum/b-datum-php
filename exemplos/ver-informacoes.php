<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    $res = $storage->get_info('/pasta_exemplo_2/frutas.txt');
    print "\nInfo /pasta_exemplo_2/frutas.txt  " . json_encode($res, JSON_PRETTY_PRINT);
}catch(Exception $e){

    die($e->getMessage());
}

# 404 nao encontrado
if ($res === false){
    die("arquivo nao encontrado");
}

?>
