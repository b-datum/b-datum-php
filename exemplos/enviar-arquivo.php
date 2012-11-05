<?php
error_reporting(E_ALL);

include('../lib/BDatum.php');

# Troque pela suas chaves
define("PARTNER_KEY", "BB");
define("NODE_KEY", "AA");

$auth = new BDatumNodeAuth( NODE_KEY, PARTNER_KEY );

$storage = new BDatumNode( $auth );

$storage->send('../etc/frutas.txt', 'pasta_exemplo/');
