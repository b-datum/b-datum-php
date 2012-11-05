<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "hA9phG4n4hGVuEj1fIxCmQ");
define("ACTIVATION_KEY", "uFxBI3En4hGMM0j1fIxCmQ");

$node = new BDatumNodeActivation(PARTNER_KEY, ACTIVATION_KEY);

# lembre-se que só ativa uma vez,
# entao vc precisa salvar isso em algum lugar
$node_key = '';
$result = $node->activate();

if (!empty($result->error)){
    die($result->error);
}

?>

Node key = <?=$node_key?>