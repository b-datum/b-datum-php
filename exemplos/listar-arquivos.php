<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    $res = $storage->get_list();
    var_dump($res);

    $res = $storage->get_list('pasta_exemplo_2');
    var_dump($res);
}catch(Exception $e){

    die($e->getMessage());
}
