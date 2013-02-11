<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    $res = $storage->get_list();
    print "\nList /  " . json_encode($res, JSON_PRETTY_PRINT);

    $res = $storage->get_list('pasta_exemplo_2');
    print "\nList /pasta_exemplo_2  " . json_encode($res, JSON_PRETTY_PRINT);
}catch(Exception $e){

    die($e->getMessage());
}
