<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');


include('../etc/config.php');
$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );
try{
    print "\ndownloading last version and saving tinto /tmp/frutas.txt...\n";
    # baixa a ultima versao do arquivo e salva no caminho informado
    $res = $storage->download('pasta_exemplo_2/frutas.txt', '/tmp/frutas.txt');
    # 404 nao encontrado
    if ($res === false){
        die("arquivo nao encontrado");
    }
    print " \ndownload /pasta_exemplo_2/frutas.txt  " . json_encode($res, JSON_PRETTY_PRINT);

    print "\ndownloading version 1 and saving into /tmp/frutas_v1.txt...\n";
    # baixa a primeira versao do arquivo e salva no caminho informado
    $res = $storage->download('pasta_exemplo_2/frutas.txt', '/tmp/frutas_v1.txt', 1);
    if ($res === false){
        die("arquivo nao encontrado");
    }
    print " \ndownload /pasta_exemplo_2/frutas.txt versao 1 " . json_encode($res, JSON_PRETTY_PRINT);


    print "\ndownloading version 1 without saving into file...\n";
    # baixa a primeira versao e retorna em $res['content']
    $res = $storage->download('pasta_exemplo_2/frutas.txt', NULL, 1);
    if ($res === false){
        die("arquivo nao encontrado");
    }
    print " \ndownload /pasta_exemplo_2/frutas.txt versao 1 sem salvar em arquivo " .
        json_encode($res, JSON_PRETTY_PRINT);


    print "\ndownloading last version to memory...\n";
    # baixa ultima versao e retorna em $res['content']
    $res = $storage->download('pasta_exemplo_2/frutas.txt'); # ou NULL, -1
    if ($res === false){
        die("arquivo nao encontrado");
    }
    print " \ndownload /pasta_exemplo_2/frutas.txt sem salvar em arquivo, ultima versao" .
        json_encode($res, JSON_PRETTY_PRINT);
}catch(Exception $e){

    die($e->getMessage());
}
