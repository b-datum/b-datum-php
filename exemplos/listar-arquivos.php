<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "ys9hzza605zZVKNJvdiB");
define("NODE_KEY", "ALThcI8EWJOPHeoP01mz");

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
