<?php
namespace Org\Util;
class DESedeCoder {

    private  $key;

    public function __construct($key='GDgLwwdK270Qj1w4xho8lyTp'){
        $this->key=$key;
    }

    //加密
    public  function encrypt($str) {
        $td = $this->gettd();
        $ret = base64_encode(mcrypt_generic($td, self::pkcs5_pad($str, 8)));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    //解密
    public  function decrypt($str) {
        $td = $this->gettd();
        $ret = self::pkcs5_unpad(mdecrypt_generic($td, base64_decode($str)));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }


    private  function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    private  function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    private  function getiv() {
        return pack('H16', '0102030405060708');
    }

    private  function gettd() {
        $iv = $this->getiv();
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        mcrypt_generic_init($td, $this->key, $iv);
        return $td;
    }

}