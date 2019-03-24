<?php
namespace MyEnc;

require_once __DIR__.'/MyEnc.php';
$ini = parse_ini_file('pass.ini');
$pass = $ini['pass'];

$opt = getOpt('f:');
$file = isset($opt['f']) ? $opt['f'] : null;
if (!$file) {
  exit('Usage: -f filename');
}

$myEnc = new MyEnc($pass);
$res = $myEnc->decodeFile($file);

echo "{$res}\n";
