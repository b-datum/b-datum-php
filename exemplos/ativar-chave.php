<?php
error_reporting(E_ALL);
header('Content-type: text/plain');

include('../lib/BDatum.php');


include('../etc/config.php');

$node = new BDatumNodeActivation(PARTNER_KEY, ACTIVATION_KEY);

# lembre-se que só precisa ativar uma vez,
# entao vc pode salvar isso em algum lugar
try {
    $result = $node->activate();
} catch (Exception $e) {

    die(print_r($e));
}
$node_key = $result->node_key;

?>

Node key = <?=$node_key?>