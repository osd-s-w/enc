<?php

require_once __DIR__.'/include.php';

$opt = getOpt('f:');
$encFile = isset($opt['f']) ? $opt['f'] : null;

$myEnc = new MyEnc($pass);
$res = $myEnc->decodeFile($encFile);

echo "{$res}\n";


