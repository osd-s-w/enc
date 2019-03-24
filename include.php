<?php

$ini = parse_ini_file('pass.ini');

$cipher = 'aes-256-gcm';
$pass = $ini['pass'];


class MyEnc
{
  protected $cipher = 'aes-256-gcm';
  protected $pass;

  public $dataFile;
  public $encFile;
  public $metaFile;
  public $outFile;

  public function __construct($pass)
  {
    $this->pass = $pass;
  }

  public function readDataFile($file)
  {
    if (!is_file($file)) {
      throw new RuntimeException('no data file');
    }
    $this->dataFile = $file;
    $this->encFile = $file.".myenc";
    $this->metaFile = $file.".myenc.meta";
    return file_get_contents($file);
  }

  public function readEncFile($file)
  {
    if (!is_file($file)) {
      throw new RuntimeException('no encrypted file');
    }
    $this->encFile = $file;
    $this->metaFile = $file.".meta";
    $this->outFile = preg_replace('/\.myenc$/', '', $file);
    if (is_file($this->outFile)) {
      $info = pathinfo($this->outFile);
      $i = 0;
      while (is_file($this->outFile)) {
        $i++;
        $this->outFile = sprintf(
          "%s/%s.%d%s"
          , $info['dirname']
          , $info['filename']
          , $i
          , $info['extension'] ? ".".$info['extension']: ''
        );
      }
    }
    return file_get_contents($this->encFile);
  }

  public function decodeFile($file)
  {
    $encData = $this->readEncFile($file);
    list($iv, $tag) = $this->readMetaFile($this->metaFile);;
    $data = $this->decode($encData, $this->cipher, $this->pass, $iv, $tag);
    $res = file_put_contents($this->outFile, $data);
    return $this->outFile;
  }

  public function decode($encData, $cipher, $pass, $iv, $tag)
  {
    $data = openssl_decrypt($encData, $cipher, $pass, $options=0, $iv, $tag);
    return $data;
  }

  public function encFile($file)
  {
    $data = $this->readDataFile($file);
    $encData = $this->encode($data, $this->cipher, $this->pass, $iv, $tag);
    $res = $this->makeMetaFile($this->metaFile, $iv, $tag);
    file_put_contents($this->encFile, $encData);
    return $this->encFile;
  }

  public function encode($data, $cipher, $pass, &$iv, &$tag) {
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $tag = null;
    $enc = openssl_encrypt($data, $cipher, $pass, $options=0, $iv, $tag);
    return $enc;
  }

  public function makeMetaFile($metaFile, $iv, $tag) {
    $meta = [];
    $meta[] = bin2hex($iv);
    $meta[] = bin2hex($tag);
    return file_put_contents($metaFile, implode("\n", $meta));
  }

  public function readMetaFile($metaFile) {
    if (!is_file($metaFile)) {
      throw new RuntimeException('no meta file');
    }
    $res = explode("\n", file_get_contents($metaFile));
    $meta = [];
    $meta[] = hex2bin($res[0]);
    $meta[] = hex2bin($res[1]);
    return $meta;
  }

}



