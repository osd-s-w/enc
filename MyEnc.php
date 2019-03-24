<?php
namespace MyEnc;

use RuntimeException;

class MyEnc
{
  protected $cipher = 'aes-256-gcm';
  protected $pass;

  protected $dataFile;
  protected $encFile;
  protected $metaFile;
  protected $outFile;

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

  public function encodeFile($file)
  {
    $data = $this->readDataFile($file);
    $ivlen = openssl_cipher_iv_length($this->cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $tag = null;
    if (preg_match('/(?:gcm|ccm)$/i', $this->cipher)) {
      $encData = openssl_encrypt($data, $this->cipher, $this->pass, $options=0, $iv, $tag);
    } else {
      $encData = openssl_encrypt($data, $this->cipher, $this->pass, $options=0, $iv);
    }
    $res = $this->makeMetaFile($this->metaFile, $iv, $tag);
    file_put_contents($this->encFile, $encData);
    return $this->encFile;
  }

  public function decodeFile($file)
  {
    $encData = $this->readEncFile($file);
    list($iv, $tag) = $this->readMetaFile($this->metaFile);
    if (preg_match('/(?:gcm|ccm)$/i', $this->cipher)) {
      $data = openssl_decrypt($encData, $this->cipher, $this->pass, $options=0, $iv, $tag);
    } else {
      $data = openssl_decrypt($encData, $this->cipher, $this->pass, $options=0, $iv);
    }
    $res = file_put_contents($this->outFile, $data);
    return $this->outFile;
  }

  public function makeMetaFile($metaFile, $iv, $tag)
  {
    $meta = [];
    $meta[] = bin2hex($iv);
    $meta[] = isset($tag) ? bin2hex($tag) : null;
    return file_put_contents($metaFile, implode("\n", $meta));
  }

  public function readMetaFile($metaFile)
  {
    if (!is_file($metaFile)) {
      throw new RuntimeException('no meta file');
    }
    $res = explode("\n", file_get_contents($metaFile));
    $meta = [];
    $meta[] = hex2bin($res[0]);
    $meta[] = isset($res[1]) ? hex2bin($res[1]) : null;
    return $meta;
  }

}
