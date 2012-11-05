<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "hA9phG4n4hGVuEj1fIxCmQ");
define("NODE_KEY", "uFxBI3En4hGMM0j1fIxCmQ");

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

$storage->send('../etc/frutas.txt', 'pasta_exemplo/');
