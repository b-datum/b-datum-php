<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('enviar-arquivo.php');
# delay para esperar o index do arquivo apos o envio
sleep(2);

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

try {
    print "\nsearching...\n";
    $res = $storage->search_by_metadata(
        array(
            'uid' => 35,
        )
    );
    print "\nsearch = " . json_encode($res, JSON_PRETTY_PRINT);

}catch(Exception $e){

    die($e->getMessage());
}

# 404 nao encontrado
if ($res === false){
    die("arquivo nao encontrado");
}

?>
