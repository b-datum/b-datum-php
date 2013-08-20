<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    print "updating meta...\n";
    $res = $storage->set_meta('pasta_exemplo_2/frutas.txt', array(
        'uid' => 22,
    ) );

    print "\nupdate = " . json_encode($res, JSON_PRETTY_PRINT);
}catch(Exception $e){

    die($e->getMessage());
}
