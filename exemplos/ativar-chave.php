<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "ys9hzza605zZVKNJvdiB");
define("ACTIVATION_KEY", "G8douGv53IW4e9M5cKrW");

$node = new BDatumNodeActivation(PARTNER_KEY, ACTIVATION_KEY);

# lembre-se que só precisa ativar uma vez,
# entao vc pode salvar isso em algum lugar
$result = $node->activate();

if (!empty($result->error)){
    die($result->error);
}
$node_key = $result->node_key;

?>

Node key = <?=$node_key?>