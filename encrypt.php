<?php

require_once __DIR__."/include.php";

$opt = getOpt('f:');
$dataFile = isset($opt['f']) ? $opt['f'] : null;

$myEnc = new MyEnc($pass);
$res = $myEnc->encFile($dataFile);

echo "{$res}\n";
