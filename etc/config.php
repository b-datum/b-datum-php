<?php

$self_path = dirname(__FILE__);

if (file_exists($self_path . '/credentials.inc') == false){
    die("$self_path/credentials.inc não encontrado.. crie o arquivo com base no $self_path/credentials.inc.example.");
}

$obj = json_decode(file_get_contents($self_path . '/credentials.inc'));


define('NODE_KEY', $obj->node_key);
define('PARTNER_KEY', $obj->partner_key);
