<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "ys9hzza605zZVKNJvdiB");
define("NODE_KEY", "ALThcI8EWJOPHeoP01mz");

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    $res = $storage->send('../etc/frutas.txt', 'dir_para_apagar');

    $del = $storage->delete('dir_para_apagar/frutas.txt');
    var_dump($del);


}catch(Exception $e){

    die($e->getMessage());
}
