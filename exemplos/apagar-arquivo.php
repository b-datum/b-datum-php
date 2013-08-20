<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    print "sending file...\n";
    $res = $storage->send('../etc/frutas.txt', 'dir_para_apagar');

    print "\nsend = " . json_encode($res, JSON_PRETTY_PRINT);

    print "\ndeleting file...\n";
    $del = $storage->delete('dir_para_apagar/frutas.txt');
    print "\ndelete = " . json_encode($del, JSON_PRETTY_PRINT);


}catch(Exception $e){

    die($e->getMessage());
}
