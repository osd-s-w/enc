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

    protected $postfixEncFile = '.myenc';
    protected $postfixMetaFile = '.myenc.meta';

    public function __construct($pass)
    {
        if ($pass=='') {
            throw new RuntimeException('set password');
        }
        $this->pass = $pass;
    }

    protected function setFiles($file)
    {
        $this->dataFile = $file;
        $this->encFile = $file.$this->postfixEncFile;
        $this->metaFile = $file.$this->postfixMetaFile;
    }

    public function readDataFile($file)
    {
        if (!is_file($file)) {
            throw new RuntimeException('no data file');
        }
        $this->setFiles($file);
        if (FALSE === ($res = file_get_contents($file))) {
            throw new RuntimeException('Failed read data file.');
        }
        return $res;
    }

    public function readEncFile($encfile)
    {
        if (!is_file($encfile)) {
            throw new RuntimeException('no encrypted file');
        }
        $exp = str_replace(".", "\\.", $this->postfixEncFile);
        if (!preg_match("/^(.+){$exp}$/", $encfile, $regexp)) {
            throw new RuntimeException('Invalid file extension. '.$encfile);
        }
        $file = $regexp[1];
        $this->setFiles($file);
        $this->outFile = $file;
        if (is_file($this->outFile)) {
            $info = pathinfo($this->outFile);
            $ext = $info['extension']!='' ? '.'.$info['extension']: '';
            $i = 0;
            while (is_file($this->outFile)) {
                $i++;
                $this->outFile = sprintf(
                    "%s/%s.%d%s"
                    , $info['dirname']
                    , $info['filename']
                    , $i
                    , $ext
                );
            }
        }
        if (FALSE === ($res = file_get_contents($this->encFile))) {
            throw new RuntimeException('Failed read enctypted file.');
        }
        return $res;
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
        if (FALSE === $encData) {
            throw new RuntimeException('Failed encrypt...');
        }
        $this->makeMetaFile($this->metaFile, $iv, $tag);
        if (FALSE === file_put_contents($this->encFile, $encData)) {
            throw new RuntimeException('Cannot make encrypted file...');
        }
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
        if (FALSE === $data) {
            throw new RuntimeException('Decrypt failed...');
        }
        if (FALSE === file_put_contents($this->outFile, $data)) {
            throw new RuntimeException('Cannot make decoded file...');
        }
        return $this->outFile;
    }

    public function makeMetaFile($metaFile, $iv, $tag)
    {
        $meta = [];
        $meta[] = bin2hex($iv);
        $meta[] = isset($tag) ? bin2hex($tag) : null;
        if (FALSE === file_put_contents($metaFile, implode("\n", $meta))) {
            throw new RuntimeException('Cannot make meta file...');
        }
        return true;
    }

    public function readMetaFile($metaFile)
    {
        if (!is_file($metaFile)) {
            throw new RuntimeException('no meta file');
        }
        if (FALSE === ($res = file_get_contents($metaFile))) {
            throw new RuntimeException('Cannot read meta file...');
        }
        $res = explode("\n", $res);
        $meta = [];
        $meta[] = hex2bin($res[0]);
        $meta[] = isset($res[1]) ? hex2bin($res[1]) : null;
        return $meta;
    }

}
