<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');


include('../etc/config.php');
$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );
try{
    # baixa a ultima versao do arquivo e salva no caminho informado
    $res = $storage->allowed_metadata();
    # 404 nao encontrado
    if ($res === false){
        die("pagina nao encontrado");
    }
    die(print_r($res, true));


}catch(Exception $e){

    die($e->getMessage());
}
