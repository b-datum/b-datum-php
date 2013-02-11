<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

include('../etc/config.php');

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    $res = $storage->send('../etc/frutas.txt', 'dir_para_apagar');

    $del = $storage->delete('dir_para_apagar/frutas.txt');
    print_r($del);


}catch(Exception $e){

    die($e->getMessage());
}
