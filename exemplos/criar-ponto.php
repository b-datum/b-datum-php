<?php
die( "does not exists anymore...");
exit;
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/ApiBDatum.php');

$api_auth = new ApiBDatumAuth('partner_test@b-datum.com', '12345');

try {
    $api_auth->authorize();
} catch ( Exception $e ){
    # pode ser login/senha invalido
    die($e->getMessage());
}

$ponto = new BDatumNode('ponto exemplo: meu computador');

