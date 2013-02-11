<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');


include('../etc/config.php');
$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );
try{
    # baixa a ultima versao do arquivo e salva no caminho informado
    $res = $storage->download('pasta_exemplo_2/frutas.txt', '/tmp/frutas.txt');
    # 404 nao encontrado
    if ($res === false){
        die("arquivo nao encontrado");
    }
    var_dump($res);

    # baixa a primeira versao do arquivo e salva no caminho informado
    $res = $storage->download('pasta_exemplo_2/frutas.txt', '/tmp/frutas_v1.txt', 1);
    if ($res === false){
        die("arquivo nao encontrado");
    }
    var_dump($res);


    # baixa a primeira versao e retorna em $res['content']
    $res = $storage->download('pasta_exemplo_2/frutas.txt', NULL, 1);
    if ($res === false){
        die("arquivo nao encontrado");
    }
    var_dump($res);


    # baixa ultima versao e retorna em $res['content']
    $res = $storage->download('pasta_exemplo_2/frutas.txt'); # ou NULL, -1
    if ($res === false){
        die("arquivo nao encontrado");
    }
    var_dump($res);
}catch(Exception $e){

    die($e->getMessage());
}
